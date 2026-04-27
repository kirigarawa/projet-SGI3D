#!/usr/bin/env python3
# ════════════════════════════════════════════════════════════════════════════
#  SGI3D - Monitoring imprimantes 3D + Détecteur de fumée FIRECLASS 601PH
# ════════════════════════════════════════════════════════════════════════════
# Bibliothèques utilisées :
# - requests : appels HTTP vers l'API OctoPrint
# - time     : pauses entre vérifications
# - smtplib  : envoi d'emails via SMTP
# - MIMEText : mise en forme de l'email
# - twilio   : envoi de SMS (pip install twilio)
# - logging  : écriture des logs dans un fichier
# - gpiozero : lecture de la GPIO pour le détecteur de fumée (pip install gpiozero)
# ════════════════════════════════════════════════════════════════════════════
import requests
import time
import smtplib
import logging
from email.mime.text import MIMEText
from twilio.rest import Client
from gpiozero import Button  # Lecture simple d'une entrée GPIO avec anti-rebond

# ─── LOGS ────────────────────────────────────────────────────────────────────
logging.basicConfig(
    filename="/home/pi/sgi3d_alertes.log",
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S"
)

# ─── CONFIGURATION EMAIL ────────────────────────────────────────────────────
EMAIL_SENDER = "matthieumondor15@gmail.com"
EMAIL_PASSWORD = "jfuv ezrl bheo aitl"
EMAIL_RECEIVERS = [
    "matthieumondor15@gmail.com",
    "jolan.schambourg972@gmail.com",
    "vaubienkevens@gmail.com"
]

# ─── CONFIGURATION SMS (Twilio) ──────────────────────────────────────────────
TWILIO_ACCOUNT_SID = "VOTRE_ACCOUNT_SID"
TWILIO_AUTH_TOKEN  = "VOTRE_AUTH_TOKEN"
TWILIO_FROM        = "+1XXXXXXXXXX"
SMS_RECEIVERS = [
    "+33XXXXXXXXX",   # Matthieu
    "+33XXXXXXXXX",   # Jolan
    "+33XXXXXXXXX",   # Vaubien
]

# ─── CONFIGURATION DÉTECTEUR DE FUMÉE FIRECLASS 601PH ───────────────────────
# Le 601PH fonctionne entre 10.5V et 33V (typique 24V).
# Impossible de le brancher DIRECTEMENT sur une GPIO du Raspberry Pi (3.3V max).
#
# SCHÉMA DE CÂBLAGE CONSEILLÉ (via opto-coupleur PC817) :
#
#   Alim 24V (+) ──┬── [L1] 601PH [L2] ──── R_limit (2.2kΩ) ── LED interne PC817 (+)
#                  │                                                       │
#   Alim 24V (−) ──┴───────────────────────────────────────── LED PC817 (−)
#
#   3.3V RPi ──── R_pullup (10kΩ) ──┬── Collecteur PC817
#                                    └── GPIO 17 (pin 11)
#   GND RPi ──────────────────────────── Émetteur PC817
#
# Principe : quand le 601PH passe en alarme, il laisse passer le courant
# (cf. Fig.3 du datasheet), la LED de l'opto s'allume, le transistor de l'opto
# devient passant, et la GPIO 17 passe de 3.3V (HIGH) à 0V (LOW).
#
# Le Button de gpiozero gère automatiquement la pull-up interne et l'anti-rebond.
SMOKE_GPIO_PIN = 17          # Broche BCM où est branché l'opto-coupleur
SMOKE_DEBOUNCE = 0.5         # Anti-rebond en secondes (évite les fausses alertes)

# Objet Button : pull_up=True → la broche est HIGH au repos, LOW quand actif
smoke_sensor = Button(SMOKE_GPIO_PIN, pull_up=True, bounce_time=SMOKE_DEBOUNCE)

# Mémoire d'état : évite de spammer les mails tant que l'alarme reste active
smoke_alert_active = False

# ─── CONFIGURATION IMPRIMANTES ──────────────────────────────────────────────
printers = [
    {
        "name": "Ultimaker 2+",
        "url": "http://localhost:5000/api/printer",
        "job_url": "http://localhost:5000/api/job",
        "api_key": "GN2-MsGMr05YG0vUw-98MLiRZKFkXcYZrkvfeztDh-8"
    },
    {
        "name": "Creality Ender v2 neo ",
        "url": "http://localhost:5001/api/printer",
        "job_url": "http://localhost:5001/api/job",
        "api_key": "API_KEY_2"
    }
]

# ─── SEUILS DE TEMPÉRATURE ──────────────────────────────────────────────────
TEMP_MAX = 260
TEMP_MIN = 150

# Dictionnaire pour mémoriser la progression de chaque imprimante
last_progress = {}


def attendre_reseau(max_tentatives=20, delai=10):
    """Attend que le réseau soit disponible avant de démarrer."""
    for tentative in range(1, max_tentatives + 1):
        try:
            requests.get("https://www.google.com", timeout=5)
            logging.info("Réseau disponible, démarrage du monitoring.")
            return
        except Exception:
            logging.warning(f"Réseau indisponible, nouvelle tentative dans {delai}s ({tentative}/{max_tentatives})...")
            time.sleep(delai)
    logging.error("Réseau toujours indisponible après toutes les tentatives.")


