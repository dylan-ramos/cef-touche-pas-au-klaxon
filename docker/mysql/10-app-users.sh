#!/bin/bash
# Crée les comptes MySQL de l'application au premier démarrage du conteneur.
#   - compte applicatif : droits de lecture/écriture sur les données uniquement ;
#   - compte de test    : droits complets sur la base de test isolée.
set -euo pipefail

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';

CREATE DATABASE IF NOT EXISTS \`${DB_TEST_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_TEST_USER}'@'%' IDENTIFIED BY '${DB_TEST_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_TEST_NAME}\`.* TO '${DB_TEST_USER}'@'%';

FLUSH PRIVILEGES;
SQL
