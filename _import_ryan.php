<?php
$opp_codes = [
    'Texans'   => 'HOU', 'Chiefs'   => 'KAN', 'Titans'  => 'TEN',
    'Jaguars'  => 'JAX', 'Raiders'  => 'LVR', 'Steelers'=> 'PIT',
    'Cowboys'  => 'DAL', 'Vikings'  => 'MIN',
];

$week_map = [
    '2022-09-11' => 1,  '2022-09-25' => 3,  '2022-10-02' => 4,
    '2022-10-16' => 6,  '2022-10-23' => 7,  '2022-11-13' => 10,
    '2022-11-28' => 12, '2022-12-04' => 13, '2022-12-17' => 15,
];

$existing = json_decode(file_get_contents('json/matt-ryan-tds.json'), true);
$next_id  = max(array_column($existing, 'id')) + 1;

// Build a lookup of already-imported 2022 plays (by quarter+minutes+seconds+yards)
$already = [];
foreach ($existing as $t) {
    if ($t['season'] === '2022') {
        $already[$t['quarter'].'|'.$t['minutes'].'|'.$t['seconds'].'|'.$t['yards_gained']] = true;
    }
}

$csv  = file('csv/matt-ryan.csv');
array_shift($csv); // drop header

$new_entries = [];
foreach (array_reverse($csv) as $line) {
    $f = str_getcsv(trim($line), ',', '"', '\\');
    [$date, $tm, $opp, $quarter, $time, $down, $togo, $loc, $score, $detail, $yds] = $f;

    // Only process 2022 season
    if (!str_starts_with($date, '2022') && $date !== '2022-12-17') {
        // keep only 2022 dates (Dec 17 is fine, Jan dates are 2021 season)
    }
    $year = substr($date, 0, 4);
    if ($year !== '2022') continue;

    $week = $week_map[$date] ?? null;
    if (!$week) { echo "UNKNOWN DATE: $date ($opp)\n"; continue; }

    [$mins, $secs] = explode(':', $time);
    $mins = (int)$mins;
    $secs = (int)$secs;

    $key = $quarter.'|'.$mins.'|'.$secs.'|'.$yds;
    if (isset($already[$key])) {
        echo "  SKIP (already exists): W$week Q$quarter $time {$yds}yds\n";
        continue;
    }

    if (!preg_match('/\bto ([A-Z][a-zA-Z\'-]+(?: [A-Z][a-zA-Z\'-]+)+) for/', $detail, $m)) {
        echo "  COULD NOT PARSE RECEIVER: $detail\n"; continue;
    }

    $new_entries[] = [
        'id'               => $next_id++,
        'season'           => '2022',
        'week'             => (string)$week,
        'team'             => 'IND',
        'opponent'         => $opp_codes[$opp] ?? $opp,
        'quarter'          => $quarter,
        'yards_gained'     => $yds,
        'players_involved' => $m[1],
        'minutes'          => (string)$mins,
        'seconds'          => (string)$secs,
        'down'             => $down,
        'distance'         => $togo,
    ];
    echo "  ADD: W$week vs {$opp_codes[$opp]} Q$quarter $time {$yds}yds to {$m[1]}\n";
}

echo "\nParsed " . count($new_entries) . " new entries\n";

$merged = array_merge($existing, $new_entries);
file_put_contents('json/matt-ryan-tds.json', json_encode($merged, JSON_PRETTY_PRINT));
echo "Wrote " . count($merged) . " total (official target: 401)\n";
