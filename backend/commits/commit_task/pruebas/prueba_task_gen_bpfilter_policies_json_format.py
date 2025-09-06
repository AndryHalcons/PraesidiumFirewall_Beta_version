import json
import convert_bpfilter
from collections import defaultdict

# // Carga el JSON desde disco y lo devuelve como diccionario (array asociativo)
# // Loads JSON from disk and returns it as a dictionary (associative array)
def load_json_as_array():
    path = "/home/praesidium/PraesidiumFirewall/data/rules_bpfilter_human_viewer.json"
    try:
        with open(path, "r") as f:
            data = json.load(f)
        return data
    except Exception:
        return {}



##########################################################################################################
############################################## constructor ###############################################
##########################################################################################################





# // Extrae los campos de match desde una regla plana
# // Extracts match fields from a flat rule
def saniticed_bpfilter_format(rule):
    iface = convert_bpfilter.transform_iface(rule.get("interface"))
    l3_proto = rule.get("l3_protocol")
    l4_proto = rule.get("l4_protocol")

    # IPv4
    ip4_saddr = convert_bpfilter.transform_ip4("ip4_saddr", rule.get("source"))
    ip4_daddr = convert_bpfilter.transform_ip4("ip4_daddr", rule.get("destination"))
    ip4_snet = convert_bpfilter.transform_ip4_net("ip4_snet", rule.get("source"))
    ip4_dnet = convert_bpfilter.transform_ip4_net("ip4_dnet", rule.get("destination"))
    ip4_proto = ""

    # IPv6
    ip6_saddr = convert_bpfilter.transform_ip6("ip6_saddr", rule.get("source"))
    ip6_daddr = convert_bpfilter.transform_ip6("ip6_daddr", rule.get("destination"))
    ip6_snet =  convert_bpfilter.transform_ip6_net("ip6_snet", rule.get("source"))
    ip6_dnet =  convert_bpfilter.transform_ip6_net("ip6_dnet", rule.get("destination"))
    ip6_nexthdr = rule.get("ipv6_next_header")

    # TCP
    tcp_sport = rule.get("sport")
    tcp_dport = rule.get("dport")
    tcp_flags = rule.get("tcp_flags")

    # UDP
    udp_sport = ""
    udp_dport = ""

    # ICMP
    icmp_type = rule.get("icmp_type")
    icmp_code = rule.get("icmp_code")

    # ICMPv6
    icmpv6_type = rule.get("icmpv6_type")
    icmpv6_code = rule.get("icmpv6_code")

    # Meta
    probability = rule.get("probability") or "100%"

    return {
        "interface": iface,
        "l3_protocol": l3_proto,
        "l4_protocol": l4_proto,
        "ip4_saddr": ip4_saddr,
        "ip4_daddr": ip4_daddr,
        "ip4_snet": ip4_snet,
        "ip4_dnet": ip4_dnet,
        "ip4_proto": ip4_proto,
        "ip6_saddr": ip6_saddr,
        "ip6_daddr": ip6_daddr,
        "ip6_snet": ip6_snet,
        "ip6_dnet": ip6_dnet,
        "ip6_nexthdr": ip6_nexthdr,
        "tcp_sport": tcp_sport,
        "tcp_dport": tcp_dport,
        "tcp_flags": tcp_flags,
        "udp_sport": udp_sport,
        "udp_dport": udp_dport,
        "icmp_type": icmp_type,
        "icmp_code": icmp_code,
        "icmpv6_type": icmpv6_type,
        "icmpv6_code": icmpv6_code,
        "probability": probability
    }








# // Genera el archivo de salida con las reglas formateadas
# // Generates the output file with formatted rules
import json

def task_gen_bpfilter_policies_json_format():
    outputPath = "/home/praesidium/PraesidiumFirewall/backend/commits/commit_task/pruebas/pruebas_machine.json"
    
    rule = {
        "id": "1",
        "hook": "BF_HOOK_XDP",
        "chain": "XDP_chain_group_1",
        "position": "1",
        "action": "accept",
        "enable": "true",
        "name": "XDPname1",
        "interface": "ens18",
        "l3_protocol": "IPv4",
        "l4_protocol": "TCP",
        "source": "1.1.1.1/24,8.8.8.8,2001:0db8:85a3:0000:0000:8a2e:0370:7334,2001:db8::/128,9.9.9.9 ,2606:4700:4700::1111,2001:4860:4860::8888,fd00:1234:5678::/64",
        "sport": "",
        "destination": "8.8.8.8,73.7.7.7/28,fe80::1ff:fe23:4567:890a,fd00:abcd:1234::/48,2001:db8:abcd::/48",
        "dport": "",
        "tcp_flags": "",
        "ipv6_next_header": "",
        "icmp_type": "",
        "icmp_code": "",
        "icmpv6_type": "",
        "icmpv6_code": "",
        "probability": ""
    }

    result = saniticed_bpfilter_format(rule)

    # Guardamos el resultado como JSON en disco
    # Save the result as JSON to disk
    try:
        with open(outputPath, "w") as f:
            json.dump(result, f, indent=4)
        print(f"Archivo guardado en: {outputPath}")
    except Exception as e:
        print(f"Error al guardar el archivo: {e}")





task_gen_bpfilter_policies_json_format()