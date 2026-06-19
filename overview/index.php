<?php
$restrictTeam = false;
include(__DIR__ . '/../templates/auth.php');
include(__DIR__ . '/../templates/header.php');
?>

<style>
    #tbl-ewh td, #tbl-fv td, #tbl-ev td, #tbl-hlm td,
    #tbl-ewh th, #tbl-fv th, #tbl-ev th, #tbl-hlm th {
        font-size: 1.04rem;
        font-weight: bold;
    }
    #tbl-ewh thead th,
    #tbl-fv thead th,
    #tbl-ev thead th,
    #tbl-hlm thead th {
        background-color: #002B5B !important;
        color: white !important;
    }
    #tbl-ewh tbody tr,
    #tbl-fv tbody tr,
    #tbl-ev tbody tr,
    #tbl-hlm tbody tr {
        background-color: #f1f5fc !important;
    }
    .spinner {
        display: inline-block;
        width: 1.5rem;
        height: 1.5rem;
        border: 3px solid #ccc;
        border-top: 3px solid #002B5B;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
#top-status-bar {
    position: sticky;
    top: 0;
    z-index: 999;
gap: 2ch;
    background: #f8fafc;
    padding: 12px 20px;

    display: flex;
    align-items: center;
    justify-content: space-between; 

    max-width: 1275px;              
    margin: 0 auto;                
}
#last-sync {
    background: linear-gradient(115deg, #0ea5e9, #0284c7);
    color: white;

    border-radius: 10px;
    padding: 6px 10px;

    font-size: 15px;
    font-weight: 600;

    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
#zero-activity-wrapper {
    display: flex;
    justify-content: flex-end; /* ✅ يمين */
}
#zero-activity-wrapper > div {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
</style>


    
        <div id="top-status-bar">

<div id="user-alert-box">
    <?php include 'zero_activity_alert.php'; ?>
</div>

    <div id="last-sync" class="alert alert-info text-center fs-4 fw-bold mb-0">
        <span class="spinner"></span> Loading last sync...
    </div>

   


<div id="user-alert-box">
    <?php include 'rate_alert.php'; ?>
</div>

</div>

<main class="flex-fill">
<div class="container mt-4">
        <!--<h5 align="center">All</h5>-->
        <div id="overview-ewh-loader" class="text-center my-2"><span class="spinner"></span> Loading...</div>
        <div id="overview-ewh"></div>

        <h6 align="center">EWH-FV</h6>
        <div id="overview-ev-loader" class="text-center my-2"><span class="spinner"></span> Loading...</div>
        <div id="overview-ev"></div>

        <h6 align="center">HLM-FV</h6>
        <div id="overview-hlm-loader" class="text-center my-2"><span class="spinner"></span> Loading...</div>
        <div id="overview-hlm"></div>

        <h6 align="center">EWH-EV</h6>
        <div id="overview-fv-loader" class="text-center my-2"><span class="spinner"></span> Loading...</div>
        <div id="overview-fv"></div>        
        

        
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php include(__DIR__ . '/../templates/footer.php'); ?>

<script>
window.addEventListener('DOMContentLoaded', () => {
    loadAllData();
    refreshUserAlert(); // ✅ أول تحميل

    setInterval(() => {
        loadAllData();
        refreshUserAlert(); // ✅ يحدث التحذير كل دقيقة
    }, 60000);
});


function loadAllData() {
    loadAndRender('getOverviewData_ewh.php', 'overview-ewh', 'tbl-ewh', 'last-sync');
    loadAndRender('getOverviewData_fv.php', 'overview-fv', 'tbl-fv');
    loadAndRender('getOverviewData_ev.php', 'overview-ev', 'tbl-ev');
    loadAndRender('getOverviewData_hlm.php', 'overview-hlm', 'tbl-hlm');
}

function number_format(number) {
    return new Intl.NumberFormat().format(number);
}

function loadAndRender(url, containerId, tableId, syncId = null) {
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (syncId && data.lastSync) {
                const timeOnly = data.lastSync.split(' ')[1] || 'N/A';
                document.getElementById(syncId).innerHTML = timeOnly;
            }
            renderTable(data, containerId, tableId);
        });
}

