#!/bin/bash
# Instalar dependencias del sistema / Install system dependencies
apt update
xargs -a requirements_ubuntu.txt apt install -y
