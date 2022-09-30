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
?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Every <?php print $qb_name; ?> TD Pass</title>
    <link rel="icon" href="/icon.ico" sizes="any"><!-- 32×32 -->
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-icon.png"><!-- 180×180 -->
    <!-- Tailwind CSS and Alpine JS -->
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css"
          rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"
            defer></script>
    <!-- JQuery JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
            crossorigin="anonymous"></script>
    <!-- Highcharts JS -->
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <!-- Magnific Popup CSS & JS -->
    <!--        <link rel="stylesheet" href="magnific/magnific-popup.css">-->
    <!--        <script src="magnific/jquery.magnific-popup.min.js"></script>-->
    <!-- Custom Modal Video JS -->
    <script>
        function gifModal(gif) {
            jQuery(function ($) {
                var modalSrc = '';
                var url = 'https://api.gfycat.com/v1/gfycats/';
                $.get(url + gif, function (data) {
                    videoSrc = "<video controls muted autoplay preload='metadata' class='responsive-video'>" +
                        "<source src='" + data.gfyItem.mp4Url + "' type='video/mp4; codecs=' avc1.42e01e, mp4a.40.2''>" +
                        "<source src='" + data.gfyItem.webmUrl + "' type='video/webm; codecs=' vp8, vorbis''>" +
                        "</video>";
                    $.magnificPopup.open({
                        items: {
                            src: data.gfyItem.mp4Url
                        },
                        type: 'iframe'
                    });
                }).fail(function () {
                    var url = 'https://api.redgifs.com/v1/gfycats/'
                    $.get(url + gifID, function (data) {
                        videoSrc = "<video controls muted autoplay preload='metadata' class='responsive-video'>" +
                            "<source src='" + data.gfyItem.mp4Url + "' type='video/mp4; codecs=' avc1.42e01e, mp4a.40.2''>" +
                            "<source src='" + data.gfyItem.webmUrl + "' type='video/webm; codecs=' vp8, vorbis''>" +
                            "</video>";
                        $('.opp-video').html(videoSrc);
                    })
                });
            });
        }
    </script>
    <style>
        a:hover {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="bg-gray-500 fixed p-2 shadow-lg text-white w-screen">
        <ul class="flex flex-wrap justify-around">
            <?php arsort($menu); ?>
            <?php foreach ($menu as $item): ?>
                <li class="hover:text-blue-300 hover:underline"><a href="<?php print $item['url']?>"><?php print $item['title']?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php if (empty($qb_path)): ?>
    <nav class="border-b-2 border-gray-200 p-4 mb-10 pt-16">
    <div class="container items-center justify-between lg:flex lg:flex-row md:flex-col mx-auto px-6 py-2">
        <div>
            <a class="text-4xl" href="/">
                Every QB TD Pass
            </a>
        </div>
    </div>
</nav>
<div class="py-10 m-auto py-10 lg:w-8/12">
    <p>
        The touchdown pass is arguably the most exciting advancement ever to 
        grace the football gridiron. The QBs on this website are the best to do 
        it, as evidenced by their inclusion on the top 10 list of TD passes. 
        Select one of the QBs from the table below or the nav above to see their
        TD passes broken down by different variables.
    </p>
</div>
    <table class="w-auto m-auto">
        <thead>
            <tr>
                <th>Rank</th>
                <th>QB</th>
                <th>TDs</th>
            </tr>
        </thead>
        <tbody>
            <?php arsort($count); $i = 1;?>
        <?php foreach ($count as $q => $c): ?>
            <tr>
                <td><?php print $i; ?></td>
                <td><a href="?qb=<?php print $q; ?>"><?php print ucwords(str_replace('-', ' ', $q)); ?></a></td>
                <td><?php print $c; ?></td>
            </tr>
            <?php $i++; ?>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php else: ?>
<nav class="border-b-2 border-gray-200 p-4 mb-10 pt-16">
    <div class="container items-center justify-between lg:flex lg:flex-row md:flex-col mx-auto px-6 py-2">
        <div>
            <a class="text-4xl" href="/">
                Every <?php print $qb_name; ?> TD Pass
            </a>
        </div>
        <div class="">
            <ul class="inline-flex flex-wrap">
                <li><a class="p-4 hover:bg-gray-500 hover:text-white"
                       href="#opponent">Opponent</a></li>
                <li><a class="p-4 hover:bg-gray-500 hover:text-white"
                       href="#season">Season</a></li>
                <li><a class="p-4 hover:bg-gray-500 hover:text-white"
                       href="#week">Week</a></li>
                <li><a class="p-4 hover:bg-gray-500 hover:text-white"
                       href="#distance">Distance</a></li>
                <li><a class="p-4 hover:bg-gray-500 hover:text-white"
                       href="/search.php?qb=<?php print $qb_path?>">Search</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="py-10 flex flex-wrap p-10">
    <p><?php print $qb_name; ?> has thrown <?php print count($json); ?>
        touchdown passes in his NFL career. Below you will find every single one
        of them, organized in different ways.</p>
</div>
<?php if ($qb_path !== 'brett-favre' && $qb_path !== 'dan-marino'): ?>
<div class="py-10" id="td-scatter"></div>
        <?php
        
            $q_times = [
                '1' => 45,
                '2' => 30,
                '3' => 15,
                '4' => 0,
                'OT' => -15
            ];
            $scatter = [];
            foreach ($tds as $team => $team_tds) {
                foreach ($team_tds as $td) {

                    if ($td['minutes'] && $td['minutes'] !== 'x') {

                        $mins = $q_times[$td['quarter']];
                        $mins += $td['minutes'];
                        $secs = $td['seconds'];
                        $time = ($mins*60) + $secs;
                        if (is_numeric($td['quarter'])) {
                            $quarter = ordinal($td['quarter']) . ' quarter';
                        } 
                        else {
                            $quarter = 'overtime';
                        }
                        $scatter[$team][] = [
                            'time' => $time,
                            'distance' => $td['yards_gained'],
                            'quarter' => $quarter,
                            // 'vid' => $td['gfycat_id'],
                            't_string' => $td['minutes'] . ':' . str_pad($td['seconds'], 2, '0', STR_PAD_LEFT),
                            'title' => $td['yards_gained'] . ' TD pass to ' . $td['players_involved'] . ' vs ' . $td['opponent'] . ' (' . $td['season'] . ')'
                        ];
                    }
                }
            }
        ?>
        <script>
            Highcharts.chart('td-scatter', {
                chart: {
                    type: 'scatter',
                    zoomType: 'xy'
                },
                title: {
                    text: 'Every <?php print $qb_last_name; ?> TD by time vs distance'
                },
                xAxis: {
                    title: {
                        enabled: true,
                        text: 'Game Time'
                    },
                    labels: {
                        enabled: false
                    },
                    alignTicks: false,
                    tickLength: 0,
                    reversed: true,
                    plotLines: [{
                        color: '#FF0000',
                        width: 2,
                        value: 1800
                    },
                    {
                        color: '#FF0000',
                        width: 2,
                        value: 0
                    }],
                    plotBands: [{
                        color: 'rgb(15 41 82 / 8%)',
                        from: 3600,
                        to: 2700,
                        label: {
                            text: '1st Quarter'
                        }
                    },
                    {
                        color: 'rgb(0 0 0 / 0%)',
                        from: 2700,
                        to: 1800,
                        label: {
                            text: '2nd Quarter'
                        }
                    },
                    {
                        color: 'rgb(15 41 82 / 8%)',
                        from: 1800,
                        to: 900,
                        label: {
                            text: '3rd Quarter'
                        }
                    },
                    {
                        color: 'rgb(0 0 0 / 0%)',
                        from: 900,
                        to: 0,
                        label: {
                            text: '4th Quarter'
                        }
                    },
                    {
                        color: 'rgb(15 41 82 / 8%)',
                        from: 0,
                        to: -900,
                        label: {
                            text: 'Overtime'
                        }
                    },
                ]
                },
                yAxis: {
                    title: {
                        text: 'Distance (yards)'
                    }
                },
                legend: {
                    layout: 'vertical',
                    align: 'left',
                    verticalAlign: 'top',
                    x: 100,
                    y: 70,
                    floating: true,
                    backgroundColor: Highcharts.defaultOptions.chart.backgroundColor,
                    borderWidth: 1
                },
                plotOptions: {
                    scatter: {
                        marker: {
                            radius: 5,
                            states: {
                                hover: {
                                    enabled: true,
                                    lineColor: 'rgb(100,100,100)'
                                }
                            }
                        },
                        states: {
                            hover: {
                                marker: {
                                    enabled: false
                                }
                            }
                        },
                        tooltip: {
                            headerFormat: '<b>{series.name}</b><br>',
                            pointFormat: '{point.y} yard TD with {point.custom.time} left in {point.custom.q}<br><em>{point.custom.title}</em>'
                        }
                    }
                },
                series: [
                    <?php $x=0; ?>
                    <?php foreach ($scatter as $team => $team_tds): ?>
                        {
                    name: '<?php print $team; ?> TDs',
                    color: '<?php print $qb_colors[$x]; ?>',
                    // point: {
                        // events: {
                        //     click: function() {

                        //         gifModal(this.custom.link);
                        //     }
                        // }
                    // },
                    data: [<?php
                        foreach ($team_tds as $td) {
                            print '{x:' . $td['time'] . ', y:'. $td['distance'].', custom: {q: "'.$td['quarter'].'", time:"'.$td['t_string'].'", title:"'.$td['title'].'"}},';
                            print "\n";
                        }
                    ?>]
                    }, 
                    <?php $x++; ?>
                    <?php endforeach; ?>
                ]
            });

        </script>
<?php endif; ?>

<div class="py-10" id="opponent">
    <h1 class="font-light text-3xl text-center">TDs by Opponent</h1>
    <div class="flex flex-wrap p-10">
        <div class="w-screen">
          <?php
          $tds_by_opp = [
            'opp' => [],
            'team' => [],
          ];
          foreach ($tds as $team => $team_tds) {
            $t = $team_map[$team];
            foreach ($team_tds as $td) {
              $tds_by_opp['team'][$t][$td['opponent']][] = $td;
              if (isset($tds_by_opp['opp'][$td['opponent']])) {
                $tds_by_opp['opp'][$td['opponent']] += 1;
              }
              else {
                $tds_by_opp['opp'][$td['opponent']] = 1;
              }
            }
            arsort($tds_by_opp['opp']);
          }
          ?>
            <div id="td-by-opp"></div>
            <script>
                Highcharts.chart('td-by-opp', {
                    chart: {
                        type: 'column'
                    },
                    legend: {
                        enabled: false
                    },
                    <?php if ($qb_last_name == 'Favre'): ?>
                        colors: ['<?php print $qb_colors[0];?>', '<?php print $qb_colors[1];?>', '<?php print $qb_colors[2];?>'],
                    <?php else: ?>
                        colors: ['<?php print $qb_colors[0];?>', '<?php print $qb_colors[1];?>'],
                    <?php endif; ?>
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: [
                          <?php foreach($tds_by_opp['opp'] as $opp => $team_tds): ?>
                            '<?php print $team_map[$opp]; ?>',
                          <?php endforeach; ?>
                        ],
                        title: {
                            text: 'Opponent'
                        }
                    },
                    yAxis: {
                        title: '<?php print $qb_last_name; ?> TDs',
                        stackLabels: {
                            enabled: true,
                        }
                    },
                    tooltip: {
                        valueSuffix: ' TDs'
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: false
                            }
                        }
                    },
                    series: [
                      <?php foreach($tds_by_opp['team'] as $team => $team_tds): ?>
                        {
                            name: '<?php print $qb_last_name; ?> TDs (<?php print $team?>)',
                            data: [
                              <?php foreach($tds_by_opp['opp'] as $team => $opp_tds): ?>
                              <?php if (isset($team_tds[$team])) {
                              print count($team_tds[$team]);
                            }
                            else {
                              print '0';
                            }?>,
                              <?php endforeach; ?>
                            ]
                        },
                      <?php endforeach;?>
                    ]
                });
            </script>
        </div>
    </div>
