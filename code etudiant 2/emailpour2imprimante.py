#!/usr/bin/env python3
# Bibliothèques utilisées :
# - requests : pour faire des appels HTTP vers l'API OctoPrint des imprimantes
# - time     : pour la pause entre chaque vérification
# - smtplib  : pour envoyer des emails via le protocole SMTP
# - MIMEText : pour formater le contenu de l'email
# - twilio   : pour envoyer des SMS d'alerte (pip install twilio)
# - logging  : pour écrire les logs dans un fichier (utile en service systemd)
import requests
import time
import smtplib
import logging
from email.mime.text import MIMEText
from twilio.rest import Client

# ─── LOGS ────────────────────────────────────────────────────────────────────
# Sur Raspberry Pi en service systemd, il n'y a pas de terminal.
# Les logs sont écrits dans /home/pi/sgi3d_alertes.log
logging.basicConfig(
    filename="/home/pi/sgi3d_alertes.log",
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S"
)

# ─── CONFIGURATION EMAIL ────────────────────────────────────────────────────
# Adresse Gmail qui envoie les alertes
EMAIL_SENDER = "sgi3d.alert@gmail.com"
# Mot de passe d'application Gmail (à générer dans les paramètres du compte Google,
# rubrique "Sécurité > Mots de passe des applications")
EMAIL_PASSWORD = "MOT_DE_PASSE_APPLICATION"

# Liste des destinataires qui recevront toutes les alertes
EMAIL_RECEIVERS = [
    "matthieumondor15@gmail.com",
    "jolan.schambourg972@gmail.com",
    "vaubienkevens@gmail.com"
]

# ─── CONFIGURATION SMS (Twilio) ──────────────────────────────────────────────
# Créer un compte gratuit sur https://www.twilio.com pour obtenir ces identifiants
TWILIO_ACCOUNT_SID = "VOTRE_ACCOUNT_SID"   # Ex : ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN  = "VOTRE_AUTH_TOKEN"    # Trouvé dans le tableau de bord Twilio
TWILIO_FROM        = "+1XXXXXXXXXX"        # Numéro Twilio qui envoie le SMS

# Liste des numéros qui recevront les alertes SMS (format international : +33XXXXXXXXX)
SMS_RECEIVERS = [
    "+33XXXXXXXXX",   # Matthieu
    "+33XXXXXXXXX",   # Jolan
    "+33XXXXXXXXX",   # Vaubien
]

# ─── CONFIGURATION IMPRIMANTES ──────────────────────────────────────────────
# Chaque entrée représente une imprimante 3D surveillée via OctoPrint.
# - name    : nom affiché dans les alertes
# - url     : endpoint API OctoPrint pour l'état de l'imprimante (température, etc.)
# - job_url : endpoint API OctoPrint pour l'état du travail en cours
# - api_key : clé d'API OctoPrint propre à chaque instance
printers = [
    {
        "name": "Ultimaker 2+",
        "url": "http://localhost:5000/api/printer",
        "job_url": "http://localhost:5000/api/job",
        "api_key": "GN2-MsGMr05YG0vUw-98MLiRZKFkXcYZrkvfeztDh-8"
    },
    {
        "name": "Ultimaker_2",
        "url": "http://localhost:5001/api/printer",
        "job_url": "http://localhost:5001/api/job",
        "api_key": "API_KEY_2"
    }
]

# ─── SEUILS DE TEMPÉRATURE ──────────────────────────────────────────────────
# Si la buse dépasse TEMP_MAX ou descend sous TEMP_MIN, une alerte est envoyée
TEMP_MAX = 260  # Température maximale acceptable (°C)
TEMP_MIN = 150  # Température minimale acceptable (°C)

# Dictionnaire pour mémoriser la progression de chaque imprimante
# lors de la vérification précédente (pour détecter un blocage)
last_progress = {}


def attendre_reseau(max_tentatives=20, delai=10):
    """Attend que le réseau soit disponible avant de démarrer.

    Sur Raspberry Pi, le script peut démarrer avant que le Wi-Fi ou l'Ethernet
    soit connecté. Cette fonction attend jusqu'à ce que Google soit joignable.
    """
    for tentative in range(1, max_tentatives + 1):
        try:
            requests.get("https://www.google.com", timeout=5)
            logging.info("Réseau disponible, démarrage du monitoring.")
            return
        except Exception:
            logging.warning(f"Réseau indisponible, nouvelle tentative dans {delai}s ({tentative}/{max_tentatives})...")
            time.sleep(delai)

    logging.error("Réseau toujours indisponible après toutes les tentatives. Le programme continue quand même.")


