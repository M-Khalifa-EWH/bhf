<?php
$restrictTeam = true;

include '../templates/auth.php';
include '../templates/header.php';
require_once __DIR__ . '/../config/mysql.php';
$connect = MySQLDatabase::connect();

/* ===============================
   HELPER FUNCTIONS
================================ */
function fetchAll($pdo, $sql){
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


function minuteKeys($minutes=60){
    $keys = [];
    $now = new DateTime();

    for($i=0; $i<$minutes; $i++){
        $t = (clone $now)->modify("-".(59-$i)." minutes");
        $keys[$t->format('H:i')] = 0;
    }

    return $keys;
}

/* ===============================
   LAST 60 MINUTES
================================ */
$minuteBase = minuteKeys();
$basmnbas = $basmnAMA = $basmnHC = $basmnC24Bhub = $basmnC24Direct = $basmnINVIA = $basmnEV = $avgmin = $minuteBase;

$sql = "
SELECT
    DATE_FORMAT(datum,'%H:%i') mn,
    COUNT(bas) ALLBAS,
    SUM(usr='10090') AMA,
    SUM(usr='12100') HC,
    SUM(usr='12000') C24BHUB,
    SUM(usr='12001') C24DIRECT,
    SUM(usr='12300') INVIA,
    SUM(usr='1000') EV,
    AVG(TIMESTAMPDIFF(SECOND,startt,endd)) avg
FROM bastix
WHERE datum>=NOW()-INTERVAL 60 MINUTE
AND startt IS NOT NULL
AND endd IS NOT NULL
AND success=1
GROUP BY mn
ORDER BY STR_TO_DATE(mn,'%H:%i')
";

$rows = fetchAll($connect,$sql);
foreach($rows as $r){
    $m = $r['mn'];
    $basmnbas[$m]=$r['ALLBAS'];
    $basmnAMA[$m]=$r['AMA'];
    $basmnHC[$m]=$r['HC'];
    $basmnC24Bhub[$m]=$r['C24BHUB'];
    $basmnC24Direct[$m]=$r['C24DIRECT'];
    $basmnINVIA[$m]=$r['INVIA'];
    $basmnEV[$m]=$r['EV'];
    $avgmin[$m]=round($r['avg'],1);
}

/* ===============================
   24H CHUNKS
================================ */
$chartData=[];
$now=new DateTime();
$start=(clone $now)->modify('-23 hours');
$chunk=345;

for($i=0;$i<4;$i++){
    $from=(clone $start)->modify("+".($i*$chunk)." minutes");
    $to=(clone $start)->modify("+".(($i+1)*$chunk)." minutes");

    $sql = "
    SELECT
        DATE_FORMAT(datum,'%Y-%m-%d %H:%i') mn,
        COUNT(bas) ALLBAS,
        SUM(usr='10090') AMA,
        SUM(usr='12100') HC,
        SUM(usr='12000') C24BHUB,
        SUM(usr='12001') C24DIRECT,
        SUM(usr='12300') INVIA,
        SUM(usr='1000') EV,
        AVG(TIMESTAMPDIFF(SECOND,startt,endd)) avg
    FROM bastix
    WHERE datum BETWEEN '".$from->format('Y-m-d H:i:s')."' AND '".$to->format('Y-m-d H:i:s')."'
    AND startt IS NOT NULL
    AND endd IS NOT NULL
    AND success=1
    GROUP BY mn
    ORDER BY mn
    ";

    $res=fetchAll($connect,$sql);
    $d=['count'=>[],'AMA'=>[],'HC'=>[],'C24BHUB'=>[],'C24DIRECT'=>[],'INVIA'=>[],'EV'=>[],'avg'=>[]];

    foreach($res as $r){
        $mn=$r['mn'];
        $d['count'][$mn]=$r['ALLBAS'];
        $d['AMA'][$mn]=$r['AMA'];
        $d['HC'][$mn]=$r['HC'];
        $d['C24BHUB'][$mn]=$r['C24BHUB'];
        $d['C24DIRECT'][$mn]=$r['C24DIRECT'];
        $d['INVIA'][$mn]=$r['INVIA'];
        $d['EV'][$mn]=$r['EV'];
        $d['avg'][$mn]=round($r['avg'],1);
    }
    $chartData["chunk_$i"]=$d;
}


$chartData=array_reverse($chartData);
$temp=array_values($chartData);
$chartData=['chunk_3'=>$temp[0],'chunk_1'=>$temp[1],'chunk_2'=>$temp[2],'chunk_0'=>$temp[3]];

?>

<style>
.chart-container-bas{
    width:100%;
    height:310px;
    margin-bottom:40px;
    background:#fff;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
    padding:15px;
}

/* ===============================
   USER BUTTONS (TOGGLE)
================================ */
#user-buttons{
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 15px;
}

#user-buttons button{
    margin:2px;
    padding:6px 16px;
    cursor:pointer;
    border-radius:50px;
    border:1px solid #ccc;
    transition: all 0.2s ease;
    background: #f2f2f2;   /* رمادي خفيف */
    font-size: 0.85rem;
}

#user-buttons button:hover{
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

#user-buttons button.active{
    background: linear-gradient(145deg, #ff4d4d, #c40000);
    color: white;
    border-color: #c40000;
    box-shadow: 0 4px 10px rgba(196, 0, 0, 0.35);
    transform: translateY(-1px);
}

