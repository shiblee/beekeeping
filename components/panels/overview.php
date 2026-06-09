<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int) $pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// ── KPI computed values ──────────────────────────────────────────
$kpi = $pdo->query("
  SELECT
    SUM(CAST(NULLIF(sr1_honey_qty,'')              AS DECIMAL(14,2))) AS total_honey_kg,
    SUM(CAST(NULLIF(How_many_Colonies_beekeeping_currently_Number,'') AS DECIMAL(12,2))) AS total_colonies,
    ROUND(AVG(CAST(NULLIF(How_many_Colonies_beekeeping_currently_Number,'') AS DECIMAL(12,2))),0) AS avg_colonies,
    COUNT(DISTINCT Beekeepers_District_Name)                           AS districts,
    ROUND(AVG(CAST(NULLIF(sr1_Honey_Revenue_Rs_2023,'')  AS DECIMAL(14,2))),0) AS avg_revenue,
    SUM(CAST(NULLIF(traditional_number,'')  AS DECIMAL(12,2))) AS trad_col,
    SUM(CAST(NULLIF(transitional_number,'') AS DECIMAL(12,2))) AS trans_col,
    SUM(CAST(NULLIF(modern_number,'')       AS DECIMAL(12,2))) AS mod_col
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

$kpi2 = $pdo->query("
  SELECT
    ROUND(AVG(CAST(NULLIF(sr1vc_profit,'') AS DECIMAL(14,2))),0)          AS avg_profit,
    SUM(CAST(NULLIF(_2022_total_output,'') AS DECIMAL(14,2)))              AS honey_2022,
    SUM(CAST(NULLIF(_2023_total_output,'') AS DECIMAL(14,2)))              AS honey_2023,
    AVG(CAST(NULLIF(Q_No_10_sr1_Aggregate_Loss_Total_Kg,'') AS DECIMAL(14,2))) AS avg_loss_kg
  FROM bk_data_part2
")->fetch(PDO::FETCH_ASSOC);

$total_honey_T  = (int)round($kpi['total_honey_kg'] / 1000);
$total_colonies = (int)($kpi['trad_col'] + $kpi['trans_col'] + $kpi['mod_col']);
$avg_col        = (int)$kpi['avg_colonies'];
$districts      = (int)$kpi['districts'];
$avg_revenue    = (int)$kpi['avg_revenue'];
$avg_profit     = (int)$kpi2['avg_profit'];
$profit_margin  = $avg_revenue > 0 ? (int)round($avg_profit / $avg_revenue * 100) : 0;
$avg_loss_kg    = (float)$kpi2['avg_loss_kg'];
$avg_honey_kg   = $kpi['total_honey_kg'] / $total;
$loss_rate      = ($avg_loss_kg + $avg_honey_kg) > 0
                  ? round($avg_loss_kg / ($avg_loss_kg + $avg_honey_kg) * 100, 1) : 0;
$yoy = ($kpi2['honey_2022'] > 0)
       ? round(($kpi2['honey_2023'] - $kpi2['honey_2022']) / $kpi2['honey_2022'] * 100, 1) : 0;

// Format helpers
function fmtT($t)  { return number_format($t) . ' T'; }
function fmtL($r)  { return '₹' . number_format(round($r/100000,1),1) . 'L'; }
function fmtCol($c){ return number_format(round($c/1000),0) . 'K'; }
// ─────────────────────────────────────────────────────────────────

$gender = $pdo->query("SELECT Beekeeper_Gender AS label, COUNT(*) AS n FROM bk_data_part1 WHERE Beekeeper_Gender!='' GROUP BY label ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);

$religion = $pdo->query("SELECT Beekeeper_Religion AS label, COUNT(*) AS n FROM bk_data_part1 WHERE Beekeeper_Religion!='' GROUP BY label ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);

$caste = $pdo->query("SELECT Beekeeper_Caste AS label, COUNT(*) AS n FROM bk_data_part1 WHERE Beekeeper_Caste!='' GROUP BY label ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);

$edu = $pdo->query("SELECT Beekeeper_Education_level AS label, COUNT(*) AS n FROM bk_data_part1 WHERE Beekeeper_Education_level!='' GROUP BY label ORDER BY n DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);

$occ = $pdo->query("
  SELECT
    CASE
      WHEN Beekeeper_Main_occupation LIKE '%Beekeeping%' THEN 'Beekeeping / Animal Husbandry'
      WHEN Beekeeper_Main_occupation LIKE '%Agriculture%' OR Beekeeper_Main_occupation LIKE '%Agricult%' THEN 'Agriculture'
      WHEN Beekeeper_Main_occupation IN ('Trade') THEN 'Trade'
      WHEN Beekeeper_Main_occupation LIKE '%Job%' OR Beekeeper_Main_occupation LIKE '%Government%' THEN 'Salaried Job'
      ELSE 'Others'
    END AS label,
    COUNT(*) AS n
  FROM bk_data_part1
  WHERE Beekeeper_Main_occupation != ''
  GROUP BY label ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Helper: SVG donut path (circumference=100 trick)
function donutPath() {
  return "M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831";
}

// Build donut segment data: returns [{pct, offset, color}]
function donutSegments(array $rows, int $total, array $colors): array {
  $segs = []; $offset = 0;
  foreach ($rows as $i => $r) {
    $pct = $total > 0 ? round($r['n'] / $total * 100) : 0;
    $segs[] = ['pct' => $pct, 'offset' => -$offset, 'color' => $colors[$i % count($colors)], 'label' => $r['label'], 'n' => $r['n']];
    $offset += $pct;
  }
  return $segs;
}
?>
<!-- ══ OVERVIEW ══ -->
<div id="p-overview" class="panel on">
  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#F2A900">Survey Overview</span>
      <div class="sec-title">Key Performance Indicators</div>
    </div>
  </div>
  <div class="kpi-g">
    <div class="kc" style="--ak:linear-gradient(90deg,#F6C453,#F2A900)">
      <div class="ki" style="--ib:#FFF3D6">🧑‍🌾</div>
      <div class="kn"><?= number_format($total) ?></div>
      <div class="kl">Total beekeepers</div>
    </div>
    <div class="kc" style="--ak:linear-gradient(90deg,#81C784,#43A047)">
      <div class="ki" style="--ib:#E9F7EC">🍯</div>
      <div class="kn"><?= fmtT($total_honey_T) ?></div>
      <div class="kl">Total honey output</div>
      <div class="kt tg"><?= $yoy >= 0 ? '↑' : '↓' ?> <?= abs($yoy) ?>% YoY</div>
    </div>
    <div class="kc" style="--ak:linear-gradient(90deg,#F6C453,#F2A900)">
      <div class="ki" style="--ib:#FFF3D6">💰</div>
      <div class="kn"><?= fmtL($avg_revenue) ?></div>
      <div class="kl">Avg annual revenue</div>
      <div class="kt ty"><?= $profit_margin ?>% profit margin</div>
    </div>
    <div class="kc" style="--ak:linear-gradient(90deg,#64B5F6,#1976D2)">
      <div class="ki" style="--ib:#E3F2FD">🐝</div>
      <div class="kn"><?= fmtCol($total_colonies) ?></div>
      <div class="kl">Active colonies</div>
      <div class="kt tb">Avg <?= $avg_col ?> / producer</div>
    </div>
    <div class="kc" style="--ak:linear-gradient(90deg,#CE93D8,#7B1FA2)">
      <div class="ki" style="--ib:#F3E5F5">📍</div>
      <div class="kn"><?= $districts ?></div>
      <div class="kl">Districts covered</div>
      <div class="kt tg">Full UP</div>
    </div>
    <div class="kc" style="--ak:linear-gradient(90deg,#EF9A9A,#C62828)">
      <div class="ki" style="--ib:#FEE8E8">⚠️</div>
      <div class="kn"><?= $loss_rate ?>%</div>
      <div class="kl">Avg loss rate</div>
      <div class="kt tr">Pre &amp; post harvest</div>
    </div>
  </div>

  <!-- Demographic sub-heading -->
  <div class="demo-sec-hdr">
    <span class="sec-label" style="--sl:#CE93D8">Respondent Profile</span>
    <div class="sec-title" style="font-size:16px">Demographic Breakdown</div>
  </div>

  <!-- 5-card demographic row -->
  <div class="demo-row">

    <?php
    /* ── GENDER ── */
    $gColors = ['#F6C453','#CE93D8'];
    $gSegs   = donutSegments($gender, $total, $gColors);
    $gDom    = $gender[0];
    $gDomPct = round($gDom['n'] / $total * 100);
    ?>
    <div class="demo-card" style="--dc:#F6C453">
      <div class="demo-title">Gender</div>
      <div class="demo-donut-wrap">
        <svg viewBox="0 0 36 36" class="demo-donut-svg">
          <path d="<?= donutPath() ?>" fill="none" stroke="#F0E6D6" stroke-width="3.8"/>
          <?php foreach($gSegs as $s): ?>
          <path d="<?= donutPath() ?>" fill="none" stroke="<?= $s['color'] ?>"
                stroke-width="3.8"
                stroke-dasharray="<?= $s['pct'] ?>, 100"
                stroke-dashoffset="<?= $s['offset'] ?>"
                stroke-linecap="round"/>
          <?php endforeach; ?>
          <text x="18" y="15.5" class="donut-big"><?= $gDomPct ?>%</text>
          <text x="18" y="21.5" class="donut-sub"><?= htmlspecialchars($gDom['label']) ?></text>
        </svg>
      </div>
      <div class="demo-legend">
        <?php foreach($gender as $i=>$r): $pct = round($r['n']/$total*100); ?>
        <div class="demo-leg-row">
          <span class="demo-dot" style="background:<?= $gColors[$i%2] ?>"></span>
          <span class="demo-leg-label"><?= htmlspecialchars($r['label']) ?></span>
          <span class="demo-leg-val"><?= number_format($r['n']) ?></span>
          <span class="demo-leg-pct"><?= $pct ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php
    /* ── RELIGION ── */
    $relColors = ['#F2A900','#64B5F6','#81C784','#CE93D8'];
    $relSegs   = donutSegments($religion, $total, $relColors);
    $relDom    = $religion[0];
    $relDomPct = round($relDom['n'] / $total * 100);
    ?>
    <div class="demo-card" style="--dc:#F2A900">
      <div class="demo-title">Religion</div>
      <div class="demo-donut-wrap">
        <svg viewBox="0 0 36 36" class="demo-donut-svg">
          <path d="<?= donutPath() ?>" fill="none" stroke="#F0E6D6" stroke-width="3.8"/>
          <?php foreach($relSegs as $s): ?>
          <path d="<?= donutPath() ?>" fill="none" stroke="<?= $s['color'] ?>"
                stroke-width="3.8"
                stroke-dasharray="<?= $s['pct'] ?>, 100"
                stroke-dashoffset="<?= $s['offset'] ?>"
                stroke-linecap="round"/>
          <?php endforeach; ?>
          <text x="18" y="15.5" class="donut-big"><?= $relDomPct ?>%</text>
          <text x="18" y="21.5" class="donut-sub"><?= htmlspecialchars($relDom['label']) ?></text>
        </svg>
      </div>
      <div class="demo-legend">
        <?php foreach($religion as $i=>$r): $pct = round($r['n']/$total*100); ?>
        <div class="demo-leg-row">
          <span class="demo-dot" style="background:<?= $relColors[$i%4] ?>"></span>
          <span class="demo-leg-label"><?= htmlspecialchars($r['label']) ?></span>
          <span class="demo-leg-val"><?= number_format($r['n']) ?></span>
          <span class="demo-leg-pct"><?= $pct ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php
    /* ── CASTE ── */
    $casteColors = ['#F2A900','#64B5F6','#CE93D8','#81C784'];
    $castTotal   = array_sum(array_column($caste, 'n'));
    ?>
    <div class="demo-card" style="--dc:#64B5F6">
      <div class="demo-title">Caste</div>
      <!-- Segmented bar -->
      <div class="seg-bar-wrap">
        <?php foreach($caste as $i=>$r): $pct = round($r['n']/$castTotal*100); ?>
        <div class="seg-bar-seg" style="width:<?= $pct ?>%;background:<?= $casteColors[$i%4] ?>" title="<?= htmlspecialchars($r['label']) ?>: <?= $pct ?>%"></div>
        <?php endforeach; ?>
      </div>
      <div class="demo-legend" style="margin-top:14px">
        <?php foreach($caste as $i=>$r): $pct = round($r['n']/$castTotal*100); ?>
        <div class="demo-leg-row">
          <span class="demo-dot" style="background:<?= $casteColors[$i%4] ?>"></span>
          <span class="demo-leg-label"><?= htmlspecialchars($r['label']) ?></span>
          <span class="demo-leg-val"><?= number_format($r['n']) ?></span>
          <span class="demo-leg-pct"><?= $pct ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php
    /* ── EDUCATION ── */
    $eduColors = ['#F2A900','#64B5F6','#81C784','#CE93D8','#EF9A9A','#4DB6AC','#FFB74D'];
    $eduMax    = (int)$edu[0]['n'];
    $eduTotal  = array_sum(array_column($edu, 'n'));
    ?>
    <div class="demo-card" style="--dc:#81C784">
      <div class="demo-title">Education</div>
      <div class="demo-bars-wrap">
        <?php foreach($edu as $i=>$r):
          $w   = round($r['n'] / $eduMax * 100);
          $pct = round($r['n'] / $total * 100);
        ?>
        <div class="demo-bar-row">
          <div class="demo-bar-label"><?= htmlspecialchars($r['label']) ?></div>
          <div class="demo-bar-track">
            <div class="demo-bar-fill" style="width:<?= $w ?>%;background:<?= $eduColors[$i%7] ?>"></div>
          </div>
          <div class="demo-bar-num"><?= number_format($r['n']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php
    /* ── OCCUPATION ── */
    $occColors = ['#F2A900','#81C784','#64B5F6','#CE93D8','#EF9A9A'];
    $occMax    = (int)$occ[0]['n'];
    ?>
    <div class="demo-card" style="--dc:#CE93D8">
      <div class="demo-title">Occupation</div>
      <div class="demo-bars-wrap">
        <?php foreach($occ as $i=>$r):
          $w   = round($r['n'] / $occMax * 100);
          $pct = round($r['n'] / $total * 100);
        ?>
        <div class="demo-bar-row">
          <div class="demo-bar-label"><?= htmlspecialchars($r['label']) ?></div>
          <div class="demo-bar-track">
            <div class="demo-bar-fill" style="width:<?= $w ?>%;background:<?= $occColors[$i%5] ?>"></div>
          </div>
          <div class="demo-bar-num"><?= number_format($r['n']) ?> <span class="demo-bar-pct"><?= $pct ?>%</span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- end .demo-row -->
</div>