def envoyer_email(message, sujet="Alerte Impression 3D"):
    """Envoie un email d'alerte à tous les destinataires configurés."""
    msg = MIMEText(message)
    msg["Subject"] = sujet
    msg["From"] = EMAIL_SENDER
    try:
        server = smtplib.SMTP("smtp.gmail.com", 587)
        server.starttls()
        server.login(EMAIL_SENDER, EMAIL_PASSWORD)
        for receiver in EMAIL_RECEIVERS:
            msg["To"] = receiver
            server.sendmail(EMAIL_SENDER, receiver, msg.as_string())
        server.quit()
        logging.info(f"Email envoyé : {message}")
    except Exception as e:
        logging.error(f"Erreur email : {e}")


def envoyer_sms(message):
    """Envoie un SMS d'alerte à tous les numéros configurés via Twilio."""
    try:
        client = Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN)
        for numero in SMS_RECEIVERS:
            client.messages.create(body=message, from_=TWILIO_FROM, to=numero)
        logging.info(f"SMS envoyé : {message}")
    except Exception as e:
        logging.error(f"Erreur SMS : {e}")


def envoyer_alerte(message, sujet="Alerte Impression 3D"):
    """Envoie l'alerte par email ET par SMS."""
    envoyer_email(message, sujet)
    envoyer_sms(message)


# ─── NOUVELLE FONCTION : SURVEILLANCE FUMÉE ─────────────────────────────────
def verifier_fumee():
    """Vérifie l'état du détecteur de fumée 601PH via GPIO.

    - smoke_sensor.is_pressed == True  → GPIO à LOW → ALARME fumée détectée
    - smoke_sensor.is_pressed == False → GPIO à HIGH → état normal

    Utilise la variable globale smoke_alert_active pour envoyer l'email UNE
    SEULE FOIS par épisode d'alarme (et pas toutes les 60 secondes).
    """
    global smoke_alert_active

    if smoke_sensor.is_pressed:
        # Le détecteur est en alarme
        if not smoke_alert_active:
            # Première détection → on envoie l'alerte
            message = (
                "🔥 ALERTE FUMÉE DÉTECTÉE 🔥\n\n"
                "Le détecteur FIRECLASS 601PH a détecté de la fumée "
                "dans la salle des imprimantes 3D.\n\n"
                "ACTIONS IMMÉDIATES :\n"
                "  1. Vérifier la salle visuellement\n"
                "  2. Couper l'alimentation des imprimantes si nécessaire\n"
                "  3. Évacuer et prévenir les secours si feu confirmé\n\n"
                "— Système SGI3D"
            )
            logging.critical("ALARME FUMÉE 601PH DÉTECTÉE !")
            envoyer_alerte(message, sujet="🔥 ALERTE FUMÉE - Salle imprimantes 3D")
            smoke_alert_active = True
    else:
        # Plus de fumée : on remet le flag à zéro pour accepter une prochaine alerte
        if smoke_alert_active:
            logging.info("Retour à la normale : plus de fumée détectée.")
            envoyer_email(
                "✅ Retour à la normale : le détecteur 601PH ne détecte plus de fumée.",
                sujet="Fin d'alerte fumée - Salle imprimantes 3D"
            )
            smoke_alert_active = False


def verifier_imprimante(printer):
    """Vérifie l'état d'une imprimante et envoie des alertes si nécessaire."""
    headers = {"X-Api-Key": printer["api_key"]}
    try:
        # 1. Température
        r = requests.get(printer["url"], headers=headers, timeout=10)
        data = r.json()
        temp = data["temperature"]["tool0"]["actual"]
        if temp > TEMP_MAX or temp < TEMP_MIN:
            envoyer_alerte(f"⚠️ Température anormale sur {printer['name']} : {temp}°C")

        # 2. État du travail
        r = requests.get(printer["job_url"], headers=headers, timeout=10)
        job = r.json()
        state = job["state"]
        progress = job["progress"]["completion"]
        if state == "Paused" or state == "Error":
            envoyer_alerte(f"⚠️ Impression arrêtée sur {printer['name']}")

        # 3. Blocage de la progression
        if printer["name"] not in last_progress:
            last_progress[printer["name"]] = progress
        else:
            if progress == last_progress[printer["name"]]:
                envoyer_alerte(f"⚠️ Imprimante bloquée : {printer['name']}")
        last_progress[printer["name"]] = progress

    except Exception as e:
        logging.warning(f"Impossible de contacter {printer['name']} : {e}")
        envoyer_alerte(f"⚠️ Impossible de contacter {printer['name']}")


# ─── DÉMARRAGE ───────────────────────────────────────────────────────────────
logging.info("=== Démarrage monitoring imprimantes 3D + détecteur fumée 601PH ===")
attendre_reseau()

# ─── BOUCLE PRINCIPALE ──────────────────────────────────────────────────────
# Le détecteur de fumée est vérifié à chaque tour de boucle (toutes les 5s),
# tandis que les imprimantes sont vérifiées moins souvent (toutes les 60s)
# pour ne pas surcharger l'API OctoPrint.
COMPTEUR_MAX = 12          # 12 cycles × 5s = 60s entre chaque check imprimantes
compteur = COMPTEUR_MAX    # Force un check imprimantes dès le démarrage

while True:

    # Vérification fumée : PRIORITAIRE, à chaque itération (toutes les 5s)
    verifier_fumee()

    # Vérification imprimantes : toutes les 60 secondes seulement
    if compteur >= COMPTEUR_MAX:
        for printer in printers:
            verifier_imprimante(printer)
        compteur = 0
    else:
        compteur += 1

    time.sleep(5)   # Pause courte pour réagir vite à une alerte fumée