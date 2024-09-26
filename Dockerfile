FROM alpine:3.20

LABEL maintainer="Bartek Fabiszewski (https://github.com/bfabiszewski)"

ARG DB_ROOT_PASS=secret1
ARG DB_USER_PASS=secret2
# supported drivers: mysql, pgsql, sqlite
ARG DB_DRIVER=mysql

ENV ULOGGER_ADMIN_USER=admin
ENV ULOGGER_DB_DRIVER=${DB_DRIVER}
ENV ULOGGER_ENABLE_SETUP=0

ENV LANG=en_US.utf-8

RUN apk add --no-cache \
    nginx \
    php83-ctype php83-fpm php83-json php83-pdo php83-session php83-simplexml php83-xmlwriter
RUN if [ "${DB_DRIVER}" = "mysql" ]; then apk add --no-cache mariadb mariadb-client php83-pdo_mysql; fi
RUN if [ "${DB_DRIVER}" = "pgsql" ]; then apk add --no-cache postgresql postgresql-client php83-pdo_pgsql; fi
RUN if [ "${DB_DRIVER}" = "sqlite" ]; then apk add --no-cache sqlite php83-pdo_sqlite; fi

RUN ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log && \
    ln -sf /dev/stdout /var/log/php83/error.log && \
    ln -sf /dev/stderr /var/log/php83/error.log

RUN rm -rf /var/www/html
RUN mkdir -p /var/www/html


COPY docker/run.sh /run.sh
RUN chmod +x /run.sh
COPY docker/init.sh /init.sh
RUN chmod +x /init.sh
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
RUN chown nginx.nginx /etc/nginx/http.d/default.conf

COPY . /var/www/html

RUN /init.sh "${DB_ROOT_PASS}" "${DB_USER_PASS}"

EXPOSE 80

VOLUME ["/data"]

CMD ["/run.sh"]