</div>


<div class="py-10 bg-gray-200" id="season">
    <h1 class="font-light text-3xl text-center">TDs by Season</h1>
    <div class="flex flex-wrap p-10">
        <div class="w-screen">
          <?php
          $tds_by_season = [];
          foreach ($json as $td) {
            $tds_by_season[$td['season']][] = $td;
          }
          ksort($tds_by_season);
          $season_avg = count($json) / count($tds_by_season);
          ?>
            <div id="td-by-season"></div>
            <script>
                Highcharts.chart('td-by-season', {
                    chart: {
                        type: 'line',
                        backgroundColor: '#e5e7eb'
                    },
                    legend: {
                        enabled: false
                    },
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: [
                          <?php foreach($tds_by_season as $season => $season_td): ?>
                            '<?php print $season; ?>',
                          <?php endforeach; ?>
                        ],
                        title: {
                            text: 'Season'
                        }
                    },
                    yAxis: {
                        title: '<?php print $qb_last_name; ?> TDs',
                        plotLines: [{
                            color: 'gray',
                            value: <?php print number_format($season_avg, 0); ?>,
                            width: '1',
                            zIndex: 4,
                            dashStyle: 'Dot',
                            label: {
                                text: 'Average: <?php print number_format($season_avg, 0); ?>',
                                align: 'left',
                            }
                        }]
                    },
                    tooltip: {
                        valueSuffix: ' TDs'
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true
                            }
                        }
                    },
                    series: [
                        {
                            name: '<?php print $qb_last_name; ?> TDs',
                            marker: {
                                symbol: 'circle'
                            },
                            data: [
                              <?php foreach ($tds_by_season as $season => $season_td) {
                              print count($tds_by_season[$season]) . ',';
                            }?>
                            ],
                            zoneAxis: 'x',
                          <?php if ($qb_last_name == 'Favre'): ?>
                            zones: [
                                {
                                    value: <?php print qb_seasons($qb_path)?>,
                                    color: '<?php print $qb_colors[0];?>'
                                },
                                {
                                    value: <?php print qb_seasons($qb_path.'2')?>,
                                    color: '<?php print $qb_colors[1];?>'
                                },
                                {
                                    color: '<?php print $qb_colors[2];?>'
                                }
                    ]
                          <?php else: ?>
                            zones: [
                                {
                                    value: <?php print qb_seasons($qb_path)?>,
                                    color: '<?php print $qb_colors[0];?>'
                                },
                                {
                                    color: '<?php print $qb_colors[1];?>'
                                }
                            ]
                          <?php endif; ?>

                        }
                    ]
                });
            </script>
        </div>
    </div>
