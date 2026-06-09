<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Section 30
$r30 = $pdo->query("
  SELECT
    SUM(CASE WHEN beekeeping_contract_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_contract,
    SUM(CASE WHEN beekeeping_contract_YES_NO='No'  THEN 1 ELSE 0 END) AS n_no_contract
  FROM bk_data_part8
")->fetch(PDO::FETCH_ASSOC);

// Contract parameters (normalised)
$params_raw = $pdo->query("
  SELECT parameters_of_contract AS v, COUNT(*) AS n
  FROM bk_data_part8
  WHERE parameters_of_contract != '' AND beekeeping_contract_YES_NO='Yes'
  GROUP BY v
")->fetchAll(PDO::FETCH_ASSOC);

$paramCounts = [];
foreach($params_raw as $p){
  $v = $p['v']; $n = (int)$p['n'];
  if(stripos($v,'Output')    !== false) $paramCounts['Output (Quantity)']  = ($paramCounts['Output (Quantity)']  ?? 0) + $n;
  if(stripos($v,'Price')     !== false) $paramCounts['Price (MRP)']         = ($paramCounts['Price (MRP)']         ?? 0) + $n;
  if(stripos($v,'Input')     !== false) $paramCounts['Input (Supply/Cost)'] = ($paramCounts['Input (Supply/Cost)'] ?? 0) + $n;
  if(stripos($v,'Quality')   !== false) $paramCounts['Quality (Nutrition)'] = ($paramCounts['Quality (Nutrition)'] ?? 0) + $n;
}
arsort($paramCounts);
$maxP = max($paramCounts) ?: 1;

// Section 31
$r31 = $pdo->query("
  SELECT
    SUM(CASE WHEN Sales_variety_wise__Yes_or_No_='Yes' THEN 1 ELSE 0 END) AS n_variety,
    SUM(CASE WHEN Sales_variety_wise__Yes_or_No_='No'  THEN 1 ELSE 0 END) AS n_no_variety
  FROM bk_data_part8
")->fetch(PDO::FETCH_ASSOC);

// Variety names from VAR00001
$var_names = $pdo->query("
  SELECT VAR00001_Honey_Varity_Name AS v, COUNT(*) AS n
  FROM bk_data_part8
  WHERE VAR00001_Honey_Varity_Name != ''
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);
$maxVN = $var_names ? max(array_column($var_names,'n')) : 1;

$n_contract = (int)$r30['n_contract'];
$n_variety  = (int)$r31['n_variety'];

function pctCT($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }

$param_colors = ['Output (Quantity)'=>'#F2A900','Price (MRP)'=>'#42A5F5','Input (Supply/Cost)'=>'#66BB6A','Quality (Nutrition)'=>'#AB47BC'];
?>

<!-- ══ BK CONTRACT & VARIETY ══ -->
<div id="p-bkcontract" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#37474F">Contract &amp; Variety Sales · अनुबंध एवं विविधता बिक्री</span>
      <div class="sec-title">Beekeeping Contract Detail &amp; Sales Variety-wise</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkct-kpi-row">
    <?php
    $kpis = [
      ['v'=>$n_contract,              'p'=>pctCT($n_contract,$total).'% of farmers',   'l'=>'Under Contract',         'h'=>'अनुबंध पर किसान',      'c'=>'#37474F'],
      ['v'=>'100%',                   'p'=>'of contractors',                            'l'=>'Oral Contracts',         'h'=>'मौखिक अनुबंध',          'c'=>'#546E7A'],
      ['v'=>$paramCounts['Output (Quantity)'] ?? 0,
                                      'p'=>pctCT($paramCounts['Output (Quantity)']??0,$n_contract).'%', 'l'=>'Output-based Param', 'h'=>'उत्पादन आधारित', 'c'=>'#F2A900'],
      ['v'=>$n_variety,               'p'=>pctCT($n_variety,$total).'% of farmers',    'l'=>'Variety-wise Sellers',   'h'=>'विविधता अनुसार बिक्री', 'c'=>'#2E7D32'],
      ['v'=>$total-$n_variety,        'p'=>pctCT($total-$n_variety,$total).'%',         'l'=>'Non-variety Sellers',    'h'=>'सामान्य बिक्री',         'c'=>'#78909C'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkct-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkct-kv"><?= $k['v'] ?></div>
      <div class="bkct-kp"><?= $k['p'] ?></div>
      <div class="bkct-kl"><?= $k['l'] ?></div>
      <div class="bkct-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkct-charts">

    <!-- Col 1: Contract details -->
    <div class="bkct-chart-card">
      <div class="bkct-card-title">Contract Detail (Section 30)</div>
      <div class="bkct-card-sub"><?= $n_contract ?> farmers (<?= pctCT($n_contract,$total) ?>%) working under contract</div>

      <!-- Contract type -->
      <div class="bkct-fact-row">
        <span class="bkct-fact-icon">📋</span>
        <div class="bkct-fact-body">
          <span class="bkct-fact-label">Contract Type</span>
          <span class="bkct-fact-val">Oral (मौखिक) — all <?= $n_contract ?> contracts</span>
        </div>
      </div>
      <div class="bkct-fact-row">
        <span class="bkct-fact-icon">🏢</span>
        <div class="bkct-fact-body">
          <span class="bkct-fact-label">Firm / Contractor</span>
          <span class="bkct-fact-val">Various local / unspecified parties</span>
        </div>
      </div>

      <!-- Parameters -->
      <div class="bkct-card-title" style="margin-top:14px">Contract Parameters</div>
      <div class="bkct-card-sub">What is governed by the contract</div>
      <?php foreach($paramCounts as $pname => $pn):
        $bw = (int)round($pn/$maxP*100);
        $color = $param_colors[$pname] ?? '#90A4AE';
      ?>
      <div class="bkct-bar-row">
        <span class="bkct-bar-label"><?= $pname ?></span>
        <div class="bkct-bar-wrap">
          <div class="bkct-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkct-bar-n" style="color:<?= $color ?>"><?= $pn ?></span>
        <span class="bkct-bar-pct"><?= pctCT($pn,$n_contract) ?>%</span>
      </div>
      <?php endforeach; ?>

      <!-- Coverage note -->
      <div class="bkct-note" style="margin-top:14px">
        <?= number_format($total - $n_contract) ?> farmers (<?= pctCT($total-$n_contract,$total) ?>%) operate without any formal or informal contract.
      </div>
    </div><!-- col1 -->

    <!-- Col 2: Variety sales -->
    <div class="bkct-chart-card">
      <div class="bkct-card-title">Sales Variety-wise (Section 31)</div>
      <div class="bkct-card-sub"><?= number_format($n_variety) ?> farmers (<?= pctCT($n_variety,$total) ?>%) sell honey variety-wise</div>

      <!-- Yes / No donut-style -->
      <div class="bkct-split-row">
        <div class="bkct-split-card" style="--sc:#2E7D32">
          <span class="bkct-split-v"><?= number_format($n_variety) ?></span>
          <span class="bkct-split-p"><?= pctCT($n_variety,$total) ?>%</span>
          <span class="bkct-split-l">Sell variety-wise</span>
          <span class="bkct-split-h">विविधता अनुसार</span>
        </div>
        <div class="bkct-split-card" style="--sc:#78909C">
          <span class="bkct-split-v"><?= number_format($total-$n_variety) ?></span>
          <span class="bkct-split-p"><?= pctCT($total-$n_variety,$total) ?>%</span>
          <span class="bkct-split-l">Sell in bulk</span>
          <span class="bkct-split-h">सामान्य बिक्री</span>
        </div>
      </div>

      <!-- Split bar -->
      <?php $vpct = pctCT($n_variety,$total); ?>
      <div class="bkct-split-bar-wrap">
        <div class="bkct-split-bar-y" style="width:<?= $vpct ?>%"></div>
        <div class="bkct-split-bar-n" style="width:<?= 100-$vpct ?>%"></div>
      </div>

      <!-- Variety names -->
      <div class="bkct-card-title" style="margin-top:16px">Honey Variety Names Reported</div>
      <div class="bkct-card-sub">Primary variety category for variety-wise sellers</div>
      <?php
      $vcolors = ['#F2A900','#42A5F5','#66BB6A','#AB47BC','#FF8A65'];
      $vi = 0;
      foreach($var_names as $vn):
        $bw = (int)round($vn['n']/$maxVN*100);
        $color = $vcolors[$vi++ % count($vcolors)];
      ?>
      <div class="bkct-bar-row">
        <span class="bkct-bar-label"><?= htmlspecialchars($vn['v']) ?></span>
        <div class="bkct-bar-wrap">
          <div class="bkct-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <span class="bkct-bar-n" style="color:<?= $color ?>"><?= number_format($vn['n']) ?></span>
        <span class="bkct-bar-pct"><?= pctCT($vn['n'],$n_variety) ?>%</span>
      </div>
      <?php endforeach; ?>

      <div class="bkct-note" style="margin-top:14px">
        Detailed variety-wise quantity and rate data (vari1–vari5) was not captured in the survey database at individual record level.
      </div>
    </div><!-- col2 -->

  </div><!-- /.bkct-charts -->

</div>
