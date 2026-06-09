<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    ROUND(AVG(CAST(NULLIF(sr1fc_beehive_box_number,'')         AS DECIMAL(10,2))),1)  AS avg_boxes,
    ROUND(AVG(CAST(NULLIF(sr1fc_per_box_cost,'')               AS DECIMAL(10,2))),0)  AS avg_box_cost,
    ROUND(AVG(CAST(NULLIF(sr1fc_own_capital,'')                AS DECIMAL(14,2))),0)  AS avg_own_cap,
    ROUND(SUM(CAST(NULLIF(sr1fc_own_capital,'')                AS DECIMAL(14,2))),0)  AS tot_own_cap,
    ROUND(AVG(CAST(NULLIF(sr1fc_borrowed_capital,'')           AS DECIMAL(14,2))),0)  AS avg_bor_cap,
    ROUND(SUM(CAST(NULLIF(sr1fc_borrowed_capital,'')           AS DECIMAL(14,2))),0)  AS tot_bor_cap,
    ROUND(AVG(CAST(NULLIF(sr1fc_borrowed_capital_intrest,'')   AS DECIMAL(14,2))),0)  AS avg_interest,
    COUNT(CASE WHEN CAST(NULLIF(sr1fc_borrowed_capital,'')     AS DECIMAL(14,2)) > 0  THEN 1 END) AS n_borrowed,

    ROUND(AVG(CAST(NULLIF(sr1vc_total_unskilled_labour_cost,'') AS DECIMAL(14,2))),0) AS avg_unskl,
    ROUND(AVG(CAST(NULLIF(sr1vc_total_skilled_labour_cost,'')  AS DECIMAL(14,2))),0)  AS avg_skl,
    ROUND(AVG(CAST(NULLIF(sr1vc_raw_material,'')               AS DECIMAL(14,2))),0)  AS avg_raw,
    ROUND(AVG(CAST(NULLIF(sr1vc_sugar_price,'')                AS DECIMAL(14,2))),0)  AS avg_sugar,
    ROUND(AVG(CAST(NULLIF(sr1vc_pest_management_cost,'')       AS DECIMAL(14,2))),0)  AS avg_pest,
    ROUND(AVG(CAST(NULLIF(sr1vc_water_cost,'')                 AS DECIMAL(14,2))),0)  AS avg_water,
    ROUND(AVG(CAST(NULLIF(sr1vc_electricity_cost,'')           AS DECIMAL(14,2))),0)  AS avg_elec,
    ROUND(AVG(CAST(NULLIF(sr1vc_picking_cost,'')               AS DECIMAL(14,2))),0)  AS avg_pick,
    ROUND(AVG(CAST(NULLIF(sr1vc_packing_cost,'')               AS DECIMAL(14,2))),0)  AS avg_pack,
    ROUND(AVG(CAST(NULLIF(sr1vc_transportation_cost,'')        AS DECIMAL(14,2))),0)  AS avg_trans,
    ROUND(AVG(CAST(NULLIF(sr1vc_miscellaneous_expenses,'')     AS DECIMAL(14,2))),0)  AS avg_misc,
    ROUND(AVG(CAST(NULLIF(sr1vc_profit,'')                     AS DECIMAL(14,2))),0)  AS avg_profit,
    ROUND(SUM(CAST(NULLIF(sr1vc_profit,'')                     AS DECIMAL(14,2))),0)  AS tot_profit,
    ROUND(AVG(CAST(NULLIF(sr1vc_labour_number,'')              AS DECIMAL(10,2))),1)  AS avg_unskl_n,
    ROUND(AVG(CAST(NULLIF(sr1vc_skilledlabour_number,'')       AS DECIMAL(10,2))),1)  AS avg_skl_n,
    ROUND(AVG(CAST(NULLIF(sr1vc_family_labour_day_number,'')   AS DECIMAL(10,2))),1)  AS avg_fam_days,
    ROUND(AVG(CAST(NULLIF(sr1vc_per_worker_wage,'')            AS DECIMAL(10,2))),0)  AS avg_wage,
    ROUND(AVG(CAST(NULLIF(sr1vc_per_worker_skilledwage,'')     AS DECIMAL(10,2))),0)  AS avg_skl_wage,
    ROUND(AVG(CAST(NULLIF(sr1vc_worker_day_number,'')          AS DECIMAL(10,2))),1)  AS avg_unskl_days,
    ROUND(AVG(CAST(NULLIF(sr1vc_skilledworker_day_number,'')   AS DECIMAL(10,2))),1)  AS avg_skl_days
  FROM bk_data_part2
