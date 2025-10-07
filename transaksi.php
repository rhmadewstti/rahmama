<?php
require 'includes/db.php';
require 'includes/auth.php';

// init cart in session
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// Add / update / remove cart
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['product_id'])){
  $action = isset($_POST['action']) ? $_POST['action'] : 'add';
  $pid = (int)$_POST['product_id'];
  $qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;

  $q = mysqli_query($conn, "SELECT id,name,price,stock FROM products WHERE id=$pid");
  if ($row = mysqli_fetch_assoc($q)){
    if (!isset($_SESSION['cart'][$pid])){
      $_SESSION['cart'][$pid] = ['name'=>$row['name'],'price'=>$row['price'],'qty'=>0];
    }
    if ($action==='remove'){
      unset($_SESSION['cart'][$pid]);
    } elseif ($action==='update'){
      $_SESSION['cart'][$pid]['qty'] = max(1,$qty);
    } else {
      $_SESSION['cart'][$pid]['qty'] += $qty;
    }
  }
  header("Location: transaksi.php");
  exit;
}

// Submit order
$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['checkout'])){
  $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

  // quick add customer (opsional)
  if (!$customer_id && !empty($_POST['new_customer_name'])){
    $n = mysqli_real_escape_string($conn, $_POST['new_customer_name']);
    $hp = mysqli_real_escape_string($conn, isset($_POST['new_customer_phone']) ? $_POST['new_customer_phone'] : '');
    mysqli_query($conn, "INSERT INTO customers(name, phone) VALUES ('$n','$hp')");
    $customer_id = mysqli_insert_id($conn);
  }
  $customer_id = $customer_id ?: 'NULL';

  $method = $_POST['payment_method'];
  $subtotal = 0;
  foreach($_SESSION['cart'] as $it){ $subtotal += $it['price']*$it['qty']; }
  $total = $subtotal;

  if ($subtotal <= 0){ 
    $msg = 'Keranjang kosong.'; 
  } else {
    mysqli_begin_transaction($conn);
    try {
      $cashier_id = (int)$_SESSION['user_id'];
      $pm = mysqli_real_escape_string($conn,$method);

      $ins = "INSERT INTO orders (order_code, customer_id, cashier_id, subtotal, discount, total, payment_method)
              VALUES (CONCAT('POS-', DATE_FORMAT(NOW(),'%Y%m%d'), '-', LPAD(FLOOR(RAND()*9999),4,'0')),
                      $customer_id, $cashier_id, $subtotal, 0, $total, '$pm')";
      mysqli_query($conn, $ins);
      $order_id = mysqli_insert_id($conn);

      foreach($_SESSION['cart'] as $pid => $it){
        $pid = (int)$pid; $qBuy = (int)$it['qty']; $price=(int)$it['price']; $line = $qBuy*$price;
        mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, qty, price, line_total) VALUES ($order_id,$pid,$qBuy,$price,$line)");
        mysqli_query($conn, "UPDATE products SET stock = stock - $qBuy WHERE id=$pid AND stock >= $qBuy");
      }
      mysqli_commit($conn);

      // Load order data for receipt
      $order = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM orders WHERE id=$order_id"));
      $items = mysqli_query($conn,"SELECT oi.*,p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE order_id=$order_id");
      $_SESSION['cart'] = []; // clear cart

      // Render receipt (untuk modal)
      ob_start();
      echo '<div class="card" id="receipt-content"><h2>Transaksi Berhasil</h2>';
      echo '<div class="badge">Kode: '.$order['order_code'].'</div><br>';
      echo '<table class="table"><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr>';
      $g=0; while($it=mysqli_fetch_assoc($items)){ $st=$it['qty']*$it['price']; $g+=$st;
        echo '<tr><td>'.$it['name'].'</td><td>'.$it['qty'].'</td><td>Rp'.number_format($it['price'],0,',','.').'</td><td>Rp'.number_format($st,0,',','.').'</td></tr>';
      }
      echo '<tr><th colspan=3 style="text-align:right">Total</th><th>Rp'.number_format($g,0,',','.').'</th></tr></table>';

      $set = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM settings ORDER BY id DESC LIMIT 1"));
      if ($order['payment_method']==='transfer'){
        $wa = ($set && !empty($set['whatsapp_phone'])) ? $set['whatsapp_phone'] : '';
        $txt = urlencode('Halo Admin, saya ingin konfirmasi transfer untuk ORDER '.$order['order_code'].' total Rp'.number_format($g,0,',','.'));
        $link = $wa ? "https://wa.me/{$wa}?text={$txt}" : "#";
        echo '<div class="notice">Silakan konfirmasi via WhatsApp: <a class="btn" href="'.$link.'" target="_blank">Chat Admin</a></div>';
      } elseif ($order['payment_method']==='qris'){
        if ($set && !empty($set['qris_image_path'])) {
          echo '<div class="card" style="margin-top:10px"><strong>QRIS Pembayaran</strong><br><img src="'.$set['qris_image_path'].'" style="max-width:240px;border:1px solid #eee;border-radius:10px"></div>';
        } else {
          echo '<div class="notice">QRIS belum diatur di menu Pengaturan.</div>';
        }
      }

      echo '<div class="flex" style="margin-top:12px"><a class="btn" href="transaksi.php">Transaksi Baru</a> <a class="btn btn-outline" href="laporan.php">Lihat Laporan</a></div>';
      $html = ob_get_clean();
      $_SESSION['last_receipt'] = $html;

      header('Location: transaksi.php?done=1');
      exit;
    } catch (Exception $e){
      mysqli_rollback($conn);
      $msg = 'Gagal menyimpan transaksi.';
    }
  }
}

