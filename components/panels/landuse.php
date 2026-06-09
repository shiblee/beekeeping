<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$counts = $pdo->query("
  SELECT
    SUM(CASE WHEN Land_Use_Details_YES_NO='Yes'           THEN 1 ELSE 0 END) AS has_land,
    SUM(CASE WHEN Land_Use_Details_Ownland_YES_NO='Yes'   THEN 1 ELSE 0 END) AS own,
    SUM(CASE WHEN Land_Use_Details_LeasedOut_YES_NO='Yes' THEN 1 ELSE 0 END) AS leased_out,
    SUM(CASE WHEN Land_Use_Details_Leased_IN_YES_NO='Yes' THEN 1 ELSE 0 END) AS leased_in,
    SUM(CASE WHEN Land_Use_Details_Fallow_YES_NO='Yes'    THEN 1 ELSE 0 END) AS fallow
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

function landTypeData($pdo, $cond, $aCol, $iCol, $nCol) {
  return $pdo->query("
    SELECT COUNT(*) AS n,
      ROUND(SUM(CAST(NULLIF($aCol,'') AS DECIMAL(10,2))),1)  AS area,
      ROUND(SUM(CAST(NULLIF($iCol,'') AS DECIMAL(10,2))),1)  AS irr,
      ROUND(SUM(CAST(NULLIF($nCol,'') AS DECIMAL(10,2))),1)  AS non_irr,
      ROUND(AVG(CAST(NULLIF($aCol,'') AS DECIMAL(10,2))),1)  AS avg_area
    FROM bk_data_part1 WHERE $cond
  ")->fetch(PDO::FETCH_ASSOC);
}

$own = landTypeData($pdo,"Land_Use_Details_Ownland_YES_NO='Yes'",  "Own_land_Area",       "Own_land_Area_Irrigated",    "Own_land_Area_Non_Irrigated");
$lo  = landTypeData($pdo,"Land_Use_Details_LeasedOut_YES_NO='Yes'","leased_out_area",     "leased_out_irrigated",        "leased_out_non_irrigated");
$li  = landTypeData($pdo,"Land_Use_Details_Leased_IN_YES_NO='Yes'","leased_in_area",      "leased_in_irrigated",         "leased_in_non_irrigated");
$fa  = landTypeData($pdo,"Land_Use_Details_Fallow_YES_NO='Yes'",  "fallow_wasteland_area","fallow_wasteland_irrigated",  "fallow_wasteland_non_irrigated");

function irrSources($pdo, $col) {
  return $pdo->query("
    SELECT CASE
        WHEN $col LIKE '%Tube%' AND $col LIKE '%Private%' THEN 'Tube Well (Private)'
        WHEN $col LIKE '%Tube%' THEN 'Tube Well (Govt)'
        WHEN $col LIKE '%Pump%' THEN 'Pumping Set'
        WHEN $col LIKE '%Canal%' THEN 'Canal'
        ELSE 'Pond / Well'
      END AS src, COUNT(*) AS n
    FROM bk_data_part1 WHERE $col!='' GROUP BY src ORDER BY n DESC
  ")->fetchAll(PDO::FETCH_ASSOC);
}

$ownSrc = irrSources($pdo,"Own_land_Area_Irrigation_Source");
$loSrc  = irrSources($pdo,"leased_out_irrigation_source");
$liSrc  = irrSources($pdo,"leased_in_irrigation_source");

$srcColors = [
  'Tube Well (Private)'=>'#F2A900','Tube Well (Govt)'=>'#81C784',
  'Pumping Set'=>'#64B5F6','Canal'=>'#4DB6AC','Pond / Well'=>'#CE93D8',
];

function irrPct($irr,$non){$d=$irr+$non;return $d>0?min(100,(int)round($irr/$d*100)):0;}

$rent = $pdo->query("SELECT
    ROUND(AVG(CAST(NULLIF(Leased_Out_Rent,'') AS DECIMAL(10,2))),0) AS lo_rent,
    ROUND(AVG(CAST(NULLIF(Leased_In_Rent,'') AS DECIMAL(10,2))),0)  AS li_rent
  FROM bk_data_part1")->fetch(PDO::FETCH_ASSOC);

$totalArea    = $own['area']+$lo['area']+$li['area']+$fa['area'];
$totalIrr     = $own['irr'] +$lo['irr'] +$li['irr'] +$fa['irr'];
$globalIrrPct = $totalArea > 0 ? round($totalIrr/$totalArea*100) : 0;
$areaMax      = max($own['area'],$lo['area'],$li['area'],$fa['area']);

$types = [
  ['label'=>'Own Land',         'hindi'=>'अपनी भूमि',         'icon'=>'🏡','color'=>'#E89B00','light'=>'#FFF8E6','cnt'=>$counts['own'],       'd'=>$own,'src'=>$ownSrc,'rent'=>null],
  ['label'=>'Leased Out',       'hindi'=>'बटाई पर दी गयी',   'icon'=>'📤','color'=>'#9C27B0','light'=>'#F8F0FF','cnt'=>$counts['leased_out'],'d'=>$lo, 'src'=>$loSrc, 'rent'=>$rent['lo_rent']?'₹'.number_format($rent['lo_rent']).' / Bigha':null],
  ['label'=>'Leased In',        'hindi'=>'बटाई पर ली गयी',   'icon'=>'📥','color'=>'#1565C0','light'=>'#E8F4FF','cnt'=>$counts['leased_in'], 'd'=>$li, 'src'=>$liSrc, 'rent'=>$rent['li_rent']?'₹'.number_format($rent['li_rent']).' / Bigha':null],
  ['label'=>'Fallow / Wasteland','hindi'=>'परती / बंजर भूमि','icon'=>'🌿','color'=>'#2E7D32','light'=>'#F0FFF0','cnt'=>$counts['fallow'],    'd'=>$fa, 'src'=>[],     'rent'=>null],
];
?>

<!-- ══ LAND USE ══ -->
<div id="p-landuse" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#43A047">Land Use Profile · भूमि उपयोग विवरण</span>
      <div class="sec-title">Land Use Details of Beekeeper Households</div>
    </div>
  </div>

  <!-- KPI Strip -->
  <div class="lu2-kpi-row">
    <div class="lu2-kpi" style="--lc:#43A047">
      <div class="lu2-kpi-top"><span class="lu2-kpi-ico">🌾</span><span class="lu2-kpi-val"><?= number_format($counts['has_land']) ?></span></div>
      <div class="lu2-kpi-lbl">Have Land Data</div>
      <div class="lu2-kpi-sub"><?= round($counts['has_land']/$total*100) ?>% of surveyed</div>
    </div>
    <div class="lu2-kpi" style="--lc:#E89B00">
      <div class="lu2-kpi-top"><span class="lu2-kpi-ico">🏡</span><span class="lu2-kpi-val"><?= number_format($counts['own']) ?></span></div>
      <div class="lu2-kpi-lbl">Own Land Holders</div>
      <div class="lu2-kpi-sub"><?= number_format($own['area']) ?> Bigha total</div>
    </div>
    <div class="lu2-kpi" style="--lc:#1565C0">
      <div class="lu2-kpi-top"><span class="lu2-kpi-ico">📥</span><span class="lu2-kpi-val"><?= number_format($counts['leased_in']) ?></span></div>
      <div class="lu2-kpi-lbl">Leased In</div>
      <div class="lu2-kpi-sub"><?= number_format($li['area']) ?> Bigha · avg <?= $li['avg_area'] ?></div>
    </div>
    <div class="lu2-kpi" style="--lc:#9C27B0">
      <div class="lu2-kpi-top"><span class="lu2-kpi-ico">📤</span><span class="lu2-kpi-val"><?= number_format($counts['leased_out']) ?></span></div>
      <div class="lu2-kpi-lbl">Leased Out</div>
      <div class="lu2-kpi-sub"><?= number_format($lo['area']) ?> Bigha · avg <?= $lo['avg_area'] ?></div>
    </div>
    <div class="lu2-kpi" style="--lc:#1976D2">
      <div class="lu2-kpi-top"><span class="lu2-kpi-ico">💧</span><span class="lu2-kpi-val"><?= $globalIrrPct ?>%</span></div>
      <div class="lu2-kpi-lbl">Overall Irrigated</div>
      <div class="lu2-kpi-sub"><?= number_format($totalIrr) ?> of <?= number_format($totalArea) ?> Bigha</div>
    </div>
    <div class="lu2-kpi" style="--lc:#2E7D32">
      <div class="lu2-kpi-top"><span class="lu2-kpi-ico">🌿</span><span class="lu2-kpi-val"><?= number_format($counts['fallow']) ?></span></div>
      <div class="lu2-kpi-lbl">Fallow / Wasteland</div>
      <div class="lu2-kpi-sub"><?= number_format($fa['area']) ?> Bigha total</div>
    </div>
  </div>

  <!-- 2×2 Type Cards -->
  <div class="lu2-grid">
  <?php foreach($types as $t):
    $d       = $t['d'];
    $ip      = irrPct($d['irr'], $d['non_irr']);
    $nip     = 100 - $ip;
    $bw      = $areaMax > 0 ? round($d['area']/$areaMax*100) : 0;
    $srcTot  = array_sum(array_column($t['src'],'n'));
  ?>
  <div class="lu2-card" style="--cc:<?= $t['color'] ?>;--cl:<?= $t['light'] ?>">

    <!-- Card header -->
    <div class="lu2-card-hdr">
      <div class="lu2-card-icon"><?= $t['icon'] ?></div>
      <div class="lu2-card-titles">
        <div class="lu2-card-name"><?= $t['label'] ?></div>
        <div class="lu2-card-hindi"><?= $t['hindi'] ?></div>
      </div>
      <div class="lu2-card-pill" style="background:var(--cc)"><?= number_format($t['cnt']) ?> Farmers</div>
    </div>

    <!-- 4 stat boxes -->
    <div class="lu2-stat-grid">
      <div class="lu2-stat-box" style="border-color:var(--cc)">
        <div class="lu2-stat-n" style="color:var(--cc)"><?= number_format($d['area']) ?></div>
        <div class="lu2-stat-l">Total Bigha</div>
      </div>
      <div class="lu2-stat-box">
        <div class="lu2-stat-n"><?= $d['avg_area'] ?></div>
        <div class="lu2-stat-l">Avg / Farmer</div>
      </div>
      <div class="lu2-stat-box" style="border-color:#1976D2">
        <div class="lu2-stat-n" style="color:#1565C0"><?= number_format($d['irr']) ?></div>
        <div class="lu2-stat-l">💧 Irrigated Bigha</div>
      </div>
      <div class="lu2-stat-box" style="border-color:#C62828">
        <div class="lu2-stat-n" style="color:#C62828"><?= number_format($d['non_irr']) ?></div>
        <div class="lu2-stat-l">🌦 Non-Irrigated</div>
      </div>
    </div>

    <!-- Area scale bar -->
    <div class="lu2-scale-wrap">
      <div class="lu2-scale-label">Area Scale <span>(relative to Own Land)</span></div>
      <div class="lu2-scale-track">
        <div class="lu2-scale-fill" style="width:<?= $bw ?>%;background:var(--cc)"></div>
      </div>
      <div class="lu2-scale-num"><?= number_format($d['area']) ?> Bigha</div>
    </div>

    <!-- Irrigation split -->
    <?php if($d['irr'] > 0 || $d['non_irr'] > 0): ?>
    <div class="lu2-irr-wrap">
      <div class="lu2-sec-label">Irrigation Split</div>
      <div class="lu2-irr-bar">
        <?php if($ip > 0): ?><div class="lu2-irr-seg irr2" style="width:<?= $ip ?>%"></div><?php endif; ?>
        <?php if($nip > 0): ?><div class="lu2-irr-seg non2" style="width:<?= $nip ?>%"></div><?php endif; ?>
      </div>
      <div class="lu2-irr-legend">
        <div class="lu2-irr-leg-item">
          <span class="lu2-irr-dot" style="background:#1565C0"></span>
          <span class="lu2-irr-pct"><?= $ip ?>%</span>
          <span class="lu2-irr-txt">Irrigated</span>
          <span class="lu2-irr-bigha"><?= number_format($d['irr']) ?> Bigha</span>
        </div>
        <?php if($nip > 0): ?>
        <div class="lu2-irr-leg-item">
          <span class="lu2-irr-dot" style="background:#C62828"></span>
          <span class="lu2-irr-pct"><?= $nip ?>%</span>
          <span class="lu2-irr-txt">Rain-fed</span>
          <span class="lu2-irr-bigha"><?= number_format($d['non_irr']) ?> Bigha</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Sources of irrigation -->
    <?php if(!empty($t['src'])): ?>
    <div class="lu2-src-wrap">
      <div class="lu2-sec-label">Sources of Irrigation</div>
      <?php foreach($t['src'] as $s):
        $sp  = $srcTot > 0 ? round($s['n']/$srcTot*100) : 0;
        $col = $srcColors[$s['src']] ?? '#A8A8A8';
      ?>
      <div class="lu2-src-row">
        <span class="lu2-src-dot" style="background:<?= $col ?>"></span>
        <span class="lu2-src-name"><?= htmlspecialchars($s['src']) ?></span>
        <div class="lu2-src-bar-wrap">
          <div class="lu2-src-bar-fill" style="width:<?= $sp ?>%;background:<?= $col ?>"></div>
        </div>
        <span class="lu2-src-pct"><?= $sp ?>%</span>
        <span class="lu2-src-count"><?= number_format($s['n']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Rent info -->
    <?php if($t['rent']): ?>
    <div class="lu2-rent">
      <span class="lu2-rent-ico">💰</span>
      <span class="lu2-rent-lbl">Average Rent</span>
      <span class="lu2-rent-val" style="color:var(--cc)"><?= $t['rent'] ?></span>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>
  </div><!-- end .lu2-grid -->

</div>
