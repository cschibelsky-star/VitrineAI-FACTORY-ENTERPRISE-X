<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/App.php';
$app = new App(dirname(__DIR__).'/data/assessorgov.sqlite');
$app->run();
