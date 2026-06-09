<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$s = $pdo->query("
  SELECT
    SUM(CASE WHEN Q_No_7_beekeeping_type='Kind of Full-Time Business' THEN 1 ELSE 0 END) AS n_ft,
    SUM(CASE WHEN Q_No_7_beekeeping_type='As a Part-Time Business'   THEN 1 ELSE 0 END) AS n_pt,
    SUM(CASE WHEN Q_No_7_beekeeping_type='As an Ideal Interest'      THEN 1 ELSE 0 END) AS n_ii,

    SUM(CASE WHEN Beekeeping_migration_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_mig_yes,
    SUM(CASE WHEN Beekeeping_migration_YES_NO='No'  THEN 1 ELSE 0 END) AS n_mig_no,

    SUM(CASE WHEN Beekeeping_migration_YES_NO='Yes'
             AND CAST(NULLIF(Beekeeping_migration_number,'') AS UNSIGNED)=1
        THEN 1 ELSE 0 END) AS mig_once,
    SUM(CASE WHEN Beekeeping_migration_YES_NO='Yes'
             AND CAST(NULLIF(Beekeeping_migration_number,'') AS UNSIGNED)=2
        THEN 1 ELSE 0 END) AS mig_twice,
    SUM(CASE WHEN Beekeeping_migration_YES_NO='Yes'
             AND CAST(NULLIF(Beekeeping_migration_number,'') AS UNSIGNED)>=3
        THEN 1 ELSE 0 END) AS mig_3plus,
    ROUND(AVG(CASE WHEN Beekeeping_migration_YES_NO='Yes'
              THEN CAST(NULLIF(Beekeeping_migration_number,'') AS DECIMAL(5,1)) END),1) AS avg_mig,

    ROUND(AVG(CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2))),1) AS avg_honey,
    MIN(CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2)))          AS min_honey,
    MAX(CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2)))          AS max_honey,
    SUM(CASE WHEN CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2)) < 10           THEN 1 ELSE 0 END) AS h_lt10,
    SUM(CASE WHEN CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2)) BETWEEN 10 AND 14.99 THEN 1 ELSE 0 END) AS h_1015,
    SUM(CASE WHEN CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2)) BETWEEN 15 AND 19.99 THEN 1 ELSE 0 END) AS h_1520,
    SUM(CASE WHEN CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2)) BETWEEN 20 AND 29.99 THEN 1 ELSE 0 END) AS h_2030,
    SUM(CASE WHEN CAST(NULLIF(honey_qualtity_per_box,'') AS DECIMAL(8,2)) >= 30          THEN 1 ELSE 0 END) AS h_gt30
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

$n_ft       = (int)$s['n_ft'];
$n_pt       = (int)$s['n_pt'];
$n_ii       = (int)$s['n_ii'];
$n_mig_yes  = (int)$s['n_mig_yes'];
$n_mig_no   = (int)$s['n_mig_no'];

function pct($n, $d) { return $d > 0 ? (int)round($n / $d * 100) : 0; }

$ft_pct  = pct($n_ft, $total);
$pt_pct  = pct($n_pt, $total);
$ii_pct  = pct($n_ii, $total);
$yes_pct = pct($n_mig_yes, $total);
$no_pct  = pct($n_mig_no, $total);
$once_pct  = pct((int)$s['mig_once'],  $n_mig_yes);
$twice_pct = pct((int)$s['mig_twice'], $n_mig_yes);
$t3p_pct   = pct((int)$s['mig_3plus'], $n_mig_yes);

$honeyBuckets = [
  ['label'=>'< 10 kg',   'n'=>(int)$s['h_lt10'], 'color'=>'#FFF176'],
  ['label'=>'10–15 kg',  'n'=>(int)$s['h_1015'], 'color'=>'#FFD54F'],
  ['label'=>'15–20 kg',  'n'=>(int)$s['h_1520'], 'color'=>'#FFAB40'],
  ['label'=>'20–30 kg',  'n'=>(int)$s['h_2030'], 'color'=>'#F2A900'],
  ['label'=>'> 30 kg',   'n'=>(int)$s['h_gt30'], 'color'=>'#E65100'],
];
$maxHB = max(array_column($honeyBuckets, 'n'));
?>

