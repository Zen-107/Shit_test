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
      var USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
  </script>
  <script src="assets/js/product.js"></script>
</body>
</html>