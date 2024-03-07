FROM php:8.2-fpm-alpine
#FROM php:8.0.7-fpm-alpine

ARG AMS_GID
ARG AMS_GROUP
ARG AMS_UID
ARG AMS_USER

ENV P_GID=${AMS_GID}
ENV P_GRP=${AMS_GROUP}
ENV P_UID=${AMS_UID}
ENV P_USR=${AMS_USER}

RUN apk update; \
    apk upgrade;

RUN apk add autoconf automake make gcc g++ libtool pkgconfig libmcrypt-dev re2c git zlib-dev xdg-utils libpng-dev libwebp-dev freetype-dev libjpeg-turbo-dev openssh-client libxslt-dev ca-certificates gmp-dev rabbitmq-c-dev libressl-dev

RUN apk add -U tzdata
RUN apk add -U imagemagick imagemagick-dev

RUN wget --no-check-certificate https://pecl.php.net/get/amqp-1.11.0beta.tgz
RUN printf "\n" | pecl install --offline ./amqp-1.11.0beta.tgz

RUN wget --no-check-certificate https://pecl.php.net/get/memcache-8.0.tgz
RUN printf "\n" | pecl install --offline ./memcache-8.0.tgz

RUN wget --no-check-certificate https://pecl.php.net/get/imagick-3.5.1.tgz
RUN printf "\n" | pecl install --offline ./imagick-3.5.1.tgz

#RUN printf "\n" | pecl install amqp-1.11.0beta
#RUN printf "\n" | pecl install memcache-8.0
#RUN printf "\n" | pecl install imagick-3.5.1

RUN addgroup -g ${P_GID} ${P_GRP}
RUN adduser -u ${P_UID} -G ${P_GRP} -h /home/${P_USR} -D ${P_USR}

RUN mkdir -p /var/www/html/www

RUN mkdir -p /var/www/lib
RUN mkdir -p /var/www/class

RUN mkdir -p /var/www/session
#RUN chmod o+w /var/www/session

RUN mkdir -p /var/www/log
#RUN chmod o+w /var/www/log
RUN touch /var/www/log/error.log
#RUN chmod o+w /var/www/log/error.log

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp 
RUN docker-php-ext-install gd gmp mysqli pdo_mysql

RUN chown -R ${P_USR}:${P_GRP} /var/www

USER ${P_USR}
