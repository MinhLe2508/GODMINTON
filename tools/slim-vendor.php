<?php
/** Remove distribution extras only; never remove runtime source or licenses. */
declare(strict_types=1);

$vendor = realpath(__DIR__ . '/../vendor');
if ($vendor === false) {
    fwrite(STDERR, "Run composer install first.\n");
    exit(1);
}
$apply = in_array('--apply', $argv, true);
$root = str_replace('\\', '/', $vendor) . '/';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendor, FilesystemIterator::SKIP_DOTS)
);
$count = 0;
$bytes = 0;
foreach ($files as $file) {
    if (!$file->isFile() || $file->isLink()) continue;
    $absolute = realpath($file->getPathname());
    if ($absolute === false) continue;
    $absolute = str_replace('\\', '/', $absolute);
    if (strpos($absolute, $root) !== 0) continue;
    $relative = substr($absolute, strlen($root));
    if (strpos($relative, 'composer/') === 0 || strpos($relative, 'PHPMailer/') === 0) continue;
    $parts = explode('/', $relative);
    if (count($parts) < 3) continue;
    $local = implode('/', array_slice($parts, 2));
    // Match only package-root documentation; source/resources may contain .md files.
    $extra = in_array($local, [
        'README.md', 'CHANGELOG.md', 'UPGRADING.md', 'SECURITY.md',
        'SMTPUTF8.md', 'COMMITMENT',
    ], true) || strpos($local, '.github/') === 0
        || (implode('/', array_slice($parts, 0, 2)) === 'psr/http-message' && strpos($local, 'docs/') === 0);
    if (strpos($relative, 'phpmailer/phpmailer/') === 0) {
        $extra = $extra || $local === 'get_oauth_token.php'
            || (strpos($local, 'language/phpmailer.lang-') === 0 && $local !== 'language/phpmailer.lang-vi.php');
    }
    if (!$extra) continue;
    $size = $file->getSize();
    if ($apply && !unlink($absolute)) {
        fwrite(STDERR, "Unable to remove vendor extra: $relative\n");
        exit(1);
    }
    $count++;
    $bytes += $size;
}
printf("Vendor extras %s: %d files, %d bytes. Runtime code, Composer metadata and licenses retained.\n", $apply ? 'removed' : 'preview', $count, $bytes);
