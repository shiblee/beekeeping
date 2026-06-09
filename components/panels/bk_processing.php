<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    SUM(CASE WHEN is_self_oprate_processing_unit_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_unit,
    ROUND(AVG(CAST(NULLIF(unit_establishment_year,'') AS DECIMAL(6,0))),0) AS avg_yr,
    ROUND(AVG(CAST(NULLIF(honey_quantity,'') AS DECIMAL(14,2))),0) AS avg_cap,
    ROUND(AVG(CAST(NULLIF(processed_honey_quantity,'') AS DECIMAL(14,2))),0) AS avg_proc,
    ROUND(AVG(CAST(NULLIF(production_price,'') AS DECIMAL(10,2))),1) AS avg_cost,
    SUM(CASE WHEN village_market='Yes' THEN 1 ELSE 0 END) AS n_village,
    ROUND(AVG(CAST(NULLIF(village_market_quantity,'') AS DECIMAL(12,2))),0) AS avg_vq,
    ROUND(AVG(CAST(NULLIF(village_market_price,'') AS DECIMAL(10,2))),0) AS avg_vp,
    SUM(CASE WHEN govt_market='Yes' THEN 1 ELSE 0 END) AS n_govt,
    ROUND(AVG(CAST(NULLIF(govt_market_quantity,'') AS DECIMAL(12,2))),0) AS avg_gq,
    ROUND(AVG(CAST(NULLIF(govt_market_price,'') AS DECIMAL(10,2))),0) AS avg_gp,
    SUM(CASE WHEN enam_portal='Yes' THEN 1 ELSE 0 END) AS n_enam,
    ROUND(AVG(CAST(NULLIF(enam_portal_quantity,'') AS DECIMAL(12,2))),0) AS avg_eq,
    ROUND(AVG(CAST(NULLIF(enam_portal_price,'') AS DECIMAL(10,2))),0) AS avg_ep,
    SUM(CASE WHEN local_agent='Yes' THEN 1 ELSE 0 END) AS n_agent,
    ROUND(AVG(CAST(NULLIF(local_agent_quantity,'') AS DECIMAL(12,2))),0) AS avg_aq,
    ROUND(AVG(CAST(NULLIF(local_agent_price,'') AS DECIMAL(10,2))),0) AS avg_ap,
    SUM(CASE WHEN private_company='Yes' THEN 1 ELSE 0 END) AS n_private,
    ROUND(AVG(CAST(NULLIF(private_company_quantity,'') AS DECIMAL(12,2))),0) AS avg_pq,
    ROUND(AVG(CAST(NULLIF(private_company_price,'') AS DECIMAL(10,2))),0) AS avg_pp
  FROM bk_data_part7
")->fetch(PDO::FETCH_ASSOC);

$d8 = $pdo->query("
  SELECT
    SUM(CASE WHEN honeyprocessing_branding_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_brand,
    SUM(CASE WHEN operation_honey_processing_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_op
  FROM bk_data_part8
")->fetch(PDO::FETCH_ASSOC);

$n_unit = (int)$d['n_unit'];
function pctPU($n,$d){ return $d > 0 ? round($n/$d*100,2) : 0; }

$channels = [
  ['name'=>'Private Company', 'hindi'=>'प्राइवेट कंपनी',            'n'=>(int)$d['n_private'], 'qty'=>(float)$d['avg_pq'], 'price'=>(int)$d['avg_pp'], 'color'=>'#F2A900'],
  ['name'=>'eNAM Portal',     'hindi'=>'ई-नाम पोर्टल',              'n'=>(int)$d['n_enam'],    'qty'=>(float)$d['avg_eq'], 'price'=>(int)$d['avg_ep'], 'color'=>'#42A5F5'],
  ['name'=>'Village Market',  'hindi'=>'ग्राम स्तर (हाट/बाजार)',    'n'=>(int)$d['n_village'], 'qty'=>(float)$d['avg_vq'], 'price'=>(int)$d['avg_vp'], 'color'=>'#66BB6A'],
  ['name'=>'Govt Market',     'hindi'=>'सरकारी मण्डी',               'n'=>(int)$d['n_govt'],    'qty'=>(float)$d['avg_gq'], 'price'=>(int)$d['avg_gp'], 'color'=>'#AB47BC'],
  ['name'=>'Local Agent',     'hindi'=>'लोकल एजेंट',                 'n'=>(int)$d['n_agent'],   'qty'=>(float)$d['avg_aq'], 'price'=>(int)$d['avg_ap'], 'color'=>'#FF8A65'],
];
$maxChN = max(array_column($channels,'n')) ?: 1;
?>

<!-- ══ BK PROCESSING UNIT ══ -->
<div id="p-bkprocess" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#01579B">Honey Processing · शहद प्रसंस्करण</span>
      <div class="sec-title">Honey Processing Unit</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkpu-kpi-row">
    <?php
    $kpis = [
      ['v'=>$n_unit,                          'p'=>pctPU($n_unit,$total).'% of farmers', 'l'=>'Processing Units',    'h'=>'प्रसंस्करण इकाइयाँ',   'c'=>'#01579B'],
      ['v'=>$d['avg_yr'] ?? '—',              'p'=>'avg. est. year',                     'l'=>'Year Established',    'h'=>'स्थापना वर्ष',           'c'=>'#0277BD'],
      ['v'=>number_format((int)$d['avg_cap']).' kg', 'p'=>'avg capacity',                'l'=>'Processing Capacity', 'h'=>'प्रसंस्करण क्षमता',     'c'=>'#0288D1'],
      ['v'=>number_format((int)$d['avg_proc']).' kg','p'=>'avg processed',               'l'=>'Processed Qty',       'h'=>'प्रसंस्कृत मात्रा',     'c'=>'#039BE5'],
      ['v'=>'₹'.$d['avg_cost'].'/kg',         'p'=>'avg cost',                           'l'=>'Processing Cost',     'h'=>'प्रसंस्करण लागत',       'c'=>'#E65100'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkpu-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkpu-kv"><?= $k['v'] ?></div>
      <div class="bkpu-kp"><?= $k['p'] ?></div>
      <div class="bkpu-kl"><?= $k['l'] ?></div>
      <div class="bkpu-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkpu-charts">

    <!-- Col 1: Unit profile -->
    <div class="bkpu-chart-card">
      <div class="bkpu-card-title">Processing Unit Profile</div>
      <div class="bkpu-card-sub">Key operating characteristics of the <?= $n_unit ?> self-operated units</div>

      <div class="bkpu-stat-grid">
        <div class="bkpu-stat-item">
          <span class="bkpu-stat-v"><?= $n_unit ?></span>
          <span class="bkpu-stat-l">Units operating</span>
        </div>
        <div class="bkpu-stat-item">
          <span class="bkpu-stat-v"><?= $d['avg_yr'] ?? '—' ?></span>
          <span class="bkpu-stat-l">Avg year established</span>
        </div>
        <div class="bkpu-stat-item">
          <span class="bkpu-stat-v"><?= number_format((int)$d['avg_cap']) ?> kg</span>
          <span class="bkpu-stat-l">Avg capacity</span>
        </div>
        <div class="bkpu-stat-item">
          <span class="bkpu-stat-v"><?= number_format((int)$d['avg_proc']) ?> kg</span>
          <span class="bkpu-stat-l">Avg processed qty</span>
        </div>
        <div class="bkpu-stat-item">
          <span class="bkpu-stat-v">₹<?= $d['avg_cost'] ?>/kg</span>
          <span class="bkpu-stat-l">Avg processing cost</span>
        </div>
        <div class="bkpu-stat-item">
          <span class="bkpu-stat-v"><?= (int)$d8['n_brand'] ?></span>
          <span class="bkpu-stat-l">With branding</span>
        </div>
      </div>

      <!-- Utilisation rate -->
      <?php
      $util = ($d['avg_cap'] > 0) ? round($d['avg_proc'] / $d['avg_cap'] * 100) : 0;
      ?>
      <div class="bkpu-util-title">Capacity Utilisation</div>
      <div class="bkpu-util-wrap">
        <div class="bkpu-util-bar" style="width:<?= $util ?>%"></div>
      </div>
      <div class="bkpu-util-label"><?= $util ?>% of capacity utilised (avg)</div>

      <!-- Honey varieties -->
      <div class="bkpu-util-title" style="margin-top:16px">Honey Varieties Processed</div>
      <?php
      $varieties = ['Mustard'=>0,'Litchi'=>0,'Eucalyptus'=>0,'Others'=>0];
      $vrows = $pdo->query("SELECT related_type_honey FROM bk_data_part7 WHERE related_type_honey!=''")->fetchAll(PDO::FETCH_COLUMN);
      foreach($vrows as $rv){
        if(strpos($rv,'Mustard')!==false)    $varieties['Mustard']++;
        if(strpos($rv,'Litchi')!==false)     $varieties['Litchi']++;
        if(strpos($rv,'Eucalyptus')!==false) $varieties['Eucalyptus']++;
        if(strpos($rv,'Others')!==false)     $varieties['Others']++;
      }
      arsort($varieties);
      $vcolors = ['Mustard'=>'#F2A900','Litchi'=>'#66BB6A','Eucalyptus'=>'#42A5F5','Others'=>'#90A4AE'];
      foreach($varieties as $vname => $vn): if($vn === 0) continue;
      ?>
      <div class="bkpu-var-row">
        <span class="bkpu-var-dot" style="background:<?= $vcolors[$vname] ?? '#999' ?>"></span>
        <span class="bkpu-var-name"><?= $vname ?> Honey</span>
        <span class="bkpu-var-n" style="color:<?= $vcolors[$vname] ?? '#999' ?>"><?= $vn ?> unit<?= $vn>1?'s':'' ?></span>
      </div>
      <?php endforeach; ?>
    </div><!-- col1 -->

    <!-- Col 2: Selling channels -->
    <div class="bkpu-chart-card">
      <div class="bkpu-card-title">Selling Channels</div>
      <div class="bkpu-card-sub">Where processed honey is sold (among <?= $n_unit ?> units)</div>

      <?php foreach($channels as $ch): ?>
      <div class="bkpu-ch-row">
        <div class="bkpu-ch-head">
          <span class="bkpu-ch-dot" style="background:<?= $ch['color'] ?>"></span>
          <div class="bkpu-ch-info">
            <span class="bkpu-ch-name"><?= $ch['name'] ?></span>
            <span class="bkpu-ch-hindi"><?= $ch['hindi'] ?></span>
          </div>
          <span class="bkpu-ch-n" style="color:<?= $ch['color'] ?>"><?= $ch['n'] ?> unit<?= $ch['n']!=1?'s':'' ?></span>
        </div>
        <?php if($ch['n'] > 0): ?>
        <div class="bkpu-ch-bar-wrap">
          <div class="bkpu-ch-bar" style="width:<?= (int)round($ch['n']/$maxChN*100) ?>%;background:<?= $ch['color'] ?>"></div>
        </div>
        <div class="bkpu-ch-meta">
          <?php if($ch['qty'] > 0): ?>
          <span class="bkpu-ch-qty">Avg qty: <?= number_format($ch['qty']) ?> kg</span>
          <?php endif; ?>
          <?php if($ch['price'] > 0): ?>
          <span class="bkpu-ch-price">₹<?= number_format($ch['price']) ?>/kg</span>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="bkpu-ch-nil">No units use this channel</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div class="bkpu-note">
        Only <?= $n_unit ?> out of <?= number_format($total) ?> surveyed farmers (<?= pctPU($n_unit,$total) ?>%) operate a self-owned honey processing unit.
      </div>
    </div><!-- col2 -->

  </div><!-- /.bkpu-charts -->

</div>
