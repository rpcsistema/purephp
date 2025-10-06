#!/bin/bash

# Script de Deploy - SaaS White Label
# Execute este script no servidor de produção

set -e

echo "🚀 Iniciando deploy da aplicação SaaS..."

# Variáveis
APP_DIR="/var/www/saas"
REPO_URL="."  # Usar arquivos locais por enquanto
BRANCH="main"
PHP_VERSION="8.2"

# Função para log
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Script pode ser executado como root em ambiente de produção

# Backup da aplicação atual (se existir)
if [ -d "$APP_DIR" ]; then
    log "📦 Fazendo backup da aplicação atual..."
    sudo cp -r $APP_DIR $APP_DIR.backup.$(date +%Y%m%d_%H%M%S)
fi

# Criar diretório da aplicação
log "📁 Preparando diretório da aplicação..."
sudo mkdir -p $APP_DIR
sudo chown -R $USER:www-data $APP_DIR

# Aplicação já foi extraída em /var/www/saas
log "📁 Aplicação já extraída em /var/www/saas"
cd $APP_DIR

# Instalar dependências do Composer
log "🎼 Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

# Instalar dependências do Node.js
log "📦 Instalando dependências Node.js..."
npm ci --production

# Build dos assets
log "🏗️ Compilando assets..."
npm run build

# Configurar permissões
log "🔐 Configurando permissões..."
sudo chown -R $USER:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

# Configurar arquivo .env
if [ ! -f "$APP_DIR/.env" ]; then
    log "⚙️ Configurando arquivo .env..."
    cp $APP_DIR/.env.example $APP_DIR/.env
    
    # Configurações básicas para acesso por IP
    sed -i "s|APP_URL=.*|APP_URL=http://191.36.228.202|" $APP_DIR/.env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=saas_main|" $APP_DIR/.env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=saas_user|" $APP_DIR/.env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=SaaS@2024!Strong|" $APP_DIR/.env
    sed -i "s|CENTRAL_DOMAINS=.*|CENTRAL_DOMAINS=191.36.228.202|" $APP_DIR/.env
    
    # Gerar chave da aplicação
    php artisan key:generate --force
    
    echo ""
    echo "🔧 Arquivo .env configurado para acesso por IP"
    echo "   URL: http://191.36.228.202"
    echo ""
fi

# Cache e otimizações
log "⚡ Otimizando aplicação..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Executar migrações
log "🗄️ Executando migrações..."
php artisan migrate --force

# Executar seeders (apenas na primeira instalação)
if [ ! -f "$APP_DIR/.deployed" ]; then
    log "🌱 Executando seeders..."
    php artisan db:seed --force
    touch $APP_DIR/.deployed
fi

# Reiniciar serviços
log "🔄 Reiniciando serviços..."
sudo systemctl reload nginx
sudo systemctl restart php$PHP_VERSION-fpm

# Configurar cron jobs
log "⏰ Configurando cron jobs..."
(crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Configurar supervisor para queues (opcional)
if command -v supervisorctl &> /dev/null; then
    log "👷 Configurando supervisor para queues..."
    sudo tee /etc/supervisor/conf.d/saas-worker.conf > /dev/null <<EOF
[program:saas-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=saas
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF
    
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start saas-worker:*
fi

log "✅ Deploy concluído com sucesso!"
echo ""
echo "🌐 Acesso à aplicação:"
echo "   URL: http://191.36.228.202"
echo ""
echo "📊 Status dos serviços:"
sudo systemctl status nginx --no-pager -l
sudo systemctl status php$PHP_VERSION-fpm --no-pager -l