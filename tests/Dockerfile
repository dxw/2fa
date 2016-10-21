FROM ubuntu:16.04

RUN apt-get update
RUN apt-get install -y ruby php composer bundler zlib1g-dev mysql-client php-mysql wget
RUN composer global require wp-cli/wp-cli
RUN ln -s ~/.composer/vendor/bin/wp /usr/local/bin/

VOLUME /app
WORKDIR /app/tests
