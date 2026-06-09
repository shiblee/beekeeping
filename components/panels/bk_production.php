<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Honey + part1 by-products
$h = $pdo->query("
  SELECT
    COUNT(CASE WHEN sr1_honey_qty!=''     THEN 1 END) AS n_honey,
    ROUND(SUM(CAST(NULLIF(sr1_honey_qty,'')          AS DECIMAL(14,2))),0) AS tot_honey_kg,
    ROUND(AVG(CAST(NULLIF(sr1_honey_qty,'')          AS DECIMAL(14,2))),0) AS avg_honey_kg,
    ROUND(AVG(CAST(NULLIF(sr1_honey_price,'')        AS DECIMAL(10,2))),0) AS avg_price,
    ROUND(AVG(CAST(NULLIF(sr1_honey_productivity,'') AS DECIMAL(10,2))),1) AS avg_prod,
    ROUND(SUM(CAST(NULLIF(sr1_Honey_Revenue_Rs_2023,'') AS DECIMAL(14,2))),0) AS tot_rev,
    ROUND(AVG(CAST(NULLIF(sr1_Honey_Revenue_Rs_2023,'') AS DECIMAL(14,2))),0) AS avg_rev,
    SUM(CASE WHEN CAST(NULLIF(sr1_honey_productivity,'') AS DECIMAL(10,1)) BETWEEN 20 AND 29.99 THEN 1 ELSE 0 END) AS prod_2030,
    SUM(CASE WHEN CAST(NULLIF(sr1_honey_productivity,'') AS DECIMAL(10,1)) >= 30               THEN 1 ELSE 0 END) AS prod_gt30,

    COUNT(CASE WHEN sr1_beeswax_qty!=''     THEN 1 END) AS n_wax,
    ROUND(AVG(CAST(NULLIF(sr1_beeswax_qty,'')    AS DECIMAL(10,2))),1) AS avg_wax_kg,
    ROUND(AVG(CAST(NULLIF(sr1_beeswax_price,'')  AS DECIMAL(10,2))),0) AS avg_wax_p,

    COUNT(CASE WHEN sr1_pollen_qty!=''     THEN 1 END) AS n_pol,
    ROUND(AVG(CAST(NULLIF(sr1_pollen_qty,'')     AS DECIMAL(10,2))),1) AS avg_pol_kg,
    ROUND(AVG(CAST(NULLIF(sr1_pollen_price,'')   AS DECIMAL(10,2))),0) AS avg_pol_p,

    COUNT(CASE WHEN sr1_royaljelly_qty!='' THEN 1 END) AS n_rj,
    ROUND(AVG(CAST(NULLIF(sr1_royaljelly_qty,'') AS DECIMAL(10,2))),1) AS avg_rj_kg,
    ROUND(AVG(CAST(NULLIF(sr1_royaljelly_price,'') AS DECIMAL(10,2))),0) AS avg_rj_p
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

// Part2 by-products + colony increase
$h2 = $pdo->query("
  SELECT
    COUNT(CASE WHEN sr1_propolis_qty!=''         THEN 1 END) AS n_prop,
    ROUND(AVG(CAST(NULLIF(sr1_propolis_qty,'')   AS DECIMAL(10,2))),1) AS avg_prop_kg,
    ROUND(AVG(CAST(NULLIF(sr1_propolis_price,'') AS DECIMAL(10,2))),0) AS avg_prop_p,
    COUNT(CASE WHEN sr1_rowbyproduct_qty!=''     THEN 1 END) AS n_rbp,
    ROUND(AVG(CAST(NULLIF(sr1_rowbyproduct_qty,'')    AS DECIMAL(10,2))),1) AS avg_rbp_kg,
    ROUND(AVG(CAST(NULLIF(sr1_rowbyproduct_price,'')  AS DECIMAL(10,2))),0) AS avg_rbp_p,
    ROUND(AVG(CAST(NULLIF(sr1_How_much_did_your_bee_colony_increase_last_year,'') AS DECIMAL(10,2))),1) AS avg_col_inc
  FROM bk_data_part2
")->fetch(PDO::FETCH_ASSOC);

// Bee varieties — parse multi-value field
$varRows = $pdo->query("SELECT sr1_bee_varities_name FROM bk_data_part1 WHERE sr1_bee_varities_name!=''")->fetchAll(PDO::FETCH_COLUMN);
$varCounts = [];
foreach($varRows as $row) {
  preg_match_all('/([A-Za-z]+) Honey/', $row, $m);
  foreach($m[1] as $v) { $v = trim($v); $varCounts[$v] = ($varCounts[$v] ?? 0) + 1; }
  if(strpos($row,'Others') !== false) $varCounts['Others'] = ($varCounts['Others'] ?? 0) + 1;
}
arsort($varCounts);

// Helpers
function pct8($n, $d) { return $d > 0 ? (int)round($n/$d*100) : 0; }
function crore($n)  { return round($n/10000000, 1) . ' Cr'; }
function lakh($n)   { return '₹' . round($n/100000, 2) . ' L'; }

$varColors = ['Mustard'=>'#F9A825','Eucalyptus'=>'#2E7D32','Others'=>'#78909C','Litchi'=>'#C62828','Sunflower'=>'#E65100'];

$byproducts = [
  ['name'=>'Beeswax',     'hindi'=>'मधुमोम',    'n'=>(int)$h['n_wax'],  'avg_kg'=>$h['avg_wax_kg'],  'price'=>(int)$h['avg_wax_p'],  'color'=>'#8D6E63'],
  ['name'=>'Pollen',      'hindi'=>'परागकण',    'n'=>(int)$h['n_pol'],  'avg_kg'=>$h['avg_pol_kg'],  'price'=>(int)$h['avg_pol_p'],  'color'=>'#FFC107'],
  ['name'=>'Row By-prod.','hindi'=>'कच्चा उप-उत्पाद','n'=>(int)$h2['n_rbp'],'avg_kg'=>$h2['avg_rbp_kg'],'price'=>(int)$h2['avg_rbp_p'],'color'=>'#546E7A'],
  ['name'=>'Propolis',    'hindi'=>'प्रोपोलिस',  'n'=>(int)$h2['n_prop'],'avg_kg'=>$h2['avg_prop_kg'],'price'=>(int)$h2['avg_prop_p'],'color'=>'#43A047'],
  ['name'=>'Royal Jelly', 'hindi'=>'रॉयल जेली', 'n'=>(int)$h['n_rj'],   'avg_kg'=>$h['avg_rj_kg'],  'price'=>(int)$h['avg_rj_p'],   'color'=>'#CE93D8'],
];
$maxBPN = max(array_column($byproducts, 'n'));
?>

<!-- ══ BK PRODUCTION ══ -->
<div id="p-bkprod" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#E65100">Production · उत्पादन विवरण</span>
      <div class="sec-title">Details of Beekeeping Production</div>
    </div>
  </div>

  <!-- KPI Strip -->
  <div class="bkp-kpi-row">
    <div class="bkp-kpi" style="--kc:#F2A900">
      <div class="bkp-kpi-val"><?= crore((int)$h['tot_honey_kg']) ?> kg</div>
      <div class="bkp-kpi-lbl">Total Honey Produced</div>
      <div class="bkp-kpi-sub">2023 season</div>
    </div>
    <div class="bkp-kpi" style="--kc:#E65100">
      <div class="bkp-kpi-val">₹<?= crore((int)$h['tot_rev']) ?></div>
      <div class="bkp-kpi-lbl">Total Honey Revenue</div>
      <div class="bkp-kpi-sub">across all farmers</div>
    </div>
    <div class="bkp-kpi" style="--kc:#1565C0">
      <div class="bkp-kpi-val"><?= lakh((int)$h['avg_rev']) ?></div>
      <div class="bkp-kpi-lbl">Avg Revenue / Farmer</div>
      <div class="bkp-kpi-sub">honey only</div>
    </div>
    <div class="bkp-kpi" style="--kc:#2E7D32">
      <div class="bkp-kpi-val">₹<?= number_format((int)$h['avg_price']) ?>/kg</div>
      <div class="bkp-kpi-lbl">Avg Honey Price</div>
      <div class="bkp-kpi-sub">farm gate</div>
    </div>
    <div class="bkp-kpi" style="--kc:#7B1FA2">
      <div class="bkp-kpi-val"><?= $h['avg_prod'] ?> kg/box</div>
      <div class="bkp-kpi-lbl">Avg Productivity</div>
      <div class="bkp-kpi-sub">per box per harvest</div>
    </div>
    <div class="bkp-kpi" style="--kc:#0277BD">
      <div class="bkp-kpi-val">+<?= $h2['avg_col_inc'] ?></div>
      <div class="bkp-kpi-lbl">Avg Colony Increase</div>
      <div class="bkp-kpi-sub">colonies added last year</div>
    </div>
  </div>

  <!-- 3-column chart row -->
  <div class="bkp-charts">

    <!-- Card 1: Bee Varieties -->
    <div class="bkp-chart-card">
      <div class="bkp-card-title">Honey Varieties Produced</div>
      <div class="bkp-card-sub">मधुमक्खी की किस्म (multi-select)</div>

      <?php foreach($varCounts as $vname => $vcnt):
        $vp = pct8($vcnt, $total);
        $col = $varColors[$vname] ?? '#78909C';
      ?>
      <div class="bkp-bar-row">
        <div class="bkp-bar-label"><?= htmlspecialchars($vname) ?> Honey</div>
        <div class="bkp-bar-track">
          <div class="bkp-bar-fill" style="width:<?= $vp ?>%;background:<?= $col ?>"></div>
        </div>
        <div class="bkp-bar-meta">
          <span class="bkp-bar-pct" style="color:<?= $col ?>"><?= $vp ?>%</span>
          <span class="bkp-bar-n"><?= number_format($vcnt) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="bkp-note">% of farmers producing each variety</div>
    </div>

    <!-- Card 2: Honey Productivity -->
    <div class="bkp-chart-card">
      <div class="bkp-card-title">Honey Production Details</div>
      <div class="bkp-card-sub">शहद उत्पादन विवरण</div>

      <div class="bkp-honey-stats">
        <div class="bkp-hstat" style="--hc:#F2A900">
          <div class="bkp-hstat-v"><?= number_format((int)$h['avg_honey_kg']) ?> kg</div>
          <div class="bkp-hstat-l">Avg Qty / Farmer</div>
        </div>
        <div class="bkp-hstat" style="--hc:#E65100">
          <div class="bkp-hstat-v">₹<?= number_format((int)$h['avg_price']) ?></div>
          <div class="bkp-hstat-l">Price / Kg (Rs)</div>
        </div>
        <div class="bkp-hstat" style="--hc:#7B1FA2">
          <div class="bkp-hstat-v"><?= $h['avg_prod'] ?> kg</div>
          <div class="bkp-hstat-l">Productivity / Box</div>
        </div>
      </div>

      <div class="bkp-sub-hdr">Productivity distribution (kg/box)</div>

      <?php
      $prodBkts = [
        ['label'=>'20–30 kg', 'n'=>(int)$h['prod_2030'], 'color'=>'#FFAB40'],
        ['label'=>'> 30 kg',  'n'=>(int)$h['prod_gt30'], 'color'=>'#F2A900'],
      ];
      $maxPB = max(array_column($prodBkts,'n'));
      foreach($prodBkts as $pb):
        $pw = $maxPB > 0 ? (int)round($pb['n']/$maxPB*100) : 0;
        $pp = pct8($pb['n'], $total);
      ?>
      <div class="bkp-bar-row">
        <div class="bkp-bar-label"><?= $pb['label'] ?></div>
        <div class="bkp-bar-track">
          <div class="bkp-bar-fill" style="width:<?= $pw ?>%;background:<?= $pb['color'] ?>"></div>
        </div>
        <div class="bkp-bar-meta">
          <span class="bkp-bar-pct" style="color:<?= $pb['color'] ?>"><?= $pp ?>%</span>
          <span class="bkp-bar-n"><?= number_format($pb['n']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkp-sub-hdr" style="margin-top:14px">Colony growth last year</div>
      <div class="bkp-big-stat" style="color:#0277BD">
        +<?= $h2['avg_col_inc'] ?> <span>colonies / farmer on average</span>
      </div>
    </div>

    <!-- Card 3: By-products -->
    <div class="bkp-chart-card">
      <div class="bkp-card-title">By-products Produced</div>
      <div class="bkp-card-sub">कच्चा सह-उत्पाद</div>

      <?php foreach($byproducts as $bp):
        $bpPct = pct8($bp['n'], $total);
        $bpW   = $maxBPN > 0 ? (int)round($bp['n']/$maxBPN*100) : 0;
      ?>
      <div class="bkp-bp-row">
        <div class="bkp-bp-label">
          <span class="bkp-bp-name" style="color:<?= $bp['color'] ?>"><?= $bp['name'] ?></span>
          <span class="bkp-bp-hindi"><?= $bp['hindi'] ?></span>
        </div>
        <div class="bkp-bar-track">
          <div class="bkp-bar-fill" style="width:<?= $bpW ?>%;background:<?= $bp['color'] ?>"></div>
        </div>
        <div class="bkp-bp-meta">
          <span class="bkp-bar-pct" style="color:<?= $bp['color'] ?>"><?= $bpPct ?>%</span>
          <span class="bkp-bar-n"><?= number_format($bp['n']) ?></span>
        </div>
        <?php if($bp['n'] > 0): ?>
        <div class="bkp-bp-chips">
          <span class="bkp-bp-chip"><?= $bp['avg_kg'] ?> kg avg</span>
          <span class="bkp-bp-chip">₹<?= number_format($bp['price']) ?>/kg</span>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div class="bkp-note" style="margin-top:8px">Bars show participation rate vs. top product (beeswax)</div>
    </div>

  </div><!-- /.bkp-charts -->
</div>
