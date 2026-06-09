<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    ROUND(AVG(CAST(NULLIF(sr1_pree_loss_NCPL_count,'')  AS DECIMAL(10,2))),1) AS avg_ncpl,
    ROUND(AVG(CAST(NULLIF(sr1_pree_loss_DPPL_count,'')  AS DECIMAL(10,2))),1) AS avg_dppl,
    COUNT(CASE WHEN sr1_Pree_loss_Due_to_Stray_Wild_Animals!='' THEN 1 END)   AS n_swa,
    ROUND(AVG(CAST(NULLIF(sr1_Pree_loss_Due_to_Stray_Wild_Animals,'')  AS DECIMAL(10,2))),1) AS avg_swa,
    COUNT(CASE WHEN sr1_Pree_loss_Side_effects_of_Chemical_Medicines!='' THEN 1 END) AS n_chem,
    ROUND(AVG(CAST(NULLIF(sr1_Pree_loss_Side_effects_of_Chemical_Medicines,'') AS DECIMAL(10,2))),1) AS avg_chem,
    ROUND(AVG(CAST(NULLIF(Q_No_10_sr1_Pre__Loss_Total_Kg,'')         AS DECIMAL(10,2))),1) AS avg_pre_total,
    ROUND(SUM(CAST(NULLIF(Q_No_10_sr1_Pre__Loss_Total_Kg,'')         AS DECIMAL(10,2))),0) AS tot_pre,

    COUNT(CASE WHEN sr1_Loss_After_wbpla_Washing_Branding_Packaging!='' THEN 1 END) AS n_wbp,
    ROUND(AVG(CAST(NULLIF(sr1_Loss_After_wbpla_Washing_Branding_Packaging,'') AS DECIMAL(10,2))),1) AS avg_wbp,
    COUNT(CASE WHEN sr1_Loss_After_Transportation!='' THEN 1 END)               AS n_trans,
    ROUND(AVG(CAST(NULLIF(sr1_Loss_After_Transportation,'')                AS DECIMAL(10,2))),1) AS avg_trans,
    COUNT(CASE WHEN sr1_Loss_After_Crystallized_Product_unfit_for_Sale!='' THEN 1 END) AS n_crys,
    ROUND(AVG(CAST(NULLIF(sr1_Loss_After_Crystallized_Product_unfit_for_Sale,'') AS DECIMAL(10,2))),1) AS avg_crys,
    COUNT(CASE WHEN sr1_Loss_After_Destruction_of_Beehives!='' THEN 1 END)     AS n_dest,
    ROUND(AVG(CAST(NULLIF(sr1_Loss_After_Destruction_of_Beehives,'')       AS DECIMAL(10,2))),1) AS avg_dest,
    ROUND(AVG(CAST(NULLIF(Q_No_10_sr1__Loss_After_Total_Kg,'')         AS DECIMAL(10,2))),1) AS avg_post_total,
    ROUND(AVG(CAST(NULLIF(Q_No_10_sr1_Aggregate_Loss_Total_Kg,'')      AS DECIMAL(10,2))),1) AS avg_agg,
    ROUND(SUM(CAST(NULLIF(Q_No_10_sr1_Aggregate_Loss_Total_Kg,'')      AS DECIMAL(10,2))),0) AS tot_agg
  FROM bk_data_part2
")->fetch(PDO::FETCH_ASSOC);

// Avg honey from Section 8 for context
$avgHoney = (float)$pdo->query("SELECT ROUND(AVG(CAST(NULLIF(sr1_honey_qty,'') AS DECIMAL(14,2))),1) FROM bk_data_part1")->fetchColumn();
$lossPct  = $avgHoney > 0 ? round($d['avg_agg'] / $avgHoney * 100, 1) : 0;

function pctL($n,$d){ return $d > 0 ? (int)round($n/$d*100) : 0; }

$preLoss = [
  ['label'=>'Natural Calamities',     'hindi'=>'प्राकृतिक आपदा',           'avg'=>(float)$d['avg_ncpl'], 'n'=>$total,             'color'=>'#B71C1C'],
  ['label'=>'Chemical Medicines',     'hindi'=>'रासायनिक दवाइयाँ',          'avg'=>(float)$d['avg_chem'], 'n'=>(int)$d['n_chem'],  'color'=>'#C62828'],
  ['label'=>'Diseases & Pests',       'hindi'=>'रोग एवं कीट',               'avg'=>(float)$d['avg_dppl'], 'n'=>$total,             'color'=>'#D32F2F'],
  ['label'=>'Stray / Wild Animals',   'hindi'=>'आवारा / जंगली जानवर',       'avg'=>(float)$d['avg_swa'],  'n'=>(int)$d['n_swa'],   'color'=>'#E53935'],
];
$maxPre = max(array_column($preLoss,'avg'));

