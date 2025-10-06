# Publicação de Releases

Este guia descreve como versionar e publicar artefatos (módulo PHP puro, scripts de deploy e docs) usando Git tags e GitHub Actions.

## Como versionar

1. Atualize o código e docs.
2. Gere artefatos localmente (opcional):
   ```bash
   bash deploy/publish-release.sh v0.1.0
   ```
3. Crie uma tag e faça push:
   ```bash
   git tag v0.1.0
   git push origin v0.1.0
   ```

## CI/CD (GitHub Actions)

Ao fazer push da tag, o workflow `.github/workflows/release.yml`:

- Executa `deploy/publish-release.sh` para gerar `dist/*`.
- Publica um Release com anexos:
  - `saaswl-pure-php-<tag>.tar.gz`
  - `saaswl-deploy-<tag>.tar.gz`
  - `saaswl-docs-<tag>.tar.gz`

## Consumindo artefatos em servidores

Baixe os pacotes diretamente da página de Releases do GitHub e aplique:

```bash
wget https://github.com/<seu-usuario>/<seu-repo>/releases/download/v0.1.0/saaswl-deploy-v0.1.0.tar.gz
tar -xzf saaswl-deploy-v0.1.0.tar.gz
sudo bash deploy/install.sh
```

Para Windows (PowerShell):

```powershell
Invoke-WebRequest -Uri https://github.com/<seu-usuario>/<seu-repo>/releases/download/v0.1.0/saaswl-deploy-v0.1.0.zip -OutFile saaswl-deploy-v0.1.0.zip
Expand-Archive -Path saaswl-deploy-v0.1.0.zip -DestinationPath . -Force
./deploy/install.ps1
```

## Boas práticas

- Use SemVer (`vMAJOR.MINOR.PATCH`).
- Mantenha CHANGELOG e notas de release.
- Não inclua credenciais em artefatos; use `.env` no servidor.