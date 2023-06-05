#!/bin/sh
set -xe

# Run this script from the root of the plugin
# Not from this directory

# Build the image
docker build -t 2fa_test_main tests/

# Get MySQL running
docker stop 2fa_test_mysql || true
docker rm 2fa_test_mysql || true
docker run -d --name=2fa_test_mysql --health-cmd='mysqladmin ping --silent' -e MYSQL_DATABASE=2fa_test -e MYSQL_ROOT_PASSWORD=foobar mariadb:10.5.8
export HOST=`docker inspect -f '{{.NetworkSettings.IPAddress}}' 2fa_test_mysql`
while  STATUS=`docker inspect --format "{{.State.Health.Status}}" 2fa_test_mysql`; [ $STATUS != "healthy" ]; do
    echo "waiting for database"
    sleep 1
done

# Run the tests
docker run --rm -v `pwd`:/app -e MYSQL_HOST=${HOST} -e MYSQL_PASSWORD=foobar 2fa_test_main sh -c 'bundle install && bundle exec rspec spec/*_spec.rb'