")->fetch(PDO::FETCH_ASSOC);

// Compute totals
$vcItems = [
  'Transportation'   => (int)$d['avg_trans'],
  'Sugar'            => (int)$d['avg_sugar'],
  'Skilled Labour'   => (int)$d['avg_skl'],
  'Miscellaneous'    => (int)$d['avg_misc'],
  'Picking'          => (int)$d['avg_pick'],
  'Raw Material'     => (int)$d['avg_raw'],
  'Pest Management'  => (int)$d['avg_pest'],
  'Packing'          => (int)$d['avg_pack'],
  'Unskilled Labour' => (int)$d['avg_unskl'],
  'Water'            => (int)$d['avg_water'],
  'Electricity'      => (int)$d['avg_elec'],
];
$vcColors = [
  'Transportation'=>'#1565C0','Sugar'=>'#E65100','Skilled Labour'=>'#7B1FA2',
  'Miscellaneous'=>'#546E7A','Picking'=>'#2E7D32','Raw Material'=>'#F57F17',
  'Pest Management'=>'#AD1457','Packing'=>'#0277BD','Unskilled Labour'=>'#558B2F',
  'Water'=>'#00838F','Electricity'=>'#F9A825',
];
$totalVC = array_sum($vcItems);
$maxVC   = max($vcItems);

function lakh9($n)  { return '₹' . round($n/100000, 1) . ' L'; }
function crore9($n) { return '₹' . round($n/10000000, 1) . ' Cr'; }
function pct9($n,$d){ return $d > 0 ? (int)round($n/$d*100) : 0; }

$borrowedPct = pct9((int)$d['n_borrowed'], $total);
$ownShare    = ($d['avg_own_cap'] + $d['avg_bor_cap']) > 0
             ? pct9((int)$d['avg_own_cap'], (int)$d['avg_own_cap'] + (int)$d['avg_bor_cap'])
             : 100;
$borShare    = 100 - $ownShare;
?>

