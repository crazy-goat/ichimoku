FROM alpine
COPY config/supervisord.conf /etc/supervisor.d/ichimoku.ini
COPY entrypoint.sh /usr/local/bin/
RUN apk update \
    && apk add  composer php-ctype php-tokenizer php-pdo php-pdo_mysql php-sockets php-xml php-session php-dom  \
                supervisor\
    && crontab -l | { cat; echo "* * * * * /var/www/ichimoku/bin/console forex:create:candles"; } | crontab - \
    && crontab -l | { cat; echo "* * * * * /var/www/ichimoku/bin/console forex:create:candles --period D"; } | crontab - \
    && crontab -l | { cat; echo "* * * * * /var/www/ichimoku/bin/console forex:create:candles --period H1"; } | crontab - \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*
CMD ["/usr/local/bin/entrypoint.sh"]
WORKDIR /var/www/ichimoku