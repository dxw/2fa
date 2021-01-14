FROM ubuntu:18.04

RUN apt-get update &&\
    DEBIAN_FRONTEND=noninteractive apt-get install -y ruby php php-mysql bundler zlib1g-dev mysql-client wget curl php-curl git php-zip ruby-dev make gcc &&\
    rm -r /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar global require wp-cli/wp-cli=^2.4 
RUN php composer.phar global require wp-cli/core-command -W
RUN php composer.phar global require wp-cli/extension-command -W
RUN php composer.phar global require wp-cli/entity-command -W
RUN php composer.phar global require wp-cli/config-command -W
RUN ln -s ~/.composer/vendor/bin/wp /usr/local/bin/

VOLUME /app
WORKDIR /app/tests
