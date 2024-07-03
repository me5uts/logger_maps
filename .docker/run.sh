#!/bin/sh

# start services
if [ "${DB_DRIVER}" = "pgsql" ]; then
  su postgres -c 'pg_ctl -D /data/pgsql start'
elif [ "${DB_DRIVER}" = "mysql" ]; then
  mysqld_safe --datadir=/data/mysql &
fi
nginx
php-fpm7 -F
