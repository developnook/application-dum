FROM memcached:1.6.21-alpine3.18

USER root

RUN apk update; \
    apk upgrade;

RUN apk add -U tzdata

USER daemon
