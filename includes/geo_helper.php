<?php
/**
 * GEO HELPER — Fungsi geometri yang dikongsi merentasi sistem
 * ------------------------------------------------------------
 */

/**
 * Point-in-polygon test (ray-casting algorithm).
 * $lat, $lng: titik yang nak diuji
 * $polygon: array of [lng, lat] pairs (format GeoJSON)
 * Return: true jika titik berada DALAM polygon
 */
function pointInPolygon($lat, $lng, $polygon) {
    $n = count($polygon);
    $inside = false;
    $j = $n - 1;
    for ($i = 0; $i < $n; $i++) {
        $xi = $polygon[$i][0]; $yi = $polygon[$i][1]; // lng, lat
        $xj = $polygon[$j][0]; $yj = $polygon[$j][1];
        $intersect = (($yi > $lat) != ($yj > $lat)) &&
            ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);
        if ($intersect) $inside = !$inside;
        $j = $i;
    }
    return $inside;
}

/**
 * Kira koordinat GPS satu titik grid (row, col) dari pusat tapak,
 * ikut sudut (bearing) dan jarak baris/lajur — formula standard
 * yang digunakan seluruh sistem (auto-plot, seed, migration).
 */
function gridToLatLng($centre_lat, $centre_lng, $bearing, $row_sp, $col_sp, $row, $col) {
    $earth_r = 6371000;
    $bear_rad = deg2rad($bearing);
    $row_m = ($row - 1) * $row_sp;
    $col_m = ($col - 1) * $col_sp;
    $delta_lat = (($row_m * cos($bear_rad)) + ($col_m * -sin($bear_rad))) / $earth_r;
    $delta_lng = (($row_m * sin($bear_rad)) + ($col_m *  cos($bear_rad))) / ($earth_r * cos(deg2rad($centre_lat)));
    return [
        round($centre_lat + rad2deg($delta_lat), 8),
        round($centre_lng + rad2deg($delta_lng), 8),
    ];
}