/* ===============================
   INFO ALERT (CENTER SMALL)
================================ */
.alert-small-center{
    width: fit-content;
    margin: 15px auto;
    text-align: center;
    font-size: 0.9rem;
    padding: 6px 12px;
    border-radius: 50px;

    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-fill">
<div class="container mt-4">

<!-- ===============================
     USER TOGGLE BUTTONS
================================ -->
<div id="user-buttons">
<?php
$users = ['All','1000 EV','12000 C24 BHUB','12001 C24 Direkt','12100 HC','12300 INVIA','10090 AMA','Avg BA Sec'];
foreach($users as $u){
    echo "<button data-user='$u'>$u</button>";
}
?>
</div>
<?php include 'zero_activity_alert.php'; ?>
<!-- ===============================
     LAST 60 MINUTES CHART
================================ -->
<div class="chart-container-bas">
<canvas id="ChartMn"></canvas>
</div>

<script>
const ChartMn = new Chart(document.getElementById("ChartMn"),{
type:'line',
data:{
    labels:<?=json_encode(array_keys($minuteBase))?>,
    datasets:[
        {label:'All',data:<?=json_encode(array_values($basmnbas))?>,borderColor:'red',yAxisID:'A'},
        {label:'1000 EV',data:<?=json_encode(array_values($basmnEV))?>,borderColor:'#800080',yAxisID:'A'},
        {label:'12000 C24 BHUB',data:<?=json_encode(array_values($basmnC24Bhub))?>,borderColor:'#f5458e',yAxisID:'A'},
        {label:'12001 C24 Direkt',data:<?=json_encode(array_values($basmnC24Direct))?>,borderColor:'#005ea8',yAxisID:'A'},
        {label:'12100 HC',data:<?=json_encode(array_values($basmnHC))?>,borderColor:'#ebc934',yAxisID:'A'},
        {label:'12300 INVIA',data:<?=json_encode(array_values($basmnINVIA))?>,borderColor:'#47c6ed',yAxisID:'A'},
        {label:'10090 AMA',data:<?=json_encode(array_values($basmnAMA))?>,borderColor:'#cc9f81',yAxisID:'A'},
        {label:'Avg BA Sec',data:<?=json_encode(array_values($avgmin))?>,borderColor:'green',yAxisID:'B'}
    ]
},
  options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    elements: {
                            line: {
                                tension: 0.5,         
                                borderWidth: 0.9     
                            },
                            point: {
                                radius: 1,          
                                hoverRadius: 4
                            }
                        },
                    scales: {A:{type:'linear',position:'left'},B:{type:'linear',position:'right',grid:{drawOnChartArea:false}}}
}
});
</script>

<!-- ===============================
     4 CHUNK CHARTS
================================ -->
<?php foreach ($chartData as $id=>$data): ?>
<div class="chart-container-bas">
<canvas id="chart_<?=$id?>"></canvas>
</div>
<script>
window['<?=$id?>'] = new Chart(document.getElementById("chart_<?=$id?>"),{
type:'line',
data:{
    labels:<?=json_encode(array_keys($data['count']))?>,
    datasets:[
        {label:'All',data:<?=json_encode(array_values($data['count']))?>,borderColor:'red',yAxisID:'A'},
        {label:'1000 EV',data:<?=json_encode(array_values($data['EV']))?>,borderColor:'#800080',yAxisID:'A'},
        {label:'12000 C24 BHUB',data:<?=json_encode(array_values($data['C24BHUB']))?>,borderColor:'#f5458e',yAxisID:'A'},
        {label:'12001 C24 Direkt',data:<?=json_encode(array_values($data['C24DIRECT']))?>,borderColor:'#005ea8',yAxisID:'A'},
        {label:'12100 HC',data:<?=json_encode(array_values($data['HC']))?>,borderColor:'#ebc934',yAxisID:'A'},
        {label:'12300 INVIA',data:<?=json_encode(array_values($data['INVIA']))?>,borderColor:'#47c6ed',yAxisID:'A'},
        {label:'10090 AMA',data:<?=json_encode(array_values($data['AMA']))?>,borderColor:'#cc9f81',yAxisID:'A'},
        {label:'Avg BA Sec',data:<?=json_encode(array_values($data['avg']))?>,borderColor:'green',yAxisID:'B'}
    ]
},
  options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    elements: {
                            line: {
                                tension: 0.5,         
                                borderWidth: 0.9     
                            },
                            point: {
                                radius: 1,          
                                hoverRadius: 4
                            }
                        },
                    scales: {
    x:{ticks:{callback:function(v){let label=this.getLabelForValue(v); return label.includes(" ") ? label.split(" ")[1] : label; }}},
    A:{type:'linear',position:'left'},
    B:{type:'linear',position:'right',grid:{drawOnChartArea:false}}
}
}
});
</script>
<?php endforeach; ?>

<script>
// ===============================
// SHARED USER TOGGLE BUTTONS
// ===============================
const allCharts = [ChartMn, chunk_0, chunk_1, chunk_2, chunk_3];

document.querySelectorAll('#user-buttons button').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const label = btn.dataset.user;

        // toggle active class
        btn.classList.toggle('active');

        allCharts.forEach(chart=>{
            chart.data.datasets.forEach(ds=>{
                if(ds.label === label){
                    ds.hidden = !ds.hidden;
                }
            });
            chart.update();
        });
    });
});
</script>

</div>
</main>


<?php include '../templates/footer.php'; ?>