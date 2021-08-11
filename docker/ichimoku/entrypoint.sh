#!/usr/bin/env sh
set -e
set -x

DIR='/var/www/ichimoku'

#if [ ! -f "$DIR/composer.json" ]; then
#  rm -rf "$DIR"
#  git clone https://github.com/crazy-goat/ichimoku.git "$DIR"
#fi
cd "$DIR"

echo "Waiting for database connection"
until nc -z -v -w30 mariadb 3306
do
  echo "Waiting for rabbitmq connection"
  # wait for 5 seconds before check again
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

/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf