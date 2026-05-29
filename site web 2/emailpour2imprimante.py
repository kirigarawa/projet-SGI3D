# Surveillance des imprimantes 3D via l'API OctoPrint + capteur de fumée Tyco 601P (GPIO 17)
# Envoie des alertes par email en cas de problème détecté (température, arrêt, blocage, hors ligne, fumée)

import requests   # Pour faire des requêtes HTTP vers l'API OctoPrint
import time       # Pour la pause entre chaque cycle de vérification
import smtplib    # Pour l'envoi d'emails via SMTP
from email.mime.text import MIMEText  # Pour construire le contenu des emails
import logging    # Pour afficher des messages de log avec horodatage
import RPi.GPIO as GPIO  # Pour la lecture du GPIO (capteur de fumée Tyco 601P)

# ─── LOGGING ─────────────────────────────
# Configure les logs : niveau INFO, avec date/heure et niveau affiché
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# ─── CONFIG EMAIL ─────────────────────────

# Adresse Gmail utilisée pour envoyer les alertes
EMAIL_SENDER = "matthieumondor15@gmail.com"

# Mot de passe d'application Gmail
EMAIL_PASSWORD = "jfuv ezrl bheo aitl"

# Liste des destinataires qui recevront les alertes
EMAIL_RECEIVERS = [
    "matthieumondor15@gmail.com",
    "jolan.schambourg972@gmail.com",
    "vaubienkevens@gmail.com"
]

# Serveur et port SMTP de Gmail (587 = TLS)
SMTP_SERVER = "smtp.gmail.com"
SMTP_PORT = 587

# ─── CONFIG CAPTEUR FUMÉE ────────────────

# GPIO BCM du Tyco 601P — signal TTL 3.3V : HIGH = alarme, LOW = normal
SMOKE_GPIO = 17

# ─── CONFIG IMPRIMANTES ───────────────────

# Liste des imprimantes à surveiller avec leur URL API OctoPrint et leur clé d'accès
printers = [
    {
        "name": "Ultimaker 2+",
        "url": "http://localhost:5000/api/printer",      # Endpoint état imprimante
        "job_url": "http://localhost:5000/api/job",      # Endpoint état du job en cours
        "api_key": "GN2-MsGMr05YG0vUw-98MLiRZKFkXcYZrkvfeztDh-8"
    },
    # Ajouter ici la deuxième imprimante si nécessaire
]

# ─── SEUILS TEMP ─────────────────────────

# Températures limites de la buse (en °C) — une alerte est envoyée si hors de cette plage
TEMP_MAX = 260  # Au-dessus : risque de surchauffe
TEMP_MIN = 150  # En dessous pendant une impression : risque d'extrusion insuffisante

# ─── MÉMOIRE ─────────────────────────────

# Mémorise la dernière progression de chaque imprimante pour détecter un blocage
last_progress = {}
# Mémorise si la progression était déjà identique au cycle précédent
stuck_flag = {}

# Mémorise l'état d'alerte actif par imprimante pour éviter les emails répétés
# Valeurs possibles : None, "temp", "stop", "stuck", "offline"
alert_state = {}

# Mémorise si une alerte fumée est déjà active (évite les emails répétés)
smoke_alerted = False

# ─── ENVOI EMAIL ─────────────────────────

def envoyer_email(message):
    """Envoie un email d'alerte à tous les destinataires configurés."""
    try:
        with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
            server.ehlo()       # Identification auprès du serveur SMTP
            server.starttls()   # Chiffrement de la connexion
            server.ehlo()       # Ré-identification après TLS

            server.login(EMAIL_SENDER, EMAIL_PASSWORD)

            # Envoi individuel à chaque destinataire
            for receiver in EMAIL_RECEIVERS:
                msg = MIMEText(message)
                msg["Subject"] = "Alerte Impression 3D"
                msg["From"] = EMAIL_SENDER
                msg["To"] = receiver

                server.sendmail(EMAIL_SENDER, receiver, msg.as_string())

        logging.info("Email envoyé")

    except smtplib.SMTPAuthenticationError:
        logging.error("Erreur authentification Gmail (mot de passe d'application incorrect)")
    except Exception as e:
        logging.error(f"Erreur email : {e}")

# ─── CAPTEUR FUMÉE GPIO ──────────────────

def on_fumee_detectee(_channel):
    """Callback déclenché immédiatement par interruption GPIO dès que le Tyco 601P détecte de la fumée."""
    global smoke_alerted
    if not smoke_alerted:
        logging.warning("ALARME FUMÉE — Tyco 601P GPIO 17")
        envoyer_email("🔥 ALARME INCENDIE — Capteur de fumée Tyco 601P déclenché (GPIO 17) !")
        smoke_alerted = True

def on_fumee_resolue(_channel):
    """Callback déclenché quand le signal GPIO repasse à LOW (fin d'alarme)."""
    global smoke_alerted
    if smoke_alerted:
        logging.info("Capteur de fumée : retour à la normale (GPIO 17)")
        smoke_alerted = False

# ─── VÉRIFICATION IMPRIMANTE ─────────────

