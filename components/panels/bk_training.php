<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Part6: overall training + pre-beekeeping
$p6 = $pdo->query("
  SELECT
    SUM(CASE WHEN is_taken_training_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_trained,
    SUM(CASE WHEN Activities_related_beekeeping_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_pre
  FROM bk_data_part6
")->fetch(PDO::FETCH_ASSOC);

// Part7: stage counts, avg days
$p7 = $pdo->query("
  SELECT
    SUM(CASE WHEN During_beekeeping_activities_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_during,
    SUM(CASE WHEN Post_harvest_activities_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_post,
    SUM(CASE WHEN Other_activities_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_other,
    ROUND(AVG(CAST(NULLIF(pre_beekeeping_traning_days,'') AS DECIMAL(6,1))),1) AS days_pre,
    ROUND(AVG(CAST(NULLIF(during_beekeeping_traning_days,'') AS DECIMAL(6,1))),1) AS days_during,
    ROUND(AVG(CAST(NULLIF(post_harvest_traning_days,'') AS DECIMAL(6,1))),1) AS days_post,
    ROUND(AVG(CAST(NULLIF(other_traning_days,'') AS DECIMAL(6,1))),1) AS days_other,
    SUM(CASE WHEN pre_beekeeping_traning_type='Formal' THEN 1 ELSE 0 END) AS pre_formal,
    SUM(CASE WHEN pre_beekeeping_traning_type='Informal' THEN 1 ELSE 0 END) AS pre_informal,
    SUM(CASE WHEN during_beekeeping_traning_type='Formal' THEN 1 ELSE 0 END) AS dur_formal,
    SUM(CASE WHEN during_beekeeping_traning_type='Informal' THEN 1 ELSE 0 END) AS dur_informal,
    SUM(CASE WHEN post_harvest_traning_type='Formal' THEN 1 ELSE 0 END) AS post_formal,
    SUM(CASE WHEN post_harvest_traning_type='Informal' THEN 1 ELSE 0 END) AS post_informal
  FROM bk_data_part7
")->fetch(PDO::FETCH_ASSOC);

// Pre training topics
$pre_topics = $pdo->query("
  SELECT Q_No_16_Pre_beekeeping_traning_name AS v, COUNT(*) AS n
  FROM bk_data_part6
  WHERE Q_No_16_Pre_beekeeping_traning_name != ''
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

// During training topics
$dur_topics = $pdo->query("
  SELECT Q_No_16_During_beekeeping_traning_name AS v, COUNT(*) AS n
  FROM bk_data_part7
  WHERE Q_No_16_During_beekeeping_traning_name != ''
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Post training topics
$post_topics = $pdo->query("
  SELECT Q_No_16_Post_harvest_traning_name AS v, COUNT(*) AS n
  FROM bk_data_part7
  WHERE Q_No_16_Post_harvest_traning_name != ''
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

$n_trained  = (int)$p6['n_trained'];
$n_pre      = (int)$p6['n_pre'];
$n_during   = (int)$p7['n_during'];
$n_post     = (int)$p7['n_post'];
$n_other    = (int)$p7['n_other'];

function pctT($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }

// Topic short labels
$topic_labels = [
  'Best Production Practices of Co-Products related'                    => 'Co-Products',
  'Best Production Practices of Beekeeping related'                     => 'BK Practices',
  'Effective control of Bees Pests and Diseases related'                => 'Pest & Disease',
  'Effective Management of Apiary related'                              => 'Apiary Mgmt',
  'Effective Marketing and Branding/Grading/Packaging of Honey related' => 'Marketing',
  'Others'                                                              => 'Others',
];

$topic_colors = [
  'Co-Products'  => '#F2A900',
  'BK Practices' => '#42A5F5',
  'Pest & Disease'=> '#66BB6A',
  'Apiary Mgmt'  => '#FF8A65',
  'Marketing'    => '#AB47BC',
  'Others'       => '#90A4AE',
];

// Stages for funnel
$stages = [
  ['label'=>'Trained',          'hindi'=>'प्रशिक्षित',                'n'=>$n_trained, 'color'=>'#2E7D32', 'days'=>null],
  ['label'=>'Pre-Beekeeping',   'hindi'=>'पूर्व-मधुमक्खी पालन',      'n'=>$n_pre,     'color'=>'#F2A900', 'days'=>(float)$p7['days_pre']],
  ['label'=>'During Beekeeping','hindi'=>'मधुमक्खी पालन के दौरान',   'n'=>$n_during,  'color'=>'#42A5F5', 'days'=>(float)$p7['days_during']],
  ['label'=>'Post-Harvest',     'hindi'=>'कटाई के बाद',               'n'=>$n_post,    'color'=>'#66BB6A', 'days'=>(float)$p7['days_post']],
  ['label'=>'Other Activities', 'hindi'=>'अन्य गतिविधियाँ',           'n'=>$n_other,   'color'=>'#FF8A65', 'days'=>(float)$p7['days_other']],
];
$maxN = $n_trained;
?>

<!-- ══ BK TRAINING ══ -->
<div id="p-bktrain" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#1B5E20">Training · प्रशिक्षण</span>
      <div class="sec-title">Training in Beekeeping</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkt-kpi-row">
    <?php
    $kpis = [
      ['v'=>number_format($n_trained),  'p'=>pctT($n_trained,$total).'%',  'l'=>'Trained Farmers',    'h'=>'प्रशिक्षित किसान',        'c'=>'#2E7D32'],
      ['v'=>number_format($n_pre),      'p'=>pctT($n_pre,$n_trained).'%',  'l'=>'Pre-Beekeeping',     'h'=>'पूर्व-मधुमक्खी पालन',     'c'=>'#F2A900'],
      ['v'=>number_format($n_during),   'p'=>pctT($n_during,$n_trained).'%','l'=>'During Beekeeping', 'h'=>'पालन के दौरान',           'c'=>'#42A5F5'],
      ['v'=>number_format($n_post),     'p'=>pctT($n_post,$n_trained).'%', 'l'=>'Post-Harvest',       'h'=>'कटाई के बाद',             'c'=>'#66BB6A'],
      ['v'=>$p7['days_pre'].' days',    'p'=>'avg duration',               'l'=>'Pre Training Days',  'h'=>'पूर्व प्रशिक्षण अवधि',   'c'=>'#E65100'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkt-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkt-kv"><?= $k['v'] ?></div>
      <div class="bkt-kp"><?= $k['p'] ?></div>
      <div class="bkt-kl"><?= $k['l'] ?></div>
      <div class="bkt-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkt-charts">

    <!-- Col 1: Stage funnel -->
    <div class="bkt-chart-card">
      <div class="bkt-card-title">Training Uptake by Stage</div>
      <div class="bkt-card-sub">% of trained farmers receiving each type</div>
      <?php foreach($stages as $st):
        $bw = $maxN > 0 ? round($st['n']/$maxN*100,1) : 0;
        $pp = pctT($st['n'], $total);
      ?>
      <div class="bkt-stage-row">
        <div class="bkt-stage-labels">
          <span class="bkt-stage-name"><?= $st['label'] ?></span>
          <span class="bkt-stage-hindi"><?= $st['hindi'] ?></span>
        </div>
        <div class="bkt-stage-bar-wrap">
          <div class="bkt-stage-bar" style="width:<?= $bw ?>%;background:<?= $st['color'] ?>"></div>
        </div>
        <div class="bkt-stage-meta">
          <span class="bkt-stage-n" style="color:<?= $st['color'] ?>"><?= number_format($st['n']) ?></span>
          <span class="bkt-stage-pct"><?= $pp ?>%</span>
          <?php if($st['days'] !== null && $st['days'] > 0): ?>
          <span class="bkt-stage-days"><?= $st['days'] ?>d</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Formal vs informal summary -->
      <div class="bkt-fi-title">Formal vs Informal</div>
      <?php
      $fi_stages = [
        ['l'=>'Pre',    'f'=>(int)$p7['pre_formal'],  'i'=>(int)$p7['pre_informal']],
        ['l'=>'During', 'f'=>(int)$p7['dur_formal'],  'i'=>(int)$p7['dur_informal']],
        ['l'=>'Post',   'f'=>(int)$p7['post_formal'], 'i'=>(int)$p7['post_informal']],
      ];
      foreach($fi_stages as $fi):
        $ftot = $fi['f'] + $fi['i'];
        $fpct = $ftot > 0 ? round($fi['f']/$ftot*100) : 0;
      ?>
      <div class="bkt-fi-row">
        <span class="bkt-fi-label"><?= $fi['l'] ?></span>
        <div class="bkt-fi-bar-wrap">
          <div class="bkt-fi-bar-f" style="width:<?= $fpct ?>%"></div>
          <div class="bkt-fi-bar-i" style="width:<?= 100-$fpct ?>%"></div>
        </div>
        <span class="bkt-fi-pct"><?= $fpct ?>% Formal</span>
      </div>
      <?php endforeach; ?>
    </div><!-- col1 -->

    <!-- Col 2: Training topics — pre + during -->
    <div class="bkt-chart-card">
      <div class="bkt-card-title">Training Content — Pre-Beekeeping</div>
      <div class="bkt-card-sub">Topics covered in pre-beekeeping training</div>
      <?php
      $pre_total_resp = array_sum(array_column($pre_topics,'n'));
      foreach($pre_topics as $t):
        $label = $topic_labels[$t['v']] ?? $t['v'];
        $color = $topic_colors[$label] ?? '#90A4AE';
        $bw = $pre_total_resp > 0 ? round($t['n']/$pre_total_resp*100,1) : 0;
      ?>
      <div class="bkt-topic-row">
        <div class="bkt-topic-labels">
          <span class="bkt-topic-name"><?= $label ?></span>
        </div>
        <div class="bkt-topic-bar-wrap">
          <div class="bkt-topic-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <div class="bkt-topic-meta">
          <span class="bkt-topic-n" style="color:<?= $color ?>"><?= number_format($t['n']) ?></span>
          <span class="bkt-topic-pct"><?= $bw ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkt-card-title" style="margin-top:18px">Training Content — During Beekeeping</div>
      <div class="bkt-card-sub">Topics covered in during-beekeeping training</div>
      <?php
      $dur_total_resp = array_sum(array_column($dur_topics,'n'));
      foreach($dur_topics as $t):
        $label = $topic_labels[$t['v']] ?? $t['v'];
        $color = $topic_colors[$label] ?? '#90A4AE';
        $bw = $dur_total_resp > 0 ? round($t['n']/$dur_total_resp*100,1) : 0;
      ?>
      <div class="bkt-topic-row">
        <div class="bkt-topic-labels">
          <span class="bkt-topic-name"><?= $label ?></span>
        </div>
        <div class="bkt-topic-bar-wrap">
          <div class="bkt-topic-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <div class="bkt-topic-meta">
          <span class="bkt-topic-n" style="color:<?= $color ?>"><?= number_format($t['n']) ?></span>
          <span class="bkt-topic-pct"><?= $bw ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div><!-- col2 -->

    <!-- Col 3: Post-harvest topics + duration comparison -->
    <div class="bkt-chart-card">
      <div class="bkt-card-title">Training Content — Post-Harvest</div>
      <div class="bkt-card-sub">Topics covered in post-harvest training</div>
      <?php
      $post_total_resp = array_sum(array_column($post_topics,'n'));
      foreach($post_topics as $t):
        $label = $topic_labels[$t['v']] ?? $t['v'];
        $color = $topic_colors[$label] ?? '#90A4AE';
        $bw = $post_total_resp > 0 ? round($t['n']/$post_total_resp*100,1) : 0;
      ?>
      <div class="bkt-topic-row">
        <div class="bkt-topic-labels">
          <span class="bkt-topic-name"><?= $label ?></span>
        </div>
        <div class="bkt-topic-bar-wrap">
          <div class="bkt-topic-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <div class="bkt-topic-meta">
          <span class="bkt-topic-n" style="color:<?= $color ?>"><?= number_format($t['n']) ?></span>
          <span class="bkt-topic-pct"><?= $bw ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Training duration by stage -->
      <div class="bkt-card-title" style="margin-top:18px">Avg Training Duration by Stage</div>
      <div class="bkt-card-sub">Average days per training stage</div>
      <?php
      $dur_data = [
        ['l'=>'Pre-Beekeeping',   'd'=>(float)$p7['days_pre'],    'c'=>'#F2A900'],
        ['l'=>'During Beekeeping','d'=>(float)$p7['days_during'], 'c'=>'#42A5F5'],
        ['l'=>'Post-Harvest',     'd'=>(float)$p7['days_post'],   'c'=>'#66BB6A'],
        ['l'=>'Other Activities', 'd'=>(float)$p7['days_other'],  'c'=>'#FF8A65'],
      ];
      $maxDays = max(array_column($dur_data,'d'));
      foreach($dur_data as $dd):
        $dw = $maxDays > 0 ? round($dd['d']/$maxDays*100) : 0;
      ?>
      <div class="bkt-dur-row">
        <span class="bkt-dur-label"><?= $dd['l'] ?></span>
        <div class="bkt-dur-bar-wrap">
          <div class="bkt-dur-bar" style="width:<?= $dw ?>%;background:<?= $dd['c'] ?>"></div>
        </div>
        <span class="bkt-dur-val" style="color:<?= $dd['c'] ?>"><?= $dd['d'] ?> days</span>
      </div>
      <?php endforeach; ?>

      <!-- Topic legend -->
      <div class="bkt-legend">
        <?php foreach($topic_colors as $lname => $lcol): ?>
        <div class="bkt-leg-item">
          <span class="bkt-leg-dot" style="background:<?= $lcol ?>"></span>
          <span><?= $lname ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div><!-- col3 -->

  </div><!-- /.bkt-charts -->

</div>
