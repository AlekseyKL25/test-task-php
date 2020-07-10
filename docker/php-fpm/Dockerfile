FROM php:7.3-fpm-alpine

RUN apk --update --no-cache add \
    bash \
    curl \
    git \
    htop \
    iputils \
    nano \
    tzdata \
    unzip \
    wget

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && install-php-extensions \
    xdebug opcache mysqli pdo_mysql apcu intl memcached

COPY ./conf.d/*  /usr/local/etc/php/conf.d/

ARG XDEBUG_IDEKEY
ARG XDEBUG_PORT
ARG XDEBUG_HOST
ARG XDEBUG_INI=/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN echo "xdebug.remote_enable = on" >> ${XDEBUG_INI} && \
    echo "xdebug.remote_autostart = on" >> ${XDEBUG_INI} && \
    echo "xdebug.remote_handler = dbgp" >> ${XDEBUG_INI} && \
    echo "xdebug.idekey = ${XDEBUG_IDEKEY}" >> ${XDEBUG_INI} && \
    echo "xdebug.remote_port = ${XDEBUG_PORT}" >> ${XDEBUG_INI} && \
    echo "xdebug.remote_host = ${XDEBUG_HOST}" >> ${XDEBUG_INI}

WORKDIR /var/www/eonx
EXPOSE 9000
CMD ["php-fpm"]