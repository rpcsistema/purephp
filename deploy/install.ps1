Param(
  [string]$RepoUrl = "https://github.com/<seu-usuario>/<seu-repo>.git",
  [string]$RepoDir = "C:\\xampp\\htdocs\\Saaswl"
)

Write-Host "[1/5] Verificando Git" -ForegroundColor Cyan
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
  Write-Error "Git não encontrado. Instale Git for Windows e tente novamente."; exit 1
}

Write-Host "[2/5] Clonando repositório" -ForegroundColor Cyan
if (-not (Test-Path $RepoDir)) { New-Item -ItemType Directory -Path $RepoDir | Out-Null }
if (-not (Get-ChildItem -Path $RepoDir)) { git clone $RepoUrl $RepoDir }

Write-Host "[3/5] Criando diretórios de sessão" -ForegroundColor Cyan
$sessions = Join-Path $RepoDir "pure-php\storage\sessions"
if (-not (Test-Path $sessions)) { New-Item -ItemType Directory -Path $sessions | Out-Null }

Write-Host "[4/5] Copiando .env exemplo" -ForegroundColor Cyan
$envExample = Join-Path $RepoDir "pure-php\.env.example"
$envFile = Join-Path $RepoDir "pure-php\.env"
if (Test-Path $envExample -and -not (Test-Path $envFile)) { Copy-Item $envExample $envFile }

Write-Host "[5/5] Pronto. Inicie servidor local:" -ForegroundColor Green
Write-Host "    cd $RepoDir" -ForegroundColor Yellow
Write-Host "    php -S 127.0.0.1:8000 -t pure-php/public" -ForegroundColor Yellow