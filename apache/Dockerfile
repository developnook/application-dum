FROM httpd:2.4.58-alpine3.19
#FROM httpd:2.4.57-alpine
#FROM httpd:2.4.48-alpine3.14

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

RUN apk add -U tzdata

RUN mkdir -p /usr/local/apache2/cert
RUN mkdir -p /usr/local/apache2/conf/conf.d

RUN sed -ri -e 's/^\s*listen\s+80\>/##commented by script## &/i;' \
    /usr/local/apache2/conf/httpd.conf
RUN echo "Include /usr/local/apache2/conf/conf.d/*.conf" \
    >> /usr/local/apache2/conf/httpd.conf

RUN addgroup -g ${P_GID} ${P_GRP}
RUN adduser -u ${P_UID} -G ${P_GRP} -h /home/${P_USR} -D ${P_USR}

RUN mkdir -p /var/www/lib
RUN mkdir -p /var/www/log
RUN mkdir -p /var/www/session
RUN mkdir -p /var/php/conf

RUN chown -R ${P_USR}:${P_GRP} /var/www
RUN chown -R ${P_USR}:${P_GRP} /usr/local/apache2

USER ${P_USR}
