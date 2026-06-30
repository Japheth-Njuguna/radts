<?php
// SMTP settings loader.
// This file is safe to commit because it reads credentials from environment variables.

if (!function_exists('radtsEnvGet')) {
	function radtsEnvGet(string $key, string $default = ''): string {
		$value = getenv($key);
		if ($value !== false && $value !== '') {
			return (string)$value;
		}

		if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
			return (string)$_ENV[$key];
		}

		if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
			return (string)$_SERVER[$key];
		}

		return $default;
	}
}

if (!function_exists('radtsLoadDotEnv')) {
	function radtsLoadDotEnv(string $envFilePath): void {
		if (!is_readable($envFilePath)) {
			return;
		}

		$lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			return;
		}

		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) {
				continue;
			}

			$parts = explode('=', $line, 2);
			if (count($parts) !== 2) {
				continue;
			}

			$name = trim($parts[0]);
			$value = trim($parts[1]);

			if ($name === '' || getenv($name) !== false) {
				continue;
			}

			$len = strlen($value);
			if ($len >= 2) {
				$first = $value[0];
				$last = $value[$len - 1];
				if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
					$value = substr($value, 1, -1);
				}
			}

			putenv($name . '=' . $value);
			$_ENV[$name] = $value;
			$_SERVER[$name] = $value;
		}
	}
}

radtsLoadDotEnv(dirname(__DIR__) . '/.env');

if (!defined('RADTS_SMTP_HOST')) {
	define('RADTS_SMTP_HOST', radtsEnvGet('RADTS_SMTP_HOST', 'smtp.gmail.com'));
}
if (!defined('RADTS_SMTP_PORT')) {
	define('RADTS_SMTP_PORT', radtsEnvGet('RADTS_SMTP_PORT', '587'));
}
if (!defined('RADTS_SMTP_SECURE')) {
	define('RADTS_SMTP_SECURE', radtsEnvGet('RADTS_SMTP_SECURE', 'tls'));
}
if (!defined('RADTS_SMTP_USER')) {
	define('RADTS_SMTP_USER', radtsEnvGet('RADTS_SMTP_USER', ''));
}
if (!defined('RADTS_SMTP_PASS')) {
	define('RADTS_SMTP_PASS', radtsEnvGet('RADTS_SMTP_PASS', ''));
}
if (!defined('RADTS_SMTP_FROM')) {
	define('RADTS_SMTP_FROM', radtsEnvGet('RADTS_SMTP_FROM', radtsEnvGet('RADTS_SMTP_USER', '')));
}
if (!defined('RADTS_SMTP_FROM_NAME')) {
	define('RADTS_SMTP_FROM_NAME', radtsEnvGet('RADTS_SMTP_FROM_NAME', 'RADTS'));
}
