<?php
/**
 * QB TD JSON Updater
 *
 * Reads CSV files from the csv/ directory and adds any new TD entries to
 * their corresponding JSON files in json/.
 *
 * CSV format (columns):
 *   Date, Tm, Opp, Quarter, Time, Down, ToGo, Location, Score, Detail, Yds, ...
 *
 * Run via browser or CLI: php update.php
 */

// ── Active QB configuration ────────────────────────────────────────────────
$active_qbs = [
    'aaron-rodgers' => [
        'name'      => 'Aaron Rodgers',
        'csv_file'  => 'csv/aaron-rodgers.csv',
        'json_file' => 'json/aaron-rodgers-tds.json',
    ],
    'matt-stafford' => [
        'name'      => 'Matt Stafford',
        'csv_file'  => 'csv/matt-stafford.csv',
        'json_file' => 'json/matt-stafford-tds.json',
    ],
    'philip-rivers' => [
        'name'      => 'Philip Rivers',
        'csv_file'  => 'csv/philip-rivers.csv',
        'json_file' => 'json/philip-rivers-tds.json',
    ],
];

// ── NFL season-opener dates (first Thursday kickoff of each season) ────────
// Used to convert a game date into (season_year, week_number).
const NFL_SEASON_STARTS = [
    2004 => '2004-09-09',
    2005 => '2005-09-08',
    2006 => '2006-09-07',
    2007 => '2007-09-06',
    2008 => '2008-09-04',
    2009 => '2009-09-10',
    2010 => '2010-09-09',
    2011 => '2011-09-08',
    2012 => '2012-09-05',
    2013 => '2013-09-05',
    2014 => '2014-09-04',
    2015 => '2015-09-10',
    2016 => '2016-09-08',
    2017 => '2017-09-07',
    2018 => '2018-09-06',
    2019 => '2019-09-05',
    2020 => '2020-09-10',
    2021 => '2021-09-09',
    2022 => '2022-09-08',
    2023 => '2023-09-07',
    2024 => '2024-09-05',
    2025 => '2025-09-04',
];

// ── Handle form submission ─────────────────────────────────────────────────
$results   = [];   // ['qb' => slug, 'added' => n, 'tds' => [...], 'error' => '...']
$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$run_all   = ($action === 'update_all');
$run_one   = ($action === 'update_one' && isset($_POST['qb']));

if ($run_all) {
    foreach ($active_qbs as $slug => $cfg) {
        $results[] = process_qb($slug, $cfg);
    }
} elseif ($run_one) {
    $slug = $_POST['qb'];
    if (isset($active_qbs[$slug])) {
        $results[] = process_qb($slug, $active_qbs[$slug]);
    }
}

// ── Core processing ────────────────────────────────────────────────────────

function process_qb(string $slug, array $cfg): array {
    $result = ['qb' => $slug, 'name' => $cfg['name'], 'added' => 0, 'tds' => [], 'error' => ''];

    if (!file_exists($cfg['csv_file'])) {
        $result['error'] = "CSV file not found: {$cfg['csv_file']}";
        return $result;
    }

    $new_tds = parse_local_csv($cfg['csv_file']);
    if (empty($new_tds)) {
        $result['error'] = "Could not parse {$cfg['csv_file']} — check the file format.";
        return $result;
    }

    $result['added'] = update_json($cfg['json_file'], $new_tds, $result['tds']);
    return $result;
}

/**
 * Parse a local CSV file in the format:
 *   Date, Tm, Opp, Quarter, Time, Down, ToGo, Location, Score, Detail, Yds, ...
 */
