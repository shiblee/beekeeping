<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Part2: 2021, 2022, and partial 2023
$p2 = $pdo->query("
  SELECT
    SUM(CASE WHEN Q_No_13__2021_Selling_Detail_YES_NO='Yes'   THEN 1 ELSE 0 END) AS n21,
    SUM(CASE WHEN Q_No_13__2022_Selling_Details_YES_NO='Yes'  THEN 1 ELSE 0 END) AS n22,
    SUM(CASE WHEN Q_No_13__2023_Selling_Details_YES_NO='Yes'  THEN 1 ELSE 0 END) AS n23,
    ROUND(AVG(CAST(NULLIF(_2021_total_output,'')  AS DECIMAL(12,2))),0) AS out21,
    ROUND(AVG(CAST(NULLIF(_2022_total_output,'')  AS DECIMAL(12,2))),0) AS out22,
    ROUND(AVG(CAST(NULLIF(_2023_total_output,'')  AS DECIMAL(12,2))),0) AS out23,
    ROUND(AVG(CAST(NULLIF(_2021_local_qty,'')     AS DECIMAL(6,2))),1) AS lq21,
    ROUND(AVG(CAST(NULLIF(_2021_local_price,'')   AS DECIMAL(10,2))),0) AS lp21,
    ROUND(AVG(CAST(NULLIF(_2021_fair_qty,'')      AS DECIMAL(6,2))),1) AS fq21,
    ROUND(AVG(CAST(NULLIF(_2021_fair_price,'')    AS DECIMAL(10,2))),0) AS fp21,
    ROUND(AVG(CAST(NULLIF(_2021_consumer_qty,'')  AS DECIMAL(6,2))),1) AS cq21,
    ROUND(AVG(CAST(NULLIF(_2021_consumer_price,'') AS DECIMAL(10,2))),0) AS cp21,
    ROUND(AVG(CAST(NULLIF(_2021_private_qty,'')   AS DECIMAL(6,2))),1) AS pq21,
    ROUND(AVG(CAST(NULLIF(_2021_private_price,'') AS DECIMAL(10,2))),0) AS pp21,
    ROUND(AVG(CAST(NULLIF(_2021_honey_qty,'')     AS DECIMAL(6,2))),1) AS hq21,
    ROUND(AVG(CAST(NULLIF(_2021_honey_price,'')   AS DECIMAL(10,2))),0) AS hp21,
    ROUND(AVG(CAST(NULLIF(_2022_local_qty,'')     AS DECIMAL(6,2))),1) AS lq22,
    ROUND(AVG(CAST(NULLIF(_2022_local_price,'')   AS DECIMAL(10,2))),0) AS lp22,
    ROUND(AVG(CAST(NULLIF(_2022_fair_qty,'')      AS DECIMAL(6,2))),1) AS fq22,
    ROUND(AVG(CAST(NULLIF(_2022_fair_price,'')    AS DECIMAL(10,2))),0) AS fp22,
    ROUND(AVG(CAST(NULLIF(_2022_consumer_qty,'')  AS DECIMAL(6,2))),1) AS cq22,
    ROUND(AVG(CAST(NULLIF(_2022_consumer_price,'') AS DECIMAL(10,2))),0) AS cp22,
    ROUND(AVG(CAST(NULLIF(_2022_private_qty,'')   AS DECIMAL(6,2))),1) AS pq22,
    ROUND(AVG(CAST(NULLIF(_2022_private_price,'') AS DECIMAL(10,2))),0) AS pp22,
    ROUND(AVG(CAST(NULLIF(_2022_honey_qty,'')     AS DECIMAL(6,2))),1) AS hq22,
    ROUND(AVG(CAST(NULLIF(_2022_honey_price,'')   AS DECIMAL(10,2))),0) AS hp22,
    ROUND(AVG(CAST(NULLIF(_2023_local_qty,'')     AS DECIMAL(6,2))),1) AS lq23,
    ROUND(AVG(CAST(NULLIF(_2023_local_price,'')   AS DECIMAL(10,2))),0) AS lp23
  FROM bk_data_part2
")->fetch(PDO::FETCH_ASSOC);

// Part3: remaining 2023 channels
$p3 = $pdo->query("
  SELECT
    ROUND(AVG(CAST(NULLIF(_2023_fair_qty,'')      AS DECIMAL(6,2))),1) AS fq23,
    ROUND(AVG(CAST(NULLIF(_2023_fair_price,'')    AS DECIMAL(10,2))),0) AS fp23,
    ROUND(AVG(CAST(NULLIF(_2023_consumer_qty,'')  AS DECIMAL(6,2))),1) AS cq23,
    ROUND(AVG(CAST(NULLIF(_2023_consumer_price,'') AS DECIMAL(10,2))),0) AS cp23,
    ROUND(AVG(CAST(NULLIF(_2023_private_qty,'')   AS DECIMAL(6,2))),1) AS pq23,
    ROUND(AVG(CAST(NULLIF(_2023_private_price,'') AS DECIMAL(10,2))),0) AS pp23,
    ROUND(AVG(CAST(NULLIF(_2023_total_percentage_sell,'') AS DECIMAL(6,2))),1) AS tpct23
  FROM bk_data_part3
")->fetch(PDO::FETCH_ASSOC);

$years = [
  [
    'year' => '2021', 'n' => (int)$p2['n21'], 'output' => (int)$p2['out21'],
    'channels' => [
      ['name'=>'Private Retailer',      'hindi'=>'निजी खुदरा विक्रेता', 'qty'=>(float)$p2['pq21'], 'price'=>(int)$p2['pp21'], 'color'=>'#F2A900'],
      ['name'=>'Local Shop',            'hindi'=>'स्थानीय दुकान',        'qty'=>(float)$p2['lq21'], 'price'=>(int)$p2['lp21'], 'color'=>'#42A5F5'],
      ['name'=>'Direct Consumer',       'hindi'=>'उपभोक्ता',             'qty'=>(float)$p2['cq21'], 'price'=>(int)$p2['cp21'], 'color'=>'#66BB6A'],
      ['name'=>'Honey Processing Unit', 'hindi'=>'शहद प्रसंस्करण इकाई',  'qty'=>(float)$p2['hq21'], 'price'=>(int)$p2['hp21'], 'color'=>'#FF8A65'],
      ['name'=>'Fair / Exhibition',     'hindi'=>'मेला/प्रदर्शनी',        'qty'=>(float)$p2['fq21'], 'price'=>(int)$p2['fp21'], 'color'=>'#CE93D8'],
    ],
  ],
  [
    'year' => '2022', 'n' => (int)$p2['n22'], 'output' => (int)$p2['out22'],
    'channels' => [
      ['name'=>'Private Retailer',      'hindi'=>'निजी खुदरा विक्रेता', 'qty'=>(float)$p2['pq22'], 'price'=>(int)$p2['pp22'], 'color'=>'#F2A900'],
      ['name'=>'Local Shop',            'hindi'=>'स्थानीय दुकान',        'qty'=>(float)$p2['lq22'], 'price'=>(int)$p2['lp22'], 'color'=>'#42A5F5'],
      ['name'=>'Direct Consumer',       'hindi'=>'उपभोक्ता',             'qty'=>(float)$p2['cq22'], 'price'=>(int)$p2['cp22'], 'color'=>'#66BB6A'],
      ['name'=>'Honey Processing Unit', 'hindi'=>'शहद प्रसंस्करण इकाई',  'qty'=>(float)$p2['hq22'], 'price'=>(int)$p2['hp22'], 'color'=>'#FF8A65'],
      ['name'=>'Fair / Exhibition',     'hindi'=>'मेला/प्रदर्शनी',        'qty'=>(float)$p2['fq22'], 'price'=>(int)$p2['fp22'], 'color'=>'#CE93D8'],
    ],
  ],
  [
    'year' => '2023', 'n' => (int)$p2['n23'], 'output' => (int)$p2['out23'],
    'channels' => [
      ['name'=>'Private Retailer',      'hindi'=>'निजी खुदरा विक्रेता', 'qty'=>(float)$p3['pq23'], 'price'=>(int)$p3['pp23'], 'color'=>'#F2A900'],
      ['name'=>'Local Shop',            'hindi'=>'स्थानीय दुकान',        'qty'=>(float)$p2['lq23'], 'price'=>(int)$p2['lp23'], 'color'=>'#42A5F5'],
      ['name'=>'Direct Consumer',       'hindi'=>'उपभोक्ता',             'qty'=>(float)$p3['cq23'], 'price'=>(int)$p3['cp23'], 'color'=>'#66BB6A'],
      ['name'=>'Fair / Exhibition',     'hindi'=>'मेला/प्रदर्शनी',        'qty'=>(float)$p3['fq23'], 'price'=>(int)$p3['fp23'], 'color'=>'#CE93D8'],
    ],
  ],
];

$maxOut = max(array_column($years,'output'));
function pctS($n,$d){ return $d > 0 ? (int)round($n/$d*100) : 0; }
?>

<!-- ══ BK SELLING ══ -->
<div id="p-bksell" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#F57F17">Selling Details · बिक्री विवरण</span>
      <div class="sec-title">Selling Details of Beekeeping Production — 2021 · 2022 · 2023</div>
    </div>
  </div>

  <!-- Output trend KPI strip -->
  <div class="bks-trend-row">
    <?php foreach($years as $yr):
      $bw = $maxOut > 0 ? (int)round($yr['output']/$maxOut*100) : 0;
      $pp = pctS($yr['n'], $total);
    ?>
    <div class="bks-trend-card">
      <div class="bks-trend-year"><?= $yr['year'] ?></div>
      <div class="bks-trend-out"><?= number_format($yr['output']) ?> <span>kg avg/farmer</span></div>
      <div class="bks-trend-bar-wrap">
        <div class="bks-trend-bar" style="width:<?= $bw ?>%"></div>
      </div>
      <div class="bks-trend-meta">
        <span class="bks-trend-n"><?= number_format($yr['n']) ?> farmers</span>
        <span class="bks-trend-pct"><?= $pp ?>%</span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- 3 year cards -->
  <div class="bks-charts">
    <?php foreach($years as $yr): ?>
    <div class="bks-chart-card">
      <div class="bks-card-hdr">
        <div class="bks-card-year"><?= $yr['year'] ?></div>
        <div class="bks-card-out"><?= number_format($yr['output']) ?> kg</div>
        <div class="bks-card-n"><?= number_format($yr['n']) ?> farmers</div>
      </div>

      <!-- Stacked channel bar -->
      <div class="bks-stack-bar">
        <?php foreach($yr['channels'] as $ch): if($ch['qty'] <= 0) continue; ?>
        <div class="bks-stack-seg" style="width:<?= min(100,(int)round($ch['qty'])) ?>%;background:<?= $ch['color'] ?>"
             title="<?= $ch['name'] ?>: <?= $ch['qty'] ?>%"></div>
        <?php endforeach; ?>
      </div>

      <!-- Channel detail rows -->
      <div class="bks-channels">
        <?php foreach($yr['channels'] as $ch): if($ch['qty'] <= 0) continue; ?>
        <div class="bks-ch-row">
          <span class="bks-ch-dot" style="background:<?= $ch['color'] ?>"></span>
          <div class="bks-ch-info">
            <span class="bks-ch-name"><?= $ch['name'] ?></span>
            <span class="bks-ch-hindi"><?= $ch['hindi'] ?></span>
          </div>
          <div class="bks-ch-bar-wrap">
            <div class="bks-ch-bar" style="width:<?= min(100,(int)round($ch['qty'])) ?>%;background:<?= $ch['color'] ?>"></div>
          </div>
          <span class="bks-ch-qty" style="color:<?= $ch['color'] ?>"><?= $ch['qty'] ?>%</span>
          <?php if($ch['price'] > 0): ?>
          <span class="bks-ch-price">₹<?= number_format($ch['price']) ?>/kg</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div><!-- /.bks-charts -->

  <!-- Channel legend -->
  <div class="bks-legend">
    <?php
    $legItems = [
      ['Private Retailer','#F2A900'],['Local Shop','#42A5F5'],['Direct Consumer','#66BB6A'],
      ['Honey Processing Unit','#FF8A65'],['Fair / Exhibition','#CE93D8'],
    ];
    foreach($legItems as [$lname,$lcol]):
    ?>
    <div class="bks-leg-item">
      <span class="bks-leg-dot" style="background:<?= $lcol ?>"></span>
      <span><?= $lname ?></span>
    </div>
    <?php endforeach; ?>
  </div>

</div>
