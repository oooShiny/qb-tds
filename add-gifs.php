<?php
require_once('helpers.php');
$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$qb_path = $_GET['qb'];
if (!empty($qb_path)) {
    $tds_data = file_get_contents('json/' . $qb_path . '-tds.json');
    $json = json_decode($tds_data, TRUE);
    $qb_name = ucwords(str_replace('-', ' ', $qb_path));
    $qba = explode(' ', $qb_name);
    $qb_last_name = end($qba);
    
    $team_map = team_map();
    $qb_colors = qb_colors($qb_path);
    
    $tds = [];
    foreach ($json as $item) {
      $tds[$item['team']][] = $item;
    }
}

$menu = [];
$count = [];
$files = scandir('json');
foreach ($files as $file) {
    if (strpos($file, '.json') !== FALSE) {
        $n = substr($file, 0, -9);
        $qbn = ucwords(str_replace('-', ' ', $n));
        $qbarr = explode(' ', $qbn);
        $last_name = end($qbarr);
        if ($last_name == 'Manning') {
            $last_name = substr($qbn, 0, 1) . '. ' . $last_name;
        }
        $menu[] = [
            'url' => '?qb=' . $n,
            'title' => $last_name
        ];
        if (empty($qb_path)) {
            $data = file_get_contents('json/'. $file);
            $json = json_decode($data, TRUE);
            $count[$n] = count($json);
        }
    }
}
 
$tds_by_week = [];
$playoff_weeks =  [
    18 => 'Wildcard',
    19 => 'Divisional',
    20 => 'Conference',
    21 => 'Super Bowl'
];
foreach($json as $td) {
    $tds_by_week[$td['season'] . '.' . sprintf('%02d',$td['week'])][] = $td;
}
ksort($tds_by_week, SORT_NUMERIC);

if (isset($_POST['submitted'])) {

    foreach ($_POST as $key => $value) {
        if ($key !== 'submitted') {
            $key_arr = explode('-', $key);
            $id = $key_arr[1];
            $time = $key_arr[0];
            foreach ($json as $c => $td) {
                if ($td['id'] == $id) {
                    $json[$c][$time] = $value;
                }
            }
        }
    }

    $newJsonString = json_encode($json);
    file_put_contents('brady-bucs-tds.json', $newJsonString);
}
?>

<table>
    <thead>
        <th>Season</th>
        <th>Week</th>
        <th>Play</th>
        <th>Receiver</th>
        <th>Opponent</th>
        <th>Highlight</th>
        <th>Minutes</th>
        <th>Seconds</th>
        <th>Video ID</th>
    </thead>
    <tbody>
        <form method="post">
            <input type="hidden" name="submitted">
        <?php foreach ($tds_by_week as $week): ?>
            <?php foreach ($week as $td): ?>
                    <tr>
                    <td><?php print $td['season']; ?></td>
                    <td><?php print $td['week']; ?></td>
                    <td><?php print $td['title']; ?></td>
                    <td><?php print $td['players_involved']; ?></td>
                    <td><?php print $td['opponent']; ?></td>
                    <td><?php print $td['minutes']; ?></td>
                    <td><?php print $td['seconds']; ?></td>
                    <td><a href="https://gfycat.com/<?php print $td['gfycat_id']; ?>">Highlight</a></td>
                    <td><input type="text" id="gfycat_id-<?php print $td['id']; ?>" name="gfycat_id-<?php print $td['id']; ?>" value="<?php print $td['gfycat_id']; ?>"></td>
                    </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <input type="submit">
        </form>
    </tbody>
</table>