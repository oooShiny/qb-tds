<?php
/**
 * nflverse TD Fetcher
 *
 * Downloads play-by-play data from nflverse-data and adds any new passing TDs
 * for the active QBs to their JSON files in json/.
 *
 * Source: https://github.com/nflverse/nflverse-data/releases/tag/pbp
 *
 * CLI only:
 *   php fetch-nflverse.php                 # current season
 *   php fetch-nflverse.php --season=2025   # specific season
 *   php fetch-nflverse.php --dry-run       # report without writing
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

chdir(__DIR__);
require_once __DIR__ . '/lib/td-json.php';

// ── Active QB configuration (keyed by nflverse gsis_id) ───────────────────
$active_qbs = [
    '00-0023459' => ['slug' => 'aaron-rodgers', 'name' => 'Aaron Rodgers'],
    '00-0026498' => ['slug' => 'matt-stafford', 'name' => 'Matt Stafford'],
];

const PBP_URL     = 'https://github.com/nflverse/nflverse-data/releases/download/pbp/play_by_play_%d.csv.gz';
const PLAYERS_URL = 'https://github.com/nflverse/nflverse-data/releases/download/players/players.csv.gz';

// nflverse team codes that differ from the site's (PFR-style) codes
const TEAM_MAP = [
    'GB' => 'GNB', 'KC' => 'KAN', 'LA' => 'LAR', 'LV' => 'LVR',
    'NE' => 'NWE', 'NO' => 'NOR', 'SF' => 'SFO', 'TB' => 'TAM',
];

// ── Options ────────────────────────────────────────────────────────────────
$opts    = getopt('', ['season:', 'dry-run']);
$dry_run = isset($opts['dry-run']);
// Season rolls over in March: Jan–Feb games belong to the previous season.
$season  = (int) ($opts['season'] ?? ((int) date('n') >= 3 ? date('Y') : date('Y') - 1));

// ── Fetch data ─────────────────────────────────────────────────────────────
echo "Fetching {$season} play-by-play...\n";
$plays = find_td_passes(download(sprintf(PBP_URL, $season)), array_keys($active_qbs));

if (empty($plays)) {
    echo "No TD passes found for active QBs in {$season}.\n";
    exit(0);
}

echo "Fetching player names...\n";
$names = load_player_names(download(PLAYERS_URL));

// ── Merge into JSON ────────────────────────────────────────────────────────
$total = 0;
foreach ($active_qbs as $gsis_id => $qb) {
    $tds = array_map(
        fn($p) => play_to_td($p, $names),
        array_values(array_filter($plays, fn($p) => $p['passer_player_id'] === $gsis_id))
    );

    $added = [];
    $count = update_json("json/{$qb['slug']}-tds.json", $tds, $added, 'td_time_key', $dry_run);
    $total += $count;

    echo "{$qb['name']}: " . count($tds) . " TDs in {$season}, {$count} new\n";
    foreach ($added as $td) {
        printf("  + %s Wk %s | Q%s %d:%02d | %s yds -> %s (vs %s)\n",
            $td['season'], $td['week'], $td['quarter'], $td['minutes'], $td['seconds'],
            $td['yards_gained'], $td['players_involved'], $td['opponent']);
    }
}

echo $dry_run ? "\nDry run: {$total} TDs would be added.\n" : "\nAdded {$total} TDs.\n";

// ── Functions ──────────────────────────────────────────────────────────────

/** Download a URL to a temp file and return its path. Exits on failure. */
function download(string $url): string {
    $data = @file_get_contents($url, false, stream_context_create([
        'http' => ['user_agent' => 'qbtds.com updater', 'timeout' => 120],
    ]));
    if ($data === false) {
        fwrite(STDERR, "Download failed: {$url}\n");
        exit(1);
    }
    $path = tempnam(sys_get_temp_dir(), 'nflverse');
    file_put_contents($path, $data);
    return $path;
}

/** Yield each row of a gzipped CSV as an associative array. */
function read_gz_csv(string $path): Generator {
    $handle  = gzopen($path, 'r');
    $headers = fgetcsv($handle, escape: '');
    while (($row = fgetcsv($handle, escape: '')) !== false) {
        if (count($row) === count($headers)) yield array_combine($headers, $row);
    }
    gzclose($handle);
}

/** Return every passing-TD play thrown by one of the given passers. */
function find_td_passes(string $pbp_path, array $passer_ids): array {
    $plays = [];
    foreach (read_gz_csv($pbp_path) as $row) {
        if ($row['pass_touchdown'] === '1' && in_array($row['passer_player_id'], $passer_ids, true)) {
            $plays[] = $row;
        }
    }
    usort($plays, fn($a, $b) => ((int) $a['week'] <=> (int) $b['week']) ?: ((int) $a['play_id'] <=> (int) $b['play_id']));
    return $plays;
}

/** Map gsis_id => display name ("D.Adams" in pbp becomes "Davante Adams"). */
function load_player_names(string $players_path): array {
    $names = [];
    foreach (read_gz_csv($players_path) as $row) {
        $names[$row['gsis_id']] = $row['display_name'];
    }
    return $names;
}

/** Convert an nflverse play row into the site's TD JSON format. */
function play_to_td(array $p, array $names): array {
    [$mins, $secs] = array_map('intval', explode(':', $p['time']));

    return [
        'season'           => $p['season'],
        // nflverse numbers playoff weeks 19-22, matching the site's scheme
        'week'             => $p['week'],
        'team'             => TEAM_MAP[$p['posteam']] ?? $p['posteam'],
        'opponent'         => TEAM_MAP[$p['defteam']] ?? $p['defteam'],
        'quarter'          => (int) $p['qtr'] >= 5 ? 'OT' : $p['qtr'],
        'yards_gained'     => $p['yards_gained'],
        'players_involved' => $names[$p['receiver_player_id']] ?? $p['receiver_player_name'],
        'minutes'          => $mins,
        'seconds'          => $secs,
        'down'             => $p['down'],
        'distance'         => $p['ydstogo'],
    ];
}

/**
 * Dedup key based on game clock rather than receiver name, since nflverse and
 * PFR can spell names differently.
 */
function td_time_key(array $td): string {
    return implode('|', [
        $td['season'],
        (int) $td['week'],
        // Older entries store overtime as "5" rather than "OT"
        $td['quarter'] === '5' ? 'OT' : $td['quarter'],
        (int) $td['minutes'],
        (int) $td['seconds'],
        (int) $td['yards_gained'],
    ]);
}
