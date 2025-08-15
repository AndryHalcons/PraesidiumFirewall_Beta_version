#!/bin/bash
# Instalar dependencias del sistema / Install system dependencies
apt update
xargs -a requirements.txt apt install -y
