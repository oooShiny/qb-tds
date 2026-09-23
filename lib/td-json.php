<?php
/**
 * Shared helpers for merging TD entries into json/{slug}-tds.json files.
 * Used by update.php (PFR CSV import) and fetch-nflverse.php (nflverse import).
 */

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
 *
 * $key_fn builds the deduplication key for a TD (defaults to td_key).
 * When $dry_run is true, nothing is written.
 */
function update_json(string $json_file, array $new_tds, array &$added_out = [], ?callable $key_fn = null, bool $dry_run = false): int {
    $key_fn ??= 'td_key';

    $existing = [];
    if (file_exists($json_file)) {
        $existing = json_decode(file_get_contents($json_file), true) ?? [];
    }

    $seen    = [];
    foreach ($existing as $td) {
        $seen[$key_fn($td)] = true;
    }

    $next_id = !empty($existing) ? (max(array_column($existing, 'id')) + 1) : 1;
    $count   = 0;

    foreach ($new_tds as $td) {
        $key = $key_fn($td);
        if (isset($seen[$key])) continue;

        $td['id']  = $next_id++;
        $existing[] = $td;
        $seen[$key] = true;
        $added_out[] = $td;
        $count++;
    }

    if ($count > 0 && !$dry_run) {
        $dir = dirname($json_file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($json_file, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return $count;
}
