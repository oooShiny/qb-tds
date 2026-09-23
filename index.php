<?php
require_once('helpers.php');
$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$qb_path = $_GET['qb'] ?? NULL;
if (!empty($qb_path)) {
  $tds_data = file_get_contents('json/' . $qb_path . '-tds.json');
  $json = json_decode($tds_data, TRUE);
  $qb_name = ucwords(str_replace('-', ' ', $qb_path));
  $qba = explode(' ', $qb_name);
  $qb_last_name = end($qba);

  $qb_colors = qb_colors($qb_path);

  $tds = [];
  foreach ($json as $item) {
    $tds[$item['team']][] = $item;
  }
}
$team_map = team_map();
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
      'title' => $last_name,
    ];
    if (empty($qb_path)) {
      $data = file_get_contents('json/' . $file);
      $json = json_decode($data, TRUE);
      $count[$n] = count($json);
      foreach ($json as $item) {
        $tds[$n][] = $item;
      }
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
    <script defer data-site="qbtds.com" src="https://stats.br0wn.net/js/script.js"></script>
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
<div class="bg-gray-500 fixed p-2 shadow-lg text-white w-screen z-50">
    <ul class="flex flex-wrap justify-around">
      <?php arsort($menu); ?>
      <?php foreach ($menu as $item): ?>
          <li class="hover:text-blue-300 hover:underline"><a
                      href="<?php print $item['url'] ?>"><?php print $item['title'] ?></a>
          </li>
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
    <div class="pb-10 m-auto lg:w-8/12">
        <p>
            The touchdown pass is arguably the most exciting advancement ever to
            grace the football gridiron. The QBs on this website are the best to
            do
            it, as evidenced by their inclusion on the top 10 list of TD passes.
            Select one of the QBs from the table below or the nav above to see
            their
            TD passes broken down by different variables.
        </p>
    </div>
    <?php
    arsort($count);
    $qb_display_names = [];
    foreach ($count as $qb => $_) {
        $qbn = ucwords(str_replace('-', ' ', $qb));
        $qbarr = explode(' ', $qbn);
        $last = end($qbarr);
        if ($last == 'Manning') {
            $last = substr($qbn, 0, 1) . '. ' . $last;
        }
        $qb_display_names[$qb] = $last;
    }
    ?>
    <div class="pb-10 m-auto lg:w-8/12">
        <div id="td-rankings"></div>
    </div>
    <script>
        var urlMap = {
          <?php foreach ($count as $qb => $_): ?>
          '<?php print addslashes($qb_display_names[$qb]); ?>': '?qb=<?php print $qb; ?>',
          <?php endforeach; ?>
        };
        Highcharts.chart('td-rankings', {
            chart: { type: 'bar' },
            title: { text: 'TD Pass Rankings' },
            xAxis: {
                categories: [
                  <?php foreach ($count as $qb => $_): ?>
                  '<?php print addslashes($qb_display_names[$qb]); ?>',
                  <?php endforeach; ?>
                ],
                title: { text: null },
                labels: {
                    formatter: function() {
                        // Leading slash: Highcharts strips bare relative hrefs as unsafe
                        return '<a href="/' + urlMap[this.value] + '">' + this.value + '</a>';
                    },
                    style: { cursor: 'pointer', textDecoration: 'underline' }
                }
            },
            yAxis: {
                min: 0,
                title: { text: 'Total TD Passes', align: 'high' },
                labels: { overflow: 'justify' }
            },
            tooltip: { valueSuffix: ' TDs' },
            plotOptions: {
                bar: {
                    colorByPoint: true,
                    colors: [
                      <?php foreach ($count as $qb => $_): ?>
                      '<?php print qb_primary_display_color($qb); ?>',
                      <?php endforeach; ?>
                    ],
                    dataLabels: { enabled: true },
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function() {
                                window.location = urlMap[this.category];
                            }
                        }
                    }
                }
            },
            legend: { enabled: false },
            series: [{
                name: 'TD Passes',
                data: [
                  <?php foreach ($count as $qb => $c): ?>
                  <?php print $c; ?>,
                  <?php endforeach; ?>
                ]
            }]
        });
    </script>

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
                           href="/search.php?qb=<?php print $qb_path ?>">Search</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="py-10 flex flex-wrap p-10">
        <p><?php print $qb_name; ?> has thrown <?php print count($json); ?>
            touchdown passes in his NFL career. Below you will find every single
            one
            of them, organized in different ways.</p>
    </div>
