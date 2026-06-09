<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Beeswax — 3 years (part3 = 21, part4 = 22, part5 = 23)
$wax = [
  '2021' => $pdo->query("SELECT
    COUNT(CASE WHEN beeswax_byproduct_outcomes21!='' THEN 1 END) AS n,
    ROUND(AVG(CAST(NULLIF(beeswax_byproduct_outcomes21,'')  AS DECIMAL(12,2))),1) AS out_kg,
    ROUND(AVG(CAST(NULLIF(beeswax_private_price21,'')       AS DECIMAL(10,2))),0) AS pp,
    ROUND(AVG(CAST(NULLIF(beeswax_local_qty21,'')           AS DECIMAL(6,2))),1)  AS lq,
    ROUND(AVG(CAST(NULLIF(beeswax_fair_qty21,'')            AS DECIMAL(6,2))),1)  AS fq,
    ROUND(AVG(CAST(NULLIF(beeswax_consumer_qty21,'')        AS DECIMAL(6,2))),1)  AS cq,
    ROUND(AVG(CAST(NULLIF(beeswax_private_qty21,'')         AS DECIMAL(6,2))),1)  AS pq,
    ROUND(AVG(CAST(NULLIF(beeswax_honey_qty21,'')           AS DECIMAL(6,2))),1)  AS hq
    FROM bk_data_part3")->fetch(PDO::FETCH_ASSOC),
  '2022' => $pdo->query("SELECT
    COUNT(CASE WHEN beeswax_byproduct_outcomes22!='' THEN 1 END) AS n,
    ROUND(AVG(CAST(NULLIF(beeswax_byproduct_outcomes22,'')  AS DECIMAL(12,2))),1) AS out_kg,
    ROUND(AVG(CAST(NULLIF(beeswax_private_price22,'')       AS DECIMAL(10,2))),0) AS pp,
    ROUND(AVG(CAST(NULLIF(beeswax_local_qty22,'')           AS DECIMAL(6,2))),1)  AS lq,
    ROUND(AVG(CAST(NULLIF(beeswax_fair_qty22,'')            AS DECIMAL(6,2))),1)  AS fq,
    ROUND(AVG(CAST(NULLIF(beeswax_consumer_qty22,'')        AS DECIMAL(6,2))),1)  AS cq,
    ROUND(AVG(CAST(NULLIF(beeswax_private_qty22,'')         AS DECIMAL(6,2))),1)  AS pq,
    ROUND(AVG(CAST(NULLIF(beeswax_honey_qty22,'')           AS DECIMAL(6,2))),1)  AS hq
    FROM bk_data_part4")->fetch(PDO::FETCH_ASSOC),
  '2023' => $pdo->query("SELECT
    COUNT(CASE WHEN beeswax_byproduct_outcomes23!='' THEN 1 END) AS n,
    ROUND(AVG(CAST(NULLIF(beeswax_byproduct_outcomes23,'')  AS DECIMAL(12,2))),1) AS out_kg,
    ROUND(AVG(CAST(NULLIF(beeswax_private_price23,'')       AS DECIMAL(10,2))),0) AS pp,
    ROUND(AVG(CAST(NULLIF(beeswax_local_qty23,'')           AS DECIMAL(6,2))),1)  AS lq,
    ROUND(AVG(CAST(NULLIF(beeswax_fair_qty23,'')            AS DECIMAL(6,2))),1)  AS fq,
    ROUND(AVG(CAST(NULLIF(beeswax_consumer_qty23,'')        AS DECIMAL(6,2))),1)  AS cq,
    ROUND(AVG(CAST(NULLIF(beeswax_private_qty23,'')         AS DECIMAL(6,2))),1)  AS pq,
    ROUND(AVG(CAST(NULLIF(beeswax_honey_qty23,'')           AS DECIMAL(6,2))),1)  AS hq
    FROM bk_data_part5")->fetch(PDO::FETCH_ASSOC),
];

// Pollen — 3 years
$pol = [
  '2021' => $pdo->query("SELECT
    COUNT(CASE WHEN pollen_byproduct_outcomes21!='' THEN 1 END) AS n,
    ROUND(AVG(CAST(NULLIF(pollen_byproduct_outcomes21,'') AS DECIMAL(12,2))),1) AS out_kg,
    ROUND(AVG(CAST(NULLIF(pollen_private_price21,'')      AS DECIMAL(10,2))),0) AS pp,
    ROUND(AVG(CAST(NULLIF(pollen_local_qty21,'')          AS DECIMAL(6,2))),1)  AS lq,
    ROUND(AVG(CAST(NULLIF(pollen_fair_qty21,'')           AS DECIMAL(6,2))),1)  AS fq,
    ROUND(AVG(CAST(NULLIF(pollen_consumer_quty21,'')      AS DECIMAL(6,2))),1)  AS cq,
    ROUND(AVG(CAST(NULLIF(pollen_private_quty21,'')       AS DECIMAL(6,2))),1)  AS pq
    FROM bk_data_part3")->fetch(PDO::FETCH_ASSOC),
  '2022' => $pdo->query("SELECT
    COUNT(CASE WHEN pollen_byproduct_outcomes22!='' THEN 1 END) AS n,
    ROUND(AVG(CAST(NULLIF(pollen_byproduct_outcomes22,'') AS DECIMAL(12,2))),1) AS out_kg,
    ROUND(AVG(CAST(NULLIF(pollen_private_price22,'')      AS DECIMAL(10,2))),0) AS pp,
    ROUND(AVG(CAST(NULLIF(pollen_local_qty22,'')          AS DECIMAL(6,2))),1)  AS lq,
    ROUND(AVG(CAST(NULLIF(pollen_fair_qty22,'')           AS DECIMAL(6,2))),1)  AS fq,
    ROUND(AVG(CAST(NULLIF(pollen_consumer_qty22,'')       AS DECIMAL(6,2))),1)  AS cq,
    ROUND(AVG(CAST(NULLIF(pollen_private_qty22,'')        AS DECIMAL(6,2))),1)  AS pq
    FROM bk_data_part4")->fetch(PDO::FETCH_ASSOC),
  '2023' => $pdo->query("SELECT
    COUNT(CASE WHEN pollen_byproduct_outcomes23!='' THEN 1 END) AS n,
    ROUND(AVG(CAST(NULLIF(pollen_byproduct_outcomes23,'') AS DECIMAL(12,2))),1) AS out_kg,
    ROUND(AVG(CAST(NULLIF(pollen_private_price23,'')      AS DECIMAL(10,2))),0) AS pp,
    ROUND(AVG(CAST(NULLIF(pollen_local_qty23,'')          AS DECIMAL(6,2))),1)  AS lq,
    ROUND(AVG(CAST(NULLIF(pollen_fair_qty23,'')           AS DECIMAL(6,2))),1)  AS fq,
    ROUND(AVG(CAST(NULLIF(pollen_consumer_qty23,'')       AS DECIMAL(6,2))),1)  AS cq,
    ROUND(AVG(CAST(NULLIF(pollen_private_qty23,'')        AS DECIMAL(6,2))),1)  AS pq
    FROM bk_data_part5")->fetch(PDO::FETCH_ASSOC),
];

function pctCP($n,$d){ return $d > 0 ? (int)round($n/$d*100) : 0; }

$chColors = ['Private'=>'#F2A900','Local'=>'#42A5F5','Consumer'=>'#66BB6A','Fair'=>'#CE93D8','Honey Unit'=>'#FF8A65'];
?>

<!-- ══ BK CO-PRODUCTS ══ -->
<div id="p-bkcp" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#6A1B9A">Co-Product Sales · सह-उत्पाद बिक्री</span>
      <div class="sec-title">Selling Details of Co-Products — 2021 · 2022 · 2023</div>
    </div>
  </div>

  <!-- Co-product overview strip -->
  <div class="bkcp-overview">
    <?php
    $products = [
      ['name'=>'Beeswax',    'hindi'=>'मधुमोम',     'icon'=>'🕯️', 'color'=>'#8D6E63',
       'n21'=>(int)$wax['2021']['n'], 'n22'=>(int)$wax['2022']['n'], 'n23'=>(int)$wax['2023']['n'],
       'out23'=>$wax['2023']['out_kg'], 'pp21'=>$wax['2021']['pp'], 'pp22'=>$wax['2022']['pp'], 'pp23'=>$wax['2023']['pp']],
      ['name'=>'Pollen',     'hindi'=>'परागकण',    'icon'=>'🌼', 'color'=>'#F9A825',
       'n21'=>(int)$pol['2021']['n'], 'n22'=>(int)$pol['2022']['n'], 'n23'=>(int)$pol['2023']['n'],
       'out23'=>$pol['2023']['out_kg'], 'pp21'=>$pol['2021']['pp'], 'pp22'=>$pol['2022']['pp'], 'pp23'=>$pol['2023']['pp']],
      ['name'=>'Royal Jelly','hindi'=>'रॉयल जेली',  'icon'=>'👑', 'color'=>'#CE93D8', 'n21'=>0,'n22'=>0,'n23'=>0,'out23'=>0,'pp21'=>0,'pp22'=>0,'pp23'=>0],
      ['name'=>'Propolis',   'hindi'=>'प्रोपोलिस',   'icon'=>'🍯', 'color'=>'#66BB6A', 'n21'=>1,'n22'=>0,'n23'=>4,'out23'=>6.5,'pp21'=>0,'pp22'=>0,'pp23'=>0],
      ['name'=>'Venom',      'hindi'=>'वेनोम',       'icon'=>'⚡', 'color'=>'#546E7A', 'n21'=>0,'n22'=>0,'n23'=>0,'out23'=>0,'pp21'=>0,'pp22'=>0,'pp23'=>0],
    ];
    foreach($products as $p): ?>
    <div class="bkcp-prod-card" style="--pcc:<?= $p['color'] ?>">
      <div class="bkcp-prod-ico"><?= $p['icon'] ?></div>
      <div class="bkcp-prod-name"><?= $p['name'] ?></div>
      <div class="bkcp-prod-hindi"><?= $p['hindi'] ?></div>
      <div class="bkcp-prod-farmers">
        <span class="bkcp-yr">2021</span><strong><?= number_format($p['n21']) ?></strong>
        <span class="bkcp-yr">2022</span><strong><?= number_format($p['n22']) ?></strong>
        <span class="bkcp-yr">2023</span><strong style="color:var(--pcc)"><?= number_format($p['n23']) ?></strong>
      </div>
      <?php if($p['out23'] > 0): ?>
      <div class="bkcp-prod-meta"><?= $p['out23'] ?> kg avg · ₹<?= number_format((int)$p['pp23']) ?>/kg</div>
      <?php else: ?>
      <div class="bkcp-prod-meta bkcp-nil">negligible / not reported</div>
      <?php endif; ?>
      <?php if($p['pp21'] > 0 && $p['pp23'] > 0):
        $diff = $p['pp23'] - $p['pp21'];
        $sign = $diff > 0 ? '+' : '';
      ?>
      <div class="bkcp-price-trend" style="color:<?= $diff>0?'#2E7D32':'#C62828' ?>">
        <?= $sign ?><?= $diff ?>% '21→'23
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- 2 detailed cards: Beeswax + Pollen -->
  <div class="bkcp-charts">

    <!-- Beeswax detail -->
    <div class="bkcp-chart-card">
      <div class="bkcp-card-hdr" style="--pcc:#8D6E63">
        <span class="bkcp-card-ico">🕯️</span>
        <div>
          <div class="bkcp-card-title">Beeswax — 3-Year Trend</div>
          <div class="bkcp-card-sub">बीवैक्स · Output, Price &amp; Channel Mix</div>
        </div>
      </div>

      <div class="bkcp-year-grid">
        <?php foreach($wax as $yr => $d):
          $maxPP = max(array_column($wax,'pp'));
          $barW = $maxPP > 0 ? (int)round($d['pp']/$maxPP*100) : 0;
        ?>
        <div class="bkcp-yr-stat">
          <div class="bkcp-yr-label"><?= $yr ?></div>
          <div class="bkcp-yr-n"><?= number_format((int)$d['n']) ?> farmers</div>
          <div class="bkcp-yr-out"><?= $d['out_kg'] ?> kg avg</div>
          <div class="bkcp-price-bar-wrap">
            <div class="bkcp-price-bar" style="width:<?= $barW ?>%;background:#8D6E63"></div>
          </div>
          <div class="bkcp-yr-price" style="color:#8D6E63">₹<?= number_format((int)$d['pp']) ?>/kg</div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="bkcp-sub-hdr">Sales channel mix (2023)</div>
      <?php
      $waxCh = [
        'Private Retailer'=>['q'=>$wax['2023']['pq'],'c'=>'#F2A900'],
        'Local Shop'      =>['q'=>$wax['2023']['lq'],'c'=>'#42A5F5'],
        'Consumer'        =>['q'=>$wax['2023']['cq'],'c'=>'#66BB6A'],
        'Honey Unit'      =>['q'=>$wax['2023']['hq'],'c'=>'#FF8A65'],
        'Fair/Exhibition' =>['q'=>$wax['2023']['fq'],'c'=>'#CE93D8'],
      ];
      foreach($waxCh as $ch => $cv): if($cv['q'] <= 0) continue; ?>
      <div class="bkcp-ch-row">
        <span class="bkcp-ch-dot" style="background:<?= $cv['c'] ?>"></span>
        <span class="bkcp-ch-name"><?= $ch ?></span>
        <div class="bkcp-ch-track"><div class="bkcp-ch-fill" style="width:<?= min(100,(int)round($cv['q'])) ?>%;background:<?= $cv['c'] ?>"></div></div>
        <span class="bkcp-ch-pct" style="color:<?= $cv['c'] ?>"><?= $cv['q'] ?>%</span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pollen detail -->
    <div class="bkcp-chart-card">
      <div class="bkcp-card-hdr" style="--pcc:#F9A825">
        <span class="bkcp-card-ico">🌼</span>
        <div>
          <div class="bkcp-card-title">Pollen — 3-Year Trend</div>
          <div class="bkcp-card-sub">परागकण · Output, Price &amp; Channel Mix</div>
        </div>
      </div>

      <div class="bkcp-year-grid">
        <?php foreach($pol as $yr => $d):
          $maxPP = max(array_column($pol,'pp'));
          $barW = $maxPP > 0 ? (int)round($d['pp']/$maxPP*100) : 0;
        ?>
        <div class="bkcp-yr-stat">
          <div class="bkcp-yr-label"><?= $yr ?></div>
          <div class="bkcp-yr-n"><?= number_format((int)$d['n']) ?> farmers</div>
          <div class="bkcp-yr-out"><?= $d['out_kg'] ?> kg avg</div>
          <div class="bkcp-price-bar-wrap">
            <div class="bkcp-price-bar" style="width:<?= $barW ?>%;background:#F9A825"></div>
          </div>
          <div class="bkcp-yr-price" style="color:#F9A825">₹<?= number_format((int)$d['pp']) ?>/kg</div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="bkcp-sub-hdr">Sales channel mix (2023)</div>
      <?php
      $polCh = [
        'Private Retailer'=>['q'=>$pol['2023']['pq'],'c'=>'#F2A900'],
        'Local Shop'      =>['q'=>$pol['2023']['lq'],'c'=>'#42A5F5'],
        'Consumer'        =>['q'=>$pol['2023']['cq'],'c'=>'#66BB6A'],
        'Fair/Exhibition' =>['q'=>$pol['2023']['fq'],'c'=>'#CE93D8'],
      ];
      foreach($polCh as $ch => $cv): if($cv['q'] <= 0) continue; ?>
      <div class="bkcp-ch-row">
        <span class="bkcp-ch-dot" style="background:<?= $cv['c'] ?>"></span>
        <span class="bkcp-ch-name"><?= $ch ?></span>
        <div class="bkcp-ch-track"><div class="bkcp-ch-fill" style="width:<?= min(100,(int)round($cv['q'])) ?>%;background:<?= $cv['c'] ?>"></div></div>
        <span class="bkcp-ch-pct" style="color:<?= $cv['c'] ?>"><?= $cv['q'] ?>%</span>
      </div>
      <?php endforeach; ?>

      <div class="bkcp-minor-note">
        Royal Jelly, Propolis &amp; Venom: negligible production (&lt;5 farmers). Pollen price rising: ₹<?= (int)$pol['2021']['pp'] ?> → ₹<?= (int)$pol['2022']['pp'] ?> → ₹<?= (int)$pol['2023']['pp'] ?>/kg
      </div>
    </div>

  </div><!-- /.bkcp-charts -->
</div>
