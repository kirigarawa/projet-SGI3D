# server.py
# ---------------------------------------------------------
# Serveur Flask principal :
# - Diffusion vidéo en direct
# - API REST pour l'application Android
# - Détection fumée + ventilation
# - Envoi de mails et SMS
# ---------------------------------------------------------

from flask import Flask, Response, jsonify
import cv2
import threading
import time
import RPi.GPIO as GPIO
import requests
import smtplib
from email.mime.text import MIMEText
from config import *

app = Flask(__name__)

# ---------------------------------------------------------
# Initialisation GPIO
# ---------------------------------------------------------
GPIO.setmode(GPIO.BCM)
GPIO.setup(SMOKE_SENSOR_PIN, GPIO.IN)          # Capteur fumée
GPIO.setup(VENTILATION_RELAY_PIN, GPIO.OUT)    # Relais ventilation
GPIO.output(VENTILATION_RELAY_PIN, GPIO.LOW)   # Ventilation OFF au démarrage

# ---------------------------------------------------------
# Initialisation caméra
# ---------------------------------------------------------
camera = cv2.VideoCapture(CAMERA_INDEX)

# Variables globales
current_smoke_level = 0.0
smoke_alert_active = False
print_status = "IDLE"


# ---------------------------------------------------------
# Lecture du statut d'impression
# ---------------------------------------------------------
def read_print_status():
    global print_status
    try:
        with open(PRINT_STATUS_FILE, "r") as f:
            print_status = f.read().strip()
    except FileNotFoundError:
        print_status = "IDLE"


# ---------------------------------------------------------
# Envoi d'un email
# ---------------------------------------------------------
def send_email(subject, body):
    msg = MIMEText(body)
    msg["Subject"] = subject
    msg["From"] = EMAIL_FROM
    msg["To"] = EMAIL_TO

    with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
        server.starttls()                 # Sécurisation TLS
        server.login(SMTP_USER, SMTP_PASSWORD)
        server.send_message(msg)


# ---------------------------------------------------------
# Envoi d'un SMS via API HTTP
# ---------------------------------------------------------
def send_sms(message):
    data = {
        "api_key": SMS_API_KEY,
        "to": SMS_PHONE_TO,
        "message": message
    }
    try:
        requests.post(SMS_API_URL, data=data, timeout=5)
    except Exception as e:
        print("Erreur envoi SMS :", e)


# ---------------------------------------------------------
# Génération du flux vidéo MJPEG
# ---------------------------------------------------------
def gen_frames():
    while True:
        success, frame = camera.read()
        if not success:
            break
        else:
            ret, buffer = cv2.imencode('.jpg', frame)
            frame_bytes = buffer.tobytes()

            # Format MJPEG pour navigateur / appli Android
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')


@app.route("/video")
def video_feed():
    return Response(gen_frames(),
                    mimetype='multipart/x-mixed-replace; boundary=frame')


# ---------------------------------------------------------
# API REST pour l'application Android (Étudiant 3)
# ---------------------------------------------------------
@app.route("/api/status")
def api_status():
    read_print_status()
    return jsonify({
        "printer_status": print_status,
        "smoke_level": current_smoke_level,
        "smoke_alert": smoke_alert_active
    })


# ---------------------------------------------------------
# API appelée par le serveur d'impression en fin de job
# ---------------------------------------------------------
@app.route("/api/print_done")
def api_print_done():
    send_email("Impression terminée", "Votre impression 3D est terminée.")
    send_sms("Impression 3D terminée.")
    return jsonify({"status": "ok", "message": "Notifications envoyées"})


# ---------------------------------------------------------
# Thread de surveillance fumée + ventilation
# ---------------------------------------------------------
def smoke_monitor():
    global current_smoke_level, smoke_alert_active

    while True:
        # Lecture du capteur (0 ou 1)
        smoke_raw = GPIO.input(SMOKE_SENSOR_PIN)
        current_smoke_level = float(smoke_raw)

        # Détection fumée
        if current_smoke_level >= SMOKE_THRESHOLD and not smoke_alert_active:
            smoke_alert_active = True
            GPIO.output(VENTILATION_RELAY_PIN, GPIO.HIGH)  # Ventilation ON

            send_email("Alerte fumée", "Niveau de fumée élevé détecté.")
            send_sms("ALERTE : fumée détectée près de l'imprimante 3D !")

        # Retour à la normale
        elif current_smoke_level < SMOKE_THRESHOLD and smoke_alert_active:
            smoke_alert_active = False
            GPIO.output(VENTILATION_RELAY_PIN, GPIO.LOW)   # Ventilation OFF

        time.sleep(1)


# ---------------------------------------------------------
# Lancement du serveur + thread de surveillance
# ---------------------------------------------------------
if __name__ == "__main__":
    t = threading.Thread(target=smoke_monitor, daemon=True)
    t.start()
    app.run(host=HOST, port=PORT, debug=False)