<?php endif; ?>
<?php if ($qb_path !== 'brett-favre' && $qb_path !== 'dan-marino'): ?>
    <div class="py-10" id="td-scatter"></div>
<?php

$q_times = [
  '1' => 45,
  '2' => 30,
  '3' => 15,
  '4' => 0,
  'OT' => -15,
];
$scatter = [];
foreach ($tds as $team => $team_tds) {
  foreach ($team_tds as $td) {
    if (array_key_exists('seconds', $td) && $td['seconds'] && $td['seconds'] !== 'x') {
      $mins = $q_times[$td['quarter']];
      $mins += $td['minutes'];
      $secs = $td['seconds'];
      $time = ($mins * 60) + $secs;
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
        'title' => $td['yards_gained'] . ' TD pass to ' . $td['players_involved'] . ' vs ' . $td['opponent'] . ' (' . $td['season'] . ')',
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
                text: 'Every <?php print $qb_last_name ?? ''; ?> TD by time vs distance'
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
              <?php $x = 0; ?>
              <?php foreach ($scatter as $team => $team_tds): ?>
                {
                    name: '<?php print $team; ?> TDs',
                    color: <?php if (isset($qb_colors[$x])): ?>
                    <?php print "'" . $qb_colors[$x] . "',"; ?>
                    <?php else: ?>
                    <?php $qb_colors = qb_colors($team);  ?>
                        {
                            radialGradient: {cx: 0.5, cy: 0.5, r: .8},
                            stops: [
                                [0, '<?php print $qb_colors[0];?>'],
                                [1, '<?php print $qb_colors[1];?>']
                            ]
                        },
                  <?php endif;?>
                    // point: {
                    // events: {
                    //     click: function() {

                    //         gifModal(this.custom.link);
                    //     }
                    // }
                    // },
                    data: [<?php
                      foreach ($team_tds as $td) {
                        print '{x:' . $td['time'] . ', y:' . $td['distance'] . ', custom: {q: "' . $td['quarter'] . '", time:"' . $td['t_string'] . '", title:"' . $td['title'] . '"}},';
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
            if (isset($team_map[$team])) {
              $t = $team_map[$team];
            }
            else {
              $t = $team;
            }
            foreach ($team_tds as $td) {
              $tds_by_opp['team'][$t][$team_map[$td['opponent']]][] = $td;
              if (isset($tds_by_opp['opp'][$team_map[$td['opponent']]])) {
                $tds_by_opp['opp'][$team_map[$td['opponent']]] += 1;
              }
              else {
                $tds_by_opp['opp'][$team_map[$td['opponent']]] = 1;
              }
            }
            arsort($tds_by_opp['opp']);
          }
          ?>
            <div id="td-by-opp"></div>
            <script>
                Highcharts.chart('td-by-opp', {
                    chart: {
                        type: 'column',
                        zoomType: 'x'
                    },
                    legend: {
                        enabled: false
                    },
                  <?php if (isset($qb_last_name)): ?>
                  <?php if ($qb_last_name == 'Favre' || $qb_path == 'aaron-rodgers'): ?>
                    colors: ['<?php print $qb_colors[0];?>', '<?php print $qb_colors[1];?>', '<?php print $qb_colors[2];?>'],
                  <?php else: ?>
                    colors: ['<?php print $qb_colors[0];?>', '<?php print $qb_colors[1];?>'],
                  <?php endif; ?>
                  <?php else: ?>
                    //  colors: [
                  <?php //foreach ($tds_by_opp['team'] as $team => $team_tds): ?>
                    <!--  --><?php //$qb_colors = qb_colors($team);  ?>
                    //      '<?php //print $qb_colors[0];?>//', '<?php //print $qb_colors[1];?>//',
                    //  <?php //endforeach; ?>
                    //  ],
                  <?php endif; ?>
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: [
                          <?php foreach($tds_by_opp['opp'] as $opp => $team_tds): ?>
                            '<?php print $opp; ?>',
                          <?php endforeach; ?>
                        ],
                        title: {
                            text: 'Opponent — drag to zoom'
                        }
                    },
                    yAxis: {
                        title: '<?php print $qb_last_name ?? ''; ?> TDs',
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
                      <?php if (empty($qb_path)): ?>
                        {
                            name: 'All QBs Combined',
                            colorByPoint: false,
                            color: '#4B6FA5',
                            data: [
                              <?php foreach ($tds_by_opp['opp'] as $opp => $n): ?>
                              <?php print $n; ?>,
                              <?php endforeach; ?>
                            ]
                        }
                      <?php else: ?>
                      <?php foreach($tds_by_opp['team'] as $team => $team_tds): ?>
                        {
                            name: '<?php print $qb_last_name ?? $team; ?> TDs',
                            data: [
                              <?php foreach($tds_by_opp['opp'] as $t => $opp_tds): ?>
                              <?php if (isset($team_tds[$t])) {
                              print count($team_tds[$t]);
                            }
                            else {
                              print '0';
                            }?>,
                              <?php endforeach; ?>
                            ]
                        },
                      <?php endforeach;?>
                      <?php endif; ?>
                    ]
                });
            </script>
        </div>
    </div>
</div>

<?php if (!empty($qb_path)): ?>
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
                              <?php if ($qb_last_name == 'Favre' || $qb_path == 'aaron-rodgers'): ?>
                                zones: [
                                    {
                                        value: <?php print qb_seasons($qb_path)?>,
                                        color: '<?php print $qb_colors[0];?>'
                                    },
                                    {
                                        value: <?php print qb_seasons($qb_path . '2')?>,
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
<?php endif; ?>

<div class="py-10" id="week">
    <h1 class="font-light text-3xl text-center"><?php print $qb_path ? 'TDs by Week' : 'TDs by Career Year'; ?></h1>
  <?php
  $tds_by_week = [];

  if ($qb_path) {
    foreach ($json as $td) {
      $tds_by_week[$td['week']][] = $td;
    }
    $week_avg = count($json) / count($tds_by_week);
    ksort($tds_by_week, SORT_NUMERIC);
  }
  else {
    foreach ($tds as $q => $qtds) {
      foreach ($qtds as $td) {
        $tds_by_week[$q][$td['week']][] = $td;
        $tds_by_week['week'][$td['week']][] = $td;
      }
    }
    ksort($tds_by_week['week'], SORT_NUMERIC);
    $week_avg = array_sum(array_map('count', $tds_by_week['week'])) / count($tds_by_week['week']);
  }


  $playoff_tds = 0;
  $playoff_weeks = [
    19 => 'Wildcard',
    20 => 'Divisional',
    21 => 'Conference',
    22 => 'Super Bowl',
  ];
  foreach ($playoff_weeks as $w => $name) {
    if (isset($tds_by_week[$w])) {
      $playoff_tds += count($tds_by_week[$w]);
    }
  }
  ?>
    <div class="flex flex-wrap p-10">
      <?php if ($qb_path): ?>
          <p class="pb-10">
            <?php print $qb_name; ?> has thrown <?php print $playoff_tds; ?>
              playoff TDs which is
              about <?php print number_format(100 * ($playoff_tds / count($json)), 1); ?>
              % of his total TDs.
          </p>
      <?php endif; ?>
        <?php if ($qb_path): ?>
        <div class="w-screen">

            <div id="td-by-week"></div>
            <script>
                Highcharts.chart('td-by-week', {
                    chart: {
                        type: 'area',
                        zoomType: 'x'
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
                              <?php if (!isset($playoff_weeks[$week])): ?>
                                'Week <?php print $week; ?>',
                              <?php else: ?>
                                '<?php print $playoff_weeks[$week]; ?>',
                              <?php endif; ?>
                          <?php endforeach; ?>
                        ],
                        title: {
                            text: 'Week — drag to zoom'
                        }
                    },
                    yAxis: {
                        title: '<?php print $qb_last_name ?? ''; ?> TDs',
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
                            name: '<?php print $qb_last_name ?? ''; ?> TDs',
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
        <?php else: ?>
        <?php
        // Chart B: Career year comparison — regular season only
        $career_year_data = [];
        $max_career_years = 0;
        foreach ($tds as $qb => $qtds) {
            $seasons = [];
            foreach ($qtds as $td) {
                if ((int)$td['week'] <= 18) {
                    $seasons[$td['season']][] = $td;
                }
            }
            if (empty($seasons)) continue;
            ksort($seasons);
            $all_seasons = array_keys($seasons);
            $first = (int)$all_seasons[0];
            $years = [];
            foreach ($all_seasons as $s) {
                $years[(int)$s - $first + 1] = count($seasons[$s]);
            }
            if (!empty($years)) {
                $max_career_years = max($max_career_years, max(array_keys($years)));
            }
            $career_year_data[$qb] = $years;
        }
        ?>
        <div class="w-screen">
            <div id="td-career-year"></div>
            <script>
                Highcharts.chart('td-career-year', {
                    chart: { type: 'line', zoomType: 'x' },
                    title: { text: 'TDs by Career Year' },
                    subtitle: { text: 'Regular season only (Week \u2264 18)' },
                    xAxis: {
                        categories: [
                          <?php for ($y = 1; $y <= $max_career_years; $y++): ?>
                          'Year <?php print $y; ?>',
                          <?php endfor; ?>
                        ],
                        title: { text: 'Career Year' }
                    },
                    yAxis: {
                        title: { text: 'TD Passes' },
                        min: 0
                    },
                    tooltip: { shared: false, valueSuffix: ' TDs' },
                    legend: {
                        enabled: true,
                        layout: 'horizontal',
                        align: 'center',
                        verticalAlign: 'bottom'
                    },
                    plotOptions: {
                        line: { connectNulls: false }
                    },
                    series: [
                      <?php foreach ($career_year_data as $qb => $years): ?>
                      {
                          name: '<?php print addslashes($qb_display_names[$qb]); ?>',
                          color: '<?php print qb_primary_display_color($qb); ?>',
                          data: [
                            <?php for ($y = 1; $y <= $max_career_years; $y++): ?>
                            <?php print isset($years[$y]) ? $years[$y] : 'null'; ?>,
                            <?php endfor; ?>
                          ]
                      },
                      <?php endforeach; ?>
                    ]
                });
            </script>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="py-10 bg-gray-200" id="player">
    <h1 class="font-light text-3xl text-center">TDs by Receiver</h1>
    <div class="flex flex-wrap p-10">
      <?php
      if ($qb_path) {
        foreach ($json as $td) {
          $tds_by_player[$td['players_involved']][] = $td;
        }
        array_multisort(array_map('count', $tds_by_player), SORT_DESC, $tds_by_player);
      }
      else {
        foreach ($tds as $q => $qtds) {
            foreach ($qtds as $td) {
              $tds_by_player[$td['players_involved']][] = $td;
            }
        }
        array_multisort(array_map('count', $tds_by_player), SORT_DESC, $tds_by_player);

      }
      ?>
      <?php if ($qb_path): ?>
        <p class="pb-10">
          <?php print $qb_name; ?> has connected
            with <?php print array_key_first($tds_by_player); ?>
            for <?php print count(array_values($tds_by_player)[0]); ?>
            touchdowns.
            He's also thrown TDs to <?php print count($tds_by_player) - 1; ?>
            other players.
        </p>
        <?php endif; ?>
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
                          <?php if (empty($qb_path)):
                          // Home page: >80 TDs = individual slice; 1-80 TDs = 20-wide buckets
                          $buckets = [];
                          foreach ($tds_by_player as $player => $ptds) {
                            $c = count($ptds);
                            if ($c > 80) {
                              print '{name: "' . addslashes($player) . '", y: ' . $c . '},';
                            } else {
                              $d = intdiv($c - 1, 20); // 0=1-20, 1=21-40, 2=41-60, 3=61-80
                              if (!isset($buckets[$d])) {
                                $buckets[$d] = ['lo' => $d * 20 + 1, 'hi' => $d * 20 + 20, 'count' => 0, 'total' => 0];
                              }
                              $buckets[$d]['count']++;
                              $buckets[$d]['total'] += $c;
                            }
                          }
                          krsort($buckets);
                          foreach ($buckets as $info) {
                            $label = $info['count'] . ' Receivers with ' . $info['lo'] . '-' . $info['hi'] . ' TDs';
                            print '{name: "' . $label . '", y: ' . $info['total'] . '},';
                          }
                          else:
                          $ones = 0; $twos = 0; $threes = 0; $fours = 0; $fives = 0;
                          $sixes = 0; $sevens = 0; $eights = 0; $nines = 0; $tens = 0;
                          foreach ($tds_by_player as $player => $ptds) {
                            if (count($ptds) > 10) {
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
                                case 6:
                                  $sixes++;
                                  break;
                                case 7:
                                  $sevens++;
                                  break;
                                case 8:
                                  $eights++;
                                  break;
                                case 9:
                                  $nines++;
                                  break;
                                case 10:
                                  $tens++;
                                  break;
                              }
                            }
                          }
                          endif; ?>
                          <?php if (!empty($qb_path)): ?>
                            {
                                name: "<?php print $tens; ?> Players with 10 TDs",
                                y: <?php print $tens * 5; ?>,
                            },
                            {
                                name: "<?php print $nines; ?> Players with 9 TDs",
                                y: <?php print $nines * 5; ?>,
                            },
                            {
                                name: "<?php print $eights; ?> Players with 8 TDs",
                                y: <?php print $eights * 5; ?>,
                            },
                            {
                                name: "<?php print $sevens; ?> Players with 7 TDs",
                                y: <?php print $sevens * 5; ?>,
                            },
                            {
                                name: "<?php print $sixes; ?> Players with 6 TDs",
                                y: <?php print $sixes * 5; ?>,
                            },
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
                          <?php endif; ?>
                        ]
                    }]
                });
            </script>
        </div>
    </div>
</div>

<?php if (empty($qb_path)): ?>
<?php
// Chart E: Regular season vs. playoff breakdown
$reg_vs_playoff = [];
foreach ($count as $qb => $_) {
    $reg = $post = 0;
    foreach ($tds[$qb] as $td) {
        (int)$td['week'] <= 18 ? $reg++ : $post++;
    }
    $reg_vs_playoff[$qb] = [$reg, $post];
}
?>
<div class="py-10" id="reg-playoff">
    <h1 class="font-light text-3xl text-center">Regular Season vs. Playoffs</h1>
    <div class="flex flex-wrap p-10">
        <div class="w-screen">
            <div id="td-reg-playoff"></div>
            <script>
                // [name, regular season TDs, playoff TDs] per QB
                var regPlayoffRows = [
                  <?php foreach ($reg_vs_playoff as $qb => $counts): ?>
                  ['<?php print addslashes($qb_display_names[$qb]); ?>', <?php print $counts[0]; ?>, <?php print $counts[1]; ?>],
                  <?php endforeach; ?>
                ];

                // Re-sort bars by the total of whichever series are visible
                function sortRegPlayoff(chart) {
                    var visible = chart.series.filter(function(s) { return s.visible; });
                    if (!visible.length) return;
                    var total = function(row) {
                        return visible.reduce(function(sum, s) { return sum + row[s.index + 1]; }, 0);
                    };
                    var rows = regPlayoffRows.slice().sort(function(a, b) { return total(b) - total(a); });
                    chart.xAxis[0].setCategories(rows.map(function(r) { return r[0]; }), false);
                    // Only reorder visible series: setData on a hidden series drops its
                    // bars, and they aren't redrawn when it's shown again. A hidden
                    // series gets reordered here once its show event fires.
                    visible.forEach(function(s) {
                        s.setData(rows.map(function(r) { return r[s.index + 1]; }), false);
                    });
                    chart.redraw();
                }

                Highcharts.chart('td-reg-playoff', {
                    chart: { type: 'bar' },
                    title: { text: 'Regular Season vs. Playoff TDs' },
                    xAxis: {
                        categories: [
                          <?php foreach ($reg_vs_playoff as $qb => $_): ?>
                          '<?php print addslashes($qb_display_names[$qb]); ?>',
                          <?php endforeach; ?>
                        ],
                        title: { text: null }
                    },
                    yAxis: {
                        min: 0,
                        title: { text: 'TD Passes' }
                    },
                    tooltip: { valueSuffix: ' TDs' },
                    plotOptions: {
                        bar: { dataLabels: { enabled: true } },
                        series: {
                            events: {
                                // Defer until Highcharts finishes its own show/hide redraw
                                show: function() { setTimeout(sortRegPlayoff, 0, this.chart); },
                                hide: function() { setTimeout(sortRegPlayoff, 0, this.chart); }
                            }
                        }
                    },
                    legend: { reversed: true },
                    series: [{
                        name: 'Regular Season',
                        color: '#4B6FA5',
                        data: [
                          <?php foreach ($reg_vs_playoff as $qb => $counts): ?>
                          <?php print $counts[0]; ?>,
                          <?php endforeach; ?>
                        ]
                    }, {
                        name: 'Playoffs',
                        color: '#C0392B',
                        data: [
                          <?php foreach ($reg_vs_playoff as $qb => $counts): ?>
                          <?php print $counts[1]; ?>,
                          <?php endforeach; ?>
                        ]
                    }]
                });
            </script>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="py-10" id="distance">
    <h1 class="font-light text-3xl text-center">TDs by Distance</h1>
  <?php
  $total_distance = 0;
  if ($qb_path) {
    foreach ($tds as $team => $team_tds) {
      $t = $team_map[$team];
      foreach ($team_tds as $td) {
        $tds_by_dist[$t][$td['yards_gained']][] = $td;
        $total_distance += $td['yards_gained'];
      }
      ksort($tds_by_dist[$t]);
    }
  }
  else {
    foreach ($tds as $q => $qtds) {
        foreach ($qtds as $td) {
            $tds_by_dist[$q][$td['yards_gained']][] = $td;
            $total_distance += $td['yards_gained'];
          }
      ksort($tds_by_dist);
    }
  }

  ?>
    <div class="flex flex-wrap p-10">
        <p class="pb-10">
          <?php $oqb = qb_yards($total_distance); ?>
            If you only count yards gained on passes that went for
            TDs, <?php print $qb_name ?? 'the top 10 QBs'; ?> has
            accumulated <?php print number_format($total_distance); ?> yards.
            That's more passing yards than <?php print $oqb['q']; ?>
            (<?php print number_format($oqb['y']); ?>) threw in his
            entire career.
        </p>
        <?php if ($qb_path): ?>
        <div class="w-screen">

            <div id="td-by-dist"></div>
            <script>
                Highcharts.chart('td-by-dist', {
                    chart: {
                        type: 'column',
                        backgroundColor: '#fff',
                        zoomType: 'x',
                        scrollablePlotArea: {
                            minWidth: 2500,
                            scrollPositionX: 0
                        }
                    },
                    legend: {
                        enabled: false
                    },
                  <?php if ($qb_last_name == 'Favre' || $qb_path == 'aaron-rodgers'): ?>
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
                            text: 'Distance (yards) — scroll to explore, drag to zoom'
                        }
                    },
                    yAxis: {
                        title: '<?php print $qb_last_name ?? ''; ?> TDs',
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
                            name: '<?php print $qb_last_name ?? $team; ?> TDs (<?php print $team; ?>)',
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
        <?php else: ?>
        <?php
        // Chart C: Normalized TD distance profiles
        $dist_buckets = [
            ['1-5 yds',   1,  5],
            ['6-10 yds',  6, 10],
            ['11-20 yds', 11, 20],
            ['21-30 yds', 21, 30],
            ['31-50 yds', 31, 50],
            ['51+ yds',   51, 99],
        ];
        $dist_profile = [];
        foreach ($tds as $qb => $qtds) {
            $total = count($qtds);
            $by_yard = [];
            foreach ($qtds as $td) { $by_yard[(int)$td['yards_gained']] = ($by_yard[(int)$td['yards_gained']] ?? 0) + 1; }
            foreach ($dist_buckets as $bucket) {
                [$label, $lo, $hi] = $bucket;
                $n = 0;
                for ($y = $lo; $y <= $hi; $y++) $n += $by_yard[$y] ?? 0;
                $dist_profile[$qb][$label] = round(100 * $n / $total, 1);
            }
        }
        $bucket_labels = array_column($dist_buckets, 0);
        ?>
        <div class="w-screen">
            <div id="td-dist-profile"></div>
            <script>
                Highcharts.chart('td-dist-profile', {
                    chart: { type: 'column' },
                    title: { text: 'TD Distance Profiles by QB' },
                    subtitle: { text: 'Normalized \u2014 removes total volume differences' },
                    xAxis: {
                        categories: ['1-5 yds', '6-10 yds', '11-20 yds', '21-30 yds', '31-50 yds', '51+ yds'],
                        title: { text: 'Pass Distance' }
                    },
                    yAxis: {
                        title: { text: 'Percentage of TDs (%)' },
                        labels: { format: '{value}%' }
                    },
                    tooltip: { valueSuffix: '%' },
                    plotOptions: {
                        column: { grouping: true, dataLabels: { enabled: false } }
                    },
                    legend: { enabled: true },
                    series: [
                      <?php foreach ($dist_profile as $qb => $profile): ?>
                      {
                          name: '<?php print addslashes($qb_display_names[$qb]); ?>',
                          color: '<?php print qb_primary_display_color($qb); ?>',
                          data: [
                            <?php foreach ($bucket_labels as $label): ?>
                            <?php print $profile[$label]; ?>,
                            <?php endforeach; ?>
                          ]
                      },
                      <?php endforeach; ?>
                    ]
                });
            </script>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php //endif; ?>
</body>
</html>