def envoyer_email(message):
    """Envoie un email d'alerte à tous les destinataires configurés."""

    # Création du message email avec le contenu texte fourni
    msg = MIMEText(message)
    msg["Subject"] = "Alerte Impression 3D"
    msg["From"] = EMAIL_SENDER

    try:
        # Connexion au serveur SMTP de Gmail sur le port 587 (TLS)
        server = smtplib.SMTP("smtp.gmail.com", 587)
        server.starttls()  # Activation du chiffrement TLS
        server.login(EMAIL_SENDER, EMAIL_PASSWORD)  # Authentification

        # Envoi individuel à chaque destinataire
        for receiver in EMAIL_RECEIVERS:
            msg["To"] = receiver
            server.sendmail(EMAIL_SENDER, receiver, msg.as_string())

        server.quit()  # Fermeture propre de la connexion SMTP
        logging.info(f"Email envoyé : {message}")

    except Exception as e:
        logging.error(f"Erreur email : {e}")


def envoyer_sms(message):
    """Envoie un SMS d'alerte à tous les numéros configurés via Twilio."""

    try:
        client = Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN)

        for numero in SMS_RECEIVERS:
            client.messages.create(
                body=message,
                from_=TWILIO_FROM,
                to=numero
            )

        logging.info(f"SMS envoyé : {message}")

    except Exception as e:
        logging.error(f"Erreur SMS : {e}")


def envoyer_alerte(message):
    """Envoie l'alerte par email ET par SMS."""
    envoyer_email(message)
    envoyer_sms(message)


def verifier_imprimante(printer):
    """Vérifie l'état d'une imprimante et envoie des alertes si nécessaire.

    Contrôles effectués :
    1. Température de la buse hors limites
    2. Impression en pause ou en erreur
    3. Progression bloquée (identique à la vérification précédente)
    4. Imprimante injoignable (exception réseau)
    """

    # En-tête d'authentification requis par l'API OctoPrint
    headers = {"X-Api-Key": printer["api_key"]}

    try:
        # ── 1. Vérification de la température ───────────────────────────────
        r = requests.get(printer["url"], headers=headers, timeout=10)
        data = r.json()

        # Récupération de la température actuelle de la buse (outil 0)
        temp = data["temperature"]["tool0"]["actual"]

        if temp > TEMP_MAX or temp < TEMP_MIN:
            envoyer_alerte(f"⚠️ Température anormale sur {printer['name']} : {temp}°C")

        # ── 2. Vérification de l'état du travail en cours ───────────────────
        r = requests.get(printer["job_url"], headers=headers, timeout=10)
        job = r.json()

        state = job["state"]                        # Ex : "Printing", "Paused", "Error"
        progress = job["progress"]["completion"]    # Pourcentage d'avancement (0-100)

        # Alerte si l'impression est mise en pause ou en erreur
        if state == "Paused" or state == "Error":
            envoyer_alerte(f"⚠️ Impression arrêtée sur {printer['name']}")

        # ── 3. Détection d'un blocage (progression figée) ───────────────────
        if printer["name"] not in last_progress:
            # Première vérification : on initialise simplement la valeur de référence
            last_progress[printer["name"]] = progress
        else:
            # Si la progression n'a pas bougé depuis le dernier contrôle, alerte
            if progress == last_progress[printer["name"]]:
                envoyer_alerte(f"⚠️ Imprimante bloquée : {printer['name']}")

        # Mise à jour de la progression pour la prochaine vérification
        last_progress[printer["name"]] = progress

    except Exception as e:
        # ── 4. Imprimante injoignable ────────────────────────────────────────
        # Une exception réseau (timeout, connexion refusée...) déclenche une alerte
        logging.warning(f"Impossible de contacter {printer['name']} : {e}")
        envoyer_alerte(f"⚠️ Impossible de contacter {printer['name']}")


# ─── DÉMARRAGE ───────────────────────────────────────────────────────────────
logging.info("=== Démarrage du monitoring imprimantes 3D ===")

# Attendre que le réseau soit prêt (important au boot du Raspberry Pi)
attendre_reseau()

# ─── BOUCLE PRINCIPALE ──────────────────────────────────────────────────────
# Le programme tourne indéfiniment et vérifie chaque imprimante toutes les 60 secondes
while True:

    for printer in printers:
        verifier_imprimante(printer)  # Vérification de chaque imprimante

    time.sleep(60)  # Pause de 60 secondes avant la prochaine série de vérifications