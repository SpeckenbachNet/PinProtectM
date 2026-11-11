<?php

/**
 * Writes the files for a GitHub release into build/dist/pinprotect/:
 *
 *   updates.xml                          polled by Joomla (System → Updates)
 *   release-notes.md                     text for the GitHub release: intro (README), changes since the last release, installation
 *   changelog.xml                        shown in Joomla's update / extension manager
 *   plg_system_pinprotect-<ver>.zip      copy of the plugin, one file per version
 *
 * Usage: php build/make-update.php <path to plg_system_pinprotect.zip>
 *
 * Version and date come from the manifest, the change notes from
 * changelog.json. An entry there may carry "security": 1-4
 * (low, medium, high, critical); Joomla 6.2+ then flags the update as a
 * security release.
 */

// GitHub releases: "latest" redirects to the newest published release (no draft, no pre-release).
// Tags are always "v" + version (vX.Y.Z).
const REPO_URL     = 'https://github.com/SpeckenbachNet/PinProtectM';
const LATEST_URL   = REPO_URL . '/releases/latest/download/';
const ELEMENT      = 'pinprotect';
const FOLDER       = 'system';
const ZIP_PREFIX   = 'plg_system_pinprotect';
const DISPLAY_NAME = 'PinProtectM';
const PHP_MINIMUM  = '8.3';

$root = dirname(__DIR__);
$zip  = $argv[1] ?? '';

if (!is_file($zip)) {
    fwrite(STDERR, "Plugin zip not found: $zip\n");
    exit(1);
}

$manifest = simplexml_load_file($root . '/plg_system_pinprotect/pinprotect.xml');
$version  = (string) $manifest->version;
$entries  = json_decode((string) file_get_contents($root . '/changelog.json'), true);

if (!is_array($entries)) {
    fwrite(STDERR, "changelog.json is not valid JSON\n");
    exit(1);
}

$current = null;

foreach ($entries as $entry) {
    if (($entry['version'] ?? '') === $version) {
        $current = $entry;
    }
}

if ($current === null) {
    fwrite(STDERR, "No changelog.json entry for version $version\n");
    exit(1);
}

// Both languages: the install message follows the backend language.
foreach (['en-GB', 'de-DE'] as $tag) {
    if (empty($current['changes'][$tag])) {
        fwrite(STDERR, "changelog.json: version $version has no changes in $tag\n");
        exit(1);
    }
}

$e        = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$security = max(0, min(4, (int) ($current['security'] ?? 0)));
$zipName  = ZIP_PREFIX . '-' . $version . '.zip';
$tag      = 'v' . $version;
$tagUrl   = REPO_URL . '/releases/download/' . $tag . '/';
$dist     = $root . '/build/dist/pinprotect';

if (!is_dir($dist) && !mkdir($dist, 0777, true)) {
    fwrite(STDERR, "Cannot create $dist\n");
    exit(1);
}

copy($zip, $dist . '/' . $zipName);

// Only the current version: Joomla offers the newest matching entry anyway, and
// older entries would point to files that may never have been uploaded.
$updates = '<?xml version="1.0" encoding="utf-8"?>' . "\n"
    . "<updates>\n"
    . "    <update>\n"
    . '        <name>' . $e(DISPLAY_NAME) . "</name>\n"
    . '        <description>' . $e(DISPLAY_NAME . ' ' . $version) . "</description>\n"
    . '        <element>' . ELEMENT . "</element>\n"
    . "        <type>plugin</type>\n"
    . '        <folder>' . FOLDER . "</folder>\n"
    // Plugins are site extensions (client_id 0); without it Joomla assumes administrator
    . "        <client>site</client>\n"
    . '        <version>' . $e($version) . "</version>\n"
    . '        <infourl title="' . $e(DISPLAY_NAME) . '">' . $e(REPO_URL . '/releases/tag/' . $tag) . "</infourl>\n"
    . '        <changelogurl>' . LATEST_URL . "changelog.xml</changelogurl>\n"
    . "        <downloads>\n"
    . '            <downloadurl type="full" format="zip">' . $e($tagUrl . $zipName) . "</downloadurl>\n"
    . "        </downloads>\n"
    . '        <sha512>' . hash_file('sha512', $zip) . "</sha512>\n"
    . ($security > 0 ? '        <security>' . $security . "</security>\n" : '')
    . "        <tags>\n"
    . "            <tag>stable</tag>\n"
    . "        </tags>\n"
    . '        <maintainer>' . $e((string) $manifest->author) . "</maintainer>\n"
    . '        <maintainerurl>' . $e((string) $manifest->authorUrl) . "</maintainerurl>\n"
    . '        <targetplatform name="joomla" version="6.*"/>' . "\n"
    . '        <php_minimum>' . PHP_MINIMUM . "</php_minimum>\n"
    . "    </update>\n"
    . "</updates>\n";