function parse_local_csv(string $path): array {
    $handle = fopen($path, 'r');
    if (!$handle) return [];

    $headers = fgetcsv($handle);  // skip header row
    if (!$headers) { fclose($handle); return []; }

    // Map column names to indices
    $col = array_flip(array_map('trim', $headers));
    $required = ['Date', 'Tm', 'Opp', 'Quarter', 'Time', 'Down', 'ToGo', 'Detail', 'Yds'];
    foreach ($required as $r) {
        if (!array_key_exists($r, $col)) { fclose($handle); return []; }
    }

    $tds = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < count($headers)) continue;

        $date    = trim($row[$col['Date']] ?? '');
        $tm      = trim($row[$col['Tm']] ?? '');
        $opp     = trim($row[$col['Opp']] ?? '');
        $quarter = trim($row[$col['Quarter']] ?? '');
        $time    = trim($row[$col['Time']] ?? '');
        $detail  = trim($row[$col['Detail']] ?? '');
        $yds     = trim($row[$col['Yds']] ?? '');
        $down    = trim($row[$col['Down']] ?? '');
        $togo    = trim($row[$col['ToGo']] ?? '');

        if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;

        [$season, $week] = date_to_season_week($date);
        [$mins, $secs]   = parse_time($time);
        $receiver        = extract_receiver($detail);
        $year            = (int) substr($date, 0, 4);

        if (empty($receiver) || !is_numeric($yds)) continue;

        $tds[] = [
            'season'           => (string) $season,
            'week'             => (string) $week,
            'team'             => team_name_to_abbr($tm, $year),
            'opponent'         => team_name_to_abbr($opp, $year),
            'quarter'          => $quarter,
            'yards_gained'     => $yds,
            'players_involved' => $receiver,
            'minutes'          => $mins,
            'seconds'          => $secs,
            'down'             => $down,
            'distance'         => $togo,
        ];
    }
    fclose($handle);

    // Sort chronologically (CSV is newest-first)
    usort($tds, function ($a, $b) {
        return ($a['season'] <=> $b['season']) ?: ($a['week'] <=> $b['week']);
    });

    return $tds;
}

/**
 * Convert a YYYY-MM-DD date string into [season_year, week_number].
 *
 * Season year: September–December = current year; January–February = previous year.
 * Week number: floor((days since season opener Thursday) / 7) + 1
 */
function date_to_season_week(string $date): array {
    $ts    = strtotime($date);
    $month = (int) date('n', $ts);
    $year  = (int) date('Y', $ts);

    $season = ($month <= 2) ? $year - 1 : $year;

    $starts = NFL_SEASON_STARTS;
    if (!isset($starts[$season])) {
        // Fallback: guess opener as first Thursday in September
        $guess = strtotime("first thursday of september $season");
        $starts[$season] = date('Y-m-d', $guess);
    }

    $opener_ts = strtotime($starts[$season]);
    $days      = (int) (($ts - $opener_ts) / 86400);
    $week      = (int) floor($days / 7) + 1;

    return [$season, $week];
}

/** Split "MM:SS" into [minutes (int), seconds (int)]. Returns [null, null] on failure. */
function parse_time(string $time): array {
    if (preg_match('/^(\d+):(\d{2})$/', $time, $m)) {
        return [(int) $m[1], (int) $m[2]];
    }
    return [null, null];
}

/**
 * Extract the receiver name from a play description.
 * Matches: "... pass ... to RECEIVER for N yards touchdown"
 */
function extract_receiver(string $detail): string {
    // When a play was overturned by a challenge, PFR appends the corrected play after ". "
    // Use only the corrected play to avoid capturing the challenge text as the receiver name.
    if (stripos($detail, 'overturned') !== false) {
        $pos = strrpos($detail, '. ');
        if ($pos !== false) $detail = substr($detail, $pos + 2);
    }
    if (preg_match("/ to ([A-Za-z][A-Za-z'.\\- ]+?) for \\d+ yards? touchdown/i", $detail, $m)) {
        return trim($m[1]);
    }
    return '';
}

/**
 * Map a full team name to its NFL abbreviation, accounting for franchise moves.
 */
