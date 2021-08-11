#!/usr/bin/env sh
mkdir -p "data/ocker/mysql"
mkdir -p "data/ocker/rabbitmq"

IS_ICHIMOKU_PHP=$(docker images | grep ichimoku-php -c)

if [ "$IS_ICHIMOKU_PHP" = "0" ];then
  cd docker/ichimoku && docker build -t ichimoku-php . && cd ..
fi

cd docker && docker-compose -p ichimoku -f docker-compose.yml up