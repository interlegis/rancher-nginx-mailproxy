FROM php:7-fpm-alpine

ENV INSTALL_PATH=/var/www/html \
    RELAY_HOST='' 

RUN apk add --update \
        openldap-dev \
        imap-dev \
        libressl-dev && \
    docker-php-ext-configure imap --with-imap --with-imap-ssl && \
    docker-php-ext-install json imap ldap && \
    rm -rf /var/cache/apk/*

COPY auth-mail.php /var/www/html

RUN touch /var/log/auth-mail.log && \
    chown www-data:www-data /var/log/auth-mail.log && \
    sed -i 's/;php_admin_flag[log_errors] = on/php_admin_flag[log_errors] = on/g' /usr/local/etc/php-fpm.d/www.conf

VOLUME /opt/nginx/sites
COPY mailauth-nginx.conf /opt/nginx/sites/mailauth.conf

WORKDIR /var/www/html

