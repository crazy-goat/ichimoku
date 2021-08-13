FROM alpine
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY entrypoint.sh /usr/local/bin/
RUN apk update \
    && apk add  composer php-ctype php-tokenizer php-pdo php-pdo_mysql php-sockets php-xml php-session php-dom  \
                supervisor\
    && crontab -l | { cat; echo "0 0,4,8,12,16,20 * * * /var/www/ichimoku/bin/console"; } | crontab - \
    && crontab -l | { cat; echo "0 0 * * * /var/www/ichimoku/bin/console"; } | crontab - \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*
CMD ["/usr/local/bin/entrypoint.sh"]
WORKDIR /var/www/ichimoku