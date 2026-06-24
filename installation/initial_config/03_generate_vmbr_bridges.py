#!/usr/bin/env python3
"""
Convierte ethernets físicas en bridges vmbrN durante la configuración inicial.

La transformación es idempotente:
- no duplica bridges si una ethernet ya pertenece a un bridge existente;
- conserva bridges existentes;
- mueve la configuración IP/rutas/DNS/DHCP de la ethernet al bridge;
- conserva campos físicos como match.* y set-name en la ethernet;
- deja la ethernet como puerto físico del bridge sin configuración L3.
"""
from __future__ import annotations

import ast
import json
import os
from pathlib import Path
from typing import Any

INTERFACES_JSON = Path(os.environ.get('PRAESIDIUM_INTERFACES_JSON', '/var/www/config/interfaces.json'))
PHYSICAL_INTERFACES_JSON = Path(os.environ.get(
    'PRAESIDIUM_PHYSICAL_INTERFACES_JSON',
    '/var/www/backend/checks/system_data/data_interfaces/physical_interfaces_list.json',
))
MAPPING_JSON = Path(os.environ.get(
    'PRAESIDIUM_VMBR_MAPPING_JSON',
    '/var/www/backend/checks/system_data/data_interfaces/vmbr_bridge_map.json',
))


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding='utf-8'))


def save_json(path: Path, data: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, indent=4, ensure_ascii=False) + '\n', encoding='utf-8')


def split_csv(value: Any) -> list[str]:
    if isinstance(value, list):
        return [str(item).strip() for item in value if str(item).strip()]
    return [item.strip() for item in str(value or '').split(',') if item.strip()]


def physical_order(ethernets: dict[str, Any]) -> list[str]:
    names = list(ethernets.keys())
    if not PHYSICAL_INTERFACES_JSON.exists():
        return sorted(names)

    try:
        data = load_json(PHYSICAL_INTERFACES_JSON)
    except Exception:
        return sorted(names)

    physical = data.get('physical_interfaces', [])
    ordered: list[str] = []
    if isinstance(physical, list):
        def sort_key(item: dict[str, Any]) -> tuple[int, str]:
            raw_ifindex = item.get('ifindex', 999999)
            try:
                ifindex = int(raw_ifindex)
            except Exception:
                ifindex = 999999
            return ifindex, str(item.get('name', ''))

        for item in sorted((i for i in physical if isinstance(i, dict)), key=sort_key):
            name = str(item.get('name', '')).strip()
            if name in ethernets and name not in ordered:
                ordered.append(name)

    for name in sorted(names):
        if name not in ordered:
            ordered.append(name)
    return ordered


def bridge_members(bridges: dict[str, Any]) -> dict[str, str]:
    members: dict[str, str] = {}
    for bridge_name, config in bridges.items():
        if not isinstance(config, dict):
            continue
        for iface in split_csv(config.get('interfaces', '')):
            members[iface] = bridge_name
    return members


def vmbr_name_for_index(index: int, bridges: dict[str, Any], reserved: set[str]) -> str:
    candidate_index = index
    while True:
        candidate = f'vmbr{candidate_index}'
        if candidate not in bridges and candidate not in reserved:
            reserved.add(candidate)
            return candidate
        candidate_index += 1


def normalize_routes(config: dict[str, Any]) -> None:
    # Normaliza rutas heredadas del conversor Netplan inicial.
    # Normalize routes inherited from the initial Netplan-to-JSON converter.
    raw_routes = config.pop('routes', None)
    if not raw_routes or config.get('routes.to') or config.get('routes.via'):
        return

    parsed: Any = None
    if isinstance(raw_routes, dict):
        parsed = raw_routes
    elif isinstance(raw_routes, list) and raw_routes:
        parsed = raw_routes[0]
    elif isinstance(raw_routes, str):
        try:
            parsed = ast.literal_eval(raw_routes)
        except Exception:
            parsed = None

    if isinstance(parsed, list) and parsed:
        parsed = parsed[0]
    if not isinstance(parsed, dict):
        return

    route_to = parsed.get('to')
    route_via = parsed.get('via')
    if route_to and route_via:
        config['routes.to'] = str(route_to)
        config['routes.via'] = str(route_via)
    if parsed.get('metric') not in (None, ''):
        config['routes.metric'] = str(parsed['metric'])


def split_ethernet_for_bridge(config: dict[str, Any]) -> tuple[dict[str, Any], dict[str, Any]]:
    # Separa campos físicos de campos que deben vivir en el bridge.
    # Split physical-interface fields from fields that should live on the bridge.
    physical_config: dict[str, Any] = {}
    bridge_config: dict[str, Any] = {}

    for key, value in config.items():
        if key.startswith('match.') or key == 'set-name':
            physical_config[key] = value
        else:
            bridge_config[key] = value

    normalize_routes(bridge_config)
    return physical_config, bridge_config


def transform(data: dict[str, Any]) -> tuple[dict[str, Any], dict[str, str]]:
    network = data.setdefault('network', {})
    network.setdefault('version', '2')
    ethernets = network.setdefault('ethernets', {})
    bridges = network.setdefault('bridges', {})

    if not isinstance(ethernets, dict) or not isinstance(bridges, dict):
        raise SystemExit('interfaces.json network.ethernets/network.bridges must be objects')

    existing_members = bridge_members(bridges)
    reserved_bridges: set[str] = set()
    mapping: dict[str, str] = {}

    for index, ethernet_name in enumerate(physical_order(ethernets)):
        ethernet_config = ethernets.get(ethernet_name)
        if not isinstance(ethernet_config, dict):
            continue

        if ethernet_name in existing_members:
            mapping[ethernet_name] = existing_members[ethernet_name]
            continue

        bridge_name = vmbr_name_for_index(index, bridges, reserved_bridges)
        physical_config, bridge_config = split_ethernet_for_bridge(ethernet_config)
        bridge_config['interfaces'] = ethernet_name
        bridges[bridge_name] = bridge_config
        ethernets[ethernet_name] = physical_config
        mapping[ethernet_name] = bridge_name

    return data, mapping


def main() -> None:
    if not INTERFACES_JSON.exists():
        raise SystemExit(f'{INTERFACES_JSON} not found')
    data = load_json(INTERFACES_JSON)
    transformed, mapping = transform(data)
    save_json(INTERFACES_JSON, transformed)
    save_json(MAPPING_JSON, {'ethernet_to_bridge': mapping})


if __name__ == '__main__':
    main()
