<?php
session_start();
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Login</title>
    <link href="dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
      body {
        background-color: #f8f9fa;
      }
      .form-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
      }
    </style>
</head>
  <body>
     <div class="container form-container">
      <div class="row w-100 justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
          
          <div class="card shadow">
            <div class="card-body p-4">
              <h3 class="card-title text-center mb-4">Silakan Login</h3>

              <?php
              if (isset($_SESSION['login_error'])) {
                  echo '<div class="alert alert-danger" role="alert">';
                  echo $_SESSION['login_error'];
                  echo '</div>';
                  unset($_SESSION['login_error']); // Hapus pesan error setelah ditampilkan
              }
              ?>           
              <form action="class/proseslogin.php" method="POST">
                <div class="mb-3">
                  <label for="text" class="form-label">Username</label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="masukkan username" required>
                </div>
                
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Ingat saya</label>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">Masuk</button>
                </div>
              </form>
            </div>
          </div>
          </div>
      </div>
    </div>
    <script src="dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </body>
</html>