function team_name_to_abbr(string $name, int $year): string {
    // Year-aware relocations
    if ($name === 'Raiders')  return $year >= 2020 ? 'LVR' : 'OAK';
    if ($name === 'Rams')     return $year >= 2016 ? 'LAR' : 'STL';
    if ($name === 'Chargers') return $year >= 2017 ? 'LAC' : 'SDG';

    static $map = [
        'Cardinals'    => 'ARI', 'Falcons'    => 'ATL', 'Ravens'     => 'BAL',
        'Bills'        => 'BUF', 'Panthers'   => 'CAR', 'Bears'      => 'CHI',
        'Bengals'      => 'CIN', 'Browns'     => 'CLE', 'Cowboys'    => 'DAL',
        'Broncos'      => 'DEN', 'Lions'      => 'DET', 'Packers'    => 'GNB',
        'Texans'       => 'HOU', 'Colts'      => 'IND', 'Jaguars'    => 'JAX',
        'Chiefs'       => 'KAN', 'Dolphins'   => 'MIA', 'Vikings'    => 'MIN',
        'Saints'       => 'NOR', 'Patriots'   => 'NWE', 'Giants'     => 'NYG',
        'Jets'         => 'NYJ', 'Eagles'     => 'PHI', 'Steelers'   => 'PIT',
        'Seahawks'     => 'SEA', '49ers'      => 'SFO', 'Buccaneers' => 'TAM',
        'Titans'       => 'TEN', 'Commanders' => 'WAS',
        // Legacy names
        'Redskins'     => 'WAS', 'Football Team' => 'WAS',
        'Oilers'       => 'OTI',
    ];

    return $map[$name] ?? $name;
}

// ── JSON update (shared between methods) ──────────────────────────────────

/** Deduplication key using fields present in both old and new data. */
function td_key(array $td): string {
    return implode('|', [
        $td['season'],
        $td['week'],
        $td['quarter'],
        $td['yards_gained'],
        strtolower(trim($td['players_involved'])),
    ]);
}

/**
 * Merge new TDs into the existing JSON file, skipping duplicates.
 * Creates the file if it doesn't yet exist.
 * Returns count of TDs added; populates $added_out.
 */
