<?php
require_once "config/koneksi.php";

$type = $_GET['type'] ?? '';
$id   = $_GET['id'] ?? '';

if ($type == "provinsi") {
    $q = mysqli_query($koneksi, "SELECT name FROM provinces ORDER BY name ASC");
    $data = [];
    while ($d = mysqli_fetch_assoc($q)) $data[] = $d;
    echo json_encode($data);
}

elseif ($type == "kota") {
    $q = mysqli_query($koneksi, "
        SELECT r.name 
        FROM regencies r
        JOIN provinces p ON r.province_id = p.id
        WHERE p.name = '$id'
        ORDER BY r.name ASC
    ");
    $data = [];
    while ($d = mysqli_fetch_assoc($q)) $data[] = $d;
    echo json_encode($data);
}

elseif ($type == "kecamatan") {
    $q = mysqli_query($koneksi, "
        SELECT d.name 
        FROM districts d
        JOIN regencies r ON d.regency_id = r.id
        WHERE r.name = '$id'
        ORDER BY d.name ASC
    ");
    $data = [];
    while ($d = mysqli_fetch_assoc($q)) $data[] = $d;
    echo json_encode($data);
}

elseif ($type == "kodepos") {
    $q = mysqli_query($koneksi, "
        SELECT postal_code 
        FROM villages v
        JOIN districts d ON v.district_id = d.id
        WHERE d.name = '$id'
        LIMIT 1
    ");
    $d = mysqli_fetch_assoc($q);
    echo $d['postal_code'] ?? '';
}
