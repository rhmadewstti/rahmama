<?php
require 'includes/db.php';
require 'includes/auth.php';

$info='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $wa = mysqli_real_escape_string($conn, $_POST['whatsapp_phone']);
  $qris_path = null;
  if (!empty($_FILES['qris_image']['name'])){
    $name = 'uploads/qris/'.time().'_'.basename($_FILES['qris_image']['name']);
    if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $name)){
      $qris_path = $name;
    }
  }
  if ($qris_path){
    mysqli_query($conn,"INSERT INTO settings (whatsapp_phone,qris_label,qris_image_path) VALUES ('$wa','QRIS Toko','$qris_path')");
  } else {
    mysqli_query($conn,"INSERT INTO settings (whatsapp_phone,qris_label) VALUES ('$wa','QRIS Toko')");
  }
  $info='Pengaturan disimpan.';
}

$last = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM settings ORDER BY id DESC LIMIT 1"));

include 'includes/header.php';
?>
<h2>Pengaturan</h2>
<?php if($info): ?><div class="notice"><?php echo $info; ?></div><?php endif; ?>
<div class="row">
  <div>
    <div class="card">
      <h3>WhatsApp & QRIS</h3>
      <form method="post" enctype="multipart/form-data" class="row">
        <div>
          <div class="label">Nomor WhatsApp Admin (contoh: 62812xxxx)</div>
          <input class="input" name="whatsapp_phone" value="<?php echo $last? htmlspecialchars($last['whatsapp_phone']):''; ?>">
        </div>
        <div>
          <div class="label">Upload Gambar QRIS (PNG/JPG)</div>
          <input class="input" type="file" name="qris_image" accept="image/*">
        </div>
        <div>
          <button class="btn">Simpan</button>
        </div>
      </form>
    </div>
  </div>
  <div>
    <div class="card">
      <h3>Backup Database Manual</h3>
      <p>Klik untuk mengekspor tabel menjadi SQL dump sederhana.</p>
      <a class="btn" href="utils_backup.php">Export Database</a>
      <?php if ($last && $last['qris_image_path']): ?>
        <div style="margin-top:12px">
          <strong>QRIS Saat Ini:</strong><br>
          <img src="<?php echo $last['qris_image_path']; ?>" style="max-width:240px;border:1px solid #eee;border-radius:10px">
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
