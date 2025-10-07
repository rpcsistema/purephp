<?php
// Bootstrap básico

// Loader simples de .env para desenvolvimento
function load_env(string $file): void
{
    if (!is_file($file)) {
        return;
    }
    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));
        // Remover aspas externas simples ou duplas
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '') {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Carregar .env local (opcional) antes do config
$envPath = __DIR__ . '/../.env';
if (is_file($envPath)) {
    load_env($envPath);
}

$config = require __DIR__ . '/../config/config.php';

// Sessão
// Usar diretório de sessões dentro do projeto para evitar problemas de permissões
$storageDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'storage';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0777, true);
}
$sessionsDir = $storageDir . DIRECTORY_SEPARATOR . 'sessions';
if (!is_dir($sessionsDir)) {
    @mkdir($sessionsDir, 0777, true);
}
if (is_dir($sessionsDir)) {
    session_save_path($sessionsDir);
}

$sessionName = 'saaswl_session';
session_name($sessionName);
// Habilitar modo estrito para evitar IDs inválidos
ini_set('session.use_strict_mode', '1');
// Limpar cookie de sessão inválido caso exista
if (!empty($_COOKIE[$sessionName]) && !preg_match('/^[A-Za-z0-9\-,]+$/', (string)$_COOKIE[$sessionName])) {
    setcookie($sessionName, '', time() - 3600, '/');
    unset($_COOKIE[$sessionName]);
}
session_start();
// Regenerar ID para reforçar segurança
session_regenerate_id(true);

// Carregar classes
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

// Inicializar schema automaticamente (apenas uma vez)
try {
    $flagFile = $storageDir . DIRECTORY_SEPARATOR . 'schema_initialized.flag';
    if (!file_exists($flagFile)) {
        $cfg = require __DIR__ . '/../config/config.php';
        // Garantir que o database exista
        $dsnNoDb = sprintf('mysql:host=%s;charset=%s', $cfg['db']['host'], $cfg['db']['charset']);
        $pdoNoDb = new \PDO($dsnNoDb, $cfg['db']['user'], $cfg['db']['pass'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['db']['name']}` CHARACTER SET {$cfg['db']['charset']} COLLATE utf8mb4_unicode_ci");

        // Aplicar schema (usa CREATE TABLE IF NOT EXISTS)
        $pdo = Database::pdo();
        $schemaSql = @file_get_contents(__DIR__ . '/../sql/schema.sql');
        if ($schemaSql) {
            $pdo->exec($schemaSql);
        }

        @file_put_contents($flagFile, date('c'));
    }
} catch (\Throwable $e) {
    // Silencioso: páginas específicas tratam erros de forma isolada
}

// Migrações incrementais simples: garantir colunas novas em tabelas existentes
try {
    $cfg = require __DIR__ . '/../config/config.php';
    $pdo = Database::pdo();

    // Garantir coluna classification em account_types
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$cfg['db']['name'], 'account_types', 'classification']);
    $hasClassification = (int) $stmt->fetchColumn() > 0;
    if (!$hasClassification) {
        $pdo->exec("ALTER TABLE `account_types` ADD COLUMN `classification` ENUM('receita','despesa') NOT NULL DEFAULT 'despesa' AFTER `description`");
    }

    // Garantir coluna account_type_id em payables
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$cfg['db']['name'], 'payables', 'account_type_id']);
    $hasPayablesAccountTypeId = (int) $stmt->fetchColumn() > 0;
    if (!$hasPayablesAccountTypeId) {
        $pdo->exec("ALTER TABLE `payables` ADD COLUMN `account_type_id` INT UNSIGNED NULL AFTER `id`");
    }

    // Garantir coluna account_type_id em receivables
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$cfg['db']['name'], 'receivables', 'account_type_id']);
    $hasReceivablesAccountTypeId = (int) $stmt->fetchColumn() > 0;
    if (!$hasReceivablesAccountTypeId) {
        $pdo->exec("ALTER TABLE `receivables` ADD COLUMN `account_type_id` INT UNSIGNED NULL AFTER `id`");
    }

    // Garantir tabelas accounts e account_ledgers
    $pdo->exec("CREATE TABLE IF NOT EXISTS `accounts` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(120) NOT NULL,
      `bank_name` VARCHAR(120) NULL,
      `number` VARCHAR(80) NULL,
      `initial_balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `accounts_name_unique` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `account_ledgers` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `account_id` INT UNSIGNED NOT NULL,
      `movement_type` ENUM('debit','credit') NOT NULL,
      `amount` DECIMAL(14,2) NOT NULL,
      `description` VARCHAR(180) NULL,
      `related_table` VARCHAR(50) NULL,
      `related_id` INT UNSIGNED NULL,
      `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `account_ledgers_account_id_index` (`account_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Garantir colunas de vínculo de conta em payables e receivables
    $stmt->execute([$cfg['db']['name'], 'payables', 'account_id']);
    $hasPayablesAccountId = (int) $stmt->fetchColumn() > 0;
    if (!$hasPayablesAccountId) {
        $pdo->exec("ALTER TABLE `payables` ADD COLUMN `account_id` INT UNSIGNED NULL AFTER `account_type_id`");
    }
    $stmt->execute([$cfg['db']['name'], 'receivables', 'account_id']);
    $hasReceivablesAccountId = (int) $stmt->fetchColumn() > 0;
    if (!$hasReceivablesAccountId) {
        $pdo->exec("ALTER TABLE `receivables` ADD COLUMN `account_id` INT UNSIGNED NULL AFTER `account_type_id`");
    }
} catch (\Throwable $e) {
    // manter silencioso para não quebrar páginas; erros podem ser tratados nas telas
}

function app_name(): string
{
    static $cfg;
    if (!$cfg) {
        $cfg = require __DIR__ . '/../config/config.php';
    }
    return $cfg['app']['name'];
}

function app_tagline(): string
{
    static $cfg;
    if (!$cfg) {
        $cfg = require __DIR__ . '/../config/config.php';
    }
    // Fallback amigável caso não exista no config
    return $cfg['app']['tagline'] ?? '';
}

function page_title(string $section): string
{
    // Monta título padronizado incluindo o nome do app e a tagline quando disponível
    $name = app_name();
    $tagline = app_tagline();
    if ($tagline) {
        return sprintf('%s - %s · %s', htmlspecialchars($name), htmlspecialchars($section), htmlspecialchars($tagline));
    }
    return sprintf('%s - %s', htmlspecialchars($name), htmlspecialchars($section));
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}