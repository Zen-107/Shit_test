<?php
session_start();
$user_id = $_SESSION["user_id"] ?? null;
$user_name = $_SESSION["user_name"] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Gift Finder – Product</title>
  <link rel="stylesheet" href="assets/css/styles.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <main class="container section" id="product">
  </main>

  <script src="assets/js/app.js"></script>
  <script>
      const USER_ID = <?php echo $user_id ? json_encode($user_id) : 'null'; ?>;
      const USER_NAME = <?php echo $user_name ? json_encode($user_name) : 'null' ?>;
  </script>
  <script src="assets/js/product.js"></script>
</body>
</html>