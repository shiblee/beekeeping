<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    SUM(CASE WHEN is_exporting_product_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_export,
    SUM(CASE WHEN within_state_p1_YES_NO='Yes'      THEN 1 ELSE 0 END) AS n_within,
    ROUND(AVG(CAST(NULLIF(within_state_p1_unitkg,'')    AS DECIMAL(14,2))),0) AS avg_within_kg,
    ROUND(AVG(CAST(NULLIF(within_state_p1_revenuers,'') AS DECIMAL(16,2))),0) AS avg_within_rev,
    SUM(CASE WHEN within_state_p1_byself_YES_NO='Yes'   THEN 1 ELSE 0 END) AS n_within_self,
    SUM(CASE WHEN within_state_is_agency_YES_NO='Yes'   THEN 1 ELSE 0 END) AS n_within_agency,
    SUM(CASE WHEN interstate_p1_YES_NO='Yes'            THEN 1 ELSE 0 END) AS n_inter,
    ROUND(AVG(CAST(NULLIF(interstate_p1_unitkg,'')      AS DECIMAL(14,2))),0) AS avg_inter_kg,
    ROUND(AVG(CAST(NULLIF(interstate_p1_revenuers,'')   AS DECIMAL(16,2))),0) AS avg_inter_rev,
    SUM(CASE WHEN interstate_p1_byself_YES_NO='Yes'     THEN 1 ELSE 0 END) AS n_inter_self,
    SUM(CASE WHEN interstate_p1_is_agency_YES_NO='Yes'  THEN 1 ELSE 0 END) AS n_inter_agency,
    SUM(CASE WHEN outof_country_p1_YES_NO='Yes'         THEN 1 ELSE 0 END) AS n_abroad
  FROM bk_data_part7
")->fetch(PDO::FETCH_ASSOC);

// Top within-state districts
$districts = $pdo->query("
  SELECT within_state_p1_district AS v, COUNT(*) AS n
  FROM bk_data_part7
  WHERE within_state_p1_district != '' AND within_state_p1_YES_NO='Yes'
  GROUP BY v ORDER BY n DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Interstate states (raw - may have combined entries)
$states_raw = $pdo->query("
  SELECT interstate_p1_state AS v, COUNT(*) AS n
  FROM bk_data_part7
  WHERE interstate_p1_state != '' AND interstate_p1_YES_NO='Yes'
  GROUP BY v ORDER BY n DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Normalise state names (combine Punjab/Panjab, Rajasthan, etc.)
$stateCounts = [];
foreach($states_raw as $sr){
  $raw = $sr['v'];
  if(stripos($raw,'Rajasthan') !== false) $stateCounts['Rajasthan'] = ($stateCounts['Rajasthan'] ?? 0) + $sr['n'];
  if(stripos($raw,'Punjab') !== false || stripos($raw,'Panjab') !== false) $stateCounts['Punjab'] = ($stateCounts['Punjab'] ?? 0) + $sr['n'];
  if(stripos($raw,'Haryana') !== false || stripos($raw,'Hariyana') !== false) $stateCounts['Haryana'] = ($stateCounts['Haryana'] ?? 0) + $sr['n'];
  if(stripos($raw,'Uttarakhand') !== false) $stateCounts['Uttarakhand'] = ($stateCounts['Uttarakhand'] ?? 0) + $sr['n'];
  if(stripos($raw,'Delhi') !== false) $stateCounts['Delhi'] = ($stateCounts['Delhi'] ?? 0) + $sr['n'];
}
arsort($stateCounts);

$n_export = (int)$d['n_export'];
$n_within = (int)$d['n_within'];
$n_inter  = (int)$d['n_inter'];

function pctEX($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }
function lakhEX($n){ return '₹' . round($n/100000, 1) . ' L'; }

$maxDist  = $districts  ? max(array_column($districts,'n'))  : 1;
$maxState = $stateCounts ? max($stateCounts) : 1;
?>

<!-- ══ BK EXPORT ══ -->
<div id="p-bkexport" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#1A237E">Export · निर्यात</span>
      <div class="sec-title">Exporting Beekeeping Produce</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkex-kpi-row">
    <?php
    $kpis = [
      ['v'=>$n_export,                           'p'=>pctEX($n_export,$total).'% of farmers', 'l'=>'Exporters',           'h'=>'निर्यातक किसान',       'c'=>'#1A237E'],
      ['v'=>$n_within,                           'p'=>pctEX($n_within,$n_export).'% of exporters','l'=>'Within State',    'h'=>'राज्य के भीतर',        'c'=>'#283593'],
      ['v'=>lakhEX((int)$d['avg_within_rev']),   'p'=>'avg revenue',                          'l'=>'Within-State Revenue','h'=>'औसत राजस्व (राज्य)',  'c'=>'#3949AB'],
      ['v'=>$n_inter,                            'p'=>pctEX($n_inter,$n_export).'% of exporters', 'l'=>'Interstate',      'h'=>'अंतरराज्यीय',          'c'=>'#1565C0'],
      ['v'=>lakhEX((int)$d['avg_inter_rev']),    'p'=>'avg revenue',                          'l'=>'Interstate Revenue',  'h'=>'औसत राजस्व (अंतरराज्य)','c'=>'#0277BD'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkex-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkex-kv"><?= $k['v'] ?></div>
      <div class="bkex-kp"><?= $k['p'] ?></div>
      <div class="bkex-kl"><?= $k['l'] ?></div>
      <div class="bkex-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkex-charts">

    <!-- Col 1: Within-state exports -->
    <div class="bkex-chart-card">
      <div class="bkex-card-title">Within-State Exports</div>
      <div class="bkex-card-sub"><?= $n_within ?> farmers · avg <?= number_format((int)$d['avg_within_kg']) ?> kg · <?= lakhEX((int)$d['avg_within_rev']) ?> avg revenue</div>

      <!-- Self vs Agency -->
      <div class="bkex-mode-row">
        <?php
        $ws_tot = (int)$d['n_within_self'] + (int)$d['n_within_agency'];
        $ws_spct = $ws_tot > 0 ? round($d['n_within_self']/$ws_tot*100) : 0;
        ?>
        <div class="bkex-mode-card" style="--mc:#2E7D32">
          <span class="bkex-mode-v"><?= (int)$d['n_within_self'] ?></span>
          <span class="bkex-mode-l">By Self · स्वयं द्वारा</span>
          <span class="bkex-mode-p"><?= $ws_spct ?>%</span>
        </div>
        <div class="bkex-mode-card" style="--mc:#F57F17">
          <span class="bkex-mode-v"><?= (int)$d['n_within_agency'] ?></span>
          <span class="bkex-mode-l">Via Agency · एजेंसी द्वारा</span>
          <span class="bkex-mode-p"><?= 100-$ws_spct ?>%</span>
        </div>
      </div>

      <!-- Top districts -->
      <div class="bkex-sub-title">Top Destination Districts</div>
      <?php foreach($districts as $dist):
        $bw = (int)round($dist['n']/$maxDist*100);
        $dlabel = ucwords(strtolower($dist['v']));
      ?>
      <div class="bkex-dest-row">
        <span class="bkex-dest-name"><?= htmlspecialchars($dlabel) ?></span>
        <div class="bkex-dest-bar-wrap">
          <div class="bkex-dest-bar" style="width:<?= $bw ?>%;background:#3949AB"></div>
        </div>
        <span class="bkex-dest-n"><?= $dist['n'] ?></span>
      </div>
      <?php endforeach; ?>
    </div><!-- col1 -->

    <!-- Col 2: Interstate exports + abroad note -->
    <div class="bkex-chart-card">
      <div class="bkex-card-title">Interstate Exports</div>
      <div class="bkex-card-sub"><?= $n_inter ?> farmers · avg <?= number_format((int)$d['avg_inter_kg']) ?> kg · <?= lakhEX((int)$d['avg_inter_rev']) ?> avg revenue</div>

      <!-- Self vs Agency -->
      <div class="bkex-mode-row">
        <?php
        $is_tot = (int)$d['n_inter_self'] + (int)$d['n_inter_agency'];
        $is_spct = $is_tot > 0 ? round($d['n_inter_self']/$is_tot*100) : 0;
        ?>
        <div class="bkex-mode-card" style="--mc:#2E7D32">
          <span class="bkex-mode-v"><?= (int)$d['n_inter_self'] ?></span>
          <span class="bkex-mode-l">By Self · स्वयं द्वारा</span>
          <span class="bkex-mode-p"><?= $is_spct ?>%</span>
        </div>
        <div class="bkex-mode-card" style="--mc:#F57F17">
          <span class="bkex-mode-v"><?= (int)$d['n_inter_agency'] ?></span>
          <span class="bkex-mode-l">Via Agency · एजेंसी द्वारा</span>
          <span class="bkex-mode-p"><?= 100-$is_spct ?>%</span>
        </div>
      </div>

      <!-- Top states -->
      <div class="bkex-sub-title">Top Destination States</div>
      <?php foreach($stateCounts as $sname => $sn):
        $bw = (int)round($sn/$maxState*100);
      ?>
      <div class="bkex-dest-row">
        <span class="bkex-dest-name"><?= htmlspecialchars($sname) ?></span>
        <div class="bkex-dest-bar-wrap">
          <div class="bkex-dest-bar" style="width:<?= $bw ?>%;background:#0277BD"></div>
        </div>
        <span class="bkex-dest-n"><?= $sn ?></span>
      </div>
      <?php endforeach; ?>

      <!-- International / Abroad note -->
      <div class="bkex-abroad-box">
        <div class="bkex-abroad-icon">🌍</div>
        <div class="bkex-abroad-text">
          <strong>International Exports</strong>
          <span>No surveyed farmer reported exporting outside India</span>
        </div>
        <div class="bkex-abroad-zero">0</div>
      </div>

      <!-- Volume comparison -->
      <div class="bkex-sub-title" style="margin-top:14px">Avg Volume Comparison</div>
      <div class="bkex-vol-row">
        <span class="bkex-vol-label">Within State</span>
        <div class="bkex-vol-bar-wrap">
          <div class="bkex-vol-bar" style="width:<?= (int)round((int)$d['avg_within_kg']/(int)$d['avg_inter_kg']*100) ?>%;background:#3949AB"></div>
        </div>
        <span class="bkex-vol-val"><?= number_format((int)$d['avg_within_kg']) ?> kg</span>
      </div>
      <div class="bkex-vol-row">
        <span class="bkex-vol-label">Interstate</span>
        <div class="bkex-vol-bar-wrap">
          <div class="bkex-vol-bar" style="width:100%;background:#0277BD"></div>
        </div>
        <span class="bkex-vol-val"><?= number_format((int)$d['avg_inter_kg']) ?> kg</span>
      </div>
    </div><!-- col2 -->

  </div><!-- /.bkex-charts -->

</div>
