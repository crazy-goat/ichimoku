FROM alpine
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY entrypoint.sh /usr/local/bin/
RUN apk update \
    && apk add git composer php-ctype php-tokenizer php-pdo php-pdo_mysql php-sockets php-xml php-session php-dom supervisor\
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*
CMD ["/usr/local/bin/entrypoint.sh"]
WORKDIR /var/www/ichimoku