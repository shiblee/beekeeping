<?php
require_once __DIR__ . '/../../config/db.php';

$rows = $pdo->query("
  SELECT
    Beekeepers_District_Name               AS district,
    COUNT(*)                               AS total,
    SUM(CASE WHEN Beekeeper_Gender = 'Female' THEN 1 ELSE 0 END) AS female,
    SUM(CASE WHEN You_are_registered_with_NBB_YES_NO = 'Yes' THEN 1 ELSE 0 END) AS nbb,
    ROUND(AVG(CAST(NULLIF(Beekeeper_Age,'') AS UNSIGNED)),1) AS avg_age
  FROM bk_data_part1
  WHERE Beekeepers_District_Name IS NOT NULL AND Beekeepers_District_Name != ''
  GROUP BY Beekeepers_District_Name
  ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Summary indicators
$summary = $pdo->query("
  SELECT
    COUNT(*) AS total,
    ROUND(AVG(CAST(NULLIF(Beekeeper_Age,'') AS UNSIGNED)),1) AS avg_age,
    SUM(CASE WHEN Beekeeper_Gender='Female' THEN 1 ELSE 0 END) AS female,
    SUM(CASE WHEN You_are_registered_with_NBB_YES_NO='Yes' THEN 1 ELSE 0 END) AS nbb,
    COUNT(DISTINCT Beekeepers_District_Name) AS districts,
    COUNT(DISTINCT Beekeepers_Block_Name) AS blocks
  FROM bk_data_part1
")->fetch(PDO::FETCH_ASSOC);

// Top 5 districts by count
$top5 = $pdo->query("
  SELECT Beekeepers_District_Name AS district,
    COUNT(*) AS total,
    SUM(CASE WHEN Beekeeper_Gender='Female' THEN 1 ELSE 0 END) AS female,
    SUM(CASE WHEN You_are_registered_with_NBB_YES_NO='Yes' THEN 1 ELSE 0 END) AS nbb,
    ROUND(AVG(CAST(NULLIF(Beekeeper_Age,'') AS UNSIGNED)),1) AS avg_age
  FROM bk_data_part1
  WHERE Beekeepers_District_Name != ''
  GROUP BY district ORDER BY total DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Top 5 districts by NBB registration %
$topNBB = $pdo->query("
  SELECT Beekeepers_District_Name AS district,
    COUNT(*) AS total,
    SUM(CASE WHEN You_are_registered_with_NBB_YES_NO='Yes' THEN 1 ELSE 0 END) AS nbb,
    ROUND(SUM(CASE WHEN You_are_registered_with_NBB_YES_NO='Yes' THEN 1 ELSE 0 END)/COUNT(*)*100) AS pct,
    ROUND(AVG(CAST(NULLIF(Beekeeper_Age,'') AS UNSIGNED)),1) AS avg_age
  FROM bk_data_part1
  WHERE Beekeepers_District_Name != ''
  GROUP BY district HAVING COUNT(*)>=10
  ORDER BY pct DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$top5Max = (int)$top5[0]['total'];

// Centroids
$centroids = [
  'Agra'=>[27.18,78.01],'Aligarh'=>[27.88,78.07],'Ambedkar Nagar'=>[26.47,82.68],
  'Amethi'=>[26.15,81.92],'Amroha'=>[28.90,78.47],'Auraiya'=>[26.46,79.51],
  'Ayodhya'=>[26.77,82.13],'Azamgarh'=>[26.07,83.18],'Badaun'=>[28.04,79.12],
  'Bagpat'=>[28.94,77.22],'Bahraich'=>[27.57,81.59],'Ballia'=>[25.76,84.15],
  'Balrampur'=>[27.42,82.18],'Barabanki'=>[26.96,81.19],'Bareilly'=>[28.35,79.43],
  'Basti'=>[26.80,82.73],'Bhadohi'=>[25.39,82.57],'Bijnor'=>[29.37,78.14],
  'Bulandshahr'=>[28.41,77.85],'Chandauli'=>[25.27,83.27],'Chitrakoot'=>[25.20,80.90],
  'Deoria'=>[26.50,83.78],'Etah'=>[27.55,78.67],'Etawah'=>[26.78,79.02],
  'Farrukhabad'=>[27.40,79.58],'Fatehpur'=>[25.93,80.81],'Firozabad'=>[27.15,78.40],
  'Gautam Buddha Nagar'=>[28.50,77.47],'Ghaziabad'=>[28.67,77.43],'Ghazipur'=>[25.57,83.57],
  'Gonda'=>[27.13,81.96],'Gorkakhpur'=>[26.76,83.37],'Gorakhpur'=>[26.76,83.37],
  'Hamirpur'=>[25.95,80.15],'Hardoi'=>[27.39,80.13],'Hapur'=>[28.72,77.78],
  'Hathras'=>[27.60,78.06],'Jaunpur'=>[25.75,82.68],'Kannauj'=>[27.06,79.92],
  'Kanpur Dehat'=>[26.40,80.14],'Kanpur Nagar'=>[26.46,80.35],'Kasganj'=>[27.81,78.64],
  'Kaushambi'=>[25.53,81.38],'Kheri'=>[27.94,80.78],'Kushinagar'=>[26.74,83.89],
  'Lucknow'=>[26.85,80.94],'Mainpuri'=>[27.23,79.02],'Mathura'=>[27.49,77.67],
  'Mau'=>[25.94,83.56],'Meerut'=>[28.98,77.71],'Mirzapur'=>[25.14,82.58],
  'Moradabad'=>[28.84,78.77],'Muzaffarnagar'=>[29.47,77.70],'Pilibhit'=>[28.63,79.80],
  'Prayagraj'=>[25.43,81.85],'Rae Bareli'=>[26.22,81.24],'Rampur'=>[28.81,79.01],
  'Saharanpur'=>[29.96,77.55],'Sambhal'=>[28.59,78.57],'Sant Kabir Nagar'=>[26.78,83.02],
  'Shahjahanpur'=>[27.88,79.91],'Shamli'=>[29.44,77.31],'Shravasti'=>[27.59,82.02],
  'Siddharthnagar'=>[27.29,83.09],'Sitapur'=>[27.57,80.68],'Sonbhadra'=>[24.70,83.00],
  'Sultanpur'=>[26.27,82.07],'Unnao'=>[26.54,80.49],'Varanasi'=>[25.32,83.00],
  'Partapgarh'=>[25.91,81.99],
];

$points = [];
foreach ($rows as $r) {
  $d = $r['district'];
  if (!isset($centroids[$d])) continue;
  $t = (int)$r['total'];
  $points[] = [
    'district' => $d, 'lat' => $centroids[$d][0], 'lng' => $centroids[$d][1],
    'total'    => $t,
    'female'   => (int)$r['female'],
    'nbb'      => (int)$r['nbb'],
    'nbb_pct'  => $t > 0 ? round($r['nbb']/$t*100) : 0,
    'f_pct'    => $t > 0 ? round($r['female']/$t*100) : 0,
    'avg_age'  => $r['avg_age'],
  ];
}

$maxTotal   = max(array_column($points, 'total'));
$pointsJson = json_encode($points);
$nbbPct     = round($summary['nbb'] / $summary['total'] * 100);
$femalePct  = round($summary['female'] / $summary['total'] * 100);
?>

<!-- ══ MAP ══ -->
<div id="p-map" class="panel">

  <div class="sec-hdr">
    <div class="sec-hdr-left">
      <span class="sec-label" style="--sl:#43A047">Geographic Distribution</span>
      <div class="sec-title">District-wise Beekeeper Map</div>
    </div>
    <div class="map-legend">
      <span class="ml-item"><span class="ml-dot" style="background:#B22222"></span>≥ 150</span>
      <span class="ml-item"><span class="ml-dot" style="background:#F2A900"></span>≥ 100</span>
      <span class="ml-item"><span class="ml-dot" style="background:#F6C453"></span>≥ 50</span>
      <span class="ml-item"><span class="ml-dot" style="background:#81C784"></span>≥ 20</span>
      <span class="ml-item"><span class="ml-dot" style="background:#90CAF9"></span>&lt; 20</span>
      <span class="ml-sep"></span>
      <span class="ml-note">Bubble size ∝ count</span>
    </div>
  </div>

  <!-- Two-column layout -->
  <div class="map-layout">

    <!-- LEFT: map -->
    <div class="map-col-map">
      <div id="up-map" class="up-map-wrap"></div>
    </div>

    <!-- RIGHT: indicators -->
    <div class="map-col-sidebar">

      <?php
      $medals = ['🥇','🥈','🥉','4','5'];
      $grandTotal = array_sum(array_column($top5, 'total'));
      ?>

      <!-- Top 5 districts -->
      <div class="ind-block">
        <div class="ind-block-title">Top 5 Districts — by Beekeeper Count</div>
        <div class="ind-rich-list">
          <?php foreach($top5 as $i => $r):
            $w       = round($r['total'] / $top5Max * 100);
            $share   = round($r['total'] / $summary['total'] * 100, 1);
            $nbbPct2 = round($r['nbb']  / $r['total'] * 100);
            $fPct2   = round($r['female'] / $r['total'] * 100);
          ?>
          <div class="ind-rich-row">
            <div class="ind-rich-top">
              <span class="ind-medal"><?= $i < 3 ? $medals[$i] : $i+1 ?></span>
              <span class="ind-rich-name"><?= htmlspecialchars($r['district']) ?></span>
              <span class="ind-rich-count"><?= number_format($r['total']) ?></span>
              <span class="ind-rich-share"><?= $share ?>% of total</span>
            </div>
            <div class="ind-rich-bar-wrap">
              <div class="ind-rich-bar" style="width:<?= $w ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Top NBB registration -->
      <div class="ind-block">
        <div class="ind-block-title">Highest NBB Registration Rate</div>
        <div class="ind-rich-list">
          <?php foreach($topNBB as $i => $r): ?>
          <div class="ind-rich-row">
            <div class="ind-rich-top">
              <span class="ind-medal"><?= $i < 3 ? $medals[$i] : $i+1 ?></span>
              <span class="ind-rich-name"><?= htmlspecialchars($r['district']) ?></span>
              <span class="ind-rich-count" style="color:#43A047"><?= $r['pct'] ?>%</span>
              <span class="ind-rich-share"><?= $r['nbb'] ?> / <?= $r['total'] ?> registered</span>
            </div>
            <div class="ind-rich-bar-wrap">
              <div class="ind-rich-bar" style="width:<?= $r['pct'] ?>%;background:linear-gradient(90deg,#81C784,#43A047)"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>


    </div><!-- end .map-col-sidebar -->
  </div><!-- end .map-layout -->

</div>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(function () {
  const points = <?= $pointsJson ?>;
  const maxVal = <?= $maxTotal ?>;

  const map = L.map('up-map', {
    center: [27.0, 80.5], zoom: 7,
    zoomControl: true, scrollWheelZoom: false,
  });

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap contributors © CARTO',
    subdomains: 'abcd', maxZoom: 19
  }).addTo(map);

  function getColor(n) {
    if (n >= 150) return '#B22222';
    if (n >= 100) return '#F2A900';
    if (n >= 50)  return '#F6C453';
    if (n >= 20)  return '#81C784';
    return '#90CAF9';
  }
  function getRadius(n) {
    return 8 + (Math.sqrt(n) / Math.sqrt(maxVal)) * 36;
  }

  points.forEach(p => {
    const circle = L.circleMarker([p.lat, p.lng], {
      radius: getRadius(p.total),
      fillColor: getColor(p.total), color: '#fff',
      weight: 1.5, fillOpacity: 0.82,
    }).addTo(map);

    const icon = L.divIcon({
      className: 'dist-label',
      html: `<span class="dl-name">${p.district}</span><span class="dl-num">${p.total}</span>`,
      iconSize: [120, 32], iconAnchor: [60, -getRadius(p.total) - 2],
    });
    L.marker([p.lat, p.lng], { icon, interactive: false }).addTo(map);

    circle.bindTooltip(
      `<strong>${p.district}</strong><br>Beekeepers: ${p.total}<br>Female: ${p.female} (${p.f_pct}%)<br>NBB Reg.: ${p.nbb} (${p.nbb_pct}%)<br>Avg Age: ${p.avg_age ?? '—'}`,
      { sticky: true, className: 'map-tooltip' }
    );

    circle.on('mouseover', function () { this.setStyle({ weight: 2.5, fillOpacity: 1 }); });
    circle.on('mouseout',  function () { this.setStyle({ weight: 1.5, fillOpacity: 0.82 }); });
  });

  map.on('click', () => {
    if (map.scrollWheelZoom.enabled()) map.scrollWheelZoom.disable();
    else map.scrollWheelZoom.enable();
  });
})();
</script>
