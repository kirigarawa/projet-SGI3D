# update_print_status.py
# ---------------------------------------------------------
# Ce script est appelé par le serveur d'impression
# pour mettre à jour le statut : IDLE / PRINTING / DONE / ERROR
# ---------------------------------------------------------

import sys
from fichier_de_configuration import PRINT_STATUS_FILE

if len(sys.argv) != 2:
    print("Usage: python update_print_status.py <IDLE|PRINTING|DONE|ERROR>")
    sys.exit(1)

status = sys.argv[1].upper()

if status not in ["IDLE", "PRINTING", "DONE", "ERROR"]:
    print("Statut invalide")
    sys.exit(1)

with open(PRINT_STATUS_FILE, "w") as f:
    f.write(status)

print("Statut mis à jour :", status)