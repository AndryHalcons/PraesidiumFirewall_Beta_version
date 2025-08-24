import json
import os

def task_update_json(date, process, status):
    history_path = '/var/www/config/commit_history/commit_history.json'

    # Verificar si el archivo existe
    # Check if the file exists
    if not os.path.exists(history_path):
        return

    # Cargar el contenido actual
    # Load current content
    with open(history_path, 'r') as f:
        try:
            data = json.load(f)
        except json.JSONDecodeError:
            return

    # Verificar si la clave date existe
    # Check if the date key exists
    if date in data.get("commits", {}):
        # Añadir la entrada process: status
        # Add the entry process: status
        if "task" not in data["commits"][date] or not isinstance(data["commits"][date]["task"], dict):
            data["commits"][date]["task"] = {}

        data["commits"][date]["task"][process] = status

        # Guardar los cambios
        # Save the changes
        with open(history_path, 'w') as f:
            json.dump(data, f, indent=4)
