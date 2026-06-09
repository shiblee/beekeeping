<?php
require_once __DIR__ . '/../../config/db.php';

$rows = $pdo->query("
  SELECT
    Beekeepers_District_Name               AS district,
    COUNT(*)                               AS total,
    SUM(CASE WHEN Beekeeper_Gender = 'Male'   THEN 1 ELSE 0 END) AS male,
    SUM(CASE WHEN Beekeeper_Gender = 'Female' THEN 1 ELSE 0 END) AS female,
    SUM(CASE WHEN You_are_registered_with_NBB_YES_NO = 'Yes' THEN 1 ELSE 0 END) AS nbb,
    ROUND(AVG(CAST(NULLIF(Beekeeper_Age,'') AS UNSIGNED)),1) AS avg_age
  FROM bk_data_part1
  WHERE Beekeepers_District_Name IS NOT NULL AND Beekeepers_District_Name != ''
  GROUP BY Beekeepers_District_Name
  ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$maxVal         = (int)$rows[0]['total'];
$totalDistricts = count($rows);
$totalBK        = array_sum(array_column($rows, 'total'));
$totalMale      = array_sum(array_column($rows, 'male'));
$totalFemale    = array_sum(array_column($rows, 'female'));
$totalNBB       = array_sum(array_column($rows, 'nbb'));
?>

<!-- ══ DISTRICT ══ -->
<div id="p-district" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#1976D2">District-wise Analysis</span>
      <div class="sec-title">Beekeeper Distribution by District</div>
    </div>
    <div class="dist-kpi-strip">
      <div class="dist-kpi"><span><?= $totalDistricts ?></span>Districts</div>
      <div class="dist-kpi"><span><?= number_format($totalBK) ?></span>Beekeepers</div>
      <div class="dist-kpi"><span><?= round($totalFemale/$totalBK*100) ?>%</span>Women</div>
      <div class="dist-kpi"><span><?= round($totalNBB/$totalBK*100) ?>%</span>NBB Reg.</div>
    </div>
  </div>

  <!-- District table -->
  <div class="dist-card">

    <div class="dist-table-wrap">
      <table class="dist-tbl" id="distTable">
        <thead>
          <tr>
            <th class="sortable" data-col="0" data-type="num"># <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="1" data-type="str">District <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="2" data-type="num">Total <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="3" data-type="num">Share % <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="4" data-type="num">Male <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="5" data-type="num">Female <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="6" data-type="num">NBB Reg. <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="7" data-type="num">NBB % <span class="sort-icon">↕</span></th>
            <th class="sortable" data-col="8" data-type="num">Avg Age <span class="sort-icon">↕</span></th>
            <th>Distribution</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r):
          $share  = round($r['total']/$totalBK*100, 1);
          $nbbPct = $r['total'] > 0 ? round($r['nbb']/$r['total']*100) : 0;
        ?>
          <tr>
            <td class="dist-rank"><?= $i+1 ?></td>
            <td class="dist-name"><?= htmlspecialchars($r['district']) ?></td>
            <td class="dist-num"><?= number_format($r['total']) ?></td>
            <td class="dist-num"><?= $share ?>%</td>
            <td class="dist-num"><?= number_format($r['male']) ?></td>
            <td class="dist-num"><?= number_format($r['female']) ?></td>
            <td class="dist-num"><?= number_format($r['nbb']) ?></td>
            <td>
              <div class="nbb-bar-bg">
                <div class="nbb-bar-fill" style="width:<?= $nbbPct ?>%"></div>
                <span><?= $nbbPct ?>%</span>
              </div>
            </td>
            <td class="dist-num"><?= $r['avg_age'] ?? '—' ?></td>
            <td>
              <div class="dist-mini-bar">
                <div class="dmb-male"   style="width:<?= $r['total']>0?round($r['male']/$r['total']*100):0 ?>%"></div>
                <div class="dmb-female" style="width:<?= $r['total']>0?round($r['female']/$r['total']*100):0 ?>%"></div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- end #p-district -->

<script>
/* ── Sort ── */
(function(){
  let lastCol = -1, asc = true;

  document.querySelectorAll('#distTable thead th.sortable').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', function(){
      const col  = +this.dataset.col;
      const type = this.dataset.type;
      asc = (lastCol === col) ? !asc : true;
      lastCol = col;

      /* update sort icons */
      document.querySelectorAll('#distTable thead th .sort-icon').forEach(s => s.textContent = '↕');
      this.querySelector('.sort-icon').textContent = asc ? '↑' : '↓';
      this.closest('thead').querySelectorAll('th').forEach(t => t.classList.remove('sort-asc','sort-desc'));
      this.classList.add(asc ? 'sort-asc' : 'sort-desc');

      const tbody = document.querySelector('#distTable tbody');
      const rows  = Array.from(tbody.querySelectorAll('tr'));

      rows.sort((a, b) => {
        let av = a.cells[col]?.textContent.trim() ?? '';
        let bv = b.cells[col]?.textContent.trim() ?? '';
        /* strip non-numeric chars for number cols */
        if(type === 'num'){
          av = parseFloat(av.replace(/[^0-9.]/g,'')) || 0;
          bv = parseFloat(bv.replace(/[^0-9.]/g,'')) || 0;
          return asc ? av - bv : bv - av;
        }
        return asc ? av.localeCompare(bv) : bv.localeCompare(av);
      });

      rows.forEach(r => tbody.appendChild(r));

      /* re-number rank column */
      tbody.querySelectorAll('tr').forEach((r,i) => {
        if(r.style.display !== 'none') r.cells[0].textContent = i + 1;
      });
    });
  });
})();
</script>
