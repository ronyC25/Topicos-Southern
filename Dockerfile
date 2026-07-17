FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql

RUN a2enmod rewrite

RUN echo "date.timezone = America/Lima" > /usr/local/etc/php/conf.d/timezone.ini