$postLoss = [
  ['label'=>'Beehive Destruction',    'hindi'=>'छत्ता नष्ट होना',            'avg'=>(float)$d['avg_dest'], 'n'=>(int)$d['n_dest'], 'color'=>'#E65100'],
  ['label'=>'Transportation',         'hindi'=>'परिवहन',                     'avg'=>(float)$d['avg_trans'],'n'=>(int)$d['n_trans'],'color'=>'#EF6C00'],
  ['label'=>'Washing/Branding/Pack',  'hindi'=>'धुलाई/ब्रान्डिंग/पैकेजिंग', 'avg'=>(float)$d['avg_wbp'],  'n'=>(int)$d['n_wbp'],  'color'=>'#F57C00'],
  ['label'=>'Crystallized Product',   'hindi'=>'क्रिस्टलीकृत उत्पाद',         'avg'=>(float)$d['avg_crys'], 'n'=>(int)$d['n_crys'], 'color'=>'#FF8F00'],
];
$maxPost = max(array_column($postLoss,'avg'));
?>

<!-- ══ BK LOSSES ══ -->
<div id="p-bklosses" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#B71C1C">Production Losses · उत्पादन में नुकसान</span>
      <div class="sec-title">Details of Loss in Beekeeping Production</div>
    </div>
  </div>

  <!-- KPI Strip -->
  <div class="bkl-kpi-row">
    <div class="bkl-kpi" style="--kc:#B71C1C">
      <div class="bkl-kpi-val"><?= $d['avg_pre_total'] ?> kg</div>
      <div class="bkl-kpi-lbl">Avg Pre-Loss</div>
      <div class="bkl-kpi-sub">per farmer</div>
    </div>
    <div class="bkl-kpi" style="--kc:#E65100">
      <div class="bkl-kpi-val"><?= $d['avg_post_total'] ?> kg</div>
      <div class="bkl-kpi-lbl">Avg Post-Loss</div>
      <div class="bkl-kpi-sub">per farmer</div>
    </div>
    <div class="bkl-kpi" style="--kc:#7B1FA2">
      <div class="bkl-kpi-val"><?= $d['avg_agg'] ?> kg</div>
      <div class="bkl-kpi-lbl">Avg Total Loss</div>
      <div class="bkl-kpi-sub">aggregate per farmer</div>
    </div>
    <div class="bkl-kpi" style="--kc:#4A148C">
      <div class="bkl-kpi-val"><?= number_format((int)$d['tot_agg'] / 1000, 1) ?> T</div>
      <div class="bkl-kpi-lbl">Total Loss (Survey)</div>
      <div class="bkl-kpi-sub"><?= number_format((int)$d['tot_agg']) ?> kg across all farmers</div>
    </div>
    <div class="bkl-kpi" style="--kc:#C62828">
      <div class="bkl-kpi-val"><?= $lossPct ?>%</div>
      <div class="bkl-kpi-lbl">Loss Rate</div>
      <div class="bkl-kpi-sub">of avg honey output</div>
    </div>
  </div>

  <!-- 3-col chart row -->
  <div class="bkl-charts">

    <!-- Card 1: Pre-loss -->
    <div class="bkl-chart-card">
      <div class="bkl-card-title">Pre-Production Losses</div>
      <div class="bkl-card-sub">मौन पालन उत्पादन के पूर्व का नुकसान (kg avg/farmer)</div>

      <?php foreach($preLoss as $pl):
        $bw = $maxPre > 0 ? (int)round($pl['avg']/$maxPre*100) : 0;
        $pp = pctL($pl['n'], $total);
      ?>
      <div class="bkl-loss-row">
        <div class="bkl-loss-label">
          <span class="bkl-loss-name"><?= $pl['label'] ?></span>
          <span class="bkl-loss-hindi"><?= $pl['hindi'] ?></span>
        </div>
        <div class="bkl-bar-track">
          <div class="bkl-bar-fill" style="width:<?= $bw ?>%;background:<?= $pl['color'] ?>"></div>
        </div>
        <div class="bkl-loss-meta">
          <span class="bkl-loss-avg" style="color:<?= $pl['color'] ?>"><?= $pl['avg'] ?> kg</span>
          <span class="bkl-loss-n"><?= $pp ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkl-total-chip" style="background:#FFEBEE;color:#B71C1C">
        Total Pre-Loss: <strong><?= $d['avg_pre_total'] ?> kg</strong> avg per farmer
        &nbsp;·&nbsp; <?= number_format((int)$d['tot_pre']) ?> kg total
      </div>
    </div>

    <!-- Card 2: Post-loss -->
    <div class="bkl-chart-card">
      <div class="bkl-card-title">Post-Production Losses</div>
      <div class="bkl-card-sub">मौन पालन उत्पादन के बाद का नुकसान (kg avg/farmer)</div>

      <?php foreach($postLoss as $pl):
        $bw = $maxPost > 0 ? (int)round($pl['avg']/$maxPost*100) : 0;
        $pp = pctL($pl['n'], $total);
      ?>
      <div class="bkl-loss-row">
        <div class="bkl-loss-label">
          <span class="bkl-loss-name"><?= $pl['label'] ?></span>
          <span class="bkl-loss-hindi"><?= $pl['hindi'] ?></span>
        </div>
        <div class="bkl-bar-track">
          <div class="bkl-bar-fill" style="width:<?= $bw ?>%;background:<?= $pl['color'] ?>"></div>
        </div>
        <div class="bkl-loss-meta">
          <span class="bkl-loss-avg" style="color:<?= $pl['color'] ?>"><?= $pl['avg'] ?> kg</span>
          <span class="bkl-loss-n"><?= $pp ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="bkl-total-chip" style="background:#FFF3E0;color:#E65100">
        Total Post-Loss: <strong><?= $d['avg_post_total'] ?> kg</strong> avg per farmer
      </div>
    </div>

    <!-- Card 3: Loss summary / context -->
    <div class="bkl-chart-card">
      <div class="bkl-card-title">Loss in Context</div>
      <div class="bkl-card-sub">कुल नुकसान · उत्पादन के सापेक्ष</div>

      <!-- Pre vs Post vs Saved bar -->
      <?php
        $preShare  = $d['avg_agg'] > 0 ? (int)round($d['avg_pre_total']  / $d['avg_agg'] * 100) : 0;
        $postShare = 100 - $preShare;
        $savedPct  = $avgHoney > 0 ? (int)round(($avgHoney - $d['avg_agg']) / $avgHoney * 100) : 0;
      ?>
      <div class="bkl-sub-hdr">Loss composition</div>
      <div class="bkl-comp-bar">
        <div class="bkl-comp-seg" style="width:<?= $preShare ?>%;background:#B71C1C" title="Pre-Loss <?= $preShare ?>%"></div>
        <div class="bkl-comp-seg" style="width:<?= $postShare ?>%;background:#E65100" title="Post-Loss <?= $postShare ?>%"></div>
      </div>
      <div class="bkl-comp-leg">
        <div class="bkl-cleg-item">
          <span class="bkl-cleg-dot" style="background:#B71C1C"></span>
          <span>Pre-Production</span>
          <strong style="color:#B71C1C"><?= $preShare ?>%</strong>
          <span class="bkl-cleg-kg">(<?= $d['avg_pre_total'] ?> kg)</span>
        </div>
        <div class="bkl-cleg-item">
          <span class="bkl-cleg-dot" style="background:#E65100"></span>
          <span>Post-Production</span>
          <strong style="color:#E65100"><?= $postShare ?>%</strong>
          <span class="bkl-cleg-kg">(<?= $d['avg_post_total'] ?> kg)</span>
        </div>
      </div>

      <div class="bkl-sub-hdr" style="margin-top:14px">Production vs Loss (avg farmer)</div>
      <div class="bkl-prod-bar">
        <div class="bkl-prod-saved" style="width:<?= $savedPct ?>%" title="Sold <?= $savedPct ?>%"></div>
        <div class="bkl-prod-lost"  style="width:<?= $lossPct ?>%"  title="Lost <?= $lossPct ?>%"></div>
      </div>
      <div class="bkl-prod-leg">
        <div class="bkl-cleg-item">
          <span class="bkl-cleg-dot" style="background:#2E7D32"></span>
          <span>Sold / Kept</span>
          <strong style="color:#2E7D32"><?= $savedPct ?>%</strong>
          <span class="bkl-cleg-kg">(<?= number_format((int)($avgHoney - $d['avg_agg'])) ?> kg)</span>
        </div>
        <div class="bkl-cleg-item">
          <span class="bkl-cleg-dot" style="background:#B71C1C"></span>
          <span>Lost</span>
          <strong style="color:#B71C1C"><?= $lossPct ?>%</strong>
          <span class="bkl-cleg-kg">(<?= $d['avg_agg'] ?> kg)</span>
        </div>
      </div>

      <div class="bkl-sub-hdr" style="margin-top:14px">Survey totals</div>
      <div class="bkl-totals-grid">
        <div class="bkl-tot" style="--tc:#B71C1C">
          <div class="bkl-tot-v"><?= number_format((int)$d['tot_pre'] / 1000, 1) ?> T</div>
          <div class="bkl-tot-l">Pre-Loss Total</div>
        </div>
        <div class="bkl-tot" style="--tc:#E65100">
          <div class="bkl-tot-v"><?= number_format(((int)$d['tot_agg'] - (int)$d['tot_pre']) / 1000, 1) ?> T</div>
          <div class="bkl-tot-l">Post-Loss Total</div>
        </div>
        <div class="bkl-tot" style="--tc:#7B1FA2">
          <div class="bkl-tot-v"><?= number_format((int)$d['tot_agg'] / 1000, 1) ?> T</div>
          <div class="bkl-tot-l">Aggregate Loss</div>
        </div>
      </div>
    </div>

  </div><!-- /.bkl-charts -->
</div>
