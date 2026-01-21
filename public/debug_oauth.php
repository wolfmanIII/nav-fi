<?php
// public/debug_oauth.php

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

echo "<h1>Debug Info</h1>";
echo "<pre>";
echo "<strong>Host:</strong> " . $request->getHost() . "\n";
echo "<strong>Port:</strong> " . $request->getPort() . "\n";
echo "<strong>Scheme:</strong> " . $request->getScheme() . "\n";
echo "<strong>Base URL:</strong> " . $request->getBaseUrl() . "\n";
echo "\n";
echo "<strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
echo "<strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "<strong>SERVER_PORT:</strong> " . ($_SERVER['SERVER_PORT'] ?? 'N/A') . "\n";
echo "<strong>REMOTE_ADDR:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "<strong>Trusted Proxies:</strong> " . print_r(Request::getTrustedProxies(), true) . "\n";
echo "<strong>Trusted Hosts:</strong> " . print_r(Request::getTrustedHosts(), true) . "\n";
echo "</pre>";
