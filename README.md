# Projeto PHP Puro

Estrutura mínima de autenticação usando PHP, MySQL, Bootstrap e JavaScript.

## Pré-requisitos
- PHP 8+
- MySQL (ou MariaDB)
- Apache (ou usar servidor embutido do PHP)

## Configuração de Banco de Dados
1. Crie o banco de dados (ou ajuste o nome em `pure-php/config/config.php`):

```sql
CREATE DATABASE IF NOT EXISTS saaswl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Edite `pure-php/config/config.php` se necessário (host, nome, usuário e senha).

3. As tabelas serão criadas acessando `/seed_user.php`.

## Rodando localmente (servidor embutido)

```bash
php -S 127.0.0.1:8001 -t pure-php/public
```

Abra:
- `http://127.0.0.1:8001/seed_user.php` para criar o usuário admin
- `http://127.0.0.1:8001/login.php` para entrar

Credenciais padrão: `admin@example.com` / `admin123`