FROM php:8.2-apache

# 1. Instalar dependências do sistema e extensões PHP
# Adicionámos 'default-mysql-client' para debug e gestão de base de dados via terminal
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql

# 2. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Ativar Mod Rewrite
RUN a2enmod rewrite

# 4. Copiar configuração do Apache
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# 5. Configurar pasta de trabalho
WORKDIR /var/www/html

# 6. Copiar os ficheiros do projeto
# Nota: Isto é importante para o 'composer install' funcionar na construção da imagem
COPY . /var/www/html

# 7. Instalar dependências do PHP (Composer)
RUN composer install --no-interaction --optimize-autoloader

# 8. Ajustar permissões
RUN chown -R www-data:www-data /var/www/html

# 9. Configurar o Entrypoint (O "Segredo" da automação)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

# Define o script que corre sempre que o container inicia
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]