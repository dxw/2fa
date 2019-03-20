FROM ubuntu:16.04

RUN apt-get update
RUN apt-get install -y ruby php7.0 php7.0-mysql bundler zlib1g-dev mysql-client wget curl php-curl
RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar global require wp-cli/wp-cli=^1.4
RUN ln -s ~/.composer/vendor/bin/wp /usr/local/bin/

VOLUME /app
WORKDIR /app/tests
