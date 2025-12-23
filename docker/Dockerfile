FROM php:8.2-apache

# 1. Instalar dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

# 2. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Ativar Mod Rewrite (Essencial para rotas)
RUN a2enmod rewrite

# 4. Copiar a nossa configuração do Apache para substituir a padrão
# Isto força o Apache a ler o teu .htaccess e aponta para a pasta public
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# 5. Configurar pasta de trabalho
WORKDIR /var/www/html

# 6. Ajustar permissões (Evita problemas de escrita)
RUN chown -R www-data:www-data /var/www/html