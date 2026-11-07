<?php
declare(strict_types=1);

// Static safety regression: does not boot Laravel or run a build.
$root = dirname(__DIR__, 2);
$files = [
 'intake' => file_get_contents($root . '/app/CommercialFactory/Services/CommercialFactoryIntakeService.php'),
 'api' => file_get_contents($root . '/app/Http/Controllers/Api/SiteFactoryIntakeController.php'),
 'commercial' => file_get_contents($root . '/app/Console/Commands/CommercialFactoryIntakeCommand.php'),
 'siteTest' => file_get_contents($root . '/app/Console/Commands/SiteFactoryApiTestCommand.php'),
];
$errors = [];
foreach ($files as $key => $source) {
 if ($source === false) $errors[] = "missing {$key}";
}
if (!$errors) {
 if (!str_contains($files['intake'], "'--dry-run' => true")) $errors[]='build is not forced dry-run';
 if (!str_contains($files['intake'], "'execution_mode' => 'simulation_only'")) $errors[]='missing simulation-only mode';
 if (!str_contains($files['intake'], "'real_build_performed' => false")) $errors[]='missing no-build flag';
 if (!str_contains($files['intake'], '\'status\' => $exitCode === 0 ? \'simulated\' : \'simulation_failed\'')) $errors[]='incorrect report statuses';
 if (str_contains($files['intake'], "'ready_for_homologation'")) $errors[]='simulated license marked HML ready';
 if (str_contains($files['intake'], '\'status\' => $factory[\'status\'] === \'passed\' ? \'finished\'')) $errors[]='legacy finished success remains';
 if (!str_contains($files['api'], "'status'] === 'simulated' ? 202 : 422")) $errors[]='HTTP semantics not updated';
 if (str_contains($files['api'], "'Pedido recebido e enviado para homologação da Factory.'")) $errors[]='API incorrectly promises homologation';
 foreach (['commercial','siteTest'] as $key) {
  if (!str_contains($files[$key], "'status']") || !str_contains($files[$key], "=== 'simulated'")) $errors[]="{$key} does not recognize simulation";
 }
}
if ($errors) {
 foreach ($errors as $error) fwrite(STDERR, "FAIL: {$error}\n");
 exit(1);
}
echo "PASS: dry-run safety invariants present; behavior requires Laravel integration tests\n";