<!-- ══ BK COSTS ══ -->
<div id="p-bkcosts" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#00695C">Cost of Production · उत्पादन लागत</span>
      <div class="sec-title">Cost of Beekeeping Production Process</div>
    </div>
  </div>

  <!-- KPI Strip -->
  <div class="bkc-kpi-row">
    <div class="bkc-kpi" style="--kc:#1565C0">
      <div class="bkc-kpi-val"><?= number_format((float)$d['avg_boxes'],1) ?></div>
      <div class="bkc-kpi-lbl">Avg Beehive Boxes</div>
      <div class="bkc-kpi-sub">per farmer</div>
    </div>
    <div class="bkc-kpi" style="--kc:#E65100">
      <div class="bkc-kpi-val">₹<?= number_format((int)$d['avg_box_cost']) ?></div>
      <div class="bkc-kpi-lbl">Avg Cost / Box</div>
      <div class="bkc-kpi-sub">fixed capital</div>
    </div>
    <div class="bkc-kpi" style="--kc:#2E7D32">
      <div class="bkc-kpi-val"><?= lakh9((int)$d['avg_own_cap']) ?></div>
      <div class="bkc-kpi-lbl">Avg Own Capital</div>
      <div class="bkc-kpi-sub">self-funded investment</div>
    </div>
    <div class="bkc-kpi" style="--kc:#7B1FA2">
      <div class="bkc-kpi-val"><?= $borrowedPct ?>%</div>
      <div class="bkc-kpi-lbl">Farmers Borrowed</div>
      <div class="bkc-kpi-sub"><?= number_format((int)$d['n_borrowed']) ?> of <?= number_format($total) ?></div>
    </div>
    <div class="bkc-kpi" style="--kc:#00695C">
      <div class="bkc-kpi-val"><?= lakh9($totalVC) ?></div>
      <div class="bkc-kpi-lbl">Avg Variable Cost</div>
      <div class="bkc-kpi-sub">per farmer / season</div>
    </div>
    <div class="bkc-kpi" style="--kc:#F2A900">
      <div class="bkc-kpi-val"><?= lakh9((int)$d['avg_profit']) ?></div>
      <div class="bkc-kpi-lbl">Avg Net Profit</div>
      <div class="bkc-kpi-sub">per farmer / season</div>
    </div>
  </div>

  <!-- 3-col chart row -->
  <div class="bkc-charts">

    <!-- Card 1: Fixed costs / capital structure -->
    <div class="bkc-chart-card">
      <div class="bkc-card-title">Fixed Costs — Capital Structure</div>
      <div class="bkc-card-sub">स्थायी लागत · पूंजी संरचना</div>

      <!-- Own vs Borrowed split bar -->
      <div class="bkc-cap-split-wrap">
        <div class="bkc-cap-split">
          <div class="bkc-cap-seg" style="width:<?= $ownShare ?>%;background:#2E7D32" title="Own Capital <?= $ownShare ?>%"></div>
          <div class="bkc-cap-seg" style="width:<?= $borShare ?>%;background:#C62828" title="Borrowed <?= $borShare ?>%"></div>
        </div>
        <div class="bkc-cap-leg">
          <div class="bkc-cap-leg-item">
            <span class="bkc-cap-dot" style="background:#2E7D32"></span>
            <span>Own Capital</span>
            <strong style="color:#2E7D32"><?= $ownShare ?>%</strong>
            <span class="bkc-cap-sub"><?= lakh9((int)$d['avg_own_cap']) ?> avg</span>
          </div>
          <div class="bkc-cap-leg-item">
            <span class="bkc-cap-dot" style="background:#C62828"></span>
            <span>Borrowed Capital</span>
            <strong style="color:#C62828"><?= $borShare ?>%</strong>
            <span class="bkc-cap-sub"><?= lakh9((int)$d['avg_bor_cap']) ?> avg</span>
          </div>
        </div>
      </div>

      <div class="bkc-sub-hdr">Key Fixed Cost Metrics</div>
      <div class="bkc-fc-grid">
        <div class="bkc-fc-stat" style="--fcc:#1565C0">
          <div class="bkc-fc-val"><?= number_format((float)$d['avg_boxes'],0) ?></div>
          <div class="bkc-fc-lbl">Avg Boxes</div>
        </div>
        <div class="bkc-fc-stat" style="--fcc:#E65100">
          <div class="bkc-fc-val">₹<?= number_format((int)$d['avg_box_cost']) ?></div>
          <div class="bkc-fc-lbl">Cost / Box</div>
        </div>
        <div class="bkc-fc-stat" style="--fcc:#7B1FA2">
          <div class="bkc-fc-val">₹<?= number_format((int)$d['avg_interest']) ?></div>
          <div class="bkc-fc-lbl">Avg Interest</div>
        </div>
        <div class="bkc-fc-stat" style="--fcc:#2E7D32">
          <div class="bkc-fc-val"><?= crore9((int)$d['tot_own_cap']) ?></div>
          <div class="bkc-fc-lbl">Total Own Cap</div>
        </div>
        <div class="bkc-fc-stat" style="--fcc:#C62828">
          <div class="bkc-fc-val"><?= crore9((int)$d['tot_bor_cap']) ?></div>
          <div class="bkc-fc-lbl">Total Borrowed</div>
        </div>
        <div class="bkc-fc-stat" style="--fcc:#00695C">
          <div class="bkc-fc-val"><?= $borrowedPct ?>%</div>
          <div class="bkc-fc-lbl">Farmers Borrowed</div>
        </div>
      </div>
    </div>

    <!-- Card 2: Variable cost breakdown -->
    <div class="bkc-chart-card">
      <div class="bkc-card-title">Variable Cost Breakdown</div>
      <div class="bkc-card-sub">परिवर्तनीय लागत · avg per farmer (₹)</div>

      <?php foreach($vcItems as $label => $amt):
        $barW = $maxVC > 0 ? (int)round($amt/$maxVC*100) : 0;
        $share = $totalVC > 0 ? (int)round($amt/$totalVC*100) : 0;
        $col = $vcColors[$label] ?? '#78909C';
      ?>
      <div class="bkc-vc-row">
        <div class="bkc-vc-label"><?= $label ?></div>
        <div class="bkc-bar-track">
          <div class="bkc-bar-fill" style="width:<?= $barW ?>%;background:<?= $col ?>"></div>
        </div>
        <div class="bkc-vc-meta">
          <span class="bkc-vc-amt" style="color:<?= $col ?>">₹<?= number_format($amt) ?></span>
          <span class="bkc-vc-share"><?= $share ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkc-vc-total">
        Total Variable Cost: <strong>₹<?= number_format($totalVC) ?></strong> avg / farmer
      </div>
    </div>

    <!-- Card 3: Labour + Profit -->
    <div class="bkc-chart-card">
      <div class="bkc-card-title">Labour Profile &amp; Profitability</div>
      <div class="bkc-card-sub">श्रमिक विवरण एवं लाभप्रदता</div>

      <div class="bkc-sub-hdr">Labour Deployment (avg per farmer)</div>
      <div class="bkc-labour-grid">
        <div class="bkc-lab-card" style="--lc:#C62828">
          <div class="bkc-lab-ico">👷</div>
          <div class="bkc-lab-type">Unskilled</div>
          <div class="bkc-lab-val"><?= $d['avg_unskl_n'] ?></div>
          <div class="bkc-lab-lbl">workers</div>
          <div class="bkc-lab-chip"><?= (float)$d['avg_unskl_days'] ?? '–' ?> days · ₹<?= number_format((int)$d['avg_wage']) ?>/day</div>
        </div>
        <div class="bkc-lab-card" style="--lc:#7B1FA2">
          <div class="bkc-lab-ico">🧑‍🔬</div>
          <div class="bkc-lab-type">Skilled</div>
          <div class="bkc-lab-val"><?= $d['avg_skl_n'] ?></div>
          <div class="bkc-lab-lbl">workers</div>
          <div class="bkc-lab-chip"><?= (float)$d['avg_skl_days'] ?? '–' ?> days · ₹<?= number_format((int)$d['avg_skl_wage']) ?>/day</div>
        </div>
        <div class="bkc-lab-card" style="--lc:#2E7D32">
          <div class="bkc-lab-ico">👨‍👩‍👦</div>
          <div class="bkc-lab-type">Family</div>
          <div class="bkc-lab-val"><?= $d['avg_fam_days'] ?></div>
          <div class="bkc-lab-lbl">person-days</div>
          <div class="bkc-lab-chip">unpaid family labour</div>
        </div>
      </div>

      <div class="bkc-sub-hdr" style="margin-top:16px">Profitability</div>
      <div class="bkc-profit-wrap">
        <div class="bkc-profit-bar-wrap">
          <div class="bkc-profit-label">Variable Cost</div>
          <?php $vcShare = ($totalVC + (int)$d['avg_profit']) > 0 ? pct9($totalVC, $totalVC + (int)$d['avg_profit']) : 0; ?>
          <div class="bkc-profit-track">
            <div class="bkc-profit-cost" style="width:<?= $vcShare ?>%"></div>
            <div class="bkc-profit-gain" style="width:<?= 100 - $vcShare ?>%"></div>
          </div>
          <div class="bkc-profit-label" style="text-align:right">Profit</div>
        </div>
        <div class="bkc-profit-stats">
          <div class="bkc-ps-item">
            <span class="bkc-ps-dot" style="background:#C62828"></span>
            <span>Avg Cost</span>
            <strong style="color:#C62828">₹<?= number_format($totalVC) ?></strong>
          </div>
          <div class="bkc-ps-item">
            <span class="bkc-ps-dot" style="background:#2E7D32"></span>
            <span>Avg Profit</span>
            <strong style="color:#2E7D32"><?= lakh9((int)$d['avg_profit']) ?></strong>
          </div>
          <div class="bkc-ps-item">
            <span class="bkc-ps-dot" style="background:#F2A900"></span>
            <span>Total Profit</span>
            <strong style="color:#F2A900"><?= crore9((int)$d['tot_profit']) ?></strong>
          </div>
        </div>
      </div>

    </div><!-- /.bkc-chart-card -->
  </div><!-- /.bkc-charts -->
</div>
