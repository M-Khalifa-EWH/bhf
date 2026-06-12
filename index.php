<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
include 'templates/header.php';
?>
<div class="container mt-4">
    <h1 align="center">EWH DASHBOARD - User:  <?= htmlspecialchars($_SESSION['user']); ?>!</h1>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
