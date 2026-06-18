<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Pengguna.php';

$userClass = new Pengguna($conn);
$userClass->logout();

header('Location: ../index.php');
exit;
