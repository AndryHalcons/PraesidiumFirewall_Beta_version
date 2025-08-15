#!/bin/bash

# Borrar contenido anterior / Delete previous content
rm -rf /var/www/html/*

# Copiar nuevo contenido / Copy new content
cp -r ../web/* /var/www/html/


