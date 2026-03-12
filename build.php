<?php
/**
 * build.php - Generates static HTML for each page.
 *
 * Usage: php build.php
 * Output: dist/index.html, dist/{qb-slug}.html, dist/search.html, dist/json/
 */

error_reporting(E_ERROR);
ini_set('display_errors', '0');

chdir(__DIR__);

$dist = __DIR__ . '/dist';
if (!is_dir($dist)) {
    mkdir($dist, 0755, true);
}

// Discover QB slugs from json/ directory
$slugs = [];
foreach (scandir(__DIR__ . '/json') as $file) {
    if (preg_match('/^(.+)-tds\.json$/', $file, $m)) {
        $slugs[] = $m[1];
    }
}

/**
 * Rewrite dynamic links to their static equivalents.
 */
function rewrite_links(string $html): string {
    // ?qb=slug  →  slug.html  (index.php nav links)
    $html = preg_replace('/href="(\?qb=([^"]+))"/', 'href="$2.html"', $html);
    // search.php?qb=  →  search.html?qb=
    $html = str_replace('href="/search.php?qb=', 'href="/search.html?qb=', $html);
    $html = str_replace("href='/search.php?qb=", "href='/search.html?qb=", $html);
    // index.php?qb=slug  →  /slug.html  (search.php subnav)
    $html = preg_replace('/href="\/index\.php\?qb=([^"#]+)#([^"]+)"/', 'href="/$1.html#$2"', $html);
    return $html;
}

/**
 * Render index.php with a given QB slug (or null for the home page).
 */
function render(?string $slug): string {
    $_GET = $slug ? ['qb' => $slug] : [];
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = $slug ? '/?qb=' . $slug : '/';

    ob_start();
    include __DIR__ . '/index.php';
    return rewrite_links(ob_get_clean());
}

/**
 * Render search.php (QB-agnostic shell — JS handles filtering at runtime).
 */
function render_search(): string {
    $_GET = [];
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/search.php';

    ob_start();
    include __DIR__ . '/search.php';
    return rewrite_links(ob_get_clean());
}

/**
 * Recursively copy a directory.
 */
function copy_dir(string $src, string $dst): void {
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    foreach (scandir($src) as $file) {
        if ($file === '.' || $file === '..') continue;
        $s = $src . '/' . $file;
        $d = $dst . '/' . $file;
        is_dir($s) ? copy_dir($s, $d) : copy($s, $d);
    }
}

/**
 * Merge all QB JSON files into a single search.json with a `qb` field on each entry.
 */
function generate_search_json(string $dist, array $slugs): void {
    $combined = [];
    foreach ($slugs as $slug) {
        $data    = json_decode(file_get_contents(__DIR__ . '/json/' . $slug . '-tds.json'), true);
        $qb_name = ucwords(str_replace('-', ' ', $slug));
        foreach ($data as $td) {
            $td['qb']      = $slug;
            $td['qb_name'] = $qb_name;
            $combined[]    = $td;
        }
    }
    usort($combined, fn($a, $b) => $b['season'] <=> $a['season'] ?: (int)$b['week'] <=> (int)$a['week']);
    file_put_contents($dist . '/json/search.json', json_encode($combined));
}

// Home page
echo "Building index.html\n";
file_put_contents($dist . '/index.html', render(null));

// One page per QB
foreach ($slugs as $slug) {
    echo "Building {$slug}.html\n";
    file_put_contents($dist . '/' . $slug . '.html', render($slug));
}

// Search page
echo "Building search.html\n";
file_put_contents($dist . '/search.html', render_search());

// Static assets
$assets = ['icon.ico', 'icon.svg', 'apple-icon.png'];
foreach ($assets as $asset) {
    if (file_exists(__DIR__ . '/' . $asset)) {
        copy(__DIR__ . '/' . $asset, $dist . '/' . $asset);
        echo "Copied {$asset}\n";
    }
}

// JSON data files (required by search.html at runtime)
echo "Copying json/\n";
copy_dir(__DIR__ . '/json', $dist . '/json');

// Combined search index
echo "Building json/search.json\n";
generate_search_json($dist, $slugs);

$total = count($slugs) + 2; // +1 home, +1 search
echo "\nDone — {$total} pages written to dist/\n";
