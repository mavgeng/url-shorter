FROM composer:latest

ARG HOST_USER_ID
ARG HOST_GROUP_ID

RUN apk --no-cache add shadow \
    && groupmod -g ${HOST_GROUP_ID} www-data \
    && usermod -u ${HOST_USER_ID} -g ${HOST_GROUP_ID} www-data

USER www-data

WORKDIR /var/www

ENTRYPOINT ["composer"]