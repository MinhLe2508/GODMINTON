<?php
/** Load simple KEY=VALUE settings from the project .env without overriding server variables. */
$godmintonEnvFile = dirname(__DIR__) . '/.env';
if (is_readable($godmintonEnvFile)) {
    foreach (file($godmintonEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $godmintonEnvLine) {
        $godmintonEnvLine = trim($godmintonEnvLine);
        if ($godmintonEnvLine === '' || $godmintonEnvLine[0] === '#' || strpos($godmintonEnvLine, '=') === false) continue;
        [$godmintonEnvKey, $godmintonEnvValue] = explode('=', $godmintonEnvLine, 2);
        $godmintonEnvKey = trim($godmintonEnvKey);
        $godmintonEnvValue = trim($godmintonEnvValue);
        if ($godmintonEnvKey !== '' && getenv($godmintonEnvKey) === false) {
            putenv($godmintonEnvKey . '=' . $godmintonEnvValue);
            $_ENV[$godmintonEnvKey] = $godmintonEnvValue;
        }
    }
}
unset($godmintonEnvFile, $godmintonEnvLine, $godmintonEnvKey, $godmintonEnvValue);
