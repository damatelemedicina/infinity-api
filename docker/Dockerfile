FROM ubuntu:20.04

ENV TZ=America/Sao_Paulo
ENV PHP=7.4
ENV PROJECT_PATH=/home/infinity/infinity-api

EXPOSE 8000

#dependencias
RUN apt-get update \
    && apt-get install -y net-tools build-essential zip unzip apt-utils git vim curl

#php + apache
RUN export DEBIAN_FRONTEND=noninteractive && \
    apt-get install -y apache2 libapache2-mod-php$PHP \
    php$PHP php$PHP-common php$PHP-xml php$PHP-xmlrpc \
    php$PHP-curl php$PHP-gd php$PHP-imagick php$PHP-cli php$PHP-dev php$PHP-imap \
    php$PHP-mbstring php$PHP-opcache php$PHP-soap php$PHP-zip php$PHP-intl \
    php$PHP-json php$PHP-bcmath php$PHP-fpm php$PHP-mysql php$PHP-sqlite3 php-pear \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 8000/' /etc/apache2/ports.conf

# mcrypt
# installed from a vendored tgz: pecl.php.net's REST channel is broken and
# the container can't reach the CDN it downloads from, so the tarball is
# fetched on the host and copied in instead
COPY docker/mcrypt-1.0.4.tgz /tmp/mcrypt.tgz
RUN apt-get install -y libmcrypt-dev \
    && pecl install /tmp/mcrypt.tgz \
    && rm /tmp/mcrypt.tgz

#composer
# Signature fetched at build time (not hardcoded) — getcomposer.org rotates
# the installer periodically, and a stale hash here just breaks the build.
RUN EXPECTED_SIGNATURE="$(curl -fsSL https://composer.github.io/installer.sig)" \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" \
    && if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then >&2 echo 'ERROR: Invalid installer signature'; rm composer-setup.php; exit 1; fi \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

#vhost
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR $PROJECT_PATH