</div>


<div class="py-10" id="week">
    <h1 class="font-light text-3xl text-center">TDs by Week</h1>
  <?php
  $tds_by_week = [];

  foreach ($json as $td) {
    $tds_by_week[$td['week']][] = $td;
  }
  $week_avg = count($json) / count($tds_by_week);
  ksort($tds_by_week);
  $playoff_tds = 0;
  if (array_key_exists(22, $tds_by_week)) {
    $playoff_weeks = [
      19 => 'Wildcard',
      20 => 'Divisional',
      21 => 'Conference',
      22 => 'Super Bowl',
    ];
  }
  else {
    $playoff_weeks = [
      18 => 'Wildcard',
      19 => 'Divisional',
      20 => 'Conference',
      21 => 'Super Bowl',
    ];
  }
  foreach ($playoff_weeks as $w => $name) {
    if (isset($tds_by_week[$w])) {
      $playoff_tds += count($tds_by_week[$w]);
    }
  }
  ?>
    <div class="flex flex-wrap p-10">
        <p class="pb-10">
          <?php print $qb_name; ?> has thrown <?php print $playoff_tds; ?>
            playoff TDs which is about <?php print number_format(100*($playoff_tds/count($json)), 1); ?>% of his total TDs.
        </p>
        <div class="w-screen">

            <div id="td-by-week"></div>
            <script>
                Highcharts.chart('td-by-week', {
                    chart: {
                        type: 'area'
                    },
                    legend: {
                        enabled: false
                    },
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: [
                          <?php foreach($tds_by_week as $week => $week_td): ?>
                          <?php if ($week < 18): ?>
                            'Week <?php print $week; ?>',
                          <?php else: ?>
                            '<?php print $playoff_weeks[$week]; ?>',
                          <?php endif; ?>
                          <?php endforeach; ?>
                        ],
                        title: {
                            text: 'Week'
                        }
                    },
                    yAxis: {
                        title: '<?php print $qb_last_name; ?> TDs',
                        plotLines: [{
                            color: 'gray',
                            value: <?php print number_format($week_avg, 0); ?>,
                            width: '1',
                            zIndex: 4,
                            dashStyle: 'Dot',
                            label: {
                                text: 'Average: <?php print number_format($week_avg, 0); ?>',
                                align: 'left',
                            }
                        }]
                    },
                    tooltip: {
                        valueSuffix: ' TDs'
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true
                            }
                        }
                    },
                    series: [
                        {
                            name: '<?php print $qb_last_name; ?> TDs',
                            marker: {
                                symbol: 'circle'
                            },
                            data: [
                              <?php foreach ($tds_by_week as $week => $week_td) {
                              print count($tds_by_week[$week]) . ',';
                            }?>
                            ],
                            zoneAxis: 'x',
                            zones: [
                                {
                                    value: <?php print array_key_first($playoff_weeks) - 1; ?>,
                                    color: '<?php print $qb_colors[0];?>'
                                },
                                {
                                    color: '<?php print $qb_colors[1];?>'
                                }
                            ]
                        }
                    ]
                });
            </script>
        </div>
    </div>
