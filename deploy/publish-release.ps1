Param(
  [string]$Version
)
if (-not $Version) { Write-Error "Uso: .\\publish-release.ps1 -Version v0.1.0"; exit 1 }

$RepoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$ArtifactDir = Join-Path $RepoRoot "dist"
if (-not (Test-Path $ArtifactDir)) { New-Item -ItemType Directory -Path $ArtifactDir | Out-Null }

Write-Host "Empacotando artefatos para $Version" -ForegroundColor Cyan

# Zip do módulo PHP puro
$zipPure = Join-Path $ArtifactDir "saaswl-pure-php-$Version.zip"
Compress-Archive -Path \
  (Join-Path $RepoRoot "pure-php\public"), \
  (Join-Path $RepoRoot "pure-php\config"), \
  (Join-Path $RepoRoot "pure-php\sql"), \
  (Join-Path $RepoRoot "pure-php\src"), \
  (Join-Path $RepoRoot "pure-php\.env.example") \
  -DestinationPath $zipPure -Force

# Zip de deploy
$zipDeploy = Join-Path $ArtifactDir "saaswl-deploy-$Version.zip"
Compress-Archive -Path \
  (Join-Path $RepoRoot "deploy\install.sh"), \
  (Join-Path $RepoRoot "deploy\install.ps1"), \
  (Join-Path $RepoRoot "deploy\README.md"), \
  (Join-Path $RepoRoot "deploy\apache2-vhost-purephp.conf") \
  -DestinationPath $zipDeploy -Force

# Zip de docs
$zipDocs = Join-Path $ArtifactDir "saaswl-docs-$Version.zip"
Compress-Archive -Path (Join-Path $RepoRoot "docs\INSTALL.md") -DestinationPath $zipDocs -Force

Write-Host "Arquivos gerados em $ArtifactDir" -ForegroundColor Green
Get-ChildItem $ArtifactDir | Format-Table Name,Length,LastWriteTime

Write-Host "Dica: crie tag e push: git tag $Version; git push origin $Version" -ForegroundColor Yellow