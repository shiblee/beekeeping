<?php
require_once __DIR__ . '/../../config/db.php';

$total = (int)$pdo->query("SELECT COUNT(*) FROM bk_data_part1")->fetchColumn();

$d = $pdo->query("
  SELECT
    SUM(CASE WHEN membership_information_YES_NO='Yes' THEN 1 ELSE 0 END) AS n_member,
    SUM(CASE WHEN sr1_member_institution_name != ''   THEN 1 ELSE 0 END) AS n_mem1,
    SUM(CASE WHEN sr2_member_institution_name != ''   THEN 1 ELSE 0 END) AS n_mem2,
    SUM(CASE WHEN sr3_member_institution_name != ''   THEN 1 ELSE 0 END) AS n_mem3
  FROM bk_data_part8
")->fetch(PDO::FETCH_ASSOC);

// Primary institution names — raw
$inst1_raw = $pdo->query("
  SELECT sr1_member_institution_name AS v, COUNT(*) AS n
  FROM bk_data_part8
  WHERE sr1_member_institution_name != ''
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Benefit status for primary membership
$ben1 = $pdo->query("
  SELECT sr1_member_institution_received_benefit AS v, COUNT(*) AS n
  FROM bk_data_part8
  WHERE sr1_member_institution_received_benefit != '' AND membership_information_YES_NO='Yes'
  GROUP BY v ORDER BY n DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Normalise institution names
function normInst($raw){
  if(stripos($raw,'National Bee Bo') !== false || $raw === 'NBB') return 'National Bee Board (NBB)';
  if(stripos($raw,'Madhu') !== false && (stripos($raw,'Kranti')!==false||stripos($raw,'Karanti')!==false||stripos($raw,'Krinti')!==false))
    return 'Madhukranti BK Welfare Society';
  if(stripos($raw,'Udyan') !== false || stripos($raw,'Udhan') !== false) return 'Jila Udyan Vibhag';
  if(stripos($raw,'FPO') !== false || stripos($raw,'F P O') !== false)   return 'FPO';
  if(stripos($raw,'Cooperative') !== false || stripos($raw,'Coperative') !== false || stripos($raw,'Copretiv') !== false) return 'Cooperative Society';
  if(stripos($raw,'Apiculture') !== false) return 'Apiculture Industry';
  if(stripos($raw,'Amroha') !== false)     return 'Amroha Kisan Producer Co.';
  return $raw;
}

$instAgg = [];
foreach($inst1_raw as $row){
  $lbl = normInst($row['v']);
  $instAgg[$lbl] = ($instAgg[$lbl] ?? 0) + (int)$row['n'];
}
arsort($instAgg);
$maxInst = max($instAgg) ?: 1;

// Normalise benefits
$benNo = 0; $benYes = 0;
foreach($ben1 as $b){
  $v = strtolower(trim($b['v']));
  if($v === 'no' || $v === '0' || stripos($v,'no benefit') !== false) $benNo += (int)$b['n'];
  else $benYes += (int)$b['n'];
}

$n_member = (int)$d['n_member'];
$n_mem1   = (int)$d['n_mem1'];
$n_mem2   = (int)$d['n_mem2'];
$n_mem3   = (int)$d['n_mem3'];

function pctMem($n,$d){ return $d > 0 ? round($n/$d*100,1) : 0; }

$inst_colors = [
  'National Bee Board (NBB)'         => '#F2A900',
  'Madhukranti BK Welfare Society'   => '#42A5F5',
  'Jila Udyan Vibhag'                => '#66BB6A',
  'FPO'                              => '#AB47BC',
  'Cooperative Society'              => '#FF8A65',
  'Apiculture Industry'              => '#26C6DA',
  'Amroha Kisan Producer Co.'        => '#8D6E63',
];

// Secondary / tertiary membership list
$all_inst2 = $pdo->query("
  SELECT sr2_member_institution_name AS v FROM bk_data_part8 WHERE sr2_member_institution_name != ''
  UNION ALL
  SELECT sr3_member_institution_name FROM bk_data_part8 WHERE sr3_member_institution_name != ''
")->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- ══ BK MEMBERSHIP ══ -->
<div id="p-bkmembership" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#880E4F">Membership · सदस्यता</span>
      <div class="sec-title">Membership Information</div>
    </div>
  </div>

  <!-- KPI strip -->
  <div class="bkmem-kpi-row">
    <?php
    $kpis = [
      ['v'=>$n_member,                                'p'=>pctMem($n_member,$total).'% of farmers',  'l'=>'Members',              'h'=>'सरकारी सदस्य किसान',     'c'=>'#880E4F'],
      ['v'=>$n_mem1,                                  'p'=>'hold 1+ membership',                      'l'=>'Single Membership',    'h'=>'एक सदस्यता',              'c'=>'#AD1457'],
      ['v'=>$n_mem2,                                  'p'=>'hold 2+ memberships',                     'l'=>'Dual Membership',      'h'=>'दो सदस्यताएँ',            'c'=>'#C2185B'],
      ['v'=>$n_mem3,                                  'p'=>'hold 3 memberships',                      'l'=>'Triple Membership',    'h'=>'तीन सदस्यताएँ',           'c'=>'#E91E63'],
      ['v'=>$benNo + $benYes > 0 ? round($benNo/($benNo+$benYes)*100).'%' : '—',
                                                      'p'=>'reported no benefit yet',                 'l'=>'No Benefit Received',  'h'=>'अभी तक कोई लाभ नहीं',   'c'=>'#E65100'],
    ];
    foreach($kpis as $k):
    ?>
    <div class="bkmem-kpi-card" style="--kc:<?= $k['c'] ?>">
      <div class="bkmem-kv"><?= $k['v'] ?></div>
      <div class="bkmem-kp"><?= $k['p'] ?></div>
      <div class="bkmem-kl"><?= $k['l'] ?></div>
      <div class="bkmem-kh"><?= $k['h'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bkmem-charts">

    <!-- Col 1: Primary institution breakdown -->
    <div class="bkmem-chart-card">
      <div class="bkmem-card-title">Primary Institutions (Membership 1)</div>
      <div class="bkmem-card-sub"><?= $n_mem1 ?> farmers — primary govt. institution they belong to</div>

      <div class="bkmem-inst-scroll">
      <?php foreach($instAgg as $iname => $in):
        $bw = (int)round($in/$maxInst*100);
        $color = $inst_colors[$iname] ?? '#90A4AE';
      ?>
      <div class="bkmem-inst-row">
        <div class="bkmem-inst-labels">
          <span class="bkmem-inst-name"><?= htmlspecialchars($iname) ?></span>
        </div>
        <div class="bkmem-inst-bar-wrap">
          <div class="bkmem-inst-bar" style="width:<?= $bw ?>%;background:<?= $color ?>"></div>
        </div>
        <div class="bkmem-inst-meta">
          <span class="bkmem-inst-n" style="color:<?= $color ?>"><?= $in ?></span>
          <span class="bkmem-inst-pct"><?= pctMem($in,$n_mem1) ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>
      </div><!-- /.bkmem-inst-scroll -->
    </div><!-- col1 -->

    <!-- Col 2: Benefit status + depth -->
    <div class="bkmem-chart-card">
      <div class="bkmem-card-title">Benefit Status</div>
      <div class="bkmem-card-sub">Whether primary membership resulted in tangible benefit</div>

      <!-- Benefit donut-style -->
      <?php
      $benTotal = $benNo + $benYes;
      $yesPct   = $benTotal > 0 ? round($benYes/$benTotal*100) : 0;
      $noPct    = 100 - $yesPct;
      ?>
      <div class="bkmem-ben-strip">
        <div class="bkmem-ben-card" style="--bc:#2E7D32">
          <div class="bkmem-ben-v"><?= $benYes ?></div>
          <div class="bkmem-ben-p"><?= $yesPct ?>%</div>
          <div class="bkmem-ben-l">Availed Benefit</div>
          <div class="bkmem-ben-h">लाभ प्राप्त किया</div>
        </div>
        <div class="bkmem-ben-card" style="--bc:#E53935">
          <div class="bkmem-ben-v"><?= $benNo ?></div>
          <div class="bkmem-ben-p"><?= $noPct ?>%</div>
          <div class="bkmem-ben-l">No Benefit Yet</div>
          <div class="bkmem-ben-h">अभी तक कोई लाभ नहीं</div>
        </div>
      </div>

      <div class="bkmem-ben-bar-wrap">
        <div class="bkmem-ben-bar-yes" style="width:<?= $yesPct ?>%"></div>
        <div class="bkmem-ben-bar-no"  style="width:<?= $noPct  ?>%"></div>
      </div>

      <!-- Membership depth -->
      <div class="bkmem-card-title" style="margin-top:18px">Membership Depth</div>
      <div class="bkmem-card-sub">How many institutions farmers are members of</div>

      <?php
      $depth = [
        ['l'=>'1 membership', 'n'=>$n_mem1 - $n_mem2, 'c'=>'#AD1457'],
        ['l'=>'2 memberships','n'=>$n_mem2 - $n_mem3,  'c'=>'#C2185B'],
        ['l'=>'3 memberships','n'=>$n_mem3,             'c'=>'#E91E63'],
      ];
      $maxD = max(array_column($depth,'n')) ?: 1;
      foreach($depth as $dep):
        $bw = (int)round($dep['n']/$maxD*100);
      ?>
      <div class="bkmem-depth-row">
        <span class="bkmem-depth-label"><?= $dep['l'] ?></span>
        <div class="bkmem-depth-bar-wrap">
          <div class="bkmem-depth-bar" style="width:<?= $bw ?>%;background:<?= $dep['c'] ?>"></div>
        </div>
        <span class="bkmem-depth-n" style="color:<?= $dep['c'] ?>"><?= $dep['n'] ?></span>
      </div>
      <?php endforeach; ?>

      <!-- Secondary institutions -->
      <?php if($all_inst2): ?>
      <div class="bkmem-card-title" style="margin-top:14px">Secondary / Tertiary Institutions</div>
      <div class="bkmem-inst2-list">
        <?php foreach(array_unique($all_inst2) as $ii): ?>
        <span class="bkmem-inst2-tag"><?= htmlspecialchars($ii) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div><!-- col2 -->

  </div><!-- /.bkmem-charts -->

</div>
