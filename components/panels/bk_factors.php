<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Parse multi-select helper
function parseCounts($pdo, $col, $keywords){
  $rows = $pdo->query("SELECT $col AS v FROM bk_data_part8 WHERE $col != ''")->fetchAll(PDO::FETCH_COLUMN);
  $counts = [];
  foreach($rows as $row)
    foreach($keywords as $kw)
      if(stripos($row,$kw) !== false) $counts[$kw] = ($counts[$kw] ?? 0) + 1;
  arsort($counts);
  return $counts;
}

$obstacles = parseCounts($pdo, 'Beekeeping_major_obstacles_Q_N_25', [
  'High Temperature','Marketing Problems','High Cost of Modern Hives','Heavy Rain',
  'Death of Bees','Effects of Agrochemicals','Lack of Adequate Credit Facilities',
  'Impact from Pests and Predators','Escape of Bees','Lack of Bee Fodder',
  'Strong Wind','Drought','Transfer of Beehive Box','Bee Variety Reduction',
  'Lack of Adequate Beekeeping Skills','Lack of access to Modern Bee Hives',
  'Water Deficiency','Lack of Institutional Capacity','Swarming',
]);

$haf = parseCounts($pdo, 'Honey_affecting_factors_Q_N_26', [
  'Loss of Bee Colonies','Migration of Bee Colonies','Drying of Water Sources',
]);

$baf = parseCounts($pdo, 'Bee_affecting_factors_Q_N_27', [
  'Bees protect against many Pests','Mite','Various Viruses','Bacterial Infection','Fungal Disease',
]);

$qf = parseCounts($pdo, 'Honey_determine_quality_factors_Q_N_28', [
  'Origin and age of Flowers','Pollen Type','Climate Factors',
]);

