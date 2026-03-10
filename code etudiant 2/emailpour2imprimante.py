# Bibliothèques utilisées :
# - requests : pour faire des appels HTTP vers l'API OctoPrint des imprimantes
# - time     : pour la pause entre chaque vérification
# - smtplib  : pour envoyer des emails via le protocole SMTP
# - MIMEText : pour formater le contenu de l'email
import requests
import time
import smtplib
from email.mime.text import MIMEText

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

    except Exception as e:
        # En cas d'échec, affiche l'erreur dans la console sans planter le programme
        print("Erreur email :", e)


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
        r = requests.get(printer["url"], headers=headers)
        data = r.json()

        # Récupération de la température actuelle de la buse (outil 0)
        temp = data["temperature"]["tool0"]["actual"]

        if temp > TEMP_MAX or temp < TEMP_MIN:
            envoyer_email(f"⚠️ Température anormale sur {printer['name']} : {temp}°C")

        # ── 2. Vérification de l'état du travail en cours ───────────────────
        r = requests.get(printer["job_url"], headers=headers)
        job = r.json()

        state = job["state"]                        # Ex : "Printing", "Paused", "Error"
        progress = job["progress"]["completion"]    # Pourcentage d'avancement (0-100)

        # Alerte si l'impression est mise en pause ou en erreur
        if state == "Paused" or state == "Error":
            envoyer_email(f"⚠️ Impression arrêtée sur {printer['name']}")

        # ── 3. Détection d'un blocage (progression figée) ───────────────────
        if printer["name"] not in last_progress:
            # Première vérification : on initialise simplement la valeur de référence
            last_progress[printer["name"]] = progress
        else:
            # Si la progression n'a pas bougé depuis le dernier contrôle, alerte
            if progress == last_progress[printer["name"]]:
                envoyer_email(f"⚠️ Imprimante bloquée : {printer['name']}")

        # Mise à jour de la progression pour la prochaine vérification
        last_progress[printer["name"]] = progress

    except:
        # ── 4. Imprimante injoignable ────────────────────────────────────────
        # Une exception réseau (timeout, connexion refusée...) déclenche une alerte
        envoyer_email(f"⚠️ Impossible de contacter {printer['name']}")


# ─── BOUCLE PRINCIPALE ──────────────────────────────────────────────────────
# Le programme tourne indéfiniment et vérifie chaque imprimante toutes les 60 secondes
while True:

    for printer in printers:
        verifier_imprimante(printer)  # Vérification de chaque imprimante

    time.sleep(60)  # Pause de 60 secondes avant la prochaine série de vérifications