</div>

<div class="py-10 bg-gray-200" id="player">
    <h1 class="font-light text-3xl text-center">TDs by Receiver</h1>
    <div class="flex flex-wrap p-10">
      <?php
      $colts_tds_by_player = [];
      $broncos_tds_by_player = [];
      foreach ($json as $td) {
        $tds_by_player[$td['players_involved']][] = $td;
      }
      array_multisort(array_map('count', $tds_by_player), SORT_DESC, $tds_by_player);
      ?>
        <p class="pb-10">
          <?php print $qb_name; ?> has connected
            with <?php print array_key_first($tds_by_player); ?>
            for <?php print count(array_values($tds_by_player)[0]); ?>
            touchdowns.
            He's also thrown TDs to <?php print count($tds_by_player) - 1; ?>
            other players.
        </p>
        <div class="w-screen">

            <div id="td-by-player"></div>
            <script>
                Highcharts.chart('td-by-player', {
                    chart: {
                        plotBackgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false,
                        type: 'pie',
                        backgroundColor: '#e5e7eb'
                    },
                    title: {
                        text: ''
                    },
                    tooltip: {
                        pointFormat: '<b>{point.y} TDs</b> ({point.percentage:.1f} %)'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                format: '<b>{point.name}</b>: {point.y} TDs ({point.percentage:.1f} %)'
                            }
                        }
                    },
                    series: [{
                        name: 'Players',
                        colorByPoint: true,
                        data: [
                          <?php $ones = 0; $twos = 0; $threes = 0; $fours = 0; $fives = 0;
                          foreach ($tds_by_player as $player => $ptds) {
                            if (count($ptds) > 5) {
                              print '{';
                              print 'name: "' . $player . '",';
                              print 'y: ' . count($ptds) . ',';
                              print '},';
                            }
                            else {
                              switch (count($ptds)) {
                                case 1:
                                  $ones++;
                                  break;
                                case 2:
                                  $twos++;
                                  break;
                                case 3:
                                  $threes++;
                                  break;
                                case 4:
                                  $fours++;
                                  break;
                                case 5:
                                  $fives++;
                                  break;
                              }
                            }
                          }?>
                            {
                                name: "<?php print $fives; ?> Players with 5 TDs",
                                y: <?php print $fives * 5; ?>,
                            },
                            {
                                name: "<?php print $fours; ?> Players with 4 TDs",
                                y: <?php print $fours * 4; ?>,
                            },
                            {
                                name: "<?php print $threes; ?> Players with 3 TDs",
                                y: <?php print $threes * 3; ?>,
                            },
                            {
                                name: "<?php print $twos; ?> Players with 2 TDs",
                                y: <?php print $twos * 2; ?>,
                            },
                            {
                                name: "<?php print $ones; ?> Players with 1 TD",
                                y: <?php print $ones; ?>,
                            },
                        ]
                    }]
                });
            </script>
        </div>
    </div>
