<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Core colony stats
$stats = $pdo->query("
  SELECT
    COUNT(*)                                                                                       AS n,
    SUM(CAST(NULLIF(How_many_Colonies_beekeeping_currently_Number,'') AS UNSIGNED))               AS total_col,
    ROUND(AVG(CAST(NULLIF(How_many_Colonies_beekeeping_currently_Number,'') AS DECIMAL(10,1))),1) AS avg_col,
    MIN(CAST(NULLIF(How_many_Colonies_beekeeping_currently_Number,'') AS UNSIGNED))               AS min_col,
    MAX(CAST(NULLIF(How_many_Colonies_beekeeping_currently_Number,'') AS UNSIGNED))               AS max_col,
    ROUND(AVG(CAST(NULLIF(Cost_per_colonies,'') AS DECIMAL(10,2))),0)                            AS avg_cost,
    MIN(CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED))                                           AS min_cost,
    MAX(CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED))                                           AS max_cost,
    SUM(CASE WHEN CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED) > 0 THEN 1 ELSE 0 END)          AS has_cost
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

// Colony count buckets
$dist = $pdo->query("
  SELECT
    SUM(CASE WHEN How_many_Colonies_beekeeping_currently_Number BETWEEN 1  AND 50  THEN 1 ELSE 0 END) AS b1,
    SUM(CASE WHEN How_many_Colonies_beekeeping_currently_Number BETWEEN 51 AND 100 THEN 1 ELSE 0 END) AS b2,
    SUM(CASE WHEN How_many_Colonies_beekeeping_currently_Number BETWEEN 101 AND 200 THEN 1 ELSE 0 END) AS b3,
    SUM(CASE WHEN How_many_Colonies_beekeeping_currently_Number BETWEEN 201 AND 500 THEN 1 ELSE 0 END) AS b4,
    SUM(CASE WHEN How_many_Colonies_beekeeping_currently_Number  > 500              THEN 1 ELSE 0 END) AS b5
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

// Cost per colony buckets
$costDist = $pdo->query("
  SELECT
    SUM(CASE WHEN CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED) BETWEEN 1    AND 1000 THEN 1 ELSE 0 END) AS c1,
    SUM(CASE WHEN CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED) BETWEEN 1001 AND 2000 THEN 1 ELSE 0 END) AS c2,
    SUM(CASE WHEN CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED) BETWEEN 2001 AND 3000 THEN 1 ELSE 0 END) AS c3,
    SUM(CASE WHEN CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED) BETWEEN 3001 AND 4000 THEN 1 ELSE 0 END) AS c4,
    SUM(CASE WHEN CAST(NULLIF(Cost_per_colonies,'') AS UNSIGNED)  > 4000               THEN 1 ELSE 0 END) AS c5
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

