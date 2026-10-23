<?php

declare(strict_types=1);

/**
 * Verifica links Markdown locais e referências explícitas a arquivos no mapa.
 *
 * Uso: php scripts/check-system-map.php
 * A ferramenta é deliberadamente conservadora: não tenta inferir se toda
 * descrição de arquitetura ou regra de negócio está semanticamente correta.
 */

$root = dirname(__DIR__);
$mapPath = $root . '/docs/MAPA_DO_SISTEMA.md';

if (!is_file($mapPath)) {
    fwrite(STDERR, "ERRO: docs/MAPA_DO_SISTEMA.md não encontrado.\n");
    exit(2);
}

$map = file_get_contents($mapPath);
if ($map === false) {
    fwrite(STDERR, "ERRO: não foi possível ler o mapa.\n");
    exit(2);
}

$errors = [];
$checkedLinks = 0;
$checkedPaths = 0;

// Verifica links Markdown locais do tipo [texto](caminho.md#secao).
preg_match_all('/\[[^\]]*\]\(([^)]+)\)/', $map, $links);
foreach ($links[1] as $target) {
    $target = trim(explode('#', $target, 2)[0]);
    if ($target === '' || preg_match('~^(?:https?://|mailto:|#)~i', $target)) {
        continue;
    }

    // Links relativos ao mapa (docs/).
    $resolved = realpath(dirname($mapPath) . '/' . $target);
    $checkedLinks++;
    if ($resolved === false || !is_file($resolved)) {
        $errors[] = "Link Markdown local não encontrado: {$target}";
    }
}

// Verifica caminhos explícitos com extensões de arquivo comuns dentro de
// código inline. Ignora placeholders, URLs, rotas HTTP e nomes de classe.
preg_match_all('/`([^\`]*\.(?:php|md|json|ya?ml|toml|ts|tsx|js|jsx|css|html|sql|env|xml))`/', $map, $paths);
$seen = [];
foreach ($paths[1] as $path) {
    $path = trim($path);
    if ($path === '' || str_contains($path, '{') || str_contains($path, '}') ||
        str_starts_with($path, 'http://') || str_starts_with($path, 'https://') ||
        str_starts_with($path, '/') || str_contains($path, ' ')) {
        continue;
    }

    // Ignora identificadores de símbolos que apenas terminam com extensão,
    // mas não têm separador de diretório.
    if (!str_contains($path, '/') && !str_starts_with($path, '.')) {
        continue;
    }

    $seen[$path] = true;
}

foreach (array_keys($seen) as $path) {
    $checkedPaths++;
    if (!is_file($root . '/' . $path)) {
        $errors[] = "Arquivo referenciado não encontrado: {$path}";
    }
}

echo "Verificação do mapa técnico\n";
echo "Links locais verificados: {$checkedLinks}\n";
echo "Referências a arquivos verificadas: {$checkedPaths}\n";

if ($errors !== []) {
    fwrite(STDERR, "\nDivergências encontradas:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "Nenhuma divergência de caminho detectada pelo verificador conservador.\n";