// Section 29: pollination rental
$r29 = $pdo->query("
  SELECT
    SUM(CASE WHEN rental_confirm_YES_NO='Yes'   THEN 1 ELSE 0 END) AS n_rental,
    SUM(CASE WHEN flowers1_confirm_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_flowers,
    ROUND(AVG(CAST(NULLIF(sr1_flowers_colonies_number,'')         AS DECIMAL(10,2))),1) AS avg_fl_col,
    ROUND(AVG(CAST(NULLIF(sr1_flowers_average_price_per_colony,'') AS DECIMAL(10,2))),0) AS avg_fl_price,
    SUM(CASE WHEN fruits1_confirm_YES_NO='Yes'  THEN 1 ELSE 0 END) AS n_fruits,
    ROUND(AVG(CAST(NULLIF(sr1_fruits_colonies_number,'')          AS DECIMAL(10,2))),1) AS avg_fr_col,
    ROUND(AVG(CAST(NULLIF(sr1_fruits_average_price_per_colony,'')  AS DECIMAL(10,2))),0) AS avg_fr_price
  FROM bk_data_part8
")->fetch(PDO::FETCH_ASSOC);

// Normalise crop helper
function normCrop($v){
  if(stripos($v,'Mustard')!==false||stripos($v,'Masterd')!==false||stripos($v,'Sarso')!==false) return 'Mustard';
  if(stripos($v,'Litchi')!==false||stripos($v,'Lichi')!==false)  return 'Litchi';
  if(stripos($v,'Ucaliptish')!==false||stripos($v,'Eucalyptus')!==false) return 'Eucalyptus';
  if(stripos($v,'Bajra')!==false||stripos($v,'Makha')!==false)   return 'Bajra/Maize';
  if(stripos($v,'Apple')!==false)  return 'Apple';
  if(stripos($v,'Sunflower')!==false) return 'Sunflower';
  if(stripos($v,'Multiflower')!==false||stripos($v,'Maltiflower')!==false) return 'Multiflowers';
  return 'Others';
}

// Record 1 crops (sr1_flowers_crop_name)
$r1_crops_raw = $pdo->query("SELECT sr1_flowers_crop_name AS v, COUNT(*) AS n FROM bk_data_part8 WHERE sr1_flowers_crop_name!='' GROUP BY v")->fetchAll(PDO::FETCH_ASSOC);
$r1_crops = [];
foreach($r1_crops_raw as $cr){ $k = normCrop($cr['v']); $r1_crops[$k] = ($r1_crops[$k] ?? 0) + (int)$cr['n']; }
arsort($r1_crops);

// Record 2 crops (sr1_fruits_crop_name = actual DB col for Record 2)
$r2_crops_raw = $pdo->query("SELECT sr1_fruits_crop_name AS v, COUNT(*) AS n FROM bk_data_part8 WHERE sr1_fruits_crop_name!='' GROUP BY v")->fetchAll(PDO::FETCH_ASSOC);
$r2_crops = [];
foreach($r2_crops_raw as $cr){ $k = normCrop($cr['v']); $r2_crops[$k] = ($r2_crops[$k] ?? 0) + (int)$cr['n']; }
arsort($r2_crops);

// Combined for crop colours
$cropAgg = [];
foreach([$r1_crops,$r2_crops] as $arr) foreach($arr as $k=>$v) $cropAgg[$k] = ($cropAgg[$k]??0)+$v;
arsort($cropAgg);

function pctF($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }

$maxObs  = max($obstacles) ?: 1;
$maxHaf  = max($haf)       ?: 1;
$maxBaf  = max($baf)       ?: 1;
$maxQf   = max($qf)        ?: 1;
$maxCrop = max($cropAgg)   ?: 1;

$ob_colors = ['#E53935','#EF5350','#EF9A9A','#FFCDD2','#FFAB40','#FF8A65','#FFE0B2','#CE93D8','#BA68C8','#9C27B0'];
$haf_colors = ['#F2A900','#42A5F5','#66BB6A'];
$baf_colors = ['#2E7D32','#F44336','#FF8A65','#AB47BC','#26C6DA'];
$qf_colors  = ['#1565C0','#42A5F5','#90CAF9'];
$crop_colors = ['Mustard'=>'#F2A900','Litchi'=>'#66BB6A','Eucalyptus'=>'#42A5F5','Bajra'=>'#FF8A65','Apple'=>'#E53935','Others'=>'#90A4AE'];
?>

<!-- ══ BK FACTORS ══ -->
<div id="p-bkfactors" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#B71C1C">Constraints &amp; Factors · बाधाएँ एवं कारक</span>
      <div class="sec-title">Major Obstacles, Production Factors &amp; Pollination Services</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkob-kpi-row">
    <?php
    $kpis = [
      ['v'=>number_format(reset($obstacles)), 'p'=>pctF(reset($obstacles),$total).'% cite top obstacle', 'l'=>'High Temperature (top obstacle)', 'h'=>'उच्च तापमान (प्रमुख बाधा)', 'c'=>'#E53935'],
      ['v'=>number_format($haf['Loss of Bee Colonies']), 'p'=>pctF($haf['Loss of Bee Colonies'],$total).'%', 'l'=>'Colony Loss (top honey factor)', 'h'=>'कॉलोनी हानि', 'c'=>'#F2A900'],
      ['v'=>number_format($baf['Bees protect against many Pests']), 'p'=>pctF($baf['Bees protect against many Pests'],$total).'%', 'l'=>'Pest Protection (bee factor)', 'h'=>'कीट सुरक्षा', 'c'=>'#2E7D32'],
      ['v'=>number_format($qf['Origin and age of Flowers']), 'p'=>pctF($qf['Origin and age of Flowers'],$total).'%', 'l'=>'Flower Origin (top quality factor)', 'h'=>'फूल की उत्पत्ति', 'c'=>'#1565C0'],
      ['v'=>(int)$r29['n_rental'], 'p'=>pctF((int)$r29['n_rental'],$total).'% of farmers', 'l'=>'Pollination Rental Services', 'h'=>'परागण किराया सेवाएँ', 'c'=>'#6A1B9A'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkob-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkob-kv"><?= $k['v'] ?></div>
      <div class="bkob-kp"><?= $k['p'] ?></div>
      <div class="bkob-kl"><?= $k['l'] ?></div>
      <div class="bkob-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkob-charts">

    <!-- Col 1: Obstacles (Sec 25) -->
    <div class="bkob-chart-card">
      <div class="bkob-card-title">Major Obstacles in Beekeeping</div>
      <div class="bkob-card-sub">Section 25 · multi-select · % of <?= number_format($total) ?> farmers</div>
      <?php $ci=0; foreach($obstacles as $oname => $on):
        $bw = (int)round($on/$maxObs*100);
        $color = $ob_colors[$ci % count($ob_colors)];
        $ci++;
        $label = str_replace(['Lack of Adequate ','Lack of access to ','High Cost of Modern ','Impact from ','Effects of ','Transfer of ','Lack of '],'', $oname);
      ?>
      <div class="bkob-bar-row">
        <span class="bkob-bar-label" title="<?= htmlspecialchars($oname) ?>"><?= htmlspecialchars($label) ?></span>
        <div class="bkob-bar-wrap">
          <div class="bkob-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkob-bar-n" style="color:<?= $color ?>"><?= number_format($on) ?></span>
        <span class="bkob-bar-pct"><?= pctF($on,$total) ?>%</span>
      </div>
      <?php endforeach; ?>
    </div><!-- col1 -->

    <!-- Col 2: Honey factors (26) + Bee factors (27) -->
    <div class="bkob-chart-card">
      <div class="bkob-card-title">Factors Affecting Honey Production</div>
      <div class="bkob-card-sub">Section 26 · % of farmers</div>
      <?php $ci=0; foreach($haf as $fname => $fn):
        $bw = (int)round($fn/$maxHaf*100);
        $color = $haf_colors[$ci++ % count($haf_colors)];
        $label = str_replace(' Bee Colonies','',$fname);
      ?>
      <div class="bkob-bar-row">
        <span class="bkob-bar-label"><?= htmlspecialchars($label) ?></span>
        <div class="bkob-bar-wrap">
          <div class="bkob-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkob-bar-n" style="color:<?= $color ?>"><?= number_format($fn) ?></span>
        <span class="bkob-bar-pct"><?= pctF($fn,$total) ?>%</span>
      </div>
      <?php endforeach; ?>

      <div class="bkob-card-title" style="margin-top:18px">Factors Affecting Bees</div>
      <div class="bkob-card-sub">Section 27 · % of farmers</div>
      <?php $ci=0; foreach($baf as $fname => $fn):
        $bw = (int)round($fn/$maxBaf*100);
        $color = $baf_colors[$ci++ % count($baf_colors)];
        $label = str_replace('Bees protect against many Pests and Diseases','Pest &amp; Disease Protection',$fname);
      ?>
      <div class="bkob-bar-row">
        <span class="bkob-bar-label"><?= $label ?></span>
        <div class="bkob-bar-wrap">
          <div class="bkob-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkob-bar-n" style="color:<?= $color ?>"><?= number_format($fn) ?></span>
        <span class="bkob-bar-pct"><?= pctF($fn,$total) ?>%</span>
      </div>
      <?php endforeach; ?>
    </div><!-- col2 -->

    <!-- Col 3: Quality factors (28) + Pollination rental (29) -->
    <div class="bkob-chart-card">
      <div class="bkob-card-title">Honey Quality Determination Factors</div>
      <div class="bkob-card-sub">Section 28 · % of farmers</div>
      <?php $ci=0; foreach($qf as $qname => $qn):
        $bw = (int)round($qn/$maxQf*100);
        $color = $qf_colors[$ci++ % count($qf_colors)];
      ?>
      <div class="bkob-bar-row">
        <span class="bkob-bar-label"><?= htmlspecialchars($qname) ?></span>
        <div class="bkob-bar-wrap">
          <div class="bkob-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkob-bar-n" style="color:<?= $color ?>"><?= number_format($qn) ?></span>
        <span class="bkob-bar-pct"><?= pctF($qn,$total) ?>%</span>
      </div>
      <?php endforeach; ?>

      <!-- Pollination rental (Sec 29) -->
      <div class="bkob-card-title" style="margin-top:18px">Pollination Rental Services</div>
      <div class="bkob-card-sub">Section 29 · <?= (int)$r29['n_rental'] ?> farmers (<?= pctF((int)$r29['n_rental'],$total) ?>%) rent colonies for pollination</div>

      <!-- Record 1 -->
      <div class="bkob-rent-rec-hdr" style="--rrc:#F2A900">Record 1 · <?= (int)$r29['n_flowers'] ?> farmers · avg <?= $r29['avg_fl_col'] ?> colonies · ₹<?= (int)$r29['avg_fl_price'] ?>/colony</div>
      <?php $r1max = max($r1_crops) ?: 1; foreach($r1_crops as $cname => $cn):
        $bw = (int)round($cn/$r1max*100);
        $color = $crop_colors[$cname] ?? '#90A4AE';
      ?>
      <div class="bkob-bar-row">
        <span class="bkob-bar-label"><?= $cname ?></span>
        <div class="bkob-bar-wrap">
          <div class="bkob-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkob-bar-n" style="color:<?= $color ?>"><?= $cn ?></span>
        <span class="bkob-bar-pct"></span>
      </div>
      <?php endforeach; ?>

      <!-- Record 2 -->
      <div class="bkob-rent-rec-hdr" style="--rrc:#66BB6A;margin-top:8px">Record 2 · <?= (int)$r29['n_fruits'] ?> farmers · avg <?= $r29['avg_fr_col'] ?> colonies · ₹<?= (int)$r29['avg_fr_price'] ?>/colony</div>
      <?php $r2max = max($r2_crops) ?: 1; foreach($r2_crops as $cname => $cn):
        $bw = (int)round($cn/$r2max*100);
        $color = $crop_colors[$cname] ?? '#90A4AE';
      ?>
      <div class="bkob-bar-row">
        <span class="bkob-bar-label"><?= $cname ?></span>
        <div class="bkob-bar-wrap">
          <div class="bkob-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkob-bar-n" style="color:<?= $color ?>"><?= $cn ?></span>
        <span class="bkob-bar-pct"></span>
      </div>
      <?php endforeach; ?>

    </div><!-- col3 -->

  </div><!-- /.bkob-charts -->

</div>
