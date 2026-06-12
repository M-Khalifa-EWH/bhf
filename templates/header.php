<?php
session_start();
$currentPage = basename($_SERVER['REQUEST_URI']);
$username = $_SESSION['user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 50px; }

        .navbar-custom { background-color: #002B5B; }
        .navbar-custom .nav-link { font-size: 1rem; font-weight: 400; color: #ffffff !important; margin: 0 10px; }
        .navbar-custom .nav-link:hover { text-decoration: underline; }
        .navbar-brand { font-weight: bold; font-size: 1.1rem; }
        .navbar-custom .nav-link.active { color: #42f5b6 !important; font-weight: bold; }

      
.navbar-toggler {
    width: 60px;
    height: 60px;
    border: none; 
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='green' stroke-width='3' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
    background-size: 30px 30px;
}

        @media (max-width: 576px) {
            .navbar-nav .nav-link {
                font-size: 1.4rem;  
                padding: 1rem 1.2rem; 
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-custom shadow-sm fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="index.php">EWH Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'overview') ? 'active' : '' ?>" href="/overview/">OVERVIEW</a>
                </li>

                <?php if ($username != 'Team'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'bas-M') ? 'active' : '' ?>" href="/bas-M/">BAs-M</a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'booking') ? 'active' : '' ?>" href="/booking/">BOOKING</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'flight') ? 'active' : '' ?>" href="/flight/">FLIGHT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'hotel') ? 'active' : '' ?>" href="/hotel/">HOTEL</a>
                </li>

                <?php if ($username != 'Team'): ?>
                <!-- <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'check24') ? 'active' : '' ?>" href="/check24/">Check24</a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'bas') ? 'active' : '' ?>" href="/bas/">BAs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'provider') ? 'active' : '' ?>" href="/provider/">Provider</a>
                </li>
                <?php endif; ?>

            </ul>

            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="navbar-text text-white me-3">User: <?= htmlspecialchars($username); ?></span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm" href="/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
