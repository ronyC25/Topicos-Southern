FROM php:8.2-apache

RUN apt-get update && apt-get install -y libldap2-dev \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install pdo_mysql ldap

RUN a2enmod rewrite ssl

RUN mkdir -p /etc/apache2/ssl
COPY docker/ssl.conf /etc/apache2/sites-available/ssl.conf
RUN a2ensite ssl

RUN echo "date.timezone = America/Lima" > /usr/local/etc/php/conf.d/timezone.ini