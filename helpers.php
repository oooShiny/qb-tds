<?php
function team_map() {

  return [
    'ARI' => 'Cardinals',
    'ATL' => 'Falcons',
    'BAL' => 'Ravens',
    'BUF' => 'Bills',
    'CAR' => 'Panthers',
    'CHI' => 'Bears',
    'CIN' => 'Bengals',
    'CLE' => 'Browns',
    'DAL' => 'Cowboys',
    'DEN' => 'Broncos',
    'DET' => 'Lions',
    'GNB' => 'Packers',
    'HOU' => 'Texans',
    'IND' => 'Colts',
    'JAX' => 'Jaguars',
    'KAN' => 'Chiefs',
    'LAR' => 'Rams',
    'LVR' => 'Raiders',
    'LAC' => 'Chargers',
    'MIA' => 'Dolphins',
    'MIN' => 'Vikings',
    'NOR' => 'Saints',
    'NWE' => 'Patriots',
    'NYG' => 'Giants',
    'NYJ' => 'Jets',
    'OAK' => 'Raiders',
    'PHI' => 'Eagles',
    'PHO' => 'Cardinals',
    'PIT' => 'Steelers',
    'RAI' => 'Raiders',
    'RAM' => 'Rams',
    'SDG' => 'Chargers',
    'SEA' => 'Seahawks',
    'SFO' => '49ers',
    'STL' => 'Rams',
    'TAM' => 'Buccaneers',
    'TEN' => 'Titans',
    'WAS' => 'Commanders'
  ];
}

/**
 * Team colors for each QB.
 */
function qb_colors($qb) {
  $colors = [
    'tom-brady' => ['#002244', '#FF7900'],
    'drew-brees' => ['#002244', '#d3bc8d'],
    'peyton-manning' => ['#003087', '#FB4F14'],
    'brett-favre' => ['#FFB612', '#125740', '#4F2683'],
    'ben-roethlisberger' => ['#000000', '#FFB612'],
    'dan-marino' => ['#005F61', '#FA4616'],
    'philip-rivers' => ['#0080C6', '#002C5F'],
    'eli-manning' => ['#0B2265', '#A71930'],
    'aaron-rodgers' => ['#203731', '#FFB612'],
    'matt-ryan' => ['#A71930', '#002C5F']
  ];
  return $colors[$qb];
}

/**
 * How many seasons before switching teams?
 */
function qb_seasons($qb) {
  $seasons = [
    'tom-brady' => 18,
    'drew-brees' => 5,
    'peyton-manning' => 13,
    'brett-favre' => 16,
    'brett-favre2' => 17,
    'ben-roethlisberger' => 18,
    'dan-marino' => 17,
    'philip-rivers' => 15,
    'eli-manning' => 16,
    'aaron-rodgers' => 16,
    'matt-ryan' => 14
  ];
  return $seasons[$qb];
}

function qb_yards($yards) {
  $qbs = [
    11015 => 'Rex Grossman',
    10711 => 'Byron Leftwich',
    9355 => 'Robert Griffin III',
    8931 => 'Kyle Boller',
    8295 => 'Shaun Hill',
    7783 => 'Brock Osweiler',
    7195 => 'Colt McCoy',
    6658 => 'Christian Ponder',
    5947 => 'Rob Johnson'
  ];
  foreach ($qbs as $yd => $qb) {
    if ($yards > $yd) {
      return [
        'q' => $qb,
        'y' => $yd
      ];
    }
  }
  return null;
}
function ordinal($number) {
  $ends = array('th','st','nd','rd','th','th','th','th','th','th');
  if ((($number % 100) >= 11) && (($number%100) <= 13))
    return $number. 'th';
  else
    return $number. $ends[$number % 10];
}