// All versions: Joomla picks the block matching the version it shows.
// Security entries go to Joomla's "Security Fixes" section, everything else to "Changes".
$changelog = '<?xml version="1.0" encoding="utf-8"?>' . "\n" . "<changelogs>\n";

foreach ($entries as $entry) {
    $section = (int) ($entry['security'] ?? 0) > 0 ? 'security' : 'change';

    $changelog .= "    <changelog>\n"
        . '        <element>' . ELEMENT . "</element>\n"
        . "        <type>plugin</type>\n"
        . '        <version>' . $e((string) $entry['version']) . "</version>\n"
        . "        <$section>\n";

    // Joomla's changelog has no languages: English.
    $changes = (array) ($entry['changes'] ?? []);
    $changes = array_is_list($changes) ? $changes : (array) ($changes['en-GB'] ?? []);

    foreach ($changes as $change) {
        // CDATA: expat delivers plain text in several chunks (e.g. at the first umlaut),
        // and Joomla's changelog parser turns every chunk into a bullet of its own.
        $changelog .= '            <item><![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', (string) $change) . "]]></item>\n";
    }

    $changelog .= "        </$section>\n" . "    </changelog>\n";
}

$changelog .= "</changelogs>\n";

file_put_contents($dist . '/updates.xml', $updates);
file_put_contents($dist . '/changelog.xml', $changelog);

// Release notes (English) for the GitHub release text: every version since the newest published
// release older than this one (no drafts, no pre-releases). GitHub's public API lists the releases;
// without an answer only the version being built is listed.
$previous = '';
$api      = @file_get_contents(
    'https://api.github.com/repos/' . substr(REPO_URL, strlen('https://github.com/')) . '/releases?per_page=100',
    false,
    stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: make-update\r\nAccept: application/vnd.github+json\r\n"]])
);

if ($api !== false) {
    foreach ((array) json_decode($api, true) as $release) {
        $releaseVersion = ltrim((string) ($release['tag_name'] ?? ''), 'v');

        if (!empty($release['draft']) || !empty($release['prerelease']) || version_compare($releaseVersion, $version, '>=')) {
            continue;
        }

        if ($previous === '' || version_compare($releaseVersion, $previous, '>')) {
            $previous = $releaseVersion;
        }
    }
}

$notes = '';

foreach ($entries as $entry) {
    $entryVersion = (string) ($entry['version'] ?? '');
    $wanted       = $previous !== ''
        ? version_compare($entryVersion, $previous, '>') && version_compare($entryVersion, $version, '<=')
        : $entryVersion === $version;

    if (!$wanted) {
        continue;
    }

    $changes = (array) ($entry['changes'] ?? []);
    $changes = array_is_list($changes) ? $changes : (array) ($changes['en-GB'] ?? []);
    $notes  .= "### $entryVersion\n\n";

    foreach ($changes as $change) {
        $notes .= '- ' . $change . "\n";
    }

    $notes .= "\n";
}

// Intro: the first paragraph below the title of README.md (maintained in one place)
$intro  = '';
$readme = @file($root . '/README.md', FILE_IGNORE_NEW_LINES);

if (\is_array($readme)) {
    $lines   = [];
    $started = false;

    foreach ($readme as $line) {
        if (!$started) {
            $started = str_starts_with($line, '# ');
            continue;
        }

        if (trim($line) === '') {
            if ($lines !== []) {
                break;
            }

            continue;
        }

        $lines[] = trim($line);
    }

    $intro = implode(' ', $lines);
}

$release = ($intro !== '' ? $intro . "\n\n" : '')
    . "## What's new\n\n"
    . rtrim($notes) . "\n\n"
    . "## Installation\n\n"
    . 'Download `' . $zipName . '` below and install it under **System → Install → Extensions**.' . "\n"
    . "Existing installations get this version under **System → Update → Extensions**.\n"
    . ($previous !== '' ? "\n**Full changes:** " . REPO_URL . '/compare/v' . $previous . '...' . $tag . "\n" : '');

file_put_contents($dist . '/release-notes.md', $release);

echo "==> Update server files for $version" . ($security > 0 ? " (security level $security)" : '') . " in build/dist/pinprotect/:\n";

foreach (['updates.xml', 'changelog.xml', $zipName] as $file) {
    echo "    $file\n";
}

echo "\n==> To publish $version as an update:\n"
    . "    1. git push origin main --tags        (tag $tag must exist on GitHub)\n"
    . "    2. " . REPO_URL . "/releases/new  ->  choose tag $tag\n"
    . "    3. drag the three files above into the release\n"
    . "    4. paste release-notes.md into the description"
    . ($previous !== '' ? " (versions after release v$previous)" : ' (only this version: no older release found)') . "\n"
    . "    5. \"Publish release\" (no pre-release, so it becomes \"Latest\")\n";
