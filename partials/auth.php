<?php
// Di-include di awal setiap halaman yang wajib login
session_start();

// Kalau belum login, kembalikan ke halaman login
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}
