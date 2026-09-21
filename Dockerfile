FROM php:8.2-apache

# Postgres PDO (Render) - was pdo_mysql for XAMPP/InfinityFree
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Apache: allow .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY . /var/www/html/

# Permissions for Apache
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80

# Use getenv() so Render env vars work
ENV APACHE_DOCUMENT_ROOT=/var/www/html
