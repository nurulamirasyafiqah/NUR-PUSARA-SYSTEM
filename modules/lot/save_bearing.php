<?php
require_once '../../config/db.php';
requireLogin();
header('Content-Type: application/json');

$id_tapak = intval($_POST['id_tapak'] ?? 0);
$bearing  = isset($_POST['bearing']) ? floatval($_POST['bearing']) : null;

if (!$id_tapak || $bearing === null) {
    echo json_encode(['ok' => false, 'msg' => 'Data tidak lengkap.']);
    exit;
}

$tapak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_tapak_kubur WHERE id_tapak=$id_tapak"));
if (!$tapak) {
    echo json_encode(['ok' => false, 'msg' => 'Tapak tidak dijumpai.']);
    exit;
}

$centre_lat = (float)$tapak['latitud'];
$centre_lng = (float)$tapak['longitud'];
$row_sp     = (float)$tapak['jarak_baris_m'];
$col_sp     = (float)$tapak['jarak_lajur_m'];
$earth_r    = 6371000;
$bear_rad   = deg2rad($bearing);

mysqli_begin_transaction($conn);
try {
    mysqli_query($conn, "UPDATE tbl_tapak_kubur SET sudut_grid=$bearing WHERE id_tapak=$id_tapak");

    $lots = mysqli_query($conn, "SELECT id_lot, no_baris, no_lajur FROM tbl_lot WHERE id_tapak=$id_tapak");
    $stmt = mysqli_prepare($conn, "UPDATE tbl_lot SET latitud=?, longitud=?, sudut=? WHERE id_lot=?");

    $count = 0;
    while ($l = mysqli_fetch_assoc($lots)) {
        $baris = (int)($l['no_baris'] ?? 1);
        $lajur = (int)($l['no_lajur'] ?? 1);
        $row_m = ($baris - 1) * $row_sp;
        $col_m = ($lajur - 1) * $col_sp;

        $delta_lat = (($row_m * cos($bear_rad)) + ($col_m * -sin($bear_rad))) / $earth_r;
        $delta_lng = (($row_m * sin($bear_rad)) + ($col_m *  cos($bear_rad))) / ($earth_r * cos(deg2rad($centre_lat)));

        $new_lat = round($centre_lat + rad2deg($delta_lat), 8);
        $new_lng = round($centre_lng + rad2deg($delta_lng), 8);

        mysqli_stmt_bind_param($stmt, 'dddi', $new_lat, $new_lng, $bearing, $l['id_lot']);
        mysqli_stmt_execute($stmt);
        $count++;
    }

    mysqli_commit($conn);
    echo json_encode(['ok' => true, 'jumlah_lot_dikemaskini' => $count]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
