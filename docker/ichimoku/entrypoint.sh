#!/usr/bin/env sh
set -e
set -x

DIR='/var/www/ichimoku'

if [ ! -f "$DIR/composer.json" ]
then
  echo "No source data found"
  exit 1;
fi

cd "$DIR"
echo "Waiting for database connection"
until nc -z -v -w30 mariadb 3306
do
  echo "Waiting for rabbitmq connection"
  sleep 1
done


until nc -z -v -w30 rabbitmq 5672
do
  echo "Waiting for rabbitmq connection"
  sleep 1
done

composer install
./bin/console doctrine:database:create --if-not-exists
./bin/console doctrine:migrations:migrate --no-interaction
./bin/console setup:rabbitmq

/usr/bin/supervisord -c /etc/supervisord.conf