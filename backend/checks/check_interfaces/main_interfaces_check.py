import sys
import os
sys.path.append('/var/www/backend/checks/check_interfaces')

from check_delete_old_interfaces import check_delete_old_interfaces
from check_generate_physical_interfaces_list import check_generate_physical_interfaces_list
from check_interfacesYML import generate_interfaces_yml
from check_new_interfaces import run_check_new_interfaces


def start_checks_interfaces():

    # Este script genera un listado de interfaces físicas en formato JSON, será el que se use en los formularios de la GUI
    # This script generates a list of physical interfaces in JSON format; it will be used in the GUI forms
    check_generate_physical_interfaces_list()
    ### Este script genera el archivo yml con el que se va a trabajar en la gui, copiando el del sistema
    # This script generates the YAML file that will be used in the GUI by copying it from the system.
    generate_interfaces_yml()
    #Este script añade las interfaces FISICAS nuevas detectadas por el sistema al archivo interfaces
    #This script adds newly detected physical interfaces from the system to the interfaces file.
    run_check_new_interfaces()
    #Este script borra las interfaces fisicas que han sido desconectadas/quitadas del sistema
    #This script removes physical interfaces that have been disconnected or removed from the system.
    check_delete_old_interfaces()

start_checks_interfaces()