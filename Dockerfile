FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli && a2enmod rewrite headers

# Apache: allow .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY . /var/www/html/

# Permissions for Apache
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80

# Use getenv() so Render/Koyeb env vars work
ENV APACHE_DOCUMENT_ROOT=/var/www/html