// Purchase source (normalised)
$sources = $pdo->query("
  SELECT
    CASE
      WHEN LOWER(Colonies_cost) LIKE '%beekeeper%' THEN 'Local Beekeeper'
      WHEN LOWER(Colonies_cost) LIKE '%market%'    THEN 'Local Market'
      WHEN LOWER(Colonies_cost) LIKE '%amroha%'    THEN 'Amroha'
      WHEN LOWER(Colonies_cost) LIKE '%allahabad%' OR LOWER(Colonies_cost) LIKE '%prayagraj%' THEN 'Allahabad / Prayagraj'
      WHEN LOWER(Colonies_cost) LIKE '%own%'  OR LOWER(Colonies_cost) LIKE '%apne%'  THEN 'Own / Self'
      WHEN LOWER(Colonies_cost) LIKE '%nbb%'  OR LOWER(Colonies_cost) LIKE '%national%' THEN 'NBB'
      ELSE 'Others'
    END AS src,
    COUNT(*) AS n
  FROM bk_data_part1
  WHERE Colonies_cost != ''
  GROUP BY src ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

$srcTotal = array_sum(array_column($sources, 'n'));

// Precompute dist rows
$distRows = [
  ['label'=>'1 – 50',    'n'=>(int)$dist['b1'], 'color'=>'#90CAF9'],
  ['label'=>'51 – 100',  'n'=>(int)$dist['b2'], 'color'=>'#64B5F6'],
  ['label'=>'101 – 200', 'n'=>(int)$dist['b3'], 'color'=>'#F2A900'],
  ['label'=>'201 – 500', 'n'=>(int)$dist['b4'], 'color'=>'#EF9A9A'],
  ['label'=>'500 +',     'n'=>(int)$dist['b5'], 'color'=>'#B22222'],
];
$distMax = max(array_column($distRows,'n'));

$costRows = [
  ['label'=>'Up to ₹1,000',    'n'=>(int)$costDist['c1'], 'color'=>'#90CAF9'],
  ['label'=>'₹1,001 – 2,000',  'n'=>(int)$costDist['c2'], 'color'=>'#81C784'],
  ['label'=>'₹2,001 – 3,000',  'n'=>(int)$costDist['c3'], 'color'=>'#F2A900'],
  ['label'=>'₹3,001 – 4,000',  'n'=>(int)$costDist['c4'], 'color'=>'#EF9A9A'],
  ['label'=>'Above ₹4,000',    'n'=>(int)$costDist['c5'], 'color'=>'#B22222'],
];
$costMax = max(array_column($costRows,'n'));

$srcColors = ['#F2A900','#64B5F6','#81C784','#CE93D8','#4DB6AC','#EF9A9A','#A8A8A8'];

// How did you start beekeeping (normalised)
$startMedium = $pdo->query("
  SELECT
    CASE
      WHEN Q_No_4_How_did_you_start_Beekeeping LIKE '%Market%'     THEN 'Bought from Market'
      WHEN Q_No_4_How_did_you_start_Beekeeping LIKE '%Parents%' OR Q_No_4_How_did_you_start_Beekeeping LIKE '%Inheritance%' THEN 'Inheritance / Parents'
      WHEN Q_No_4_How_did_you_start_Beekeeping LIKE '%Government%' OR Q_No_4_How_did_you_start_Beekeeping LIKE '%NGO%' THEN 'Govt / NGO Gift'
      WHEN Q_No_4_How_did_you_start_Beekeeping LIKE '%Forest%'     THEN 'From the Forest'
      WHEN Q_No_4_How_did_you_start_Beekeeping LIKE '%Flock%'      THEN 'Flock Catching'
      ELSE 'Any Other'
    END AS src,
    COUNT(*) AS n
  FROM bk_data_part1
  WHERE Q_No_4_How_did_you_start_Beekeeping != ''
  GROUP BY src ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);
$startTotal = array_sum(array_column($startMedium,'n'));
$startColors = ['#F2A900','#81C784','#64B5F6','#CE93D8','#4DB6AC','#EF9A9A'];

// When did you start (year distribution)
$yearDist = $pdo->query("
  SELECT
    SUM(CASE WHEN CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED) < 2005                                  THEN 1 ELSE 0 END) AS y1,
    SUM(CASE WHEN CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED) BETWEEN 2005 AND 2009 THEN 1 ELSE 0 END) AS y2,
    SUM(CASE WHEN CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED) BETWEEN 2010 AND 2014 THEN 1 ELSE 0 END) AS y3,
    SUM(CASE WHEN CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED) BETWEEN 2015 AND 2019 THEN 1 ELSE 0 END) AS y4,
    SUM(CASE WHEN CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED) BETWEEN 2020 AND 2024 THEN 1 ELSE 0 END) AS y5,
    ROUND(AVG(CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED)),0) AS avg_yr,
    MIN(CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED)) AS min_yr,
    MAX(CAST(NULLIF(Q_No_3_When_did_you_start_Beekeeping,'') AS UNSIGNED)) AS max_yr
  FROM bk_data_part1
  WHERE Q_No_3_When_did_you_start_Beekeeping != ''
")->fetch(PDO::FETCH_ASSOC);

