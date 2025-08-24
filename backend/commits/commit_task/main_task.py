from task_gen_json_entry import gen_json_entry
from task_gen_json_mkdir import gen_json_mkdir
from task_gen_interface_config import gen_interface_config

def start_commit_process(user, date):
    # genera la entrada en /var/www/config/commit_history/commit_history.json
    # generates the entry in /var/www/config/commit_history/commit_history.json
    gen_json_entry(user, date)

    # copia la configuracion actual a un directorio en /var/www/config/commit_history con formato commit_date
    # copies the current configuration to a directory in /var/www/config/commit_history with format commit_date
    gen_json_mkdir(user, date)

    # Aplica la configuración de las interfaces de red  
    # Applies the network interface configuration
    gen_interface_config(user, date)



#only devops
start_commit_process("praesidium", "20250824142408")

#{"commit":{"date":"20250824142408","user":"praesidium"}}