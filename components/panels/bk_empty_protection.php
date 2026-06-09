<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    SUM(CASE WHEN Q_No_11_is_empty_box_YES_NO='Yes' THEN 1 ELSE 0 END)  AS yes_empty,
    SUM(CASE WHEN Q_No_11_is_empty_box_YES_NO='No'  THEN 1 ELSE 0 END)  AS no_empty,
    ROUND(AVG(CAST(NULLIF(Empty_box_number_of_Beheives,'') AS DECIMAL(10,2))),1) AS avg_empty,
    SUM(CAST(NULLIF(Empty_box_number_of_Beheives,'') AS UNSIGNED))       AS tot_empty,
    SUM(CASE WHEN CAST(NULLIF(Empty_box_number_of_Beheives,'') AS UNSIGNED) BETWEEN  1 AND 10 THEN 1 ELSE 0 END) AS b_1_10,
    SUM(CASE WHEN CAST(NULLIF(Empty_box_number_of_Beheives,'') AS UNSIGNED) BETWEEN 11 AND 25 THEN 1 ELSE 0 END) AS b_11_25,
    SUM(CASE WHEN CAST(NULLIF(Empty_box_number_of_Beheives,'') AS UNSIGNED) BETWEEN 26 AND 50 THEN 1 ELSE 0 END) AS b_26_50,
    SUM(CASE WHEN CAST(NULLIF(Empty_box_number_of_Beheives,'') AS UNSIGNED) > 50              THEN 1 ELSE 0 END) AS b_gt50,
    SUM(CASE WHEN Q_No_12_is_adopt_protection_YES_NO='Yes' THEN 1 ELSE 0 END) AS yes_prot,
    SUM(CASE WHEN Q_No_12_is_adopt_protection_YES_NO='No'  THEN 1 ELSE 0 END) AS no_prot
  FROM bk_data_part2
")->fetch(PDO::FETCH_ASSOC);

// Parse protection types from comma-separated values
$protRows = $pdo->query("SELECT adopt_protection_type FROM bk_data_part2 WHERE adopt_protection_type!=''")->fetchAll(PDO::FETCH_COLUMN);
$protCounts = [];
foreach($protRows as $row) {
  if(strpos($row,'Chemical Spraying')  !== false) $protCounts['Chemical Spraying']  = ($protCounts['Chemical Spraying']  ?? 0) + 1;
  if(strpos($row,'Biological Pest')    !== false) $protCounts['Biological Pest Mgmt'] = ($protCounts['Biological Pest Mgmt'] ?? 0) + 1;
  if(strpos($row,'Electrical Trap')    !== false) $protCounts['Electrical Trap']    = ($protCounts['Electrical Trap']    ?? 0) + 1;
  if(strpos($row,'Other')              !== false) $protCounts['Other Methods']       = ($protCounts['Other Methods']      ?? 0) + 1;
}
arsort($protCounts);

$protColors = ['Chemical Spraying'=>'#2E7D32','Biological Pest Mgmt'=>'#1565C0','Electrical Trap'=>'#E65100','Other Methods'=>'#78909C'];

function pctEP($n,$d){ return $d > 0 ? (int)round($n/$d*100) : 0; }

$yesEmpty = (int)$d['yes_empty'];
$noEmpty  = (int)$d['no_empty'];
$yesPct   = pctEP($yesEmpty, $total);
$noPct    = 100 - $yesPct;
$yesProt  = (int)$d['yes_prot'];
$protPct  = pctEP($yesProt, $total);

$emptyBuckets = [
  ['label'=>'1–10 boxes',  'n'=>(int)$d['b_1_10'],  'color'=>'#90CAF9'],
  ['label'=>'11–25 boxes', 'n'=>(int)$d['b_11_25'], 'color'=>'#42A5F5'],
  ['label'=>'26–50 boxes', 'n'=>(int)$d['b_26_50'], 'color'=>'#1E88E5'],
  ['label'=>'> 50 boxes',  'n'=>(int)$d['b_gt50'],  'color'=>'#1565C0'],
];
$maxEB = max(array_column($emptyBuckets,'n'));
?>

