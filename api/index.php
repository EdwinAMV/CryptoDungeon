<?php

// Vercel has a read-only application bundle. Only compiled views and framework
// manifests use temporary storage; rooms, sessions and cache remain in Neon.
require __DIR__.'/../bootstrap/vercel.php';

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
