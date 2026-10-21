<?php
// Evita "headers already sent" al iniciar la sesión dentro de PHPUnit.
ob_start();
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.php';