</div>

<div class="py-10" id="distance">
    <h1 class="font-light text-3xl text-center">TDs by Distance</h1>
  <?php
  $total_distance = 0;
  foreach ($tds as $team => $team_tds) {
    $t = $team_map[$team];
    foreach ($team_tds as $td) {
      $tds_by_dist[$t][$td['yards_gained']][] = $td;
      $total_distance += $td['yards_gained'];
    }
    ksort($tds_by_dist[$t]);
  }

  ?>
    <div class="flex flex-wrap p-10">
        <p class="pb-10">
            <?php $oqb = qb_yards($total_distance); ?>
            If you only count yards gained on passes that went for
            TDs, <?php print $qb_name; ?> has
            accumulated <?php print number_format($total_distance); ?> yards.
            That's more passing yards than <?php print $oqb['q']; ?> (<?php print number_format($oqb['y']); ?>) threw in his
            entire career.
        </p>
        <div class="w-screen">

            <div id="td-by-dist"></div>
            <script>
                Highcharts.chart('td-by-dist', {
                    chart: {
                        type: 'column',
                        backgroundColor: '#fff'
                    },
                    legend: {
                        enabled: false
                    },
                  <?php if ($qb_last_name == 'Favre'): ?>
                    colors: ['<?php print $qb_colors[0];?>', '<?php print $qb_colors[1];?>', '<?php print $qb_colors[2];?>'],
                  <?php else: ?>
                    colors: ['<?php print $qb_colors[0];?>', '<?php print $qb_colors[1];?>'],
                  <?php endif; ?>
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: [
                          <?php for($i = 1; $i < 100; $i++): ?>
                            '<?php print $i; ?>',
                          <?php endfor; ?>
                        ],
                        title: {
                            text: 'Distance'
                        }
                    },
                    yAxis: {
                        title: '<?php print $qb_last_name; ?> TDs',
                        stackLabels: {
                            enabled: true,
                        }
                    },
                    tooltip: {
                        valueSuffix: ' TDs'
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: false
                            }
                        }
                    },
                    series: [
                      <?php foreach($tds_by_dist as $team => $team_tds): ?>
                        {
                            name: '<?php print $qb_last_name; ?> TDs (<?php print $team; ?>)',
                            data: [
                              <?php for($i = 1; $i < 100; $i++): ?>
                              <?php if (isset($tds_by_dist[$team][$i])) {
                              print count($tds_by_dist[$team][$i]);
                            }
                            else {
                              print '0';
                            }?>,
                              <?php endfor; ?>
                            ]
                        },
                      <?php endforeach; ?>
                    ]
                });
            </script>
        </div>
    </div>
</div>
<?php endif; ?>
</body>
</html>
