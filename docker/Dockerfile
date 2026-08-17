FROM ubuntu:20.04

ENV TZ=America/Sao_Paulo
ENV PHP=7.4

EXPOSE 8000

#dependencias
RUN apt-get update \
    && apt-get install -y net-tools build-essential zip unzip apt-utils git vim

#php
RUN export DEBIAN_FRONTEND=noninteractive && \ 
    apt-get install -y php$PHP php$PHP-common php$PHP-xml php$PHP-xmlrpc \
    php$PHP-curl php$PHP-gd php$PHP-imagick php$PHP-cli php$PHP-dev php$PHP-imap \
    php$PHP-mbstring php$PHP-opcache php$PHP-soap php$PHP-zip php$PHP-intl \
    php$PHP-json php$PHP-bcmath php$PHP-fpm php$PHP-mysql php$PHP-sqlite3 php-pear

# mcrypt
RUN apt-get install -y libmcrypt-dev \
    && pecl channel-update pecl.php.net \
    && pecl install mcrypt

#composer
# https://composer.github.io/installer.sig
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

#projeto - fontes
#RUN cd ./app \
#    && composer update \
#    && composer install \
#    && php artisan key:generate \
#    && chown -R www-data:www-data $PROJECT_PATH \
#    && chmod -R 777 storage bootstrap/cache

