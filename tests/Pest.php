<?php

pest()->extend(Tests\TestCase::class)->in('Feature');

/**
 * Helper: path to the test fixture directory.
 */
function testFixturePath(string $relative = ''): string
{
    return FCMS_TEST_DIR . ($relative ? '/' . ltrim($relative, '/') : '');
}

/**
 * Helper: write a markdown content file into the test fixture.
 */
function createTestContent(string $relativePath, string $body, array $metadata = []): string
{
    $file = FCMS_TEST_DIR . '/content/' . ltrim($relativePath, '/');
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $content = '';
    if (!empty($metadata)) {
        $content .= "<!-- json\n" . json_encode($metadata, JSON_PRETTY_PRINT) . "\n-->\n";
    }
    $content .= $body;
    file_put_contents($file, $content);
    return $file;
}
