<?php
require 'includes/db.php';
require 'includes/auth.php';

$info='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (isset($_POST['create'])){
    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $price = (int)$_POST['price'];
    $stock = (int)$_POST['stock'];
    mysqli_query($conn, "INSERT INTO products (name, price, stock) VALUES ('$name',$price,$stock)");
    $info='Produk ditambahkan.';
  }
  if (isset($_POST['update'])){
    $id=(int)$_POST['id'];
    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $price = (int)$_POST['price'];
    $stock = (int)$_POST['stock'];
    mysqli_query($conn, "UPDATE products SET name='$name', price=$price, stock=$stock WHERE id=$id");
    $info='Produk diupdate.';
  }
}
if (isset($_GET['delete'])){
  $id=(int)$_GET['delete'];
  mysqli_query($conn, "DELETE FROM products WHERE id=$id");
  header("Location: produk.php"); exit;
}

$items = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
include 'includes/header.php';
?>
<h2>Produk</h2>
<?php if($info): ?><div class="notice"><?php echo $info; ?></div><?php endif; ?>
<div class="row">
  <div>
    <div class="card">
      <h3>Tambah Produk</h3>
      <form method="post" class="row">
        <div><div class="label">Nama</div><input class="input" name="name" required></div>
        <div><div class="label">Harga (Rp)</div><input class="input" type="number" name="price" required></div>
        <div><div class="label">Stok</div><input class="input" type="number" name="stock" required></div>
        <div><button class="btn" name="create">Simpan</button></div>
      </form>
    </div>
  </div>
  <div>
    <div class="card">
      <h3>Daftar Produk</h3>
      <table class="table">
        <tr><th>ID</th><th>Nama</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
        <?php while($p=mysqli_fetch_assoc($items)): ?>
          <tr>
            <td><?php echo $p['id']; ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td>Rp<?php echo number_format($p['price'],0,',','.'); ?></td>
            <td><?php echo $p['stock']; ?></td>
            <td>
              <form method="post" class="flex">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <input class="input" style="width:180px" name="name" value="<?php echo htmlspecialchars($p['name']); ?>">
                <input class="input" style="width:120px" type="number" name="price" value="<?php echo $p['price']; ?>">
                <input class="input" style="width:90px" type="number" name="stock" value="<?php echo $p['stock']; ?>">
                <button class="btn btn-outline" name="update">Update</button>
                <a class="btn btn-danger" href="?delete=<?php echo $p['id']; ?>" onclick="return confirm('Hapus produk?')">Hapus</a>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