<!-- ══ BK EMPTY + PROTECTION ══ -->
<div id="p-bkep" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#1565C0">Sections 11 &amp; 12 · खाली छत्ते एवं सुरक्षा</span>
      <div class="sec-title">Empty Beehives &amp; Pest Protection</div>
    </div>
  </div>

  <!-- KPI Strip -->
  <div class="bkep-kpi-row">
    <div class="bkep-kpi" style="--kc:#1565C0">
      <div class="bkep-kpi-val"><?= $yesPct ?>%</div>
      <div class="bkep-kpi-lbl">Have Empty Boxes</div>
      <div class="bkep-kpi-sub"><?= number_format($yesEmpty) ?> farmers</div>
    </div>
    <div class="bkep-kpi" style="--kc:#42A5F5">
      <div class="bkep-kpi-val"><?= $d['avg_empty'] ?></div>
      <div class="bkep-kpi-lbl">Avg Empty Boxes</div>
      <div class="bkep-kpi-sub">per farmer</div>
    </div>
    <div class="bkep-kpi" style="--kc:#0277BD">
      <div class="bkep-kpi-val"><?= number_format((int)$d['tot_empty']) ?></div>
      <div class="bkep-kpi-lbl">Total Empty Boxes</div>
      <div class="bkep-kpi-sub">across all farmers</div>
    </div>
    <div class="bkep-kpi" style="--kc:#2E7D32">
      <div class="bkep-kpi-val"><?= $protPct ?>%</div>
      <div class="bkep-kpi-lbl">Adopt Protection</div>
      <div class="bkep-kpi-sub"><?= number_format($yesProt) ?> farmers</div>
    </div>
  </div>

  <!-- 2-col chart row -->
  <div class="bkep-charts">

    <!-- Card 1: Empty boxes -->
    <div class="bkep-chart-card">
      <div class="bkep-card-title">Empty Beehives in Boxes</div>
      <div class="bkep-card-sub">क्या आपके बक्सों में खाली छत्ते रह जाते हैं?</div>

      <!-- Yes/No split -->
      <div class="bkep-yn-wrap">
        <div class="bkep-yn-bar">
          <div class="bkep-yn-seg" style="width:<?= $yesPct ?>%;background:#1565C0" title="Yes <?= $yesPct ?>%"></div>
          <div class="bkep-yn-seg" style="width:<?= $noPct ?>%;background:#E0E0E0"  title="No <?= $noPct ?>%"></div>
        </div>
        <div class="bkep-yn-leg">
          <div class="bkep-yn-item">
            <span class="bkep-yn-dot" style="background:#1565C0"></span>
            <span>Yes</span>
            <strong style="color:#1565C0"><?= $yesPct ?>%</strong>
            <span class="bkep-yn-n">(<?= number_format($yesEmpty) ?>)</span>
          </div>
          <div class="bkep-yn-item">
            <span class="bkep-yn-dot" style="background:#BDBDBD"></span>
            <span>No</span>
            <strong style="color:#757575"><?= $noPct ?>%</strong>
            <span class="bkep-yn-n">(<?= number_format($noEmpty) ?>)</span>
          </div>
        </div>
      </div>

      <div class="bkep-sub-hdr">Distribution by number of empty boxes</div>
      <?php foreach($emptyBuckets as $eb):
        $bw = $maxEB > 0 ? (int)round($eb['n']/$maxEB*100) : 0;
        $ep = pctEP($eb['n'], $yesEmpty);
      ?>
      <div class="bkep-bar-row">
        <div class="bkep-bar-label"><?= $eb['label'] ?></div>
        <div class="bkep-bar-track">
          <div class="bkep-bar-fill" style="width:<?= $bw ?>%;background:<?= $eb['color'] ?>"></div>
        </div>
        <div class="bkep-bar-meta">
          <span class="bkep-bar-pct" style="color:<?= $eb['color'] ?>"><?= $ep ?>%</span>
          <span class="bkep-bar-n"><?= number_format($eb['n']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Card 2: Protection -->
    <div class="bkep-chart-card">
      <div class="bkep-card-title">Protection &amp; Pest Management</div>
      <div class="bkep-card-sub">क्या आप सुरक्षा एवं कीट प्रबंधन अपनाते हैं?</div>

      <!-- Yes/No split -->
      <?php $noProtPct = 100 - $protPct; ?>
      <div class="bkep-yn-wrap">
        <div class="bkep-yn-bar">
          <div class="bkep-yn-seg" style="width:<?= $protPct ?>%;background:#2E7D32" title="Yes <?= $protPct ?>%"></div>
          <div class="bkep-yn-seg" style="width:<?= $noProtPct ?>%;background:#E0E0E0" title="No <?= $noProtPct ?>%"></div>
        </div>
        <div class="bkep-yn-leg">
          <div class="bkep-yn-item">
            <span class="bkep-yn-dot" style="background:#2E7D32"></span>
            <span>Yes</span>
            <strong style="color:#2E7D32"><?= $protPct ?>%</strong>
            <span class="bkep-yn-n">(<?= number_format($yesProt) ?>)</span>
          </div>
          <div class="bkep-yn-item">
            <span class="bkep-yn-dot" style="background:#BDBDBD"></span>
            <span>No</span>
            <strong style="color:#757575"><?= $noProtPct ?>%</strong>
            <span class="bkep-yn-n">(<?= number_format((int)$d['no_prot']) ?>)</span>
          </div>
        </div>
      </div>

      <div class="bkep-sub-hdr">Protection method adoption (multi-select)</div>
      <?php $maxProt = $protCounts ? max($protCounts) : 1;
      foreach($protCounts as $method => $cnt):
        $mw = (int)round($cnt/$maxProt*100);
        $mp = pctEP($cnt, $yesProt);
        $col = $protColors[$method] ?? '#78909C';
      ?>
      <div class="bkep-bar-row">
        <div class="bkep-bar-label"><?= $method ?></div>
        <div class="bkep-bar-track">
          <div class="bkep-bar-fill" style="width:<?= $mw ?>%;background:<?= $col ?>"></div>
        </div>
        <div class="bkep-bar-meta">
          <span class="bkep-bar-pct" style="color:<?= $col ?>"><?= $mp ?>%</span>
          <span class="bkep-bar-n"><?= number_format($cnt) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="bkep-note">% of protection adopters using each method</div>
    </div>

  </div><!-- /.bkep-charts -->
</div>
