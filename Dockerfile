FROM php:8.2-fpm-alpine
RUN apk add --no-cache \
    nginx bash git libreoffice
VOLUME /srv/web
WORKDIR /srv/web
EXPOSE 80
RUN rm /etc/nginx/nginx.conf
RUN ln -s /srv/web/nginx.conf /etc/nginx/nginx.conf
RUN ln -s /srv/web/php.ini /usr/local/etc/php/conf.d/upload.ini
# run php-fpm and nginx
CMD php-fpm -D && nginx -g 'daemon off;'
