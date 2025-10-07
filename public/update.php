<?php
require __DIR__ . '/bootstrap.php';
if (!Auth::check()) { redirect('/login.php'); }

$user = Auth::user();
$cfg = require __DIR__ . '/../config/config.php';

// Leitura de variáveis para GitHub
$repo = getenv('GITHUB_REPO') ?: '';
$org = getenv('GITHUB_ORG') ?: '';
$token = getenv('GITHUB_TOKEN') ?: '';
$repo = isset($_GET['repo']) ? trim((string)$_GET['repo']) : $repo;
$org = isset($_GET['org']) ? trim((string)$_GET['org']) : $org;
$error = null; $info = null; $release = null; $assets = [];

function ghRequest(string $url, ?string $token): array {
    $headers = [
        'User-Agent: Saaswl-Updater',
        'Accept: application/vnd.github+json',
    ];
    if ($token) { $headers[] = 'Authorization: Bearer ' . $token; }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false) { throw new Exception('Erro CURL: ' . $err); }
    if ($httpCode >= 400) { throw new Exception('HTTP ' . $httpCode . ' ao consultar GitHub'); }
    $json = json_decode($body, true);
    if (!is_array($json)) { throw new Exception('Resposta inesperada do GitHub'); }
    return $json;
}

function getUrlSize(string $url, ?string $token): int {
    $headers = [ 'User-Agent: Saaswl-Updater' ];
    if ($token) { $headers[] = 'Authorization: Bearer ' . $token; }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $len = (int)curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    curl_close($ch);
    if ($len > 0) { return $len; }
    // Fallback: parse headers
    if (is_string($resp)) {
        foreach (explode("\r\n", $resp) as $line) {
            if (stripos($line, 'Content-Length:') === 0) {
                $val = trim(substr($line, strlen('Content-Length:')));
                $num = (int)$val;
                if ($num > 0) { return $num; }
            }
        }
    }
    return 0;
}

function formatBytes(int $bytes): string {
    $units = ['B','KB','MB','GB','TB'];
    $i = 0; $val = (float)$bytes;
    while ($i < count($units)-1 && $val >= 1024) { $val /= 1024; $i++; }
    return ($i === 0 ? number_format($val, 0) : number_format($val, 2)) . ' ' . $units[$i];
}

