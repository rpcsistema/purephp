#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/var/www/saaswl"
DOC_ROOT="$REPO_DIR/pure-php/public"
SITE_CONF="/etc/apache2/sites-available/saaswl.conf"

echo "[1/6] Atualizando pacotes"
sudo apt update -y
sudo apt install -y git apache2 mysql-client php php-mysql php-xml php-mbstring

echo "[2/6] Criando diretórios"
sudo mkdir -p "$REPO_DIR"
sudo chown -R "$USER":"$USER" "$REPO_DIR"

echo "[3/6] Clonando repositório (se vazio)"
if [ -z "$(ls -A "$REPO_DIR" 2>/dev/null)" ]; then
  git clone https://github.com/<seu-usuario>/<seu-repo>.git "$REPO_DIR"
fi

echo "[4/6] Configurando Apache"
sudo tee "$SITE_CONF" > /dev/null <<EOF
<VirtualHost *:80>
    ServerName saaswl.local
    DocumentRoot $DOC_ROOT

    <Directory $DOC_ROOT>
        AllowOverride All
        Require all granted
        Options FollowSymLinks
    </Directory>

    ErrorLog \\${APACHE_LOG_DIR}/saaswl_error.log
    CustomLog \\${APACHE_LOG_DIR}/saaswl_access.log combined
</VirtualHost>
EOF

sudo a2dissite 000-default.conf || true
sudo a2ensite saaswl.conf
sudo systemctl reload apache2

echo "[5/6] Permissões de storage/sessions"
sudo mkdir -p "$REPO_DIR/pure-php/storage/sessions"
sudo chown -R www-data:www-data "$REPO_DIR/pure-php/storage/sessions"
sudo chmod -R 770 "$REPO_DIR/pure-php/storage/sessions"

echo "[6/6] Finalizado. Ajuste pure-php/.env com credenciais do banco."
echo "Abra: http://saaswl.local/"