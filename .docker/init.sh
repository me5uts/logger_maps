#!/bin/sh

mkdir -p /run/nginx
chown nginx:nginx /run/nginx

# Fix permission issues on mounted volume in macOS
sed -i "s/^nobody:.*$/nobody:x:1000:50::nobody:\/:\/sbin\/nologin/" /etc/passwd
sed -i "s/^nobody:.*$/nobody:x:50:/" /etc/group

# Prepare ulogger filesystem
grep '^[$<?]' /var/www/html/config.default.php > /var/www/html/config.php
mkdir -p /data/uploads
rm -rf /var/www/html/uploads
ln -s /data/uploads /var/www/html/uploads
chown nobody:nobody /var/www/html/uploads
chmod 775 /var/www/html/uploads

# Hash admin user password for insert in db later
ULOGGER_ADMIN_PASS_HASHED=$(php -f /var/www/html/.docker/hash_pwd.php "${ULOGGER_ADMIN_PASS}")

# Prepare ulogger database
if [ "${DB_DRIVER}" = "sqlite" ]; then
  sed -i "s/^\$dbuser = .*$//" /var/www/html/config.php
  sed -i "s/^\$dbpass = .*$//" /var/www/html/config.php
else
  sed -i "s/^\$dbuser = .*$/\$dbuser = \"${DB_USER_NAME}\";/" /var/www/html/config.php
  sed -i "s/^\$dbpass = .*$/\$dbpass = \"${DB_USER_PASS}\";/" /var/www/html/config.php
fi

if [ "${DB_DRIVER}" = "pgsql" ]; then
  export PGDATA=/data/pgsql
  mkdir -p ${PGDATA} /run/postgresql /etc/postgres
  chown postgres:postgres ${PGDATA} /run/postgresql /etc/postgres
  su postgres -c "initdb --auth-host=md5 --auth-local=trust --locale=${ULOGGER_LANG} --encoding=utf8"
  sed -ri "s/^#(listen_addresses\s*=\s*)\S+/\1'*'/" ${PGDATA}/postgresql.conf
  echo "host all all 0.0.0.0/0 md5" >> ${PGDATA}/pg_hba.conf
  su postgres -c "pg_ctl -w start"
  su postgres -c "psql -c \"ALTER USER postgres WITH PASSWORD '${DB_ROOT_PASS}'\""
  su postgres -c "psql -c \"CREATE USER ${DB_USER_NAME} WITH PASSWORD '${DB_USER_PASS}'\""
  su postgres -c "createdb -E UTF8 -l en_US.utf-8 -O ${DB_USER_NAME} ulogger"
  su postgres -c "psql -U ${DB_USER_NAME} < /var/www/html/scripts/ulogger.pgsql"
  su postgres -c "psql -c \"GRANT ALL PRIVILEGES ON DATABASE ulogger TO ${DB_USER_NAME}\""
  su postgres -c "psql -d ulogger -c \"GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO ${DB_USER_NAME}\""
  su postgres -c "psql -d ulogger -c \"GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ${DB_USER_NAME}\""
  su postgres -c "psql -d ulogger -c 'INSERT INTO users (login, password, admin) VALUES ('\''${ULOGGER_ADMIN_USER}'\'', '\''${ULOGGER_ADMIN_PASS_HASHED}'\'', TRUE)'"
  su postgres -c "pg_ctl -w stop"
  sed -i "s/^\$dbdsn = .*$/\$dbdsn = \"pgsql:host=localhost;port=5432;dbname=ulogger\";/" /var/www/html/config.php
elif [ "${DB_DRIVER}" = "sqlite" ]; then
  mkdir -p /data/sqlite
  sqlite3 -init /var/www/html/scripts/ulogger.sqlite /data/sqlite/ulogger.db .exit
  sqlite3 -line /data/sqlite/ulogger.db "INSERT INTO users (login, password, admin) VALUES ('${ULOGGER_ADMIN_USER}', '${ULOGGER_ADMIN_PASS_HASHED}', 1)"
  chown -R nobody:nobody /data/sqlite
  sed -i "s/^\$dbdsn = .*$/\$dbdsn = \"sqlite:\/data\/sqlite\/ulogger.db\";/" /var/www/html/config.php
else
  sed -i "s/.*skip-networking.*/#skip-networking/" /etc/my.cnf.d/mariadb-server.cnf
  mkdir -p /run/mysqld /data/mysql
  chown mysql:mysql /run/mysqld /data/mysql
  mysql_install_db --user=mysql --datadir=/data/mysql
  mysqld_safe --datadir=/data/mysql &
  mysqladmin --silent --wait=30 ping
  mysqladmin -u root password "${DB_ROOT_PASS}"
  mysql -u root -p"${DB_ROOT_PASS}" < /var/www/html/scripts/ulogger.mysql
  mysql -u root -p"${DB_ROOT_PASS}" -e "CREATE USER '${DB_USER_NAME}'@'localhost' IDENTIFIED BY '${DB_USER_PASS}'"
  mysql -u root -p"${DB_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON ulogger.* TO '${DB_USER_NAME}'@'localhost'"
  mysql -u root -p"${DB_ROOT_PASS}" -e "CREATE USER '${DB_USER_NAME}'@'%' IDENTIFIED BY '${DB_USER_PASS}'"
  mysql -u root -p"${DB_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON ulogger.* TO '${DB_USER_NAME}'@'%'"
  mysql -u root -p"${DB_ROOT_PASS}" -e "INSERT INTO users (login, password, admin) VALUES ('${ULOGGER_ADMIN_USER}', '${ULOGGER_ADMIN_PASS}', TRUE)" ulogger
  mysqladmin -u root -p"${DB_ROOT_PASS}" shutdown
  sed -i "s/^\$dbdsn = .*$/\$dbdsn = \"mysql:host=localhost;port=3306;dbname=ulogger;charset=utf8\";/" /var/www/html/config.php
fi
