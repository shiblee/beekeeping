<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Section 21: market competition
$mc_rows = $pdo->query("
  SELECT Q_No_21_Market_Competition AS v, COUNT(*) AS n
  FROM bk_data_part7
  WHERE Q_No_21_Market_Competition != ''
  GROUP BY v
")->fetchAll(PDO::FETCH_ASSOC);

// Normalise by detecting keywords
$mc = ['Price' => 0, 'Quality' => 0, 'Species / Variety' => 0, 'Any Other' => 0];
$mc_total = 0;
foreach($mc_rows as $row){
  $v = $row['v']; $n = (int)$row['n'];
  $mc_total += $n;
  if(stripos($v,'Price') !== false || mb_strpos($v,'????') !== false) $mc['Price'] += $n;
  if(stripos($v,'Quality') !== false || mb_strpos($v,'????????') !== false) $mc['Quality'] += $n;
  if(stripos($v,'Species') !== false || stripos($v,'Variety') !== false) $mc['Species / Variety'] += $n;
  if(stripos($v,'Any Other') !== false || stripos($v,'Other') !== false) $mc['Any Other'] += $n;
}
arsort($mc);
$maxMC = max($mc) ?: 1;

// Section 22: tech awareness
$n_aware = (int)$pdo->query("
  SELECT SUM(CASE WHEN Q_No_22_beekeeping_technology_YES_NO='Yes' THEN 1 ELSE 0 END)
  FROM bk_data_part7
")->fetchColumn();

// Section 22: scheme stats from part8
$s = $pdo->query("
  SELECT
    SUM(CASE WHEN nbm_registered_YES_NO='Yes'            THEN 1 ELSE 0 END) AS nbm_r,
    SUM(CASE WHEN nbm_availed_benefit='Yes'              THEN 1 ELSE 0 END) AS nbm_b,
    SUM(CASE WHEN srkvic_registered_YES_NO='Yes'         THEN 1 ELSE 0 END) AS kvic_r,
    SUM(CASE WHEN srkvic_availed_benefit='Yes'           THEN 1 ELSE 0 END) AS kvic_b,
    SUM(CASE WHEN dbb_registered_YES_NO='Yes'            THEN 1 ELSE 0 END) AS dbb_r,
    SUM(CASE WHEN dbb_availed_benefit='Yes'              THEN 1 ELSE 0 END) AS dbb_b,
    SUM(CASE WHEN FPO_NAFED_registered_YES_NO='Yes'      THEN 1 ELSE 0 END) AS fpo_r,
    SUM(CASE WHEN FPO_NAFED_availed_benefit='Yes'        THEN 1 ELSE 0 END) AS fpo_b,
    SUM(CASE WHEN KVKs_registered_YES_NO='Yes'           THEN 1 ELSE 0 END) AS kvk_r,
    SUM(CASE WHEN KVKs_availed_benefit='Yes'             THEN 1 ELSE 0 END) AS kvk_b,
    SUM(CASE WHEN MIDH_registered_YES_NO='Yes'           THEN 1 ELSE 0 END) AS midh_r,
    SUM(CASE WHEN MIDH_availed_benefit='Yes'             THEN 1 ELSE 0 END) AS midh_b,
    SUM(CASE WHEN SHDM_registered_YES_NO='Yes'           THEN 1 ELSE 0 END) AS shdm_r,
    SUM(CASE WHEN SHDM_availed_benefit='Yes'             THEN 1 ELSE 0 END) AS shdm_b,
    SUM(CASE WHEN DHDM_registered_YES_NO='Yes'           THEN 1 ELSE 0 END) AS dhdm_r,
    SUM(CASE WHEN DHDM_availed_benefit='Yes'             THEN 1 ELSE 0 END) AS dhdm_b,
    SUM(CASE WHEN Sphoorti_MSMEs_registered_YES_NO='Yes' THEN 1 ELSE 0 END) AS msme_r,
    SUM(CASE WHEN Sphoorti_MSMEs_availed_benefit='Yes'   THEN 1 ELSE 0 END) AS msme_b
  FROM bk_data_part8
")->fetch(PDO::FETCH_ASSOC);

$schemes = [
  ['code'=>'DHDM', 'label'=>'DHDM',           'full'=>'District Horticulture Dev. Mission', 'hindi'=>'जिला बागवानी विकास मिशन',         'r'=>(int)$s['dhdm_r'], 'b'=>(int)$s['dhdm_b'], 'color'=>'#1A237E'],
  ['code'=>'NBM',  'label'=>'NBM',             'full'=>'National Beekeeping Mission',        'hindi'=>'राष्ट्रीय मधुमक्खी पालन मिशन',   'r'=>(int)$s['nbm_r'],  'b'=>(int)$s['nbm_b'],  'color'=>'#1565C0'],
  ['code'=>'SHDM', 'label'=>'SHDM',            'full'=>'State Horticulture Dev. Mission',    'hindi'=>'राज्य बागवानी विकास मिशन',        'r'=>(int)$s['shdm_r'], 'b'=>(int)$s['shdm_b'], 'color'=>'#0277BD'],
  ['code'=>'MIDH', 'label'=>'MIDH',            'full'=>'Mission for Integrated Dev. of Horticulture','hindi'=>'एकीकृत बागवानी विकास मिशन','r'=>(int)$s['midh_r'],'b'=>(int)$s['midh_b'], 'color'=>'#0288D1'],
  ['code'=>'FPO',  'label'=>'FPO/NAFED',       'full'=>'Honey Farmer Producer Organisation', 'hindi'=>'शहद किसान उत्पादक संगठन',         'r'=>(int)$s['fpo_r'],  'b'=>(int)$s['fpo_b'],  'color'=>'#4A148C'],
  ['code'=>'KVK',  'label'=>'KVK',             'full'=>'Krishi Vigyan Kendra',               'hindi'=>'कृषि विज्ञान केंद्र',             'r'=>(int)$s['kvk_r'],  'b'=>(int)$s['kvk_b'],  'color'=>'#6A1B9A'],
  ['code'=>'KVIC', 'label'=>'Sweet Rev. KVIC', 'full'=>'Sweet Revolution (KVIC)',            'hindi'=>'"मीठी क्रांति" KVIC',             'r'=>(int)$s['kvic_r'], 'b'=>(int)$s['kvic_b'], 'color'=>'#E65100'],
  ['code'=>'MSME', 'label'=>'Sphoorti MSME',   'full'=>'Sphoorti (MSME)',                   'hindi'=>'(स्फूर्ति) एमएसएमई',              'r'=>(int)$s['msme_r'], 'b'=>(int)$s['msme_b'], 'color'=>'#FF8A65'],
  ['code'=>'DBB',  'label'=>'DBB',             'full'=>'Development of Bee Breeders',        'hindi'=>'मधुमक्खी प्रजनकों का विकास',      'r'=>(int)$s['dbb_r'],  'b'=>(int)$s['dbb_b'],  'color'=>'#2E7D32'],
];
$maxR = max(array_column($schemes,'r')) ?: 1;

function pctSC($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }

$mc_colors = ['Price'=>'#F2A900','Quality'=>'#42A5F5','Species / Variety'=>'#66BB6A','Any Other'=>'#90A4AE'];
?>

<!-- ══ BK MARKET & SCHEMES ══ -->
<div id="p-bkschemes" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#B71C1C">Market &amp; Schemes · बाजार एवं योजनाएँ</span>
      <div class="sec-title">Market Competition &amp; Government Scheme Awareness</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkmc-kpi-row">
    <?php
    $kpis = [
      ['v'=>number_format($mc_total),     'p'=>'respondents',                       'l'=>'Competition Responses', 'h'=>'बाजार प्रतियोगिता',          'c'=>'#B71C1C'],
      ['v'=>number_format($mc['Price']),  'p'=>pctSC($mc['Price'],$mc_total).'%',  'l'=>'Face Price Competition', 'h'=>'मूल्य प्रतिस्पर्धा',          'c'=>'#F2A900'],
      ['v'=>number_format($mc['Quality']),'p'=>pctSC($mc['Quality'],$mc_total).'%','l'=>'Quality Competition',    'h'=>'गुणवत्ता प्रतिस्पर्धा',      'c'=>'#42A5F5'],
      ['v'=>number_format($n_aware),      'p'=>pctSC($n_aware,$total).'%',         'l'=>'Aware of BK Boards',    'h'=>'बोर्ड/आयोग से परिचित',        'c'=>'#1A237E'],
      ['v'=>number_format((int)$s['dhdm_r']), 'p'=>pctSC((int)$s['dhdm_r'],$total).'%', 'l'=>'Registered DHDM (top scheme)', 'h'=>'DHDM में पंजीकृत', 'c'=>'#4A148C'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkmc-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkmc-kv"><?= $k['v'] ?></div>
      <div class="bkmc-kp"><?= $k['p'] ?></div>
      <div class="bkmc-kl"><?= $k['l'] ?></div>
      <div class="bkmc-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkmc-charts">

    <!-- Col 1: Market competition -->
    <div class="bkmc-chart-card">
      <div class="bkmc-card-title">Market Competition Faced</div>
      <div class="bkmc-card-sub">Types of competition (multi-select) — <?= number_format($mc_total) ?> farmers responded</div>

      <?php foreach($mc as $ctype => $cn):
        $bw = (int)round($cn/$maxMC*100);
        $color = $mc_colors[$ctype] ?? '#999';
        $pct = pctSC($cn, $mc_total);
      ?>
      <div class="bkmc-mc-row">
        <div class="bkmc-mc-labels">
          <span class="bkmc-mc-name" style="color:<?= $color ?>"><?= $ctype ?></span>
        </div>
        <div class="bkmc-mc-bar-wrap">
          <div class="bkmc-mc-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <div class="bkmc-mc-meta">
          <span class="bkmc-mc-n" style="color:<?= $color ?>"><?= number_format($cn) ?></span>
          <span class="bkmc-mc-pct"><?= $pct ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Combination breakdown -->
      <div class="bkmc-card-title" style="margin-top:18px">Top Competition Combinations</div>
      <div class="bkmc-card-sub">Most common multi-factor responses</div>
      <?php
      $combos = [
        ['l'=>'Price + Quality + Species', 'n'=>472+192, 'c'=>'#B71C1C'],
        ['l'=>'Price + Quality',           'n'=>361+167, 'c'=>'#E53935'],
        ['l'=>'Price only',                'n'=>255+48,  'c'=>'#EF9A9A'],
        ['l'=>'Price + Species',           'n'=>193+88,  'c'=>'#FFAB40'],
        ['l'=>'Any Other',                 'n'=>26,      'c'=>'#90A4AE'],
      ];
      $maxC = max(array_column($combos,'n'));
      foreach($combos as $cb):
        $bw = (int)round($cb['n']/$maxC*100);
      ?>
      <div class="bkmc-mc-row">
        <div class="bkmc-mc-labels">
          <span class="bkmc-mc-name" style="color:<?= $cb['c'] ?>"><?= $cb['l'] ?></span>
        </div>
        <div class="bkmc-mc-bar-wrap">
          <div class="bkmc-mc-bar" style="width:<?= $bw ?>%;background:<?= $cb['c'] ?>"></div>
        </div>
        <div class="bkmc-mc-meta">
          <span class="bkmc-mc-n" style="color:<?= $cb['c'] ?>"><?= number_format($cb['n']) ?></span>
          <span class="bkmc-mc-pct"><?= pctSC($cb['n'],$mc_total) ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div><!-- col1 -->

    <!-- Col 2 & 3: Scheme registrations -->
    <div class="bkmc-chart-card bkmc-span2">
      <div class="bkmc-card-title">Government Scheme Registration &amp; Benefit Uptake</div>
      <div class="bkmc-card-sub">Farmers registered vs. availed benefit — all 9 schemes · sorted by registration count</div>

      <div class="bkmc-scheme-grid">
        <?php foreach($schemes as $sc):
          $rw = (int)round($sc['r']/$maxR*100);
          $bpct = $sc['r'] > 0 ? round($sc['b']/$sc['r']*100,1) : 0;
          $rpct = pctSC($sc['r'], $total);
        ?>
        <div class="bkmc-scheme-row">
          <div class="bkmc-sch-head">
            <span class="bkmc-sch-code" style="background:<?= $sc['color'] ?>10;color:<?= $sc['color'] ?>;border-color:<?= $sc['color'] ?>"><?= $sc['code'] ?></span>
            <div class="bkmc-sch-info">
              <span class="bkmc-sch-full"><?= $sc['full'] ?></span>
              <span class="bkmc-sch-hindi"><?= $sc['hindi'] ?></span>
            </div>
            <div class="bkmc-sch-nums">
              <span class="bkmc-sch-r" style="color:<?= $sc['color'] ?>"><?= number_format($sc['r']) ?></span>
              <span class="bkmc-sch-rl">registered</span>
            </div>
          </div>
          <!-- Registration bar -->
          <div class="bkmc-sch-bar-wrap">
            <div class="bkmc-sch-bar-r" style="width:<?= $rw ?>%;background:<?= $sc['color'] ?>"></div>
            <?php if($sc['b'] > 0): $bw2 = (int)round($sc['b']/$maxR*100); ?>
            <div class="bkmc-sch-bar-b" style="width:<?= $bw2 ?>%;background:<?= $sc['color'] ?>33"></div>
            <?php endif; ?>
          </div>
          <div class="bkmc-sch-foot">
            <span class="bkmc-sch-rpct"><?= $rpct ?>% of farmers</span>
            <?php if($sc['b'] > 0): ?>
            <span class="bkmc-sch-bpill" style="background:<?= $sc['color'] ?>15;color:<?= $sc['color'] ?>">
              <?= $sc['b'] ?> availed benefit (<?= $bpct ?>% of registered)
            </span>
            <?php else: ?>
            <span class="bkmc-sch-nil">0 availed benefit</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div><!-- /.bkmc-scheme-grid -->
    </div><!-- col2+3 -->

  </div><!-- /.bkmc-charts -->

</div>
