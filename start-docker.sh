#!/usr/bin/env sh
mkdir -p "data/docker/mysql"
mkdir -p "data/docker/rabbitmq"

IS_ICHIMOKU_PHP=$(docker images | grep ichimoku-php -c)

if [ "$IS_ICHIMOKU_PHP" = "0" ];then
  cd docker/ichimoku && docker build -t ichimoku-php . && cd ../../
fi

if [ ! -e docker/.env ]
then
  cp docker/.env.dist docker/.env
fi

cd docker && docker-compose -p ichimoku -f docker-compose.yml up -d