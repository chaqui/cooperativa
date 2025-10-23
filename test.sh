#!/bin/bash

# Script para ejecutar pruebas unitarias con SQLite
# Uso: ./test.sh [opciones]

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🧪 Ejecutando Pruebas Unitarias con SQLite${NC}"
echo "=================================================="

# Verificar que composer esté instalado
if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Composer no está instalado${NC}"
    exit 1
fi

# Verificar que vendor/bin/phpunit existe
if [ ! -f "./vendor/bin/phpunit" ]; then
    echo -e "${YELLOW}📦 Instalando dependencias...${NC}"
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Ejecutar pruebas
echo -e "${YELLOW}🚀 Ejecutando pruebas...${NC}"

if [ "$1" = "--coverage" ]; then
    echo -e "${YELLOW}📊 Generando reporte de cobertura...${NC}"
    ./vendor/bin/phpunit --coverage-html coverage-report --coverage-text
elif [ "$1" = "--filter" ] && [ -n "$2" ]; then
    echo -e "${YELLOW}🔍 Ejecutando pruebas filtradas: $2${NC}"
    ./vendor/bin/phpunit --filter="$2"
elif [ "$1" = "--unit" ]; then
    echo -e "${YELLOW}🔧 Ejecutando solo pruebas unitarias...${NC}"
    ./vendor/bin/phpunit tests/Unit
elif [ "$1" = "--feature" ]; then
    echo -e "${YELLOW}🎯 Ejecutando solo pruebas de funcionalidad...${NC}"
    ./vendor/bin/phpunit tests/Feature
else
    ./vendor/bin/phpunit
fi

# Verificar resultado
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Todas las pruebas pasaron correctamente${NC}"
else
    echo -e "${RED}❌ Algunas pruebas fallaron${NC}"
    exit 1
fi

echo "=================================================="
echo -e "${GREEN}🎉 Pruebas completadas${NC}"
