<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    SUM(CASE WHEN beekeeping_help_horticulture_department_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_help,
    SUM(CASE WHEN beekeeping_boxes_YES_NO='Yes'              THEN 1 ELSE 0 END) AS n_bb,
    ROUND(AVG(CAST(NULLIF(bb_help_dbt_cash,'')     AS DECIMAL(14,2))),0) AS avg_bb_dbt,
    SUM(CAST(NULLIF(bb_help_dbt_cash,'')           AS DECIMAL(14,2))) AS sum_bb_dbt,
    SUM(CASE WHEN honeyprocessing_branding_YES_NO='Yes'      THEN 1 ELSE 0 END) AS n_hpbgp,
    ROUND(AVG(CAST(NULLIF(hpbgp_help_dbt_cash,'')  AS DECIMAL(14,2))),0) AS avg_hpbgp_dbt,
    SUM(CAST(NULLIF(hpbgp_help_dbt_cash,'')        AS DECIMAL(14,2))) AS sum_hpbgp_dbt,
    SUM(CASE WHEN operation_honey_processing_YES_NO='Yes'    THEN 1 ELSE 0 END) AS n_ohpu,
    ROUND(AVG(CAST(NULLIF(ohpu_help_dbt_cash,'')   AS DECIMAL(14,2))),0) AS avg_ohpu_dbt,
    SUM(CAST(NULLIF(ohpu_help_dbt_cash,'')         AS DECIMAL(14,2))) AS sum_ohpu_dbt,
    SUM(CASE WHEN other_help_YES_NO='Yes'                    THEN 1 ELSE 0 END) AS n_other,
    ROUND(AVG(CAST(NULLIF(oh_help_dbt_cash,'')     AS DECIMAL(14,2))),0) AS avg_oh_dbt,
    SUM(CAST(NULLIF(oh_help_dbt_cash,'')           AS DECIMAL(14,2))) AS sum_oh_dbt
  FROM bk_data_part8
")->fetch(PDO::FETCH_ASSOC);

// Scheme names for beekeeping boxes
$bb_schemes = $pdo->query("
  SELECT bb_help_scheme_name AS v, COUNT(*) AS n
  FROM bk_data_part8
  WHERE bb_help_scheme_name != '' AND beekeeping_boxes_YES_NO='Yes'
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Year distribution of box help
$bb_years = $pdo->query("
  SELECT bb_help_year AS v, COUNT(*) AS n
  FROM bk_data_part8
  WHERE bb_help_year != '' AND beekeeping_boxes_YES_NO='Yes'
  GROUP BY v ORDER BY v ASC
")->fetchAll(PDO::FETCH_ASSOC);

$n_help  = (int)$d['n_help'];
$n_bb    = (int)$d['n_bb'];
$n_hpbgp = (int)$d['n_hpbgp'];
$n_ohpu  = (int)$d['n_ohpu'];
$n_other = (int)$d['n_other'];

function pctH23($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }
function lakhH23($n){ return '₹' . round($n/100000, 1) . ' L'; }
function crH23($n)  { return '₹' . round($n/10000000, 2) . ' Cr'; }

// Normalise scheme labels
function schemeLabel($raw){
  if(stripos($raw,'National Beekeeping') !== false) return 'National BK & Honey Mission';
  if(stripos($raw,'Sweet Revolution')    !== false) return 'Sweet Revolution (KVIC)';
  if(stripos($raw,'Bee Breeders')        !== false) return 'Dev. of Bee Breeders';
  if(stripos($raw,'FPO')                 !== false || stripos($raw,'NAFED') !== false) return 'FPO/NAFED';
  if(stripos($raw,'Others')              !== false || stripos($raw,'Other') !== false) return 'Others';
  return $raw;
}

$scheme_colors = [
  'National BK & Honey Mission' => '#F2A900',
  'Sweet Revolution (KVIC)'     => '#42A5F5',
  'Dev. of Bee Breeders'        => '#66BB6A',
  'FPO/NAFED'                   => '#AB47BC',
  'Others'                      => '#90A4AE',
];

$schemeAgg = [];
foreach($bb_schemes as $s){
  $lbl = schemeLabel($s['v']);
  $schemeAgg[$lbl] = ($schemeAgg[$lbl] ?? 0) + (int)$s['n'];
}
arsort($schemeAgg);
$maxSch = max($schemeAgg) ?: 1;
$maxYr  = $bb_years ? max(array_column($bb_years,'n')) : 1;

$help_types = [
  ['label'=>'Beekeeping Boxes',           'hindi'=>'मधुमक्खी पालन बक्से',              'n'=>$n_bb,    'avg'=>(int)$d['avg_bb_dbt'],    'sum'=>(float)$d['sum_bb_dbt'],    'color'=>'#F2A900'],
  ['label'=>'Other Help',                 'hindi'=>'अन्य सहायता',                       'n'=>$n_other, 'avg'=>(int)$d['avg_oh_dbt'],    'sum'=>(float)$d['sum_oh_dbt'],    'color'=>'#42A5F5'],
  ['label'=>'Honey Processing Unit Op.',  'hindi'=>'शहद प्रसंस्करण इकाई संचालन',       'n'=>$n_ohpu,  'avg'=>(int)$d['avg_ohpu_dbt'],  'sum'=>(float)$d['sum_ohpu_dbt'],  'color'=>'#FF8A65'],
  ['label'=>'Branding / Grading / Pack',  'hindi'=>'ब्रांडिंग/ग्रेडिंग/पैकेजिंग',     'n'=>$n_hpbgp,'avg'=>(int)$d['avg_hpbgp_dbt'],'sum'=>(float)$d['sum_hpbgp_dbt'], 'color'=>'#66BB6A'],
];
$maxHT = max(array_column($help_types,'n')) ?: 1;

$total_dbt = (float)$d['sum_bb_dbt'] + (float)$d['sum_oh_dbt'] + (float)$d['sum_ohpu_dbt'] + (float)$d['sum_hpbgp_dbt'];
?>

<!-- ══ BK DEPT HELP ══ -->
<div id="p-bkhelp" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#1B5E20">Govt. Help · सरकारी सहायता</span>
      <div class="sec-title">Help from District Horticulture Department</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkh-kpi-row">
    <?php
    $kpis = [
      ['v'=>$n_help,                       'p'=>pctH23($n_help,$total).'% of farmers', 'l'=>'Received Dept. Help',  'h'=>'सहायता प्राप्त किसान',  'c'=>'#1B5E20'],
      ['v'=>$n_bb,                         'p'=>pctH23($n_bb,$n_help).'% of helped',   'l'=>'Got Beekeeping Boxes', 'h'=>'बक्से प्राप्त',          'c'=>'#F2A900'],
      ['v'=>'₹'.number_format((int)$d['avg_bb_dbt']),'p'=>'avg DBT per farmer',        'l'=>'Avg Box DBT Cash',    'h'=>'औसत DBT राशि (बक्से)',   'c'=>'#E65100'],
      ['v'=>crH23($total_dbt),             'p'=>'total DBT across all types',           'l'=>'Total DBT Disbursed', 'h'=>'कुल DBT वितरण',          'c'=>'#2E7D32'],
      ['v'=>$n_other,                      'p'=>pctH23($n_other,$n_help).'% of helped', 'l'=>'Other Help',          'h'=>'अन्य सहायता प्राप्त',   'c'=>'#0277BD'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkh-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkh-kv"><?= $k['v'] ?></div>
      <div class="bkh-kp"><?= $k['p'] ?></div>
      <div class="bkh-kl"><?= $k['l'] ?></div>
      <div class="bkh-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkh-charts">

    <!-- Col 1: Help type breakdown -->
    <div class="bkh-chart-card">
      <div class="bkh-card-title">Help Type Distribution</div>
      <div class="bkh-card-sub">Farmers by type of horticulture dept. help received</div>

      <?php foreach($help_types as $ht):
        $bw = (int)round($ht['n']/$maxHT*100);
      ?>
      <div class="bkh-type-row">
        <div class="bkh-type-labels">
          <span class="bkh-type-name"><?= $ht['label'] ?></span>
          <span class="bkh-type-hindi"><?= $ht['hindi'] ?></span>
        </div>
        <div class="bkh-type-bar-wrap">
          <div class="bkh-type-bar" style="width:<?= $bw ?>%;background:<?= $ht['color'] ?>"></div>
        </div>
        <div class="bkh-type-meta">
          <span class="bkh-type-n" style="color:<?= $ht['color'] ?>"><?= $ht['n'] ?></span>
          <span class="bkh-type-pct"><?= pctH23($ht['n'],$n_help) ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- DBT summary table -->
      <div class="bkh-card-title" style="margin-top:16px">DBT Cash Received (by Type)</div>
      <div class="bkh-dbt-table">
        <?php foreach($help_types as $ht): if($ht['n'] == 0) continue; ?>
        <div class="bkh-dbt-row">
          <span class="bkh-dbt-dot" style="background:<?= $ht['color'] ?>"></span>
          <span class="bkh-dbt-label"><?= $ht['label'] ?></span>
          <span class="bkh-dbt-avg" style="color:<?= $ht['color'] ?>">₹<?= number_format($ht['avg']) ?></span>
          <span class="bkh-dbt-total"><?= lakhH23($ht['sum']) ?> total</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div><!-- col1 -->

    <!-- Col 2: Scheme breakdown for boxes -->
    <div class="bkh-chart-card">
      <div class="bkh-card-title">Scheme-wise Box Distribution</div>
      <div class="bkh-card-sub">Under which scheme beekeeping boxes were received (<?= $n_bb ?> farmers)</div>

      <?php foreach($schemeAgg as $sname => $sn):
        $bw = (int)round($sn/$maxSch*100);
        $color = $scheme_colors[$sname] ?? '#90A4AE';
      ?>
      <div class="bkh-sch-row">
        <div class="bkh-sch-labels">
          <span class="bkh-sch-name"><?= $sname ?></span>
        </div>
        <div class="bkh-sch-bar-wrap">
          <div class="bkh-sch-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <div class="bkh-sch-meta">
          <span class="bkh-sch-n" style="color:<?= $color ?>"><?= $sn ?></span>
          <span class="bkh-sch-pct"><?= pctH23($sn,$n_bb) ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div><!-- col2 -->

    <!-- Col 3: Year trend of box help -->
    <div class="bkh-chart-card">
      <div class="bkh-card-title">Year-wise Box Assistance</div>
      <div class="bkh-card-sub">When beekeeping boxes were distributed by the department</div>

      <?php foreach($bb_years as $yr):
        $bw = (int)round($yr['n']/$maxYr*100);
      ?>
      <div class="bkh-yr-row">
        <span class="bkh-yr-label"><?= htmlspecialchars($yr['v']) ?></span>
        <div class="bkh-yr-bar-wrap">
          <div class="bkh-yr-bar" style="width:<?= $bw ?>%"></div>
        </div>
        <span class="bkh-yr-n"><?= $yr['n'] ?></span>
      </div>
      <?php endforeach; ?>

      <!-- DBT note -->
      <div class="bkh-note">
        Total DBT disbursed across all 4 help types: <strong><?= crH23($total_dbt) ?></strong>.
        Beekeeping boxes account for <?= pctH23((float)$d['sum_bb_dbt'], $total_dbt) ?>% of total transfers.
      </div>
    </div><!-- col3 -->

  </div><!-- /.bkh-charts -->

</div>
