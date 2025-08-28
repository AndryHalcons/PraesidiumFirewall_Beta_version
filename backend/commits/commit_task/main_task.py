from task_gen_json_entry import gen_json_entry
from task_gen_json_mkdir import gen_json_mkdir
from task_gen_interface_config import gen_interface_config
from task_gen_nftables_policies import gen_nftables_policies
from task_gen_bpfilter_policies import gen_bpfilter_policies

def start_commit_process(user, date):
    # genera la entrada en /var/www/config/commit_history/commit_history.json
    # generates the entry in /var/www/config/commit_history/commit_history.json
    gen_json_entry(user, date)

    #copia la configuracion actual a un directorio en /var/www/config/commit_history con formato commit_date 
    #y tambien genera los archivos de la carpeta config_running
    #Copy the current configuration to a directory at /var/www/config/commit_history using the format commit_date, 
    #and also generate the files in the config_running folder.
    gen_json_mkdir(user, date)

    # Aplica la configuración de las interfaces de red  
    # Applies the network interface configuration
    gen_interface_config(user, date)


    # Aplica las reglas de nftables, verifica, limpia y aplica.
    # Applies the nftables rules: verifies, flushes, and then applies.
    gen_nftables_policies(user, date)

    # Aplica las reglas de bpfilter, verifica, limpia y aplica.
    # Applies the bpfilter rules: verifies, flushes, and then applies.
    gen_bpfilter_policies(user, date)



#only devops
#start_commit_process("praesidium", "20250824142408")

#{"commit":{"date":"20250824142408","user":"praesidium"}}