FROM php:8.2-apache

# Install PostgreSQL PDO extension + zip (for future use)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (used for clean URLs / future use)
RUN a2enmod rewrite

# Point Apache's document root at /var/www/html/public instead of /var/www/html
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides (not required yet, but harmless to enable)
RUN printf '<Directory /var/www/html/public>\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/directory-override.conf \
    && a2enconf directory-override

WORKDIR /var/www/html

COPY . /var/www/html

# Apache runs as www-data; make sure it can read everything
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
