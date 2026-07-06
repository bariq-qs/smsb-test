<?php

// Router for `php -S`, copied from Laravel's own `illuminate/foundation/resources/server.php`
// (the same script `php artisan serve` uses). Needed because passing `public/index.php`
// directly as the router makes the built-in server run the full framework for every
// request — including static files under `public/storage` (PDF attachments, Excel
// exports) — which never resolves to a route and comes back as a 404/HTML page instead
// of the actual file. This script lets the built-in server serve real files as-is and
// only falls through to Laravel for everything else.

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
