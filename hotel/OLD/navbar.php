<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Suchen Tool</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
        .header-buttons {
            margin-bottom: 20px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
            margin: 0 0.1em;
        }
        .navbar {
            
            background-color: #dce2e3;
        }
        .navbar-brand {
            color: #080808;
        }
        .navbar-nav .nav-link {
            color: #080808;
            margin-right: 15px;
            transition: color 0.3s;
        }
        .navbar-nav .nav-link:hover {
            color: #d1d1d1;
        }
        .navbar-nav .nav-link.active {
            color: red; /* Blue color for active link */
        }
        .navbar-toggler-icon {
            background-image: url('data:image/svg+xml;charset=utf8,<svg viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-width="2" d="M4 7h22M4 15h22M4 23h22"/></svg>');
        }
        .container {
            max-width: 1200px;
        }
        .btn-custom {
        background-color: #dce2e3;
        http://10.15.16.25/hotel/top/topProvider/top.php
        color: #000; /* Optional: Set the text color */
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <!-- <li class="nav-item">
                    <a class="nav-link" href="basPreis.php">BAs</a>
                </li> 
                <li class="nav-item">
                    <a class="nav-link" href="buchungen.php">BUCHUNG</a>
                </li>-->
                <li class="nav-item">
                    <a class="nav-link" href="top.php">TOP5</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="message.php">MESSAGE</a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" href="basplus.php">Not_Activated</a>
                </li> -->
                 <li class="nav-item">
                    <a class="nav-link" href="hinflug.php">HINFLUG</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="errorcode.php">ErCo1137</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ss_err_0453.php">ErCo0453</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="priceDiff.php">PriceDiff</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="priceCorr.php">PriceCorr</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="provider.php">Provider</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
    // Add 'active' class to the current link
    document.addEventListener('DOMContentLoaded', function() {
        var currentPath = window.location.pathname.split('/').pop();
        var navbarLinks = document.querySelectorAll('.navbar-nav .nav-link');

        navbarLinks.forEach(function(link) {
            var linkPath = link.getAttribute('href');
            if (currentPath === linkPath) {
                link.classList.add('active');
            }
        });
    });
</script>