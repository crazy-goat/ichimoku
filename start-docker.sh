#!/usr/bin/env sh
mkdir -p "data/docker/mysql"
mkdir -p "data/docker/rabbitmq"

START_AS_DAEMON=""
REBUILD_IMAGE="0"

# idiomatic parameter and option handling in sh
while test $# -gt 0
do
    case "$1" in
        --build) REBUILD_IMAGE="1"
            ;;
        --daemon) START_AS_DAEMON="-d"
            ;;
    esac
    shift
done

IS_ICHIMOKU_PHP=$(docker images | grep ichimoku-php -c)

if [ "$IS_ICHIMOKU_PHP" = "0" ] || [ $REBUILD_IMAGE = "1" ]
then
  cd docker/ichimoku && docker build -t ichimoku-php . && cd ../../
fi

IS_MARIADB_ROCKSBD=$(docker images | grep mariadb-rocksdb -c)

if [ "$IS_MARIADB_ROCKSBD" = "0" ] || [ $REBUILD_IMAGE = "1" ]
then
  cd docker/rocksdb && docker build -t mariadb-rocksdb . && cd ../../
fi


if [ ! -e docker/.env ]
then
  cp docker/.env.dist docker/.env
fi

cd docker && docker-compose -p ichimoku -f docker-compose.yml up ${START_AS_DAEMON}