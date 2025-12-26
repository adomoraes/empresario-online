#!/bin/bash
set -e

echo "⏳ Aguardando MySQL iniciar..."

# Loop simples para verificar se o MySQL está respondendo
# Ajusta o host (mysql_nativo) e user/pass conforme teu docker-compose/env
until php -r "try { new PDO('mysql:host=mysql_nativo;dbname=meu_banco', 'root', 'root'); } catch(PDOException \$e) { exit(1); }" > /dev/null 2>&1; do
  echo "MySQL indisponível - aguardando..."
  sleep 3
done

echo "✅ MySQL conectado!"

# Opcional: Garantir que as tabelas existem (Schema)
# mysql -h mysql_nativo -u root -proot meu_banco < /var/www/html/sql/schema.sql

echo "🌱 Executando Seeding..."
php /var/www/html/seed_runner.php

echo "🚀 Iniciando Apache..."
exec apache2-foreground