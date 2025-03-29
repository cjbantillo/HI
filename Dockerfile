FROM php:8.0-fpm

# Install the necessary extensions
RUN docker-php-ext-install mysqli
