<?php 
// File: includes/head.php
// Memulai HTML, CSS, dan wrapper utama layout.

// Asumsikan session_start() sudah dipanggil di file utama (misalnya dashboard.php)
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' | Flowerindo' : 'Flowerindo Admin Panel'; ?></title> 
    
    <link href="../dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --tblr-primary: #588f99; /* Warna utama */
        }
        body {
            background-color: #f5f7fa; /* Latar belakang sangat terang */
            font-family: sans-serif; 
        }
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -18rem;
            transition: margin .25s ease-out;
            background-color: #ffffff; 
            box-shadow: 1px 0 10px rgba(0, 0, 0, 0.05);
            width: 18rem; 
        }
        #sidebar-wrapper .sidebar-heading {
            padding: 1.5rem 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--tblr-primary);
        }
        #sidebar-wrapper .list-group-item {
            color: #495057;
            background-color: transparent;
            border: none;
            padding: 12px 1.5rem;
            font-weight: 500;
        }
        #sidebar-wrapper .list-group-item:hover,
        #sidebar-wrapper .list-group-item.active {
            background-color: var(--tblr-primary);
            color: #fff;
            border-radius: 4px; 
            margin: 0 1rem;
            width: calc(100% - 2rem);
        }
        #page-content-wrapper {
            min-width: 100vw;
            padding: 0;
        }
        .navbar {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9eef2;
        }
        .card {
            border: none; 
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.07), 0 0 0 1px rgba(0, 0, 0, 0.02); 
        }
        @media (min-width: 992px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }
            .toggled #sidebar-wrapper {
                margin-left: -18rem;
            }
        }
        .toggled #sidebar-wrapper {
            margin-left: 0;
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">