// Ações: checar a última release e baixar um asset
try {
    // Resolver owner/repo a partir de entradas flexíveis (nome simples, owner/repo, URL http(s) ou SSH, com/sem .git)
    if (!$repo) { throw new Exception('Configure GITHUB_REPO no .env ou informe no formulário.'); }
    $input = trim($repo);
    $input = preg_replace('/\.git$/i', '', $input); // remover sufixo .git
    $input = trim($input, "/\t\n\r \0\x0B");
    $repoSlug = '';
    // URL HTTPS: https://github.com/owner/repo(.git)
    if (preg_match('#^https?://github\.com/([^/]+)/([^/]+)$#i', $input, $m)) {
        $repoSlug = $m[1] . '/' . $m[2];
    }
    // URL com caminho extra (e.g., termina com barra) – normalizar
    elseif (preg_match('#^https?://github\.com/([^/]+)/([^/?\#]+)#i', $input, $m)) {
        $name = preg_replace('/\.git$/i', '', $m[2]);
        $repoSlug = $m[1] . '/' . $name;
    }
    // SSH: git@github.com:owner/repo(.git)
    elseif (preg_match('#^git@github\.com:([^/]+)/([^/]+)$#i', $input, $m)) {
        $repoSlug = $m[1] . '/' . preg_replace('/\.git$/i', '', $m[2]);
    }
    // owner/repo informado diretamente
    elseif (strpos($input, '/') !== false) {
        $repoSlug = $input;
    }
    // apenas nome do repo – precisa de org/owner
    else {
        if ($org) { $repoSlug = $org . '/' . $input; }
        else { throw new Exception('Defina GITHUB_ORG ou informe um link completo do GitHub (ex.: https://github.com/owner/repo.git) ou use owner/repo.'); }
    }
    // Validar formato final
    if (strpos($repoSlug, '/') === false) { throw new Exception('Formato inválido do repositório após normalização.'); }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $assetUrl = $_POST['asset_url'] ?? '';
        $assetName = $_POST['asset_name'] ?? 'download.zip';
        if (!$assetUrl) { throw new Exception('Asset inválido.'); }

        // Download do asset com cabeçalho de autorização quando necessário
        $headers = [
            'User-Agent: Saaswl-Updater',
            'Accept: application/octet-stream',
        ];
        if ($token) { $headers[] = 'Authorization: Bearer ' . $token; }

        $ch = curl_init($assetUrl);
        $dest = __DIR__ . '/assets/' . basename($assetName);
        $fp = fopen($dest, 'wb');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
        ]);
        $ok = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if (!$ok || $httpCode >= 400) { throw new Exception('Falha no download: ' . $err . ' (HTTP ' . $httpCode . ')'); }
        $info = 'Download concluído: ' . basename($dest);
    }

    // Buscar última release
    // Montar URL sem codificar a barra entre owner e repo
    list($ownerPart, $repoPart) = explode('/', $repoSlug, 2);
    if (!$ownerPart || !$repoPart) { throw new Exception('Owner/Repo inválidos.'); }
    $latest = ghRequest('https://api.github.com/repos/' . rawurlencode($ownerPart) . '/' . rawurlencode($repoPart) . '/releases/latest', $token);
    $release = [
        'tag_name' => $latest['tag_name'] ?? 'unknown',
        'name' => $latest['name'] ?? '',
        'published_at' => $latest['published_at'] ?? '',
        'html_url' => $latest['html_url'] ?? '',
        'body' => $latest['body'] ?? '',
    ];
    $assetsRaw = is_array($latest['assets'] ?? null) ? $latest['assets'] : [];
    // Resolver tamanhos e normalizar lista de assets para exibição
    $assets = [];
    foreach ($assetsRaw as $a) {
        $downloadUrl = $a['browser_download_url'] ?? '';
        $sizeBytes = (int)($a['size'] ?? 0);
        if ($sizeBytes <= 0 && $downloadUrl) { $sizeBytes = getUrlSize($downloadUrl, $token); }
        $assets[] = [
            'name' => $a['name'] ?? 'arquivo',
            'browser_download_url' => $downloadUrl,
            'size_bytes' => $sizeBytes,
            'size_pretty' => formatBytes($sizeBytes),
        ];
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo page_title('Atualizações'); ?></title>
  <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
  <h3>Atualizações (GitHub)</h3>
  <div class="alert alert-info py-1 px-2 mb-3" style="display:inline-block">Rótulo de teste: build local</div>
  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($info): ?><div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div><?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Repositório</label>
          <input type="text" class="form-control" name="repo" value="<?php echo htmlspecialchars($repo); ?>" placeholder="repo, owner/repo ou URL (ex.: https://github.com/owner/repo.git)">
          <div class="form-text">Você pode colar o link completo do GitHub (ex.: `https://github.com/rpcsistema/purephp.git`), usar `owner/repo` ou informar apenas o nome do repo com o Owner ao lado.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Organização/Owner</label>
          <input type="text" class="form-control" name="org" value="<?php echo htmlspecialchars($org); ?>" placeholder="owner (ex.: minhaempresa)">
          <div class="form-text">Opcional se você já usar `owner/repo` no campo acima.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Token</label>
          <input type="password" class="form-control" value="<?php echo $token ? '••••••••' : ''; ?>" placeholder="opcional" readonly>
          <div class="form-text">Defina `GITHUB_TOKEN` no `.env` para repositórios privados.</div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-secondary btn-sm">Aplicar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Última release</h5>
      <?php if ($release): ?>
        <p><strong>Tag:</strong> <?php echo htmlspecialchars($release['tag_name']); ?></p>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($release['name']); ?></p>
        <p><strong>Publicada:</strong> <?php echo htmlspecialchars($release['published_at']); ?></p>
        <?php if ($release['html_url']): ?><p><a href="<?php echo htmlspecialchars($release['html_url']); ?>" target="_blank" class="btn btn-outline-light btn-sm">Ver no GitHub</a></p><?php endif; ?>
        <?php if ($release['body']): ?><pre class="small" style="white-space: pre-wrap; background: #111; padding: .75rem; border-radius: .5rem;"><?php echo htmlspecialchars($release['body']); ?></pre><?php endif; ?>

        <h6>Assets</h6>
        <?php if ($assets): ?>
          <div class="list-group">
            <?php foreach ($assets as $a): ?>
              <?php $downloadUrl = $a['browser_download_url'] ?? ''; ?>
              <div class="list-group-item bg-dark text-light">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div><strong><?php echo htmlspecialchars($a['name'] ?? 'arquivo'); ?></strong></div>
                    <div class="small">Tamanho: <?php echo htmlspecialchars($a['size_pretty'] ?? '0 B'); ?></div>
                  </div>
                  <?php if ($downloadUrl): ?>
                    <form method="post">
                      <input type="hidden" name="asset_url" value="<?php echo htmlspecialchars($downloadUrl); ?>">
                      <input type="hidden" name="asset_name" value="<?php echo htmlspecialchars($a['name'] ?? 'download.zip'); ?>">
                      <button type="submit" class="btn btn-primary btn-sm">Baixar</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-3">
            <span class="small text-muted">Também disponível:</span>
            <?php if (!empty($release['tag_name'])): ?>
              <?php $tag = urlencode($release['tag_name']); ?>
              <a class="btn btn-outline-secondary btn-sm" target="_blank" href="https://github.com/<?php echo htmlspecialchars($repoSlug); ?>/archive/refs/tags/<?php echo htmlspecialchars($release['tag_name']); ?>.zip">Source code (zip)</a>
              <a class="btn btn-outline-secondary btn-sm" target="_blank" href="https://github.com/<?php echo htmlspecialchars($repoSlug); ?>/archive/refs/tags/<?php echo htmlspecialchars($release['tag_name']); ?>.tar.gz">Source code (tar.gz)</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-warning">Nenhum asset disponível para a última release.</div>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert-secondary">Não foi possível carregar informações da última release.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>