<?php
$dirs = ['uploads/products', 'uploads/profile', 'uploads/custom_designs'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    echo $dir . ': ';
    echo !is_dir($path) ? 'MISSING' : (is_writable($path) ? 'OK - writable' : 'EXISTS but NOT writable');
    echo '<br>';
}