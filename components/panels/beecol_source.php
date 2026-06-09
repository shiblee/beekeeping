<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

// Participation & per-type stats in one query
$stats = $pdo->query("
  SELECT
    SUM(CASE WHEN Q_No_6_source_beecol_traditional_YES_NO='Yes'  THEN 1 ELSE 0 END) AS n_trad,
    SUM(CASE WHEN Q_No_6_source_beecol_transitional_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_trans,
    SUM(CASE WHEN Q_No_6_source_beecol_modern_YES_NO='Yes'       THEN 1 ELSE 0 END) AS n_mod,

    ROUND(SUM(CASE WHEN Q_No_6_source_beecol_traditional_YES_NO='Yes'
      THEN CAST(NULLIF(traditional_number,'') AS UNSIGNED) ELSE 0 END),0)           AS tot_trad,
    ROUND(AVG(CASE WHEN Q_No_6_source_beecol_traditional_YES_NO='Yes'
      THEN CAST(NULLIF(traditional_number,'') AS DECIMAL(10,1)) END),1)             AS avg_trad,
    ROUND(AVG(CASE WHEN Q_No_6_source_beecol_traditional_YES_NO='Yes'
      THEN CAST(NULLIF(traditional_value,'')  AS DECIMAL(10,2)) END),0)             AS val_trad,

    ROUND(SUM(CASE WHEN Q_No_6_source_beecol_transitional_YES_NO='Yes'
      THEN CAST(NULLIF(transitional_number,'') AS UNSIGNED) ELSE 0 END),0)          AS tot_trans,
    ROUND(AVG(CASE WHEN Q_No_6_source_beecol_transitional_YES_NO='Yes'
      THEN CAST(NULLIF(transitional_number,'') AS DECIMAL(10,1)) END),1)            AS avg_trans,
    ROUND(AVG(CASE WHEN Q_No_6_source_beecol_transitional_YES_NO='Yes'
      THEN CAST(NULLIF(transitional_value,'')  AS DECIMAL(10,2)) END),0)            AS val_trans,

    ROUND(SUM(CASE WHEN Q_No_6_source_beecol_modern_YES_NO='Yes'
      THEN CAST(NULLIF(modern_number,'') AS UNSIGNED) ELSE 0 END),0)                AS tot_mod,
    ROUND(AVG(CASE WHEN Q_No_6_source_beecol_modern_YES_NO='Yes'
      THEN CAST(NULLIF(modern_number,'') AS DECIMAL(10,1)) END),1)                  AS avg_mod,
    ROUND(AVG(CASE WHEN Q_No_6_source_beecol_modern_YES_NO='Yes'
      THEN CAST(NULLIF(modern_value,'')  AS DECIMAL(10,2)) END),0)                  AS val_mod
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

// Normalised source helper
function beeSources($pdo, $col) {
  return $pdo->query("
    SELECT
      CASE
        WHEN $col LIKE '%Horticulture%' OR $col LIKE '%Horti%' THEN 'Horticulture Dept.'
        WHEN $col LIKE '%NGO%' OR $col LIKE '%Private Agency%' THEN 'NGO / Private Agency'
        WHEN $col LIKE '%Forest%'  OR $col LIKE '%Tree%' THEN 'Forest / Trees'
        WHEN $col LIKE '%House%' OR $col LIKE '%threshold%' THEN 'Near House'
        ELSE 'Others / Market'
      END AS src,
      COUNT(*) AS n
    FROM bk_data_part1
    WHERE $col != ''
    GROUP BY src ORDER BY n DESC
  ")->fetchAll(PDO::FETCH_ASSOC);
}

$tradSrc  = beeSources($pdo, 'traditional_source');
$transSrc = beeSources($pdo, 'transitional_source');
$modSrc   = beeSources($pdo, 'modern_source');

$srcColors = ['#F2A900','#64B5F6','#81C784','#CE93D8','#EF9A9A'];

$colTypes = [
  [
    'key'    => 'mod',
    'label'  => 'Modern',
    'hindi'  => 'आधुनिक',
    'icon'   => '🏭',
    'color'  => '#1565C0',
    'light'  => '#E8F4FF',
    'n'      => (int)$stats['n_mod'],
    'total'  => (int)$stats['tot_mod'],
    'avg'    => $stats['avg_mod'],
    'val'    => (int)$stats['val_mod'],
    'src'    => $modSrc,
  ],
  [
    'key'    => 'trad',
    'label'  => 'Traditional',
    'hindi'  => 'पारंपरिक',
    'icon'   => '🪵',
    'color'  => '#6D4C41',
    'light'  => '#FFF3E0',
    'n'      => (int)$stats['n_trad'],
    'total'  => (int)$stats['tot_trad'],
    'avg'    => $stats['avg_trad'],
    'val'    => (int)$stats['val_trad'],
    'src'    => $tradSrc,
  ],
  [
    'key'    => 'trans',
    'label'  => 'Transitional',
    'hindi'  => 'संक्रमणकालीन',
    'icon'   => '🔄',
    'color'  => '#E65100',
    'light'  => '#FFF8E6',
    'n'      => (int)$stats['n_trans'],
    'total'  => (int)$stats['tot_trans'],
    'avg'    => $stats['avg_trans'],
    'val'    => (int)$stats['val_trans'],
    'src'    => $transSrc,
  ],
];

$grandTotal = $stats['n_mod'] + $stats['n_trad'] + $stats['n_trans'];
$grandColonies = $stats['tot_mod'] + $stats['tot_trad'] + $stats['tot_trans'];
?>

<!-- ══ BEE COLONY SOURCE ══ -->
<div id="p-beecol" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#1565C0">Colony Source · कालोनी का स्रोत</span>
      <div class="sec-title">Source of Bee Colonies by Hive Type</div>
    </div>
  </div>

  <!-- Type overview strip -->
  <div class="bcs-overview">
    <!-- Stacked participation bar -->
    <div class="bcs-stack-card">
      <div class="bcs-stack-title">Farmer Participation by Colony Type</div>
      <div class="bcs-stack-bar">
        <?php foreach($colTypes as $ct):
          $w = $total > 0 ? round($ct['n']/$total*100) : 0;
        ?>
        <div class="bcs-stack-seg" style="width:<?= $w ?>%;background:<?= $ct['color'] ?>"
             title="<?= $ct['label'] ?>: <?= $w ?>%"></div>
        <?php endforeach; ?>
      </div>
      <div class="bcs-stack-legend">
        <?php foreach($colTypes as $ct):
          $pct = $total > 0 ? round($ct['n']/$total*100) : 0;
        ?>
        <div class="bcs-leg-item">
          <span class="bcs-leg-dot" style="background:<?= $ct['color'] ?>"></span>
          <span class="bcs-leg-name"><?= $ct['label'] ?></span>
          <span class="bcs-leg-pct" style="color:<?= $ct['color'] ?>"><?= $pct ?>%</span>
          <span class="bcs-leg-n">(<?= number_format($ct['n']) ?> farmers)</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Colony count stack -->
    <div class="bcs-stack-card">
      <div class="bcs-stack-title">Total Colonies by Hive Type</div>
      <div class="bcs-stack-bar">
        <?php foreach($colTypes as $ct):
          $w = $grandColonies > 0 ? round($ct['total']/$grandColonies*100) : 0;
        ?>
        <div class="bcs-stack-seg" style="width:<?= $w ?>%;background:<?= $ct['color'] ?>"
             title="<?= $ct['label'] ?>: <?= number_format($ct['total']) ?>"></div>
        <?php endforeach; ?>
      </div>
      <div class="bcs-stack-legend">
        <?php foreach($colTypes as $ct):
          $pct = $grandColonies > 0 ? round($ct['total']/$grandColonies*100) : 0;
        ?>
        <div class="bcs-leg-item">
          <span class="bcs-leg-dot" style="background:<?= $ct['color'] ?>"></span>
          <span class="bcs-leg-name"><?= $ct['label'] ?></span>
          <span class="bcs-leg-pct" style="color:<?= $ct['color'] ?>"><?= $pct ?>%</span>
          <span class="bcs-leg-n">(<?= number_format($ct['total']) ?> colonies)</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- 3 type detail cards -->
  <div class="bcs-cards">
    <?php foreach($colTypes as $ct):
      $srcTot = array_sum(array_column($ct['src'], 'n'));
      $farmerPct = $total > 0 ? round($ct['n']/$total*100) : 0;
    ?>
    <div class="bcs-card" style="--bcc:<?= $ct['color'] ?>;--bcl:<?= $ct['light'] ?>">

      <!-- Header -->
      <div class="bcs-card-hdr">
        <span class="bcs-card-ico"><?= $ct['icon'] ?></span>
        <div class="bcs-card-titles">
          <div class="bcs-card-name"><?= $ct['label'] ?></div>
          <div class="bcs-card-hindi"><?= $ct['hindi'] ?></div>
        </div>
        <div class="bcs-card-pill" style="background:<?= $ct['color'] ?>"><?= number_format($ct['n']) ?> Farmers</div>
      </div>

      <!-- Farmer share bar -->
      <div class="bcs-share-wrap">
        <div class="bcs-share-track">
          <div class="bcs-share-fill" style="width:<?= $farmerPct ?>%;background:<?= $ct['color'] ?>"></div>
        </div>
        <span class="bcs-share-pct" style="color:<?= $ct['color'] ?>"><?= $farmerPct ?>% of beekeepers</span>
      </div>

      <!-- Stats 2×2 -->
      <div class="bcs-stat-grid">
        <div class="bcs-stat" style="border-left-color:<?= $ct['color'] ?>">
          <div class="bcs-stat-n" style="color:<?= $ct['color'] ?>"><?= number_format($ct['total']) ?></div>
          <div class="bcs-stat-l">Total Colonies</div>
        </div>
        <div class="bcs-stat">
          <div class="bcs-stat-n"><?= $ct['avg'] ?></div>
          <div class="bcs-stat-l">Avg / Farmer</div>
        </div>
        <div class="bcs-stat">
          <div class="bcs-stat-n">₹<?= number_format($ct['val']) ?></div>
          <div class="bcs-stat-l">Avg Value / Colony</div>
        </div>
        <div class="bcs-stat">
          <div class="bcs-stat-n">₹<?= number_format(round($ct['total'] * $ct['val'] / 100000)) ?>L</div>
          <div class="bcs-stat-l">Est. Total Value</div>
        </div>
      </div>

      <!-- Acquisition source -->
      <?php if(!empty($ct['src'])): ?>
      <div class="bcs-src-wrap">
        <div class="bcs-src-title">Acquisition Source</div>
        <?php foreach($ct['src'] as $i => $s):
          $sp  = $srcTot > 0 ? round($s['n']/$srcTot*100) : 0;
          $col = $srcColors[$i % count($srcColors)];
        ?>
        <div class="bcs-src-row">
          <span class="bcs-src-dot" style="background:<?= $col ?>"></span>
          <span class="bcs-src-name"><?= htmlspecialchars($s['src']) ?></span>
          <div class="bcs-src-track">
            <div class="bcs-src-fill" style="width:<?= $sp ?>%;background:<?= $col ?>"></div>
          </div>
          <span class="bcs-src-pct"><?= $sp ?>%</span>
          <span class="bcs-src-n"><?= number_format($s['n']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>
  </div>

</div>
