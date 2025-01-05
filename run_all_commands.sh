#!/bin/bash
set -e

# Ejecutar parse:files
php artisan parse:files --output-file=docs/parse_all.json --verbose

# Ejecutar analyze:files
php artisan analyze:files --output-file=docs/analyze_all.json --verbose

# Ejecutar passes:process
php artisan passes:process --verbose

# Procesar la cola hasta que esté vacía
php artisan queue:work --stop-when-empty

# Iniciar el servidor de Artisan
php artisan serve
