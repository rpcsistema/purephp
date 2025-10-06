# 🚀 Guia de Deploy - SaaS White Label

Este guia contém todos os scripts e instruções necessárias para fazer o deploy da aplicação SaaS White Label em um servidor de produção.

## 📋 Informações do Servidor

- **IP**: 191.36.228.202
- **Usuário**: ricardo
- **Sistema**: Ubuntu/Debian (assumido)

## 🗂️ Arquivos de Deploy

### Scripts Principais

1. **`server-setup.sh`** - Configuração inicial do servidor
2. **`deploy.sh`** - Deploy da aplicação
3. **`ssl-setup.sh`** - Configuração de SSL/HTTPS

### Arquivos de Configuração

4. **`nginx-config.conf`** - Configuração do Nginx
5. **`mysql-setup.sql`** - Configuração do MySQL
6. **`production.env`** - Template do arquivo .env

## 🚀 Processo de Deploy

### Via Repositório GitHub (recomendado)

1. Clone o repositório no servidor:
   ```bash
   sudo apt update && sudo apt install -y git
   git clone https://github.com/<seu-usuario>/<seu-repo>.git /var/www/saaswl
   cd /var/www/saaswl
   ```
2. Configure `.env` do módulo PHP puro:
   ```bash
   cp pure-php/.env.example pure-php/.env
   nano pure-php/.env
   ```
3. Instale e configure Apache com `deploy/install.sh`:
   ```bash
   sudo bash deploy/install.sh
   ```
4. Ajuste DNS e SSL conforme necessário.

### Passo 1: Preparar o Servidor

```bash
# Conectar ao servidor
ssh ricardo@191.36.228.202

# Fazer upload dos scripts
scp deploy/* ricardo@191.36.228.202:~/

# Executar configuração inicial
chmod +x server-setup.sh
sudo ./server-setup.sh
```

### Passo 2: Configurar MySQL

```bash
# Executar configuração segura do MySQL
sudo mysql_secure_installation

# Aplicar configurações do banco
sudo mysql < mysql-setup.sql
```

### Passo 3: Configurar Nginx

```bash
# Copiar configuração do Nginx
sudo cp nginx-config.conf /etc/nginx/sites-available/saas

# Ativar o site
sudo ln -s /etc/nginx/sites-available/saas /etc/nginx/sites-enabled/

# Remover site padrão
sudo rm -f /etc/nginx/sites-enabled/default

# Testar configuração
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx
```

### Passo 4: Deploy da Aplicação

```bash
# Executar deploy
chmod +x deploy.sh
./deploy.sh
```

Alternativamente, use `deploy/install.ps1` no Windows/XAMPP para preparar ambiente local.

### Passo 5: Configurar SSL

```bash
# Configurar SSL
chmod +x ssl-setup.sh
sudo ./ssl-setup.sh
```

## ⚙️ Configurações Importantes

### Arquivo .env

Copie o arquivo `production.env` para `/var/www/saas/.env` e configure:

```bash
cd /var/www/saas
cp ~/production.env .env
nano .env
```

**Variáveis importantes para configurar:**

- `APP_URL` - URL principal da aplicação
- `DB_*` - Credenciais do banco de dados
- `MAIL_*` - Configurações de email
- `CENTRAL_DOMAINS` - Domínios principais
- `TENANT_DOMAIN_SUFFIX` - Sufixo para subdomínios

### Domínios e DNS

Configure os seguintes registros DNS:

```
A     seu-dominio.com        191.36.228.202
A     www.seu-dominio.com    191.36.228.202
A     *.seu-dominio.com      191.36.228.202  (wildcard para subdomínios)
```

## 🔧 Comandos Úteis

### Logs da Aplicação

```bash
# Logs do Laravel
tail -f /var/www/saas/storage/logs/laravel.log

# Logs do Nginx
tail -f /var/log/nginx/saas_error.log
tail -f /var/log/nginx/saas_access.log

# Logs do PHP-FPM
tail -f /var/log/php8.2-fpm.log
```

### Manutenção

```bash
# Limpar cache
cd /var/www/saas
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recompilar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Executar migrações
php artisan migrate

# Verificar status das queues
php artisan queue:work --once
```

### Backup

```bash
# Backup do banco de dados
mysqldump -u saas_user -p saas_main > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup dos arquivos
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/saas
```

## 🛡️ Segurança

### Firewall

```bash
# Verificar status do firewall
sudo ufw status

# Permitir apenas portas necessárias
sudo ufw allow 22    # SSH
sudo ufw allow 80    # HTTP
sudo ufw allow 443   # HTTPS
```

### Atualizações

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Atualizar Composer
composer self-update

# Atualizar Node.js packages
npm audit fix
```

## 🚨 Troubleshooting

### Problemas Comuns

1. **Erro 500**: Verificar logs do Laravel e permissões
2. **Erro de conexão com banco**: Verificar credenciais no .env
3. **Assets não carregam**: Executar `npm run build`
4. **SSL não funciona**: Verificar configuração do Nginx e certificados

### Comandos de Diagnóstico

```bash
# Verificar serviços
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis

# Verificar conectividade
curl -I http://localhost
curl -I https://seu-dominio.com

# Verificar certificado SSL
openssl s_client -connect seu-dominio.com:443
```

## 📞 Suporte

Para problemas ou dúvidas:

1. Verificar logs da aplicação
2. Consultar documentação do Laravel
3. Verificar configurações do servidor

## 🎉 Conclusão

Após seguir todos os passos, sua aplicação SaaS White Label estará rodando em produção com:

- ✅ Servidor configurado (LEMP stack)
- ✅ SSL/HTTPS ativo
- ✅ Multi-tenancy funcionando
- ✅ Sistema de white label ativo
- ✅ Backup e monitoramento básico

**URL de acesso**: https://seu-dominio.com

## 📦 Releases

Para distribuir e instalar rapidamente:

- Use os artefatos publicados em Releases do GitHub (`saaswl-deploy-<tag>`, `saaswl-pure-php-<tag>`, `saaswl-docs-<tag>`)
- Scripts de publicação:
  - Linux: `deploy/publish-release.sh vX.Y.Z`
  - Windows: `deploy/publish-release.ps1 -Version vX.Y.Z`

Veja `docs/RELEASE.md` para o fluxo completo de versionamento e consumo.