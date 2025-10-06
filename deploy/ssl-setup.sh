#!/bin/bash

# Script de Configuração SSL - SaaS White Label
# Configura SSL/HTTPS com Let's Encrypt

set -e

# Variáveis (CONFIGURE ANTES DE EXECUTAR)
DOMAIN="seu-dominio.com"
EMAIL="seu-email@gmail.com"
WILDCARD_DOMAIN="*.seu-dominio.com"

echo "🔒 Configurando SSL para SaaS Multi-tenant..."

# Verificar se o Certbot está instalado
if ! command -v certbot &> /dev/null; then
    echo "❌ Certbot não encontrado. Execute primeiro o server-setup.sh"
    exit 1
fi

# Verificar se o Nginx está rodando
if ! systemctl is-active --quiet nginx; then
    echo "❌ Nginx não está rodando. Iniciando..."
    sudo systemctl start nginx
fi

# Função para configurar SSL para domínio principal
setup_main_domain() {
    echo "🌐 Configurando SSL para domínio principal: $DOMAIN"
    
    # Obter certificado SSL
    sudo certbot --nginx \
        -d $DOMAIN \
        -d www.$DOMAIN \
        --email $EMAIL \
        --agree-tos \
        --no-eff-email \
        --redirect \
        --non-interactive
    
    if [ $? -eq 0 ]; then
        echo "✅ SSL configurado com sucesso para $DOMAIN"
    else
        echo "❌ Erro ao configurar SSL para $DOMAIN"
        exit 1
    fi
}

# Função para configurar SSL wildcard (requer DNS challenge)
setup_wildcard_ssl() {
    echo "🌟 Configurando SSL Wildcard para subdomínios..."
    echo "⚠️  ATENÇÃO: SSL Wildcard requer validação DNS manual"
    echo "    Você precisará adicionar um registro TXT no seu DNS"
    
    read -p "Deseja continuar com SSL Wildcard? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo certbot certonly \
            --manual \
            --preferred-challenges dns \
            -d $DOMAIN \
            -d $WILDCARD_DOMAIN \
            --email $EMAIL \
            --agree-tos \
            --no-eff-email
        
        if [ $? -eq 0 ]; then
            echo "✅ Certificado wildcard obtido com sucesso"
            echo "📝 Agora você precisa atualizar a configuração do Nginx"
        else
            echo "❌ Erro ao obter certificado wildcard"
        fi
    else
        echo "⏭️  Pulando configuração de SSL Wildcard"
    fi
}

# Função para configurar renovação automática
setup_auto_renewal() {
    echo "🔄 Configurando renovação automática..."
    
    # Adicionar cron job para renovação
    (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet --nginx") | crontab -
    
    # Testar renovação
    sudo certbot renew --dry-run
    
    if [ $? -eq 0 ]; then
        echo "✅ Renovação automática configurada com sucesso"
    else
        echo "⚠️  Aviso: Pode haver problemas com a renovação automática"
    fi
}

# Função para configurar headers de segurança
setup_security_headers() {
    echo "🛡️  Configurando headers de segurança..."
    
    # Criar configuração de segurança
    sudo tee /etc/nginx/snippets/ssl-security.conf > /dev/null <<EOF
# SSL Security Configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
ssl_session_tickets off;

# HSTS (HTTP Strict Transport Security)
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;

# Security Headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

# OCSP Stapling
ssl_stapling on;
ssl_stapling_verify on;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 5s;
EOF

    echo "✅ Headers de segurança configurados"
}

# Função para testar SSL
test_ssl() {
    echo "🧪 Testando configuração SSL..."
    
    # Testar conectividade HTTPS
    if curl -s -I https://$DOMAIN | grep -q "HTTP/2 200"; then
        echo "✅ HTTPS funcionando corretamente"
    else
        echo "⚠️  Possível problema com HTTPS"
    fi
    
    # Verificar certificado
    echo "📋 Informações do certificado:"
    echo | openssl s_client -servername $DOMAIN -connect $DOMAIN:443 2>/dev/null | openssl x509 -noout -dates
}

# Menu principal
echo "🔒 Configuração SSL - SaaS White Label"
echo "======================================"
echo "Domínio configurado: $DOMAIN"
echo "Email: $EMAIL"
echo ""
echo "Opções disponíveis:"
echo "1) Configurar SSL básico (domínio principal)"
echo "2) Configurar SSL Wildcard (subdomínios)"
echo "3) Configurar renovação automática"
echo "4) Configurar headers de segurança"
echo "5) Testar SSL"
echo "6) Configuração completa (todas as opções)"
echo ""

read -p "Escolha uma opção (1-6): " choice

case $choice in
    1)
        setup_main_domain
        ;;
    2)
        setup_wildcard_ssl
        ;;
    3)
        setup_auto_renewal
        ;;
    4)
        setup_security_headers
        ;;
    5)
        test_ssl
        ;;
    6)
        setup_main_domain
        setup_wildcard_ssl
        setup_auto_renewal
        setup_security_headers
        test_ssl
        ;;
    *)
        echo "❌ Opção inválida"
        exit 1
        ;;
esac

echo ""
echo "🎉 Configuração SSL concluída!"
echo "📝 Lembre-se de:"
echo "   - Atualizar os DNS para apontar para este servidor"
echo "   - Configurar o arquivo .env com os domínios corretos"
echo "   - Testar a aplicação em https://$DOMAIN"