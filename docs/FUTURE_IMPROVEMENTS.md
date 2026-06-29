# Future improvements / Mejoras futuras

## nftables atomic apply

Current Praesidium nftables apply flow verifies the generated JSON, backs up the running
ruleset, runs `nft flush ruleset`, and then loads the generated ruleset. This is not a
true atomic replacement because there is a window between the separate flush and apply
commands.

Flujo actual de Praesidium para nftables: verificar el JSON generado, hacer backup del
ruleset activo, ejecutar `nft flush ruleset` y después cargar el nuevo ruleset. Esto no
es un reemplazo atómico real porque existe una ventana entre el flush y el apply.

### Desired direction / Dirección deseada

Generate one complete nftables batch file that contains both the cleanup and the full
replacement ruleset, then validate and apply that single file:

```bash
sudo nft -c -f /var/www/config_running/nftables_ruleset.nft
sudo nft -f /var/www/config_running/nftables_ruleset.nft
```

The batch must include the cleanup inside the same file, for example:

```nft
flush ruleset

table inet filter {
    chain input {
        type filter hook input priority filter; policy accept;
        # generated rules
    }
}

table inet nat {
    # generated NAT chains and rules
}
```

Important: simply adding or loading an extra `.nft` file without cleanup is unsafe. It
can duplicate rules, collide with existing tables/chains, or leave an incoherent ruleset.
The cleanup and recreation must be part of the same `nft -f` batch if the goal is atomic
replacement.

Importante: añadir o cargar un `.nft` adicional sin limpieza integrada es inseguro. Puede
duplicar reglas, chocar con tablas/chains existentes o dejar un ruleset incoherente. La
limpieza y la recreación deben formar parte del mismo batch `nft -f` si el objetivo es
reemplazo atómico.

### Scope decision / Decisión de alcance

Using `flush ruleset` means Praesidium owns the whole nftables ruleset. If Praesidium
should avoid touching rules created by other system components, a future design should use
Praesidium-owned tables, for example `table inet praesidium_filter` and
`table inet praesidium_nat`, and replace only those tables carefully.

Usar `flush ruleset` implica que Praesidium se adueña de todo el ruleset nftables. Si se
quiere evitar tocar reglas creadas por otros componentes del sistema, un diseño futuro
debería usar tablas propias de Praesidium, por ejemplo `table inet praesidium_filter` y
`table inet praesidium_nat`, y reemplazar sólo esas tablas de forma controlada.

### Still required / Aún necesario

Atomic apply only protects against partial load failures. It does not protect against a
valid but wrong ruleset that locks out SSH/WebGUI or breaks forwarding. Praesidium should
still keep anti-lockout strategy, post-apply connectivity checks, and rollback records.

El apply atómico sólo protege contra cargas parciales fallidas. No protege contra un
ruleset válido pero incorrecto que bloquee SSH/WebGUI o rompa forwarding. Praesidium debe
mantener estrategia anti-lockout, pruebas post-apply de conectividad y registros de
rollback.
