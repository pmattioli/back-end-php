FROM php:7.4.9-apache
RUN apt-get update && \
apt-get install -y libicu-dev libzip-dev && a2enmod headers rewrite && \
docker-php-ext-install mysqli pdo pdo_mysql gettext intl zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer global require "laravel/lumen-installer"
ENV PATH $PATH:/tmp/vendor/bin
COPY . .
COPY config.php ../RetinaLyze_ConfigFiles/config.php
RUN composer i
ENTRYPOINT ["php","-S","0.0.0.0:8085","-t","public"]