function update_json(string $json_file, array $new_tds, array &$added_out = []): int {
    $existing = [];
    if (file_exists($json_file)) {
        $existing = json_decode(file_get_contents($json_file), true) ?? [];
    }

    $seen    = [];
    foreach ($existing as $td) {
        $seen[td_key($td)] = true;
    }

    $next_id = !empty($existing) ? (max(array_column($existing, 'id')) + 1) : 1;
    $count   = 0;

    foreach ($new_tds as $td) {
        $key = td_key($td);
        if (isset($seen[$key])) continue;

        $td['id']  = $next_id++;
        $existing[] = $td;
        $seen[$key] = true;
        $added_out[] = $td;
        $count++;
    }

    if ($count > 0) {
        $dir = dirname($json_file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($json_file, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return $count;
}

// ── Current JSON stats (for display) ──────────────────────────────────────
function json_stats(string $json_file): array {
    if (!file_exists($json_file)) return ['count' => 0, 'latest' => '—'];
    $data = json_decode(file_get_contents($json_file), true) ?? [];
    if (empty($data)) return ['count' => 0, 'latest' => '—'];
    $last = end($data);
    return [
        'count'  => count($data),
        'latest' => "Season {$last['season']}, Wk {$last['week']}",
    ];
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>QB TD JSON Updater</title>
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-8">
<div class="max-w-3xl mx-auto">

    <h1 class="text-3xl font-bold mb-1">QB TD JSON Updater</h1>
    <p class="text-gray-500 mb-8 text-sm">
        Reads CSV files from <code>csv/</code> and adds new TDs to <code>json/</code>.
    </p>

    <?php if (!empty($results)): ?>
    <div class="mb-8 space-y-4">
        <?php foreach ($results as $r): ?>
        <div class="p-4 rounded border <?= empty($r['error']) ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300' ?>">
            <?php if ($r['error']): ?>
                <p class="font-semibold text-red-800"><?= htmlspecialchars($r['name']) ?>: <?= htmlspecialchars($r['error']) ?></p>
            <?php else: ?>
                <p class="font-semibold text-green-800">
                    <?= htmlspecialchars($r['name']) ?>:
                    added <strong><?= $r['added'] ?></strong> new TD<?= $r['added'] !== 1 ? 's' : '' ?>
                    <?= $r['added'] === 0 ? '— already up to date' : '' ?>
                </p>
                <?php if (!empty($r['tds'])): ?>
                <ul class="mt-2 text-sm text-green-900 space-y-0.5 list-disc list-inside">
                    <?php foreach ($r['tds'] as $td): ?>
                    <li><?= htmlspecialchars("Season {$td['season']} Wk {$td['week']} | Q{$td['quarter']} {$td['minutes']}:{$td['seconds']} | {$td['yards_gained']} yds → {$td['players_involved']} (vs {$td['opponent']})") ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Current status ─────────────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Current Status</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="pb-2 pr-4">QB</th>
                    <th class="pb-2 pr-4">CSV file</th>
                    <th class="pb-2 pr-4">TDs in JSON</th>
                    <th class="pb-2">Latest entry</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($active_qbs as $slug => $cfg):
                $csv_exists = file_exists($cfg['csv_file']);
                $stats = json_stats($cfg['json_file']);
            ?>
            <tr class="border-b last:border-0">
                <td class="py-2 pr-4 font-medium"><?= htmlspecialchars($cfg['name']) ?></td>
                <td class="py-2 pr-4 <?= $csv_exists ? 'text-green-700' : 'text-red-500' ?>">
                    <?= $csv_exists ? '✓ ' : '✗ ' ?><code><?= htmlspecialchars($cfg['csv_file']) ?></code>
                </td>
                <td class="py-2 pr-4"><?= $stats['count'] ?></td>
                <td class="py-2 text-gray-500"><?= $stats['latest'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Update controls ────────────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Run Update</h2>

        <!-- Update all -->
        <form method="POST" class="mb-6">
            <input type="hidden" name="action" value="update_all">
            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded text-lg">
                Update All Active QBs
            </button>
            <p class="text-xs text-gray-400 mt-2 text-center">
                Parses all CSV files and adds any TDs not already in the JSON files.
            </p>
        </form>

        <div class="border-t pt-4">
            <p class="text-sm font-medium text-gray-600 mb-3">Or update a single QB:</p>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($active_qbs as $slug => $cfg): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_one">
                    <input type="hidden" name="qb" value="<?= htmlspecialchars($slug) ?>">
                    <button type="submit"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded">
                        <?= htmlspecialchars($cfg['name']) ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── How to add new QBs ─────────────────────────────────────────── -->
    <details class="mt-6 bg-white rounded-lg shadow">
        <summary class="p-4 cursor-pointer font-medium text-gray-700">How to add or update a QB's CSV file</summary>
        <div class="px-4 pb-4 text-sm text-gray-600 space-y-2">
            <p>Place a CSV file named <code>&lt;qb-slug&gt;.csv</code> in the <code>csv/</code> directory.</p>
            <p>The CSV must have these columns (in any order):</p>
            <code class="block bg-gray-50 p-2 rounded text-xs">
                Date, Tm, Opp, Quarter, Time, Down, ToGo, Location, Score, Detail, Yds
            </code>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>Date</strong>: YYYY-MM-DD</li>
                <li><strong>Tm / Opp</strong>: Full team name (e.g., "Steelers", "Ravens")</li>
                <li><strong>Quarter</strong>: 1–4 or OT</li>
                <li><strong>Time</strong>: MM:SS remaining in quarter</li>
                <li><strong>Down / ToGo</strong>: Down number and yards to go</li>
                <li><strong>Detail</strong>: Play description containing "to RECEIVER for N yards touchdown"</li>
                <li><strong>Yds</strong>: Pass distance in yards</li>
            </ul>
            <p>Then add the QB to the <code>$active_qbs</code> array at the top of <code>update.php</code>
               and <code>helpers.php</code>.</p>
        </div>
    </details>

    <p class="mt-6 text-center text-sm text-gray-400">
        <a href="/" class="hover:underline">← Back to site</a>
    </p>
</div>
</body>
</html>
