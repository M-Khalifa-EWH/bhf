<?php
$currentPage = $_SERVER['REQUEST_URI'];
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm px-0">
  <div class="container">
    <a class="navbar-brand ms-0" href="<?php echo BASE_URL; ?>">Hotel Tool</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- Top10 Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo str_contains($currentPage,'/top/')?'active':''; ?>" href="#" role="button" data-bs-toggle="dropdown">Top10</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo str_contains($currentPage,'topHotel.php')?'active':''; ?>" href="<?php echo BASE_URL; ?>top/topHotel.php">Hotel</a></li>
            <li><a class="dropdown-item <?php echo str_contains($currentPage,'topFlug.php')?'active':''; ?>" href="<?php echo BASE_URL; ?>top/topFlug.php">Flug</a></li>
            <li><a class="dropdown-item <?php echo str_contains($currentPage,'topPlayer.php')?'active':''; ?>" href="<?php echo BASE_URL; ?>top/topPlayer.php">Player</a></li>
          </ul>
        </li>

        <!-- Booking -->
        <li class="nav-item">
          <a class="nav-link <?php echo str_contains($currentPage,'/booking/')?'active':''; ?>" href="<?php echo BASE_URL; ?>booking/hinflug.php">Booking</a>
        </li>

        <!-- Errors Dropdown -->
        <!-- <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php //echo str_contains($currentPage,'/errors/')?'active':''; ?>" href="#" role="button" data-bs-toggle="dropdown">Errors</a>
          <ul class="dropdown-menu">
            <!-- <li><a class="dropdown-item <?php //echo str_contains($currentPage,'errorcode.php')?'active':''; ?>" href="<?php //echo BASE_URL; ?>errors/errorcode.php">Errorcode</a></li> -->
            <!--<li><a class="dropdown-item <?php //echo str_contains($currentPage,'ss_err_1137.php')?'active':''; ?>" href="<?php //echo BASE_URL; ?>errors/ss_err_1137.php">SS:ERR:1137</a></li>
            <li><a class="dropdown-item <?php //echo str_contains($currentPage,'ss_err_0453.php')?'active':''; ?>" href="<?php //echo BASE_URL; ?>errors/ss_err_0453.php">SS:ERR:0453</a></li>
          </ul>
        </li> -->

        <!-- Price Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo str_contains($currentPage,'/price/')?'active':''; ?>" href="#" role="button" data-bs-toggle="dropdown">Price</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo str_contains($currentPage,'priceDiff.php')?'active':''; ?>" href="<?php echo BASE_URL; ?>price/priceDiff.php">Difference</a></li>
            <li><a class="dropdown-item <?php echo str_contains($currentPage,'priceCorr.php')?'active':''; ?>" href="<?php echo BASE_URL; ?>price/priceCorr.php">Correction</a></li>
          </ul>
        </li>
<li class="nav-item">
          <a class="nav-link <?php echo str_contains($currentPage,'/messages/')?'active':''; ?>" href="<?php echo BASE_URL; ?>messages/messages.php">Messages</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo str_contains($currentPage,'/providers/')?'active':''; ?>" href="<?php echo BASE_URL; ?>providers/provider.php">Provider</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo str_contains($currentPage,'/errorCode/')?'active':''; ?>" href="<?php echo BASE_URL; ?>errorCode/errorCode.php">ErrCode-Gestern</a>
        </li>

      </ul>
    </div>
  </div>
</nav>