<!-- ══ BK OPERATIONS ══ -->
<div id="p-bkops" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#2E7D32">Operations · परिचालन</span>
      <div class="sec-title">Beekeeping Operations — Scale, Mobility &amp; Productivity</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bko-kpi-row">
    <div class="bko-kpi" style="--kc:#1565C0">
      <div class="bko-kpi-val"><?= $ft_pct ?>%</div>
      <div class="bko-kpi-lbl">Full-Time Beekeepers</div>
      <div class="bko-kpi-sub"><?= number_format($n_ft) ?> farmers</div>
    </div>
    <div class="bko-kpi" style="--kc:#EF6C00">
      <div class="bko-kpi-val"><?= $pt_pct ?>%</div>
      <div class="bko-kpi-lbl">Part-Time Beekeepers</div>
      <div class="bko-kpi-sub"><?= number_format($n_pt) ?> farmers</div>
    </div>
    <div class="bko-kpi" style="--kc:#2E7D32">
      <div class="bko-kpi-val"><?= $yes_pct ?>%</div>
      <div class="bko-kpi-lbl">Migrate with Colonies</div>
      <div class="bko-kpi-sub"><?= number_format($n_mig_yes) ?> farmers</div>
    </div>
    <div class="bko-kpi" style="--kc:#7B1FA2">
      <div class="bko-kpi-val"><?= $s['avg_mig'] ?>×</div>
      <div class="bko-kpi-lbl">Avg Migrations / Year</div>
      <div class="bko-kpi-sub">among migrants</div>
    </div>
    <div class="bko-kpi" style="--kc:#F2A900">
      <div class="bko-kpi-val"><?= $s['avg_honey'] ?> kg</div>
      <div class="bko-kpi-lbl">Avg Honey / Box</div>
      <div class="bko-kpi-sub">per harvest</div>
    </div>
  </div>

  <!-- 3 chart cards -->
  <div class="bko-charts">

    <!-- Card 1: Engagement type -->
    <div class="bko-chart-card">
      <div class="bko-card-title">How do you do Beekeeping?</div>
      <div class="bko-card-sub">आप मधुमक्खी पालन कैसे करते हैं?</div>

      <?php
      $types = [
        ['label'=>'Full-Time Business', 'hindi'=>'पूर्णकालिक व्यवसाय', 'n'=>$n_ft,  'pct'=>$ft_pct,  'color'=>'#1565C0'],
        ['label'=>'Part-Time Business', 'hindi'=>'अंशकालिक व्यवसाय',   'n'=>$n_pt,  'pct'=>$pt_pct,  'color'=>'#EF6C00'],
        ['label'=>'Ideal Interest',     'hindi'=>'आदर्श रुचि',          'n'=>$n_ii,  'pct'=>$ii_pct,  'color'=>'#388E3C'],
      ];
      foreach($types as $t): ?>
      <div class="bko-bar-row">
        <div class="bko-bar-label">
          <span class="bko-bar-name"><?= $t['label'] ?></span>
          <span class="bko-bar-hindi"><?= $t['hindi'] ?></span>
        </div>
        <div class="bko-bar-track">
          <div class="bko-bar-fill" style="width:<?= $t['pct'] ?>%;background:<?= $t['color'] ?>"></div>
        </div>
        <div class="bko-bar-meta">
          <span class="bko-bar-pct" style="color:<?= $t['color'] ?>"><?= $t['pct'] ?>%</span>
          <span class="bko-bar-n"><?= number_format($t['n']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bko-total-row">Total respondents: <?= number_format($total) ?></div>
    </div>

    <!-- Card 2: Migration -->
    <div class="bko-chart-card">
      <div class="bko-card-title">Migration with Colonies</div>
      <div class="bko-card-sub">क्या आप कालोनियों के साथ प्रवास करते हैं?</div>

      <!-- Yes / No split bar -->
      <div class="bko-mig-split-wrap">
        <div class="bko-mig-split">
          <div class="bko-mig-seg" style="width:<?= $yes_pct ?>%;background:#2E7D32" title="Yes <?= $yes_pct ?>%"></div>
          <div class="bko-mig-seg" style="width:<?= $no_pct ?>%;background:#C62828"  title="No <?= $no_pct ?>%"></div>
        </div>
        <div class="bko-mig-leg">
          <div class="bko-mig-leg-item">
            <span class="bko-mig-dot" style="background:#2E7D32"></span>
            <span>Yes</span>
            <strong style="color:#2E7D32"><?= $yes_pct ?>%</strong>
            <span class="bko-mig-n">(<?= number_format($n_mig_yes) ?>)</span>
          </div>
          <div class="bko-mig-leg-item">
            <span class="bko-mig-dot" style="background:#C62828"></span>
            <span>No</span>
            <strong style="color:#C62828"><?= $no_pct ?>%</strong>
            <span class="bko-mig-n">(<?= number_format($n_mig_no) ?>)</span>
          </div>
        </div>
      </div>

      <div class="bko-sub-hdr">Migration frequency (among migrants)</div>
      <?php
      $freqs = [
        ['label'=>'Once a year',   'n'=>(int)$s['mig_once'],  'pct'=>$once_pct,  'color'=>'#81C784'],
        ['label'=>'Twice a year',  'n'=>(int)$s['mig_twice'], 'pct'=>$twice_pct, 'color'=>'#2E7D32'],
        ['label'=>'3+ times/year', 'n'=>(int)$s['mig_3plus'], 'pct'=>$t3p_pct,   'color'=>'#1B5E20'],
      ];
      foreach($freqs as $f): ?>
      <div class="bko-bar-row bko-bar-row--sm">
        <div class="bko-bar-label bko-bar-label--sm">
          <span class="bko-bar-name"><?= $f['label'] ?></span>
        </div>
        <div class="bko-bar-track">
          <div class="bko-bar-fill" style="width:<?= $f['pct'] ?>%;background:<?= $f['color'] ?>"></div>
        </div>
        <div class="bko-bar-meta">
          <span class="bko-bar-pct" style="color:<?= $f['color'] ?>"><?= $f['pct'] ?>%</span>
          <span class="bko-bar-n"><?= number_format($f['n']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="bko-avg-chip" style="background:#E8F5E9;color:#1B5E20">
        Avg <?= $s['avg_mig'] ?> migrations / year
      </div>
    </div>

    <!-- Card 3: Honey per box distribution -->
    <div class="bko-chart-card">
      <div class="bko-card-title">Honey per Box per Harvest (Kg)</div>
      <div class="bko-card-sub">प्रति बक्से प्रति फसल शहद उत्पादन</div>

      <?php foreach($honeyBuckets as $b):
        $barW = $maxHB > 0 ? (int)round($b['n']/$maxHB*100) : 0;
        $bp   = pct($b['n'], $total);
      ?>
      <div class="bko-hist-row">
        <div class="bko-hist-lbl"><?= $b['label'] ?></div>
        <div class="bko-hist-track">
          <div class="bko-hist-fill" style="width:<?= $barW ?>%;background:<?= $b['color'] ?>"></div>
        </div>
        <div class="bko-hist-meta">
          <span class="bko-hist-pct"><?= $bp ?>%</span>
          <span class="bko-hist-n"><?= number_format($b['n']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bko-honey-stats">
        <div class="bko-hstat" style="border-color:#F2A900">
          <div class="bko-hstat-v" style="color:#F2A900"><?= $s['avg_honey'] ?> kg</div>
          <div class="bko-hstat-l">Average</div>
        </div>
        <div class="bko-hstat">
          <div class="bko-hstat-v"><?= (int)$s['min_honey'] ?> kg</div>
          <div class="bko-hstat-l">Minimum</div>
        </div>
        <div class="bko-hstat">
          <div class="bko-hstat-v"><?= (int)$s['max_honey'] ?> kg</div>
          <div class="bko-hstat-l">Maximum</div>
        </div>
      </div>
    </div>

  </div><!-- /.bko-charts -->
</div>
