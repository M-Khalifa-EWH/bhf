<?php
$pageTitle = "Price Difference";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer1.php';
?>

<style>
.container { max-width: 1200px; }
.btn-custom { background-color: #dce2e3; border-color: #dce2e3; color: #000; }
.dataTables_wrapper .dataTables_paginate .paginate_button { padding:0.5em 1em; margin:0 0.1em; }
.table th, .table td { white-space: nowrap; }
</style>

<div class="container mt-4">
    <form method="POST" action="" class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="Statusderbuchung" class="form-label">Status der Buchung</label>
                <select name="Statusderbuchung" class="form-select" id="Statusderbuchung">
                    <option value="ORDER_OK" <?php echo (isset($_POST['Statusderbuchung']) && $_POST['Statusderbuchung']=='ORDER_OK')?'selected':''; ?>>Buchung.OK</option>
                    <option value="S_ORDER_OK" <?php echo (isset($_POST['Statusderbuchung']) && $_POST['Statusderbuchung']=='S_ORDER_OK')?'selected':''; ?>>S_ORDER_OK</option>
                    <option value="BOOKING_FAIL" <?php echo (isset($_POST['Statusderbuchung']) && $_POST['Statusderbuchung']=='BOOKING_FAIL')?'selected':''; ?>>BOOKING_FAIL</option>
                    <option value="BOOKING_FAIL,CREDITCARD_FAIL,NEW,POOR_CREDITWORTHINESS,REQ_OK,S_ORDER_OK" <?php echo (isset($_POST['Statusderbuchung']) && $_POST['Statusderbuchung']=='BOOKING_FAIL,CREDITCARD_FAIL,NEW,POOR_CREDITWORTHINESS,REQ_OK,S_ORDER_OK')?'selected':''; ?>>Buchung.NOT.OK</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="monthPicker" class="form-label">Hinflug Monat</label>
                <input type="month" name="selectedMonth" class="form-control" id="monthPicker" value="<?php echo isset($_POST['selectedMonth'])?$_POST['selectedMonth']:date('Y-m'); ?>">
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button type="submit" name="submit" class="btn btn-custom">Suchen</button>
            </div>
        </div>
    </form>
</div>

<?php
ini_set('memory_limit','1024M');
if(isset($_POST['submit'])){
    $Statusderbuchung = $_POST['Statusderbuchung'];
    $statusArray = explode(',', $Statusderbuchung);

    $connect = SQLServer1::connect();
    if(!$connect){ die(print_r(sqlsrv_errors(), true)); }

    $sql = "SELECT Buchungsdatum, Buchungsuhrzeit as Uhrzeit, Statusderbuchung, PlayerBrand, Transfertyp, Betrag,
            Zahlungsart, Hinflugdatum, TrademarkCode, FlugnummerHin, AbflugzeitHin, AnkunftszeitHin, AbflugzeitRück, AnkunftszeitRück,
            AirlineHin, Buchungsnummer, Von, Nach, Nachname, ErwachseneAktiv, KinderAktiv, BabiesAktiv,
            HotelCode, Hotelname, GiataCode, Zimmerart, ZimmerartCode, Verpflegung, AnkunftsdatumHin, AnkunftsdatumRück, Transfer,
            Agenturnummer
            FROM dbo.csv_export_copy
            WHERE CAST(CONVERT(datetime, Buchungsdatum, 104) AS date) >= DATEADD(day, -4, GETDATE())
            AND Statusderbuchung IN (".implode(',', array_fill(0,count($statusArray),'?')).")
            ORDER BY CAST(CONVERT(datetime, Buchungsdatum, 104) AS date) DESC";

    $stmt = sqlsrv_query($connect, $sql, $statusArray);
    if(!$stmt){ die(print_r(sqlsrv_errors(), true)); }

    $data = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){ $data[] = $row; }
    sqlsrv_close($connect);

    echo '<div class="table-responsive">
    <table id="dataTable" class="table table-striped table-bordered">
    <thead>
    <tr>
        <th>B.Datum</th><th>Uhrzeit</th><th>V. Marke</th><th>Transfer</th><th>Transfertyp</th>
        <th>PlayerBrand</th><th>Buchungs.Nr.</th><th>Betrag</th><th>Agency</th><th>Von</th><th>Nach</th>
        <th>H.F. Datum</th><th>G. Code</th><th>Htl. Code</th><th>Htl. Name</th>
        <th>Zmr. Art</th><th>Zmr. Code</th><th>Verpflegung</th><th>Ankft.D. H.</th><th>Ankft.D. R.</th>
        <th>Airline H</th><th>FlNr. H</th><th>Ab.Fl. H.</th><th>Ankft.Z. H.</th><th>Ab.Fl. R.</th><th>Ankft.Z. R.</th>
        <th>Nachname</th><th>Erw. Aktiv</th><th>CHD. Aktiv</th><th>INF. Aktiv</th><th>Zahlungsart</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th>B.Datum</th><th>Uhrzeit</th><th>V. Marke</th><th>Transfer</th><th>Transfertyp</th>
        <th>PlayerBrand</th><th>Buchungs.Nr.</th><th>Betrag</th><th>Agency</th><th>Von</th><th>Nach</th>
        <th>H.F. Datum</th><th>G. Code</th><th>Htl. Code</th><th>Htl. Name</th>
        <th>Zmr. Art</th><th>Zmr. Code</th><th>Verpflegung</th><th>Ankft.D. H.</th><th>Ankft.D. R.</th>
        <th>Airline H</th><th>FlNr. H</th><th>Ab.Fl. H.</th><th>Ankft.Z. H.</th><th>Ab.Fl. R.</th><th>Ankft.Z. R.</th>
        <th>Nachname</th><th>Erw. Aktiv</th><th>CHD. Aktiv</th><th>INF. Aktiv</th><th>Zahlungsart</th>
    </tr>
    </tfoot>
    <tbody>';

    foreach($data as $row){
        echo "<tr>
        <td>{$row['Buchungsdatum']}</td><td>{$row['Uhrzeit']}</td><td>{$row['TrademarkCode']}</td><td>{$row['Transfer']}</td><td>{$row['Transfertyp']}</td>
        <td>{$row['PlayerBrand']}</td><td>{$row['Buchungsnummer']}</td><td>{$row['Betrag']}</td><td>{$row['Agenturnummer']}</td><td>{$row['Von']}</td><td>{$row['Nach']}</td>
        <td>{$row['Hinflugdatum']}</td><td>{$row['GiataCode']}</td><td>{$row['HotelCode']}</td><td>{$row['Hotelname']}</td>
        <td>{$row['Zimmerart']}</td><td>{$row['ZimmerartCode']}</td><td>{$row['Verpflegung']}</td><td>{$row['AnkunftsdatumHin']}</td><td>{$row['AnkunftsdatumRück']}</td>
        <td>{$row['AirlineHin']}</td><td>{$row['FlugnummerHin']}</td><td>{$row['AbflugzeitHin']}</td><td>{$row['AnkunftszeitHin']}</td><td>{$row['AbflugzeitRück']}</td><td>{$row['AnkunftszeitRück']}</td>
        <td>{$row['Nachname']}</td><td>{$row['ErwachseneAktiv']}</td><td>{$row['KinderAktiv']}</td><td>{$row['BabiesAktiv']}</td><td>{$row['Zahlungsart']}</td>
        </tr>";
    }

    echo '</tbody></table></div>';
}
?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function(){
    var table = $('#dataTable').DataTable({
        dom: 'Bfltip',
        buttons: ['csv','excel','print'],
        pageLength: 15,
        lengthMenu: [[10,25,50,-1],[10,25,50,"All"]],
        initComplete: function(){
            this.api().columns().every(function(){
                var column = this;
                var input = $('<input placeholder="Suche" style="width:100%"/>')
                    .appendTo($(column.footer()).empty())
                    .on('keyup change clear', function(){
                        if(column.search() !== this.value) column.search(this.value).draw();
                    });
            });
        }
    });
    $('#dataTable tfoot tr').appendTo('#dataTable thead');
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>