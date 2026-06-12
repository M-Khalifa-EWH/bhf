<?php
include $_SERVER['DOCUMENT_ROOT'].'/hotel/config/config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle ?? "Hotel Suchen Tool"; ?></title>

  <!-- jQuery أولاً وقبل كل شيء -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/custom.css">
</head>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/navbar.php'; ?>