<?php
require 'includes/db.php';
session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';
  $q = mysqli_query($conn, "SELECT * FROM users WHERE username='".mysqli_real_escape_string($conn,$username)."' LIMIT 1");
  if ($q && mysqli_num_rows($q)==1){
    $u = mysqli_fetch_assoc($q);
    if (password_verify($password, $u['password_hash'])){
      $_SESSION['user_id']=$u['id'];
      $_SESSION['name']=$u['name'];
      header("Location: transaksi.php");
      exit;
    } else { $error = 'Password salah'; }
  } else { $error = 'User tidak ditemukan'; }
}
include 'includes/header.php';
?>
<div class="card" style="max-width:420px;margin:80px auto;">
  <h2>Login Admin</h2>
  <?php if($error): ?><div class="notice"><?php echo $error; ?></div><?php endif; ?>
  <form method="post" class="row">
    <div>
      <div class="label">Username</div>
      <input class="input" type="text" name="username" required placeholder="Nama Admin">
    </div>
    <div>
      <div class="label">Password</div>
      <input class="input" type="password" name="password" required placeholder="••••••••">
    </div>
    <div>
      <button class="btn" type="submit">Masuk</button>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
