<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    SUM(CASE WHEN isco_beeswax_products_YES_NO='Yes'       THEN 1 ELSE 0 END) AS n_export,
    SUM(CASE WHEN within_state_bw1_YES_NO='Yes'            THEN 1 ELSE 0 END) AS n_within,
    ROUND(AVG(CAST(NULLIF(within_state_bw1_unitkg,'')      AS DECIMAL(14,2))),0) AS avg_within_kg,
    ROUND(AVG(CAST(NULLIF(within_state_bw1_revenuers,'')   AS DECIMAL(16,2))),0) AS avg_within_rev,
    SUM(CASE WHEN within_state_bw1_byself_YES_NO='Yes'     THEN 1 ELSE 0 END) AS n_within_self,
    SUM(CASE WHEN within_state_bw1_is_agency_YES_NO='Yes'  THEN 1 ELSE 0 END) AS n_within_agency,
    SUM(CASE WHEN interstate_bw1_YES_NO='Yes'              THEN 1 ELSE 0 END) AS n_inter,
    ROUND(AVG(CAST(NULLIF(interstate_bw1_unitkg,'')        AS DECIMAL(14,2))),0) AS avg_inter_kg,
    ROUND(AVG(CAST(NULLIF(interstate_bw1_revenuers,'')     AS DECIMAL(16,2))),0) AS avg_inter_rev,
    SUM(CASE WHEN interstate_bw1_byself_YES_NO='Yes'       THEN 1 ELSE 0 END) AS n_inter_self,
    SUM(CASE WHEN interstate_bw1_is_agency_YES_NO='Yes'    THEN 1 ELSE 0 END) AS n_inter_agency,
    SUM(CASE WHEN outof_country_bw1_YES_NO='Yes'           THEN 1 ELSE 0 END) AS n_abroad
  FROM bk_data_part7
")->fetch(PDO::FETCH_ASSOC);

