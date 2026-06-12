<?php
// zero_activity_alert.php

if (!isset($connect)) {
    return; // حماية لو الملف انعمل include بالغلط بدون اتصال
}

/* ===============================
   ZERO ACTIVITY - LAST 10 MINUTES
================================ */
$sql = "
SELECT
    SUM(usr='1000') AS EV,
    SUM(usr='12000') AS C24BHUB,
    SUM(usr='12001') AS C24DIRECT,
    SUM(usr='12100') AS HC,
    SUM(usr='12300') AS INVIA,
    SUM(usr='10090') AS AMA
FROM bastix
WHERE datum >= NOW() - INTERVAL 10 MINUTE
AND startt IS NOT NULL
AND endd IS NOT NULL
AND success = 1
";

$zeroStatus = $connect->query($sql)->fetch(PDO::FETCH_ASSOC);

$users = [
    'EV'        => '1000 EV',
    'C24BHUB'   => '12000 C24 BHUB',
    'C24DIRECT' => '12001 C24 Direkt',
    'HC'        => '12100 HC',
    'INVIA'     => '12300 INVIA',
    'AMA'       => '10090 AMA'
];

$zeroAlerts = [];

foreach ($users as $key => $label) {
    if ((int)$zeroStatus[$key] === 0) {
        $zeroAlerts[] = $label;
    }
}
?>

<?php if (!empty($zeroAlerts)): ?>
<div class="alert alert-cyan alert-small-center mt-3">
    <?php foreach ($zeroAlerts as $u): ?>
        🔴 User <strong><?= $u ?></strong> has no activity in the last 10 minutes.<br>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-success mt-3">
    🟢 All users have activity in the last 10 minutes.
</div>
<?php endif; ?>