$yearRows = [
  ['label'=>'Before 2005', 'n'=>(int)$yearDist['y1'], 'color'=>'#CE93D8'],
  ['label'=>'2005 – 2009', 'n'=>(int)$yearDist['y2'], 'color'=>'#64B5F6'],
  ['label'=>'2010 – 2014', 'n'=>(int)$yearDist['y3'], 'color'=>'#4DB6AC'],
  ['label'=>'2015 – 2019', 'n'=>(int)$yearDist['y4'], 'color'=>'#F2A900'],
  ['label'=>'2020 – 2024', 'n'=>(int)$yearDist['y5'], 'color'=>'#81C784'],
];
$yearMax   = max(array_column($yearRows,'n'));
$yearTotal = array_sum(array_column($yearRows,'n'));

// Total investment
$totalCost = $pdo->query("
  SELECT
    ROUND(AVG(CAST(NULLIF(colonies_total_cost,'') AS DECIMAL(14,2))),0) AS avg_tc,
    SUM(CAST(NULLIF(colonies_total_cost,'') AS UNSIGNED))               AS sum_tc,
    COUNT(*)                                                            AS n_tc
  FROM bk_data_part1
  WHERE colonies_total_cost != '' AND CAST(NULLIF(colonies_total_cost,'') AS UNSIGNED) > 0
")->fetch(PDO::FETCH_ASSOC);
?>

<!-- ══ COLONIES ══ -->
<div id="p-colonies" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#F2A900">Colony Overview · कॉलोनी विवरण</span>
      <div class="sec-title">Beekeeping Colony Details</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="col-kpi-row" style="grid-template-columns:repeat(8,1fr)">
    <div class="col-kpi" style="--ck:#F2A900">
      <div class="col-kpi-ico">🐝</div>
      <div class="col-kpi-val"><?= number_format($stats['total_col']) ?></div>
      <div class="col-kpi-lbl">Total Colonies</div>
      <div class="col-kpi-sub">Across <?= number_format($total) ?> beekeepers</div>
    </div>
    <div class="col-kpi" style="--ck:#1976D2">
      <div class="col-kpi-ico">📦</div>
      <div class="col-kpi-val"><?= $stats['avg_col'] ?></div>
      <div class="col-kpi-lbl">Avg Colonies / Beekeeper</div>
      <div class="col-kpi-sub">Min <?= $stats['min_col'] ?> · Max <?= number_format($stats['max_col']) ?></div>
    </div>
    <div class="col-kpi" style="--ck:#43A047">
      <div class="col-kpi-ico">💰</div>
      <div class="col-kpi-val">₹<?= number_format($stats['avg_cost']) ?></div>
      <div class="col-kpi-lbl">Avg Cost / Colony</div>
      <div class="col-kpi-sub">Most paid ₹2,001–₹3,000</div>
    </div>
    <div class="col-kpi" style="--ck:#9C27B0">
      <div class="col-kpi-ico">🏦</div>
      <div class="col-kpi-val">₹<?= round($totalCost['avg_tc']/1000) ?>K</div>
      <div class="col-kpi-lbl">Avg Total Investment</div>
      <div class="col-kpi-sub">Total ₹<?= round($totalCost['sum_tc']/10000000,1) ?> Cr across all</div>
    </div>
    <div class="col-kpi" style="--ck:#CE93D8">
      <div class="col-kpi-ico">🏪</div>
      <div class="col-kpi-val"><?= round($startMedium[0]['n']/$startTotal*100) ?>%</div>
      <div class="col-kpi-lbl">Bought from Market</div>
      <div class="col-kpi-sub">Most common start method</div>
    </div>
    <div class="col-kpi" style="--ck:#4DB6AC">
      <div class="col-kpi-ico">📅</div>
      <div class="col-kpi-val"><?= $yearDist['avg_yr'] ?></div>
      <div class="col-kpi-lbl">Avg Start Year</div>
      <div class="col-kpi-sub"><?= $yearDist['min_yr'] ?> – <?= $yearDist['max_yr'] ?></div>
    </div>
    <div class="col-kpi" style="--ck:#EF9A9A">
      <div class="col-kpi-ico">📊</div>
      <div class="col-kpi-val"><?= round(($dist['b3']+$dist['b4'])/$total*100) ?>%</div>
      <div class="col-kpi-lbl">Hold 101–500 Colonies</div>
      <div class="col-kpi-sub">Most common size range</div>
    </div>
    <div class="col-kpi" style="--ck:#81C784">
      <div class="col-kpi-ico">📈</div>
      <div class="col-kpi-val"><?= round($yearDist['y5']/$yearTotal*100) ?>%</div>
      <div class="col-kpi-lbl">Started 2020–2024</div>
      <div class="col-kpi-sub"><?= number_format($yearDist['y5']) ?> beekeepers</div>
    </div>
  </div>

  <!-- Three chart cards -->
  <div class="col-charts-row">

    <!-- Card 1: Colony count distribution -->
    <div class="col-chart-card">
      <div class="col-card-title">Colony Count Distribution</div>
      <div class="col-card-sub">How many colonies does each beekeeper hold?</div>
      <div class="col-bar-list">
        <?php foreach($distRows as $r):
          $w   = $distMax > 0 ? round($r['n']/$distMax*100) : 0;
          $pct = round($r['n']/$total*100);
        ?>
        <div class="col-bar-row">
          <div class="col-bar-label"><?= $r['label'] ?></div>
          <div class="col-bar-track">
            <div class="col-bar-fill" style="width:<?= $w ?>%;background:<?= $r['color'] ?>"></div>
          </div>
          <div class="col-bar-meta">
            <span class="col-bar-n"><?= number_format($r['n']) ?></span>
            <span class="col-bar-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Inline stacked bar (all buckets) -->
      <div class="col-stack-wrap">
        <div class="col-stack-lbl">Overall distribution</div>
        <div class="col-stack-bar">
          <?php foreach($distRows as $r):
            $w = round($r['n']/$total*100);
          ?>
          <?php if($w>0): ?>
          <div class="col-stack-seg" style="width:<?= $w ?>%;background:<?= $r['color'] ?>"
               title="<?= $r['label'] ?>: <?= $w ?>%"></div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Card 2: Cost per colony distribution -->
    <div class="col-chart-card">
      <div class="col-card-title">Cost per Colony</div>
      <div class="col-card-sub">Purchase price distribution across <?= number_format($stats['has_cost']) ?> beekeepers</div>
      <div class="col-bar-list">
        <?php foreach($costRows as $r):
          $w   = $costMax > 0 ? round($r['n']/$costMax*100) : 0;
          $pct = $stats['has_cost'] > 0 ? round($r['n']/$stats['has_cost']*100) : 0;
        ?>
        <div class="col-bar-row">
          <div class="col-bar-label"><?= $r['label'] ?></div>
          <div class="col-bar-track">
            <div class="col-bar-fill" style="width:<?= $w ?>%;background:<?= $r['color'] ?>"></div>
          </div>
          <div class="col-bar-meta">
            <span class="col-bar-n"><?= number_format($r['n']) ?></span>
            <span class="col-bar-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Avg cost indicator -->
      <div class="col-avg-badge">
        <span class="col-avg-lbl">Average cost per colony</span>
        <span class="col-avg-val">₹<?= number_format($stats['avg_cost']) ?></span>
      </div>
    </div>

    <!-- Card 3: Purchase source -->
    <div class="col-chart-card">
      <div class="col-card-title">Where Colonies Are Purchased</div>
      <div class="col-card-sub"><?= number_format($srcTotal) ?> beekeepers reported purchase source</div>
      <div class="col-bar-list">
        <?php foreach($sources as $i => $s):
          $pct = $srcTotal > 0 ? round($s['n']/$srcTotal*100) : 0;
          $col = $srcColors[$i % count($srcColors)];
        ?>
        <div class="col-bar-row">
          <div class="col-bar-label col-src-label">
            <span class="col-src-dot" style="background:<?= $col ?>"></span>
            <?= htmlspecialchars($s['src']) ?>
          </div>
          <div class="col-bar-track">
            <div class="col-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
          </div>
          <div class="col-bar-meta">
            <span class="col-bar-n"><?= number_format($s['n']) ?></span>
            <span class="col-bar-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- end .col-charts-row row 1 -->

  <!-- Sub-heading: Getting Started -->
  <div class="demo-sec-hdr" style="margin-top:8px">
    <span class="sec-label" style="--sl:#81C784">Entry into Beekeeping</span>
    <div class="sec-title" style="font-size:16px">How &amp; When Beekeeping Was Started</div>
  </div>

  <!-- Row 2: How started + When started -->
  <div class="col-charts-row col-charts-row--2col" style="margin-top:4px">

    <!-- Card 4: How started -->
    <div class="col-chart-card">
      <div class="col-card-title">How Beekeeping Was Started</div>
      <div class="col-card-sub">आपने मधुमक्खी पालन कैसे शुरू किया · <?= number_format($startTotal) ?> responses</div>
      <div class="col-bar-list">
        <?php foreach($startMedium as $i => $s):
          $pct = $startTotal > 0 ? round($s['n']/$startTotal*100) : 0;
          $col = $startColors[$i % count($startColors)];
        ?>
        <div class="col-bar-row">
          <div class="col-bar-label col-src-label">
            <span class="col-src-dot" style="background:<?= $col ?>"></span>
            <?= htmlspecialchars($s['src']) ?>
          </div>
          <div class="col-bar-track">
            <div class="col-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
          </div>
          <div class="col-bar-meta">
            <span class="col-bar-n"><?= number_format($s['n']) ?></span>
            <span class="col-bar-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Stacked overview -->
      <div class="col-stack-wrap">
        <div class="col-stack-lbl">Overall distribution</div>
        <div class="col-stack-bar">
          <?php foreach($startMedium as $i => $s):
            $w = $startTotal > 0 ? round($s['n']/$startTotal*100) : 0;
            if($w > 0): ?>
          <div class="col-stack-seg" style="width:<?= $w ?>%;background:<?= $startColors[$i%count($startColors)] ?>"
               title="<?= htmlspecialchars($s['src']) ?>: <?= $w ?>%"></div>
          <?php endif; endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Card 5: When started (year distribution) -->
    <div class="col-chart-card">
      <div class="col-card-title">When Beekeeping Was Started</div>
      <div class="col-card-sub">Year-wise entry of beekeepers · avg start year <?= $yearDist['avg_yr'] ?></div>
      <div class="col-bar-list">
        <?php foreach($yearRows as $r):
          $w   = $yearMax > 0 ? round($r['n']/$yearMax*100) : 0;
          $pct = $yearTotal > 0 ? round($r['n']/$yearTotal*100) : 0;
        ?>
        <div class="col-bar-row">
          <div class="col-bar-label"><?= $r['label'] ?></div>
          <div class="col-bar-track">
            <div class="col-bar-fill" style="width:<?= $w ?>%;background:<?= $r['color'] ?>"></div>
          </div>
          <div class="col-bar-meta">
            <span class="col-bar-n"><?= number_format($r['n']) ?></span>
            <span class="col-bar-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Stacked timeline -->
      <div class="col-stack-wrap">
        <div class="col-stack-lbl">Timeline overview</div>
        <div class="col-stack-bar">
          <?php foreach($yearRows as $r):
            $w = $yearTotal > 0 ? round($r['n']/$yearTotal*100) : 0;
            if($w > 0): ?>
          <div class="col-stack-seg" style="width:<?= $w ?>%;background:<?= $r['color'] ?>"
               title="<?= $r['label'] ?>: <?= $w ?>%"></div>
          <?php endif; endforeach; ?>
        </div>
        <div class="col-year-badges">
          <span class="col-year-badge" style="background:#F0FFF0;color:#2E7D32;border-color:#C8E6C9">
            📅 Earliest: <?= $yearDist['min_yr'] ?>
          </span>
          <span class="col-year-badge" style="background:#FFF8E6;color:#E65100;border-color:#FFCC80">
            📅 Latest: <?= $yearDist['max_yr'] ?>
          </span>
          <span class="col-year-badge" style="background:#E3F2FD;color:#0D47A1;border-color:#BBDEFB">
            📊 Average: <?= $yearDist['avg_yr'] ?>
          </span>
        </div>
      </div>
    </div>

  </div><!-- end row 2 -->
</div>
