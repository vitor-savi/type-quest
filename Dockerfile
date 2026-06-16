FROM php:8.2-apache

# Instalar extensão PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar DocumentRoot para /var/www/html/src
ENV APACHE_DOCUMENT_ROOT /var/www/html/src

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar arquivos do projeto
COPY . /var/www/html/

EXPOSE 80
