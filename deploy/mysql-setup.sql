-- Configuração do MySQL para SaaS Multi-tenant

-- Criar banco de dados principal
CREATE DATABASE IF NOT EXISTS saas_main CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário da aplicação
CREATE USER IF NOT EXISTS 'saas_user'@'localhost' IDENTIFIED BY 'SaaS@2024!Strong';

-- Conceder privilégios
GRANT ALL PRIVILEGES ON saas_main.* TO 'saas_user'@'localhost';
GRANT CREATE, DROP, ALTER ON *.* TO 'saas_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Configurações de performance
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB
SET GLOBAL max_connections = 200;
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;

-- Mostrar configurações
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
SHOW VARIABLES LIKE 'max_connections';

-- Verificar usuário criado
SELECT User, Host FROM mysql.user WHERE User = 'saas_user';