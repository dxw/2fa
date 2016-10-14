#!/bin/sh
set -xe

# Run this script from the root of the plugin
# Not from this directory

# Build the image
docker build -t 2fa_test_main tests/

# Get MySQL running
docker stop 2fa_test_mysql || true
docker rm 2fa_test_mysql || true
docker run -d --name=2fa_test_mysql -e MYSQL_DATABASE=2fa_test -e MYSQL_ROOT_PASSWORD=foobar mysql
export HOST=`docker inspect -f '{{.NetworkSettings.IPAddress}}' 2fa_test_mysql`

# Run the tests
docker run -ti --rm -v `pwd`:/app -e MYSQL_HOST=${HOST} -e MYSQL_PASSWORD=foobar 2fa_test_main sh -c 'bundle install && bundle exec rspec spec/*_spec.rb'
