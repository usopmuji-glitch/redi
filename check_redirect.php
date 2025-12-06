<?php
require_once 'functions.php';
session_start();

$device = get_device_id();
$data = load_data();
$currentPage = isset($data[$device]['currentPage']) ? $data[$device]['currentPage'] : "waiting.php";

// Devolver solo el currentPage sin exponer el JSON completo
echo json_encode(["currentPage" => $currentPage]);
?>
