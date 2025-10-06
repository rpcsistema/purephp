#!/usr/bin/env bash
set -euo pipefail

VERSION=${1:-}
if [[ -z "$VERSION" ]]; then
  echo "Uso: $0 <versao> (ex: v0.1.0)"; exit 1
fi

REPO_ROOT=$(cd "$(dirname "$0")/.." && pwd)
ARTIFACT_DIR="$REPO_ROOT/dist"
mkdir -p "$ARTIFACT_DIR"

echo "Empacotando artefatos para $VERSION"

# Pacote do módulo PHP puro (apenas o necessário)
TAR_NAME="saaswl-pure-php-$VERSION.tar.gz"
tar -czf "$ARTIFACT_DIR/$TAR_NAME" -C "$REPO_ROOT" \
  pure-php/public \
  pure-php/config \
  pure-php/sql \
  pure-php/src \
  pure-php/.env.example

# Pacote de deploy
DEPLOY_TAR_NAME="saaswl-deploy-$VERSION.tar.gz"
tar -czf "$ARTIFACT_DIR/$DEPLOY_TAR_NAME" -C "$REPO_ROOT" \
  deploy/install.sh \
  deploy/install.ps1 \
  deploy/README.md \
  deploy/apache2-vhost-purephp.conf

# Docs
DOCS_TAR_NAME="saaswl-docs-$VERSION.tar.gz"
tar -czf "$ARTIFACT_DIR/$DOCS_TAR_NAME" -C "$REPO_ROOT" docs/INSTALL.md

echo "Arquivos gerados em $ARTIFACT_DIR:"
ls -lh "$ARTIFACT_DIR"

echo "Dica: crie tag e push: git tag $VERSION && git push origin $VERSION"