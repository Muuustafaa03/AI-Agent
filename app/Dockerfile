FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite

# PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set ServerName to silence warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy the entire app directory contents
COPY . /var/www/html/

# The public folder should now be at /var/www/html/public
# Configure Apache DocumentRoot
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf

# Add Directory configuration
RUN echo '<Directory /var/www/html/public/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Dynamic PORT for Render
EXPOSE 80
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf && \
    sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground