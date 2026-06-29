<?php
session_start();

/*
|--------------------------------------------------------------------------
| AUTH CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Not logged in → show login page
    include __DIR__ . '/../admin-login.php';
    exit;
}
?>
<?php require_once __DIR__.'/../../config.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body {
    background: #f1f5f9;
    font-family: Arial, sans-serif;
    margin: 0;
}

.container {
    padding: 30px;
}

h2 {
    margin-bottom: 15px;
}

.search-box {
    margin-bottom: 15px;
}

.search-box input {
    padding: 10px;
    width: 300px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    outline: none;
}

.table-wrapper {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #020617;
    color: #fff;
}

th, td {
    padding: 12px;
    text-align: left;
}

tbody tr {
    border-bottom: 1px solid #e5e7eb;
}

tbody tr:hover {
    background: #f8fafc;
}

.loading {
    padding: 20px;
    text-align: center;
    color: #64748b;
}
</style>

<style>
/* ===== RESET ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
}

/* ===== BODY ===== */
body {
    background-color: #f1f5f9;
    color: #0f172a;
}

/* ===== LAYOUT ===== */
.dashboard {
    display: flex;
    min-height: 100vh;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 240px;
    background-color: #020617;
    color: #fff;
    padding: 20px;
    min-height: 100vh;
}

.sidebar h1 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 22px;
    letter-spacing: 1px;
}

.sidebar ul {
    list-style: none;
}

.sidebar ul li {
    padding: 14px 16px;
    margin-bottom: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s;
}

.sidebar ul li:hover,
.sidebar ul li.active {
    background-color: #1e293b;
}
.sidebar ul li a{
    text-decoration: none;
    color: #fff;
}
/* ===== MAIN CONTENT ===== */
.main {
    flex: 1;
    padding: 25px;
}

/* ===== TOP BAR ===== */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.topbar h2 {
    font-size: 24px;
}

.admin-info {
    background: #fff;
    padding: 10px 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* ===== STATS CARDS ===== */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.card h3 {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 10px;
}

.card p {
    font-size: 26px;
    font-weight: bold;
}

/* ===== TABLE CONTAINER ===== */
.table-box {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.table-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.table-header h3 {
    font-size: 18px;
}

.table-header input {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #cbd5f5;
    outline: none;
}

/* ===== TABLE ===== */
table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #020617;
    color: #fff;
}

th, td {
    padding: 12px;
    text-align: left;
}

tbody tr {
    border-bottom: 1px solid #e5e7eb;
}

tbody tr:hover {
    background-color: #f8fafc;
}
.pagination-btn {
    display: inline-block;
    margin: 0 4px;
    padding: 6px 12px;
    background: #e5e7eb;
    color: #020617;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.pagination-btn.active {
    background: #020617;
    color: #fff;
}

.pagination-btn:hover {
    background: #1e293b;
    color: #fff;
}
.sidebar ul li.logout a {
    color: #fca5a5;
    text-decoration: none;
    display: block;
}

.sidebar ul li.logout:hover {
    background-color: #7f1d1d;
}

/* ===== RESPONSIVE ===== */
/* @media (max-width: 768px) {
    .sidebar {
        display: none;
    }
} */
</style>
</head>

<body>
    <div class="dashboard">
