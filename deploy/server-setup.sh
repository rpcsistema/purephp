#!/bin/bash

# Script de Configuração do Servidor de Produção
# SaaS White Label - Laravel Multi-tenant

echo "🚀 Iniciando configuração do servidor de produção..."

# Atualizar sistema
echo "📦 Atualizando sistema..."
sudo apt update && sudo apt upgrade -y

# Instalar dependências básicas
echo "🔧 Instalando dependências básicas..."
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Instalar Nginx
echo "🌐 Instalando Nginx..."
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx

# Instalar PHP 8.2
echo "🐘 Instalando PHP 8.2..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-pgsql php8.2-sqlite3 php8.2-redis php8.2-xml php8.2-curl php8.2-gd php8.2-mbstring php8.2-zip php8.2-bcmath php8.2-intl php8.2-readline php8.2-msgpack php8.2-igbinary php8.2-ldap php8.2-dev

# Configurar PHP
echo "⚙️ Configurando PHP..."
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 100M/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.2/fpm/php.ini

# Instalar MySQL
echo "🗄️ Instalando MySQL..."
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql

# Instalar Composer
echo "🎼 Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Instalar Node.js e npm
echo "📦 Instalando Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar Redis
echo "🔴 Instalando Redis..."
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Configurar Firewall
echo "🔥 Configurando Firewall..."
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw allow 80
sudo ufw allow 443
sudo ufw --force enable

# Criar usuário para aplicação
echo "👤 Criando usuário da aplicação..."
sudo adduser --disabled-password --gecos "" saas
sudo usermod -aG www-data saas

# Criar diretórios
echo "📁 Criando estrutura de diretórios..."
sudo mkdir -p /var/www/saas
sudo chown -R saas:www-data /var/www/saas
sudo chmod -R 755 /var/www/saas

# Configurar SSL com Certbot
echo "🔒 Instalando Certbot para SSL..."
sudo apt install -y certbot python3-certbot-nginx

echo "✅ Configuração básica do servidor concluída!"
echo "📋 Próximos passos:"
echo "   1. Configurar MySQL (executar mysql_secure_installation)"
echo "   2. Fazer deploy da aplicação"
echo "   3. Configurar domínios e SSL"
echo "   4. Configurar backup automático"