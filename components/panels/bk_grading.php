<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    SUM(CASE WHEN is_under_harvest_grading_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_grade,
    SUM(CASE WHEN is_under_harvest_grading_YES_NO='No'  THEN 1 ELSE 0 END) AS no_grade,
    SUM(CASE WHEN manual_grading_YES_NO='Yes'  THEN 1 ELSE 0 END) AS n_manual,
    SUM(CASE WHEN machine_grading_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_machine,
    SUM(CASE WHEN branding1_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_b1,
    ROUND(AVG(CAST(NULLIF(branding1_varieties_total_sell,'')    AS DECIMAL(12,2))),0) AS b1_sell,
    ROUND(AVG(CAST(NULLIF(branding1_varieties_total_revenue,'') AS DECIMAL(14,2))),0) AS b1_rev,
    ROUND(AVG(CAST(NULLIF(branding1_varieties_average_price,'') AS DECIMAL(10,2))),0) AS b1_price,
    SUM(CASE WHEN branding2_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_b2,
    ROUND(AVG(CAST(NULLIF(branding2_varieties_total_sell,'')    AS DECIMAL(12,2))),0) AS b2_sell,
    ROUND(AVG(CAST(NULLIF(branding2_varieties_total_revenue,'') AS DECIMAL(14,2))),0) AS b2_rev,
    ROUND(AVG(CAST(NULLIF(branding2_varieties_average_price,'') AS DECIMAL(10,2))),0) AS b2_price,
    SUM(CASE WHEN branding3_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_b3,
    ROUND(AVG(CAST(NULLIF(branding3_varieties_total_sell,'')    AS DECIMAL(12,2))),0) AS b3_sell,
    ROUND(AVG(CAST(NULLIF(branding3_varieties_total_revenue,'') AS DECIMAL(14,2))),0) AS b3_rev,
    ROUND(AVG(CAST(NULLIF(branding3_varieties_average_price,'') AS DECIMAL(10,2))),0) AS b3_price,
    SUM(CAST(NULLIF(branding1_colyellow_number,'') AS UNSIGNED))  AS tot_yellow,
    SUM(CAST(NULLIF(branding1_colrbrown_number,'') AS UNSIGNED))  AS tot_rbrown,
    SUM(CAST(NULLIF(branding1_coldbrown_number,'') AS UNSIGNED))  AS tot_dbrown,
    ROUND(AVG(CAST(NULLIF(branding1_colyellow_price,'') AS DECIMAL(10,2))),0) AS p_yellow,
    ROUND(AVG(CAST(NULLIF(branding1_colrbrown_price,'') AS DECIMAL(10,2))),0) AS p_rbrown,
    ROUND(AVG(CAST(NULLIF(branding1_coldbrown_price,'') AS DECIMAL(10,2))),0) AS p_dbrown,
    SUM(CASE WHEN branding1_weight250_confirm_YES_NO='Yes'  THEN 1 ELSE 0 END) AS b1_w250,
    SUM(CASE WHEN branding1_weight500_confirm_YES_NO='Yes'  THEN 1 ELSE 0 END) AS b1_w500,
    SUM(CASE WHEN branding1_weight1000_confirm_YES_NO='Yes' THEN 1 ELSE 0 END) AS b1_w1000,
    ROUND(AVG(CAST(NULLIF(branding1_weight250_price,'')  AS DECIMAL(10,2))),0) AS avg_250p,
    ROUND(AVG(CAST(NULLIF(branding1_weight500_price,'')  AS DECIMAL(10,2))),0) AS avg_500p,
    ROUND(AVG(CAST(NULLIF(branding1_weight1000_price,'') AS DECIMAL(10,2))),0) AS avg_1000p
  FROM bk_data_part6
")->fetch(PDO::FETCH_ASSOC);

// Brand variety types (normalised)
$brandRows = $pdo->query("SELECT branding1_type_of_brand FROM bk_data_part6 WHERE branding1_type_of_brand!=''")->fetchAll(PDO::FETCH_COLUMN);
$bvCounts = [];
foreach($brandRows as $row) {
  if(strpos($row,'Mustard')    !== false) $bvCounts['Mustard Honey']    = ($bvCounts['Mustard Honey']    ?? 0) + 1;
  if(strpos($row,'Eucalyptus') !== false) $bvCounts['Eucalyptus Honey'] = ($bvCounts['Eucalyptus Honey'] ?? 0) + 1;
  if(strpos($row,'Litchi')     !== false) $bvCounts['Litchi Honey']     = ($bvCounts['Litchi Honey']     ?? 0) + 1;
  if(strpos($row,'Sunflower')  !== false) $bvCounts['Sunflower Honey']  = ($bvCounts['Sunflower Honey']  ?? 0) + 1;
  if(strpos($row,'Others')     !== false) $bvCounts['Others']           = ($bvCounts['Others']           ?? 0) + 1;
}
arsort($bvCounts);
$maxBV = $bvCounts ? max($bvCounts) : 1;

function pctG($n,$d){ return $d > 0 ? (int)round($n/$d*100) : 0; }
function lakhG($n)  { return '₹' . round($n/100000, 1) . ' L'; }

$n_grade  = (int)$d['n_grade'];
$gradePct = pctG($n_grade, $total);
$manPct   = pctG((int)$d['n_manual'], $n_grade);
$macPct   = pctG((int)$d['n_machine'], $n_grade);

$brandings = [
  ['label'=>'Branding I',   'n'=>(int)$d['n_b1'], 'sell'=>(int)$d['b1_sell'], 'rev'=>(int)$d['b1_rev'], 'price'=>(int)$d['b1_price'], 'color'=>'#F2A900'],
  ['label'=>'Branding II',  'n'=>(int)$d['n_b2'], 'sell'=>(int)$d['b2_sell'], 'rev'=>(int)$d['b2_rev'], 'price'=>(int)$d['b2_price'], 'color'=>'#E65100'],
  ['label'=>'Branding III', 'n'=>(int)$d['n_b3'], 'sell'=>(int)$d['b3_sell'], 'rev'=>(int)$d['b3_rev'], 'price'=>(int)$d['b3_price'], 'color'=>'#6A1B9A'],
];
$maxBN = max(array_column($brandings,'n'));

$colors = [
  ['label'=>'Yellow',        'hindi'=>'पीला',            'n'=>(int)$d['tot_yellow'], 'price'=>(int)$d['p_yellow'], 'color'=>'#FDD835','text'=>'#555'],
  ['label'=>'Reddish Brown', 'hindi'=>'लालिमायुक्त भूरा','n'=>(int)$d['tot_rbrown'], 'price'=>(int)$d['p_rbrown'], 'color'=>'#8D6E63','text'=>'#fff'],
  ['label'=>'Dark Brown',    'hindi'=>'गहरा भूरा',        'n'=>(int)$d['tot_dbrown'], 'price'=>(int)$d['p_dbrown'], 'color'=>'#4E342E','text'=>'#fff'],
];
$maxCN = max(array_column($colors,'n'));
?>

<!-- ══ BK GRADING ══ -->
<div id="p-bkgrade" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#6A1B9A">Grading &amp; Branding · ग्रेडिंग एवं ब्रांडिंग</span>
      <div class="sec-title">Honey Grading after Harvest &amp; Branding Details</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkg-kpi-row">
    <div class="bkg-kpi" style="--kc:#6A1B9A">
      <div class="bkg-kpi-val"><?= $gradePct ?>%</div>
      <div class="bkg-kpi-lbl">Do Grading</div>
      <div class="bkg-kpi-sub"><?= number_format($n_grade) ?> farmers</div>
    </div>
    <div class="bkg-kpi" style="--kc:#1565C0">
      <div class="bkg-kpi-val"><?= $manPct ?>%</div>
      <div class="bkg-kpi-lbl">Manual Grading</div>
      <div class="bkg-kpi-sub"><?= number_format((int)$d['n_manual']) ?> farmers</div>
    </div>
    <div class="bkg-kpi" style="--kc:#2E7D32">
      <div class="bkg-kpi-val"><?= $macPct ?>%</div>
      <div class="bkg-kpi-lbl">Machine Grading</div>
      <div class="bkg-kpi-sub"><?= number_format((int)$d['n_machine']) ?> farmers</div>
    </div>
    <div class="bkg-kpi" style="--kc:#F2A900">
      <div class="bkg-kpi-val"><?= pctG((int)$d['n_b1'],$total) ?>%</div>
      <div class="bkg-kpi-lbl">Have Branding I</div>
      <div class="bkg-kpi-sub"><?= number_format((int)$d['n_b1']) ?> farmers</div>
    </div>
    <div class="bkg-kpi" style="--kc:#E65100">
      <div class="bkg-kpi-val">₹<?= number_format((int)$d['b1_price']) ?>/kg</div>
      <div class="bkg-kpi-lbl">Avg Branded Price</div>
      <div class="bkg-kpi-sub">Branding I avg</div>
    </div>
  </div>

  <!-- 3-col charts -->
  <div class="bkg-charts">

    <!-- Card 1: Grading methods + variety brands -->
    <div class="bkg-chart-card">
      <div class="bkg-card-title">Grading Methods</div>
      <div class="bkg-card-sub">ग्रेडिंग की विधि</div>

      <!-- Manual vs Machine split -->
      <?php $noGradePct = 100 - $gradePct; ?>
      <div class="bkg-yn-bar">
        <div class="bkg-yn-seg" style="width:<?= $gradePct ?>%;background:#6A1B9A" title="Grading done <?= $gradePct ?>%"></div>
        <div class="bkg-yn-seg" style="width:<?= $noGradePct ?>%;background:#E0E0E0"></div>
      </div>
      <div class="bkg-yn-leg">
        <span class="bkg-yn-dot" style="background:#6A1B9A"></span><span>Graded</span>
        <strong style="color:#6A1B9A"><?= $gradePct ?>%</strong>
        <span class="bkg-yn-n">(<?= number_format($n_grade) ?>)</span>
        <span style="margin-left:12px;color:#9E9E9E">Not graded: <?= $noGradePct ?>%</span>
      </div>

      <div class="bkg-sub-hdr">Method among graders</div>
      <?php
      $methods = [
        ['label'=>'Manual Grading', 'hindi'=>'मेनुअल ग्रेडिंग', 'n'=>(int)$d['n_manual'], 'color'=>'#1565C0'],
        ['label'=>'Machine Grading','hindi'=>'मशीन ग्रेडिंग',   'n'=>(int)$d['n_machine'],'color'=>'#2E7D32'],
      ];
      foreach($methods as $m):
        $mp = pctG($m['n'], $n_grade);
      ?>
      <div class="bkg-bar-row">
        <div class="bkg-bar-label">
          <span class="bkg-bar-name"><?= $m['label'] ?></span>
          <span class="bkg-bar-hindi"><?= $m['hindi'] ?></span>
        </div>
        <div class="bkg-bar-track"><div class="bkg-bar-fill" style="width:<?= $mp ?>%;background:<?= $m['color'] ?>"></div></div>
        <div class="bkg-bar-meta">
          <span class="bkg-bar-pct" style="color:<?= $m['color'] ?>"><?= $mp ?>%</span>
          <span class="bkg-bar-n"><?= number_format($m['n']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkg-sub-hdr">Honey variety branded (Branding I)</div>
      <?php foreach($bvCounts as $bname => $bcnt):
        $bw = (int)round($bcnt/$maxBV*100);
        $bp = pctG($bcnt, (int)$d['n_b1']);
      ?>
      <div class="bkg-bar-row bkg-bar-row--sm">
        <div class="bkg-bar-label bkg-bar-label--sm"><?= $bname ?></div>
        <div class="bkg-bar-track"><div class="bkg-bar-fill" style="width:<?= $bw ?>%;background:#F9A825"></div></div>
        <div class="bkg-bar-meta">
          <span class="bkg-bar-pct" style="color:#F9A825"><?= $bp ?>%</span>
          <span class="bkg-bar-n"><?= number_format($bcnt) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Card 2: 3-tier branding -->
    <div class="bkg-chart-card">
      <div class="bkg-card-title">3-Tier Branding Overview</div>
      <div class="bkg-card-sub">तीन स्तरीय ब्रांडिंग विवरण</div>

      <?php foreach($brandings as $b):
        $bpct = pctG($b['n'], $total);
        $bw   = $maxBN > 0 ? (int)round($b['n']/$maxBN*100) : 0;
      ?>
      <div class="bkg-brand-card" style="--bc:<?= $b['color'] ?>">
        <div class="bkg-brand-hdr">
          <span class="bkg-brand-label"><?= $b['label'] ?></span>
          <span class="bkg-brand-n" style="background:<?= $b['color'] ?>"><?= number_format($b['n']) ?> farmers</span>
        </div>
        <div class="bkg-brand-bar-wrap">
          <div class="bkg-brand-bar" style="width:<?= $bw ?>%;background:<?= $b['color'] ?>"></div>
        </div>
        <div class="bkg-brand-stats">
          <div class="bkg-brand-stat">
            <span class="bkg-bs-v" style="color:<?= $b['color'] ?>"><?= $bpct ?>%</span>
            <span class="bkg-bs-l">of farmers</span>
          </div>
          <div class="bkg-brand-stat">
            <span class="bkg-bs-v"><?= number_format((int)$b['sell']) ?> kg</span>
            <span class="bkg-bs-l">avg sell</span>
          </div>
          <div class="bkg-brand-stat">
            <span class="bkg-bs-v">₹<?= number_format((int)$b['price']) ?></span>
            <span class="bkg-bs-l">avg ₹/kg</span>
          </div>
          <div class="bkg-brand-stat">
            <span class="bkg-bs-v"><?= lakhG((int)$b['rev']) ?></span>
            <span class="bkg-bs-l">avg revenue</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkg-note">Farmers may hold multiple brandings simultaneously</div>
    </div>

    <!-- Card 3: Colour grading -->
    <div class="bkg-chart-card">
      <div class="bkg-card-title">Honey Colour Grading</div>
      <div class="bkg-card-sub">शहद का रंग आधारित वर्गीकरण (Branding I)</div>

      <?php foreach($colors as $col):
        $cw = $maxCN > 0 ? (int)round($col['n']/$maxCN*100) : 0;
        $cp = $col['n'] > 0 ? pctG($col['n'], array_sum(array_column($colors,'n'))) : 0;
      ?>
      <div class="bkg-col-row">
        <div class="bkg-col-swatch" style="background:<?= $col['color'] ?>;color:<?= $col['text'] ?>">
          <?= $col['label'][0] ?>
        </div>
        <div class="bkg-col-info">
          <span class="bkg-col-name"><?= $col['label'] ?></span>
          <span class="bkg-col-hindi"><?= $col['hindi'] ?></span>
        </div>
        <div class="bkg-bar-track" style="background:#F0EBE3">
          <div class="bkg-bar-fill" style="width:<?= $cw ?>%;background:<?= $col['color'] ?>"></div>
        </div>
        <div class="bkg-col-meta">
          <span class="bkg-col-n"><?= number_format($col['n']) ?> units</span>
          <?php if($col['price'] > 0): ?>
          <span class="bkg-col-p">₹<?= number_format($col['price']) ?>/unit</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkg-sub-hdr" style="margin-top:14px">Packaging size distribution (Branding I farmers)</div>
      <?php
      $packs = [
        ['label'=>'250g packs', 'w'=>'b1_w250', 'color'=>'#42A5F5'],
        ['label'=>'500g packs', 'w'=>'b1_w500', 'color'=>'#1E88E5'],
        ['label'=>'1 Kg packs', 'w'=>'b1_w1000','color'=>'#1565C0'],
      ];
      $packMap = ['b1_w250'=>(int)$d['b1_w250'],'b1_w500'=>(int)$d['b1_w500'],'b1_w1000'=>(int)$d['b1_w1000']];
      $priceMap = ['b1_w250'=>$d['avg_250p']??'–','b1_w500'=>$d['avg_500p']??'–','b1_w1000'=>$d['avg_1000p']??'–'];
      $maxPack = max(array_values($packMap)) ?: 1;
      // Re-fetch avg prices
      foreach($packs as $pk):
        $pw = (int)round($packMap[$pk['w']]/$maxPack*100);
      ?>
      <div class="bkg-bar-row bkg-bar-row--sm">
        <div class="bkg-bar-label bkg-bar-label--sm"><?= $pk['label'] ?></div>
        <div class="bkg-bar-track"><div class="bkg-bar-fill" style="width:<?= $pw ?>%;background:<?= $pk['color'] ?>"></div></div>
        <div class="bkg-bar-meta">
          <span class="bkg-bar-pct" style="color:<?= $pk['color'] ?>"><?= number_format($packMap[$pk['w']]) ?></span>
          <span class="bkg-bar-n">adopters</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div><!-- /.bkg-charts -->
</div>
