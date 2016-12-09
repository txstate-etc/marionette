FROM php:5-apache

RUN docker-php-ext-install -j$(nproc) mysqli

COPY src/ /var/www/html/
