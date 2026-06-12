<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


if (preg_match('#^(.*)/index\.php$#', $path, $m)) {
    $path = $m[1];
}


$path = rtrim($path, '/');
if ($path === '') $path = '/';


$allowedByUser = [
    'Team'  => ['/overview'],
    'Alena' => ['/overview', '/provider'],
];


$user = $_SESSION['user'];
if (isset($restrictTeam) && isset($allowedByUser[$user])) {
    $allowed = $allowedByUser[$user];

 
    if (!in_array($path, $allowed, true)) {
       
        $target = $allowed[0];

       
        if ($path !== $target) {
            header("Location: {$target}/");
            exit;
        }
    }
}