function renderTable(data, containerId, tableId) {
    const loader = document.getElementById(containerId + '-loader');
    if (loader) loader.remove();

    if (!data || !data.bas) {
        document.getElementById(containerId).innerHTML = "<div class='text-danger'>Failed to load data.</div>";
        return;
    }

    // Determine key based on tableId
    let key = 'ewh';
    if (tableId.includes('hlm')) key = 'hlm';
    else if (tableId.includes('fv')) key = 'fv';
    else if (tableId.includes('ev')) key = 'ev';

    const bas = parseInt(data.bas.day?.[key]) || 0;
    const fail = parseInt(data.fail.day?.[key]) || 0;
    const jump = parseInt(data.jump.day?.[key]) || 0;
    const avg = parseFloat(data.avg.day?.[key]) || 0;

    const tix = extractTixObject(data.tix?.day?.[key]);
    const notOk = parseInt(tix.notOk) || 0;
    const orderOk = parseInt(tix.ORDER_OK) || 0;
    const orderTixOk = tix.orderTixOk || "0";

    const failRate = bas > 0 ? ((fail / bas) * 100).toFixed(1) : 0;
    const jumpRate = (bas - fail) > 0 ? ((jump / (bas - fail)) * 100).toFixed(1) : 0;

    let html = `<table class="table table-bordered" id="${tableId}">
        <thead>
            <tr>
                <th>TIME</th>
                <th>BOOKINGS / TIX OK</th>
                <th>B NOT OK</th>
                <th>COUNT BA</th>
                <th>%FAILED</th>
                <th>%PRICE JUMPS</th>
                <th>AVG BA TIME (S)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>THIS DAY</td>
                <td align="right">${orderOk} / ${orderTixOk}</td>
                <td align="right" class="notok">${notOk}</td>
                <td align="right">${bas.toLocaleString()}</td>
                <td align="right" class="tdfail">${failRate} %</td>
                <td align="right" class="tdjump">${jumpRate} %</td>
                <td align="right" class="tdavg">${avg.toFixed(1)}</td>
            </tr>`;

    html += generateTimeRow(data, 'hour', 'Last hour', key);
    html += generateTimeRow(data, 'hour2', 'Hour before', key);

    html += `</tbody></table>`;
    document.getElementById(containerId).innerHTML = html;

    setTimeout(() => applyStyling(`#${tableId}`), 100);
}

function generateTimeRow(data, timeKey, label, keyName) {
    const bas = parseInt(data.bas[timeKey]?.[keyName]) || 0;
    const fail = parseInt(data.fail[timeKey]?.[keyName]) || 0;
    const jump = parseInt(data.jump[timeKey]?.[keyName]) || 0;
    const avg = parseFloat(data.avg[timeKey]?.[keyName]) || 0;

    const tix = extractTixObject(data.tix?.[timeKey]?.[keyName]);
    const orderOk = parseInt(tix.ORDER_OK) || 0;
    const orderTixOk = tix.orderTixOk || 0;
    const notOk = parseInt(tix.notOk) || 0;

    const failRate = bas > 0 ? ((fail / bas) * 100).toFixed(1) : 0;
    const jumpRate = (bas - fail) > 0 ? ((jump / (bas - fail)) * 100).toFixed(1) : 0;

    return `<tr>
        <td>${label}</td>
        <td align="right">${orderOk} / ${orderTixOk}</td>
        <td align="right" class="notok">${notOk}</td>
        <td align="right">${bas.toLocaleString()}</td>
        <td align="right" class="tdfail">${failRate} %</td>
        <td align="right" class="tdjump">${jumpRate} %</td>
        <td align="right" class="tdavg">${avg.toFixed(1)}</td>
    </tr>`;
}

// Extract booking metrics safely
function extractTixObject(tixData) {
    return (tixData && typeof tixData === 'object') ? tixData : {};
}

// Apply color styling based on thresholds
function applyStyling(selector) {
    $(`${selector} .tdfail`).each(function () {
        const val = parseFloat($(this).text());
        $(this).css('color', val >= 15 ? 'red' : val >= 10 ? 'orange' : 'green');
    });

    $(`${selector} .tdjump`).each(function () {
        const val = parseFloat($(this).text());
        $(this).css('color', val >= 15 ? 'red' : val >= 10 ? 'orange' : 'green');
    });

    $(`${selector} .tdavg`).each(function () {
        const val = parseFloat($(this).text());
        $(this).css('color', val >= 6 ? 'red' : val >= 4 ? 'orange' : 'green');
    });

    $(`${selector} .notok`).each(function () {
        const val = parseInt($(this).text());
        $(this).css('color', val >= 3 ? 'red' : val > 0 ? 'orange' : 'green');
    });

    // Normalize empty or invalid cells
    $(`${selector} td`).each(function () {
        const txt = $(this).text().trim();
        if (txt === '' || txt === 'undefined' || txt === 'null') {
            $(this).text("0");
        }
    });
}
function refreshUserAlert() {
    fetch('zero_activity_alert.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('zero-alert').innerHTML = html;
        });

    fetch('rate_alert.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('rate-alert').innerHTML = html;
        });
}


</script>


</body>
</html>