def verifier_imprimante(printer):
    """
    Vérifie l'état d'une imprimante via l'API OctoPrint.
    Envoie une alerte email si un problème est détecté (une seule fois par type de problème).
    """
    headers = {"X-Api-Key": printer["api_key"]}

    try:
        # ─── TEMPÉRATURE ───
        # Récupère les données de l'imprimante (dont la température de la buse)
        r = requests.get(printer["url"], headers=headers, timeout=5)
        r.raise_for_status()  # Lève une exception si le code HTTP indique une erreur
        data = r.json()

        # Extraction de la température réelle de l'outil 0 (buse principale)
        temp = data.get("temperature", {}).get("tool0", {}).get("actual")

        if temp is not None:
            if temp > TEMP_MAX or temp < TEMP_MIN:
                # Alerte uniquement si on n'a pas déjà signalé un problème de température
                if alert_state.get(printer["name"]) != "temp":
                    envoyer_email(f"⚠️ Température anormale sur {printer['name']} : {temp}°C")
                    alert_state[printer["name"]] = "temp"
            else:
                # Température revenue à la normale : on efface l'alerte température
                if alert_state.get(printer["name"]) == "temp":
                    alert_state[printer["name"]] = None

        # ─── JOB ───
        # Récupère l'état du job d'impression en cours
        r = requests.get(printer["job_url"], headers=headers, timeout=5)
        r.raise_for_status()
        job = r.json()

        state = job.get("state")                              # Ex : "Printing", "Paused", "Error"
        progress = job.get("progress", {}).get("completion")  # Pourcentage d'avancement (0-100)

        if state in ["Paused", "Error"]:
            # L'impression est en pause ou en erreur : envoyer une alerte si pas déjà fait
            if alert_state.get(printer["name"]) != "stop":
                envoyer_email(f"⚠️ Impression arrêtée sur {printer['name']}")
                alert_state[printer["name"]] = "stop"
        else:
            # L'impression a repris : on efface l'alerte d'arrêt
            if alert_state.get(printer["name"]) == "stop":
                alert_state[printer["name"]] = None

        # ─── BLOQUÉ ───
        # Détecte si la progression n'a pas avancé depuis le dernier cycle (imprimante bloquée)
        if progress is not None:
            if printer["name"] not in last_progress:
                # Premier enregistrement de la progression pour cette imprimante
                last_progress[printer["name"]] = progress
                stuck_flag[printer["name"]] = False
            elif progress == last_progress[printer["name"]]:
                # Progression identique : on attend un 2e cycle identique avant d'alerter
                if stuck_flag.get(printer["name"]):
                    if alert_state.get(printer["name"]) != "stuck":
                        envoyer_email(f"⚠️ Imprimante bloquée : {printer['name']}")
                        alert_state[printer["name"]] = "stuck"
                else:
                    stuck_flag[printer["name"]] = True
            else:
                # La progression a avancé : reset
                stuck_flag[printer["name"]] = False
                if alert_state.get(printer["name"]) == "stuck":
                    alert_state[printer["name"]] = None
            last_progress[printer["name"]] = progress  # Mise à jour pour le prochain cycle

        # Reset offline si OK : l'imprimante répond, on efface l'alerte hors ligne
        if alert_state.get(printer["name"]) == "offline":
            alert_state[printer["name"]] = None

    except requests.exceptions.ConnectionError:
        # L'imprimante ne répond pas sur le réseau
        if alert_state.get(printer["name"]) != "offline":
            envoyer_email(f"⚠️ {printer['name']} hors ligne")
            alert_state[printer["name"]] = "offline"
        logging.error(f"{printer['name']} inaccessible")

    except Exception as e:
        logging.error(f"Erreur {printer['name']} : {e}")

# ─── BOUCLE PRINCIPALE ───────────────────

if __name__ == "__main__":
    logging.info("Démarrage surveillance imprimantes 3D + capteur de fumée Tyco 601P")

    # ─── INIT GPIO ───
    GPIO.setmode(GPIO.BCM)
    # Pull-down : le pin reste LOW quand aucun signal (évite les faux positifs)
    GPIO.setup(SMOKE_GPIO, GPIO.IN, pull_up_down=GPIO.PUD_DOWN)

    # Interruptions matérielles : réaction immédiate sans attendre le cycle de 60s
    # bouncetime=500ms pour ignorer les rebonds électriques du capteur
    GPIO.add_event_detect(SMOKE_GPIO, GPIO.RISING,  callback=on_fumee_detectee, bouncetime=500)
    GPIO.add_event_detect(SMOKE_GPIO, GPIO.FALLING, callback=on_fumee_resolue,  bouncetime=500)

    logging.info(f"Capteur de fumée actif sur GPIO {SMOKE_GPIO}")

    try:
        # Boucle infinie : vérifie toutes les imprimantes toutes les 60 secondes
        while True:
            for printer in printers:
                logging.info(f"Vérification : {printer['name']}")
                verifier_imprimante(printer)

            time.sleep(60)  # Attente de 60 secondes avant le prochain cycle

    finally:
        GPIO.cleanup()  # Libère les GPIO proprement à l'arrêt du script