<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/products';
$required = ['key', 'name', 'version', 'status', 'runtime', 'health', 'core'];
$allowedStatus = ['prototype', 'development', 'homologation', 'production', 'legacy'];
$errors = [];
$validated = 0;

foreach (glob($root . '/*/product.json') ?: [] as $file) {
    $raw = file_get_contents($file);
    $data = json_decode((string) $raw, true);

    if (! is_array($data)) {
        $errors[] = $file . ': JSON inválido - ' . json_last_error_msg();
        continue;
    }

    foreach ($required as $field) {
        if (! array_key_exists($field, $data)) {
            $errors[] = $file . ': campo obrigatório ausente: ' . $field;
        }
    }

    if (isset($data['key']) && ! preg_match('/^[a-z0-9-]+$/', (string) $data['key'])) {
        $errors[] = $file . ': key inválida';
    }

    if (isset($data['version']) && ! preg_match('/^\d+\.\d+\.\d+$/', (string) $data['version'])) {
        $errors[] = $file . ': version deve usar SemVer';
    }

    if (isset($data['status']) && ! in_array($data['status'], $allowedStatus, true)) {
        $errors[] = $file . ': status inválido';
    }

    if (! isset($data['health']['path']) || ! str_starts_with((string) $data['health']['path'], '/')) {
        $errors[] = $file . ': health.path deve começar com /';
    }

    $validated++;
}

if ($validated === 0) {
    $errors[] = 'Nenhum manifesto products/*/product.json encontrado.';
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo sprintf("Manifestos validados: %d\n", $validated);
