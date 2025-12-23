# Usamos a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instalar extensões necessárias para o MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Ativar o mod_rewrite do Apache
# Isto é crucial para transformar URLs como /users em index.php?route=/users
RUN a2enmod rewrite

# Alterar a raiz do documento para a pasta /public
# (Assim o utilizador não consegue aceder ao código fonte em /src diretamente)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Define o diretório de trabalho
WORKDIR /var/www/html