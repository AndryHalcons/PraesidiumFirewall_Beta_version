import sys
import json
from commit_task.main_task import start_commit_process  # Importar la función

def start_commit(user, date):
    try:
        # Llamar a la función externa
        start_commit_process(user, date)
        return date, user
    except Exception:
        return None, None

# Verificar que se recibió el JSON como argumento
if len(sys.argv) < 2:
    print(json.dumps({"error": "No se recibió JSON"}))
    sys.exit(1)

try:
    # Decodificar el JSON recibido
    commit_data = json.loads(sys.argv[1])

    # Extraer fecha y usuario del JSON
    date = commit_data["commit"]["date"]
    user = commit_data["commit"]["user"]

    # Llamar a la función con los valores directamente
    date, user = start_commit(user, date)

    # Confirmar éxito incluyendo date y user
    print(json.dumps({
        "status": "ok",
        "date": date,
        "user": user
    }))

except json.JSONDecodeError:
    print(json.dumps({"error": "JSON inválido"}))
    sys.exit(1)
except KeyError:
    print(json.dumps({"error": "Faltan campos 'date' o 'user'"}))
    sys.exit(1)
