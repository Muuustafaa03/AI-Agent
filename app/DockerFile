FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite

# PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set ServerName to silence warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy app
WORKDIR /var/www/html
COPY . /var/www/html

# Point DocumentRoot to /public
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's#<Directory /var/www/>#<Directory /var/www/html/public/>#' /etc/apache2/apache2.conf \
 && sed -i 's/AllowOverride None/AllowOverride All/i' /etc/apache2/apache2.conf

# CRITICAL: Render requires dynamic PORT binding
EXPOSE 80
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf && \
    sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground