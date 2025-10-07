<?php
require 'includes/db.php';
require 'includes/auth.php';

if (isset($_GET['export']) && in_array($_GET['export'],['harian','bulanan'])){
  $type = $_GET['export'];
  if ($type==='harian'){
    $date = date('Y-m-d');
    $rs = mysqli_query($conn,"SELECT p.name, SUM(oi.qty) as qty, SUM(oi.line_total) as omzet
      FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id
      WHERE DATE(o.created_at)=CURDATE() GROUP BY p.id ORDER BY omzet DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_harian_'+$date+'.csv');
  } else {
    $ym = date('Y-m');
    $rs = mysqli_query($conn,"SELECT p.name, SUM(oi.qty) as qty, SUM(oi.line_total) as omzet
      FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id
      WHERE DATE_FORMAT(o.created_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m') GROUP BY p.id ORDER BY omzet DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_bulanan_'+$ym+'.csv');
  }
  $out = fopen('php://output','w');
  fputcsv($out, ['Produk','Qty','Omzet']);
  while($r=mysqli_fetch_assoc($rs)){ fputcsv($out, [$r['name'],$r['qty'],$r['omzet']]); }
  fclose($out); exit;
}

include 'includes/header.php';
?>
<h2>Laporan</h2>
<div class="card">
  <p>Unduh laporan saat ini:</p>
  <a class="btn" href="?export=harian">Export CSV Harian (hari ini)</a>
  <a class="btn btn-outline" href="?export=bulanan">Export CSV Bulanan (bulan ini)</a>
</div>

<div class="card" style="margin-top:12px">
  <h3>Ringkasan Hari Ini</h3>
  <table class="table">
    <tr><th>Produk</th><th>Qty</th><th>Omzet</th></tr>
    <?php
      $rs = mysqli_query($conn,"SELECT p.name, SUM(oi.qty) as qty, SUM(oi.line_total) as omzet
        FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id
        WHERE DATE(o.created_at)=CURDATE() GROUP BY p.id ORDER BY omzet DESC");
      while($r=mysqli_fetch_assoc($rs)){
        echo '<tr><td>'.htmlspecialchars($r['name']).'</td><td>'.$r['qty'].'</td><td>Rp'.number_format($r['omzet'],0,',','.').'</td></tr>';
      }
    ?>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