// load data
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC LIMIT 100"); // tampilkan semua; tombol akan disable jika stok 0
$customers = mysqli_query($conn, "SELECT * FROM customers ORDER BY name ASC");
$settings = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM settings ORDER BY id DESC LIMIT 1"));

include 'includes/header.php';
?>
<h2>Transaksi</h2>
<?php if(!empty($msg)): ?><div class="notice"><?php echo $msg; ?></div><?php endif; ?>

<div class="row">
  <div>
    <div class="card">

      <!-- tools: cari & dark mode -->
      <div class="header-tools" style="margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
        <input type="text" id="product-search" placeholder="Cari produk..." onkeyup="filterProducts()" style="flex:1;padding:0.5rem;margin-right:1rem;">
        <button onclick="toggleDarkMode()" class="btn">🌙/☀️</button>
      </div>

      <!-- hidden forms sekali saja -->
      <form id="add-cart-form" method="post" style="display:none">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id">
        <input type="hidden" name="qty">
      </form>
      <form id="update-cart-form" method="post" style="display:none">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="product_id">
        <input type="hidden" name="qty">
      </form>
      <form id="remove-cart-form" method="post" style="display:none">
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="product_id">
      </form>

      <div class="grid">
  <?php while($p = mysqli_fetch_assoc($products)): ?>
    <?php
      if ($p['stock'] <= 10) {
        $cls = 'card danger';
      } elseif ($p['stock'] <= 15) {
        $cls = 'card warn';
      } else {
        $cls = 'card';
      }
    ?>
    <div class="<?php echo $cls; ?>" style="padding:12px">
      <div style="font-weight:700"><?php echo htmlspecialchars($p['name']); ?></div>
      <div>Rp<?php echo number_format($p['price'],0,',','.'); ?></div>
      <div class="flex">
        <span class="badge">Stok: <?php echo (int)$p['stock']; ?></span>
        <span class="right"></span>
        <?php if ((int)$p['stock'] > 0): ?>
          <button class="btn" onclick="addToCart(<?php echo (int)$p['id']; ?>)">+ Tambah</button>
        <?php else: ?>
          <button class="btn" disabled>Habis</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
</div>
    </div>
  </div>

  <div>
    <div class="card">
      <h3>Keranjang</h3>
      <table class="table">
        <tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Sub</th><th></th></tr>
        <?php $subtotal=0; if (empty($_SESSION['cart'])): ?>
          <tr><td colspan="5" class="empty-cart">Keranjang masih kosong</td></tr>
        <?php endif; foreach($_SESSION['cart'] as $pid=>$it): $st=$it['price']*$it['qty']; $subtotal+=$st; ?>
          <tr>
            <td><?php echo htmlspecialchars($it['name']); ?></td>
            <td><input type="number" min="1" value="<?php echo (int)$it['qty']; ?>" style="width:70px" onchange="updateQty(<?php echo (int)$pid; ?>, this.value)"></td>
            <td>Rp<?php echo number_format($it['price'],0,',','.'); ?></td>
            <td>Rp<?php echo number_format($st,0,',','.'); ?></td>
            <td><button type="button" class="btn btn-outline" onclick="removeItem(<?php echo (int)$pid; ?>)">✕</button></td>
          </tr>
        <?php endforeach; ?>
        <tr><th colspan="3" style="text-align:right">Total</th><th>Rp<?php echo number_format($subtotal,0,',','.'); ?></th><th></th></tr>
      </table>

      <form method="post" class="row">
        <div>
          <div class="label">Customer (opsional)</div>
          <select name="customer_id">
            <option value="">-- pilih --</option>
            <?php mysqli_data_seek($customers, 0); while($c=mysqli_fetch_assoc($customers)): ?>
              <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endwhile; ?>
          </select>

          <div style="margin-top:8px"><label><input type="checkbox" id="quick-cust-toggle"> Tambah customer cepat</label></div>
          <div id="quick-cust" style="display:none;margin-top:8px" class="row">
            <input type="text" name="new_customer_name" placeholder="Nama baru">
            <input type="text" name="new_customer_phone" placeholder="No. HP (opsional)">
          </div>
        </div>

        <div>
          <div class="label">Metode Pembayaran</div>
          <select name="payment_method" required>
            <option value="tunai">Tunai</option>
            <option value="transfer">Transfer (WA Admin)</option>
            <option value="qris">QRIS</option>
          </select>
        </div>

        <div>
          <button class="btn" name="checkout" value="1">Selesaikan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
