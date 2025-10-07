<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (isset($_POST['create'])){
    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    mysqli_query($conn, "INSERT INTO customers (name, phone) VALUES ('$name','$phone')");
  }
  if (isset($_POST['update'])){
    $id=(int)$_POST['id'];
    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    mysqli_query($conn, "UPDATE customers SET name='$name', phone='$phone' WHERE id=$id");
  }
}
if (isset($_GET['delete'])){
  $id=(int)$_GET['delete'];
  mysqli_query($conn, "DELETE FROM customers WHERE id=$id");
  header("Location: customer.php"); exit;
}

$items = mysqli_query($conn,"SELECT * FROM customers ORDER BY id DESC");
include 'includes/header.php';
?>
<h2>Customer</h2>
<div class="row">
  <div>
    <div class="card">
      <h3>Tambah Customer</h3>
      <form method="post" class="row">
        <div><div class="label">Nama</div><input class="input" name="name" required></div>
        <div><div class="label">No HP</div><input class="input" name="phone"></div>
        <div><button class="btn" name="create">Simpan</button></div>
      </form>
    </div>
  </div>
  <div>
    <div class="card">
      <h3>Daftar Customer</h3>
      <table class="table">
        <tr><th>ID</th><th>Nama</th><th>HP</th><th>Aksi</th></tr>
        <?php while($c=mysqli_fetch_assoc($items)): ?>
          <tr>
            <td><?php echo $c['id']; ?></td>
            <td><?php echo htmlspecialchars($c['name']); ?></td>
            <td><?php echo htmlspecialchars($c['phone']); ?></td>
            <td>
              <form method="post" class="flex">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <input class="input" style="width:200px" name="name" value="<?php echo htmlspecialchars($c['name']); ?>">
                <input class="input" style="width:160px" name="phone" value="<?php echo htmlspecialchars($c['phone']); ?>">
                <button class="btn btn-outline" name="update">Update</button>
                <a class="btn btn-danger" href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Hapus customer?')">Hapus</a>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