// Top within-state districts
$districts = $pdo->query("
  SELECT within_state_bw1_district AS v, COUNT(*) AS n
  FROM bk_data_part7
  WHERE within_state_bw1_district != '' AND within_state_bw1_YES_NO='Yes'
  GROUP BY v ORDER BY n DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Interstate states — normalise
$states_raw = $pdo->query("
  SELECT interstate_bw1_state AS v, COUNT(*) AS n
  FROM bk_data_part7
  WHERE interstate_bw1_state != '' AND interstate_bw1_YES_NO='Yes'
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

$stateCounts = [];
foreach($states_raw as $sr){
  $raw = $sr['v'];
  if(stripos($raw,'Rajasthan')   !== false) $stateCounts['Rajasthan']    = ($stateCounts['Rajasthan']    ?? 0) + $sr['n'];
  if(stripos($raw,'Haryana')     !== false || stripos($raw,'Hariyana') !== false || stripos($raw,'Harayana') !== false)
                                            $stateCounts['Haryana']      = ($stateCounts['Haryana']      ?? 0) + $sr['n'];
  if(stripos($raw,'Punjab')      !== false || stripos($raw,'Panjab')   !== false)
                                            $stateCounts['Punjab']       = ($stateCounts['Punjab']       ?? 0) + $sr['n'];
  if(stripos($raw,'Madhya Pradesh')!==false||stripos($raw,'MadhyaPradesh')!==false)
                                            $stateCounts['Madhya Pradesh']= ($stateCounts['Madhya Pradesh']??0) + $sr['n'];
  if(stripos($raw,'Gujarat')     !== false) $stateCounts['Gujarat']      = ($stateCounts['Gujarat']      ?? 0) + $sr['n'];
}
arsort($stateCounts);

$n_export = (int)$d['n_export'];
$n_within = (int)$d['n_within'];
$n_inter  = (int)$d['n_inter'];

function pctBX($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }
function lakhBX($n){ return '₹' . round($n/100000, 1) . ' L'; }

$maxDist  = $districts   ? max(array_column($districts,'n')) : 1;
$maxState = $stateCounts ? max($stateCounts)                 : 1;
$maxVol   = max((int)$d['avg_within_kg'], (int)$d['avg_inter_kg']) ?: 1;
?>

<!-- ══ BK BEESWAX EXPORT ══ -->
<div id="p-bkbwexport" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#4A148C">Co-Product Export · सह-उत्पाद निर्यात</span>
      <div class="sec-title">Exporting Co-Product (Beeswax) of Beekeeping</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkbx-kpi-row">
    <?php
    $kpis = [
      ['v'=>$n_export,                          'p'=>pctBX($n_export,$total).'% of farmers', 'l'=>'Beeswax Exporters',   'h'=>'मोम निर्यातक किसान',     'c'=>'#4A148C'],
      ['v'=>$n_within,                          'p'=>pctBX($n_within,$n_export).'% of exporters','l'=>'Within State',    'h'=>'राज्य के भीतर',           'c'=>'#6A1B9A'],
      ['v'=>lakhBX((int)$d['avg_within_rev']),  'p'=>'avg revenue',                          'l'=>'Within-State Revenue','h'=>'औसत राजस्व (राज्य)',     'c'=>'#7B1FA2'],
      ['v'=>$n_inter,                           'p'=>pctBX($n_inter,$n_export).'% of exporters', 'l'=>'Interstate',      'h'=>'अंतरराज्यीय',             'c'=>'#1565C0'],
      ['v'=>lakhBX((int)$d['avg_inter_rev']),   'p'=>'avg revenue',                          'l'=>'Interstate Revenue',  'h'=>'औसत राजस्व (अंतरराज्य)', 'c'=>'#0277BD'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkbx-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkbx-kv"><?= $k['v'] ?></div>
      <div class="bkbx-kp"><?= $k['p'] ?></div>
      <div class="bkbx-kl"><?= $k['l'] ?></div>
      <div class="bkbx-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkbx-charts">

    <!-- Col 1: Within-state -->
    <div class="bkbx-chart-card">
      <div class="bkbx-card-title">Within-State Exports</div>
      <div class="bkbx-card-sub"><?= $n_within ?> farmers · avg <?= number_format((int)$d['avg_within_kg']) ?> kg · <?= lakhBX((int)$d['avg_within_rev']) ?> avg revenue</div>

      <div class="bkbx-mode-row">
        <?php
        $ws_tot  = (int)$d['n_within_self'] + (int)$d['n_within_agency'];
        $ws_spct = $ws_tot > 0 ? round($d['n_within_self']/$ws_tot*100) : 0;
        ?>
        <div class="bkbx-mode-card" style="--mc:#2E7D32">
          <span class="bkbx-mode-v"><?= (int)$d['n_within_self'] ?></span>
          <span class="bkbx-mode-l">By Self · स्वयं द्वारा</span>
          <span class="bkbx-mode-p"><?= $ws_spct ?>%</span>
        </div>
        <div class="bkbx-mode-card" style="--mc:#F57F17">
          <span class="bkbx-mode-v"><?= (int)$d['n_within_agency'] ?></span>
          <span class="bkbx-mode-l">Via Agency · एजेंसी द्वारा</span>
          <span class="bkbx-mode-p"><?= 100-$ws_spct ?>%</span>
        </div>
      </div>

      <div class="bkbx-sub-title">Top Destination Districts</div>
      <?php foreach($districts as $dist):
        $bw = (int)round($dist['n']/$maxDist*100);
        $dlabel = ucwords(strtolower($dist['v']));
      ?>
      <div class="bkbx-dest-row">
        <span class="bkbx-dest-name"><?= htmlspecialchars($dlabel) ?></span>
        <div class="bkbx-dest-bar-wrap">
          <div class="bkbx-dest-bar" style="width:<?= $bw ?>%;background:#7B1FA2"></div>
        </div>
        <span class="bkbx-dest-n"><?= $dist['n'] ?></span>
      </div>
      <?php endforeach; ?>
    </div><!-- col1 -->

    <!-- Col 2: Interstate + abroad note + volume comparison -->
    <div class="bkbx-chart-card">
      <div class="bkbx-card-title">Interstate Exports</div>
      <div class="bkbx-card-sub"><?= $n_inter ?> farmers · avg <?= number_format((int)$d['avg_inter_kg']) ?> kg · <?= lakhBX((int)$d['avg_inter_rev']) ?> avg revenue</div>

      <div class="bkbx-mode-row">
        <?php
        $is_tot  = (int)$d['n_inter_self'] + (int)$d['n_inter_agency'];
        $is_spct = $is_tot > 0 ? round($d['n_inter_self']/$is_tot*100) : 0;
        ?>
        <div class="bkbx-mode-card" style="--mc:#2E7D32">
          <span class="bkbx-mode-v"><?= (int)$d['n_inter_self'] ?></span>
          <span class="bkbx-mode-l">By Self · स्वयं द्वारा</span>
          <span class="bkbx-mode-p"><?= $is_spct ?>%</span>
        </div>
        <div class="bkbx-mode-card" style="--mc:#F57F17">
          <span class="bkbx-mode-v"><?= (int)$d['n_inter_agency'] ?></span>
          <span class="bkbx-mode-l">Via Agency · एजेंसी द्वारा</span>
          <span class="bkbx-mode-p"><?= 100-$is_spct ?>%</span>
        </div>
      </div>

      <div class="bkbx-sub-title">Top Destination States</div>
      <?php foreach($stateCounts as $sname => $sn):
        $bw = (int)round($sn/$maxState*100);
      ?>
      <div class="bkbx-dest-row">
        <span class="bkbx-dest-name"><?= htmlspecialchars($sname) ?></span>
        <div class="bkbx-dest-bar-wrap">
          <div class="bkbx-dest-bar" style="width:<?= $bw ?>%;background:#0277BD"></div>
        </div>
        <span class="bkbx-dest-n"><?= $sn ?></span>
      </div>
      <?php endforeach; ?>

      <!-- International note -->
      <div class="bkbx-abroad-box">
        <div class="bkbx-abroad-icon">🌍</div>
        <div class="bkbx-abroad-text">
          <strong>International Exports</strong>
          <span>No surveyed farmer exported beeswax outside India</span>
        </div>
        <div class="bkbx-abroad-zero">0</div>
      </div>

      <!-- Volume comparison -->
      <div class="bkbx-sub-title" style="margin-top:14px">Avg Volume Comparison</div>
      <?php
      $vols = [
        ['l'=>'Within State','kg'=>(int)$d['avg_within_kg'],'c'=>'#7B1FA2'],
        ['l'=>'Interstate',  'kg'=>(int)$d['avg_inter_kg'], 'c'=>'#0277BD'],
      ];
      foreach($vols as $vl):
        $vw = (int)round($vl['kg']/$maxVol*100);
      ?>
      <div class="bkbx-vol-row">
        <span class="bkbx-vol-label"><?= $vl['l'] ?></span>
        <div class="bkbx-vol-bar-wrap">
          <div class="bkbx-vol-bar" style="width:<?= $vw ?>%;background:<?= $vl['c'] ?>"></div>
        </div>
        <span class="bkbx-vol-val" style="color:<?= $vl['c'] ?>"><?= number_format($vl['kg']) ?> kg</span>
      </div>
      <?php endforeach; ?>
    </div><!-- col2 -->

  </div><!-- /.bkbx-charts -->

</div>
