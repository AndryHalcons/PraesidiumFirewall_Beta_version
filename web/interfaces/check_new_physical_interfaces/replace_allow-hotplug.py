##Reemplaza allow-hotplug que no es compatible con ifquery por auto
##Replace allow-hotplug, which is not compatible with ifquery, with auto
with open("/etc/network/interfaces", "r+") as f:
    content = f.read().replace("allow-hotplug", "auto")
    f.seek(0)
    f.write(content)
    f.truncate()
