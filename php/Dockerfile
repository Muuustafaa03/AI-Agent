FROM php:8.2-apache

# Enable rewrite + set a ServerName to silence warnings
RUN a2enmod rewrite && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Use our vhost that points DocumentRoot to /public and allows .htaccess (if you ever need it)
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

# Install mysqli
RUN docker-php-ext-install mysqli

WORKDIR /var/www/html