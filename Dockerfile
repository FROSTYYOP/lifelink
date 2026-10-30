FROM php:8.2-apache

# Enable Apache mod_rewrite for .htaccess routing
RUN a2enmod rewrite headers deflate

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Configure Apache port to 10000 (Render default port)
RUN sed -i 's/80/10000/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Copy project files into Apache web root
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]
