# config.py
# ---------------------------------------------------------
# Fichier de configuration centralisé pour ton serveur.
# Il permet de modifier facilement les paramètres réseau,
# les GPIO, les identifiants email/SMS, etc.
# ---------------------------------------------------------

# --- Réseau / serveur ---
HOST = "0.0.0.0"   # Le serveur écoute sur toutes les interfaces réseau
PORT = 5000        # Port HTTP du serveur Flask

# --- Caméra ---
CAMERA_INDEX = 0   # 0 = caméra par défaut du Raspberry Pi

# --- GPIO ---
SMOKE_SENSOR_PIN = 17       # Broche du capteur de fumée (MQ-2 ou autre)
VENTILATION_RELAY_PIN = 27  # Broche du relais de ventilation

# --- Fichier de statut d'impression ---
# Ce fichier est mis à jour par le serveur d'impression (OctoPrint ou script maison)
PRINT_STATUS_FILE = "/home/pi/print_status.txt"

# --- Email ---
SMTP_SERVER = "smtp.example.com"
SMTP_PORT = 587
SMTP_USER = "ton_mail@example.com"
SMTP_PASSWORD = "mot_de_passe"
EMAIL_FROM = "ton_mail@example.com"
EMAIL_TO = "destinataire@example.com"

# --- SMS (API HTTP générique) ---
SMS_API_URL = "https://api.smsprovider.com/send"
SMS_API_KEY = "TA_CLE_API_SMS"
SMS_PHONE_TO = "+596XXXXXXXX"

# --- Seuil fumée ---
# Si ton capteur renvoie 0/1, ce seuil reste à 0.5
SMOKE_THRESHOLD = 0.5