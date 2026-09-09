<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /sismuni_inventario/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inventario - Muni Pucusana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand font-weight-bold" href="/sismuni_inventario/index.php">
            <i class="bi bi-building"></i> Municipalidad Pucusana - TI
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/sismuni_inventario/dashboard.php"><i class="bi bi-pie-chart"></i> Estadísticas</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Inventarios</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/sismuni_inventario/modules/inventario_general.php">General de Cómputo</a></li>
                        <li><a class="dropdown-item" href="/sismuni_inventario/modules/perifericos.php">Periféricos</a></li>
                        <li><a class="dropdown-item" href="/sismuni_inventario/modules/impresoras.php">Impresoras</a></li>
                        <li><a class="dropdown-item" href="/sismuni_inventario/modules/telefonos.php">Teléfonos IP</a></li>
                        <li><a class="dropdown-item" href="/sismuni_inventario/modules/desuso.php">Equipos en Desuso</a></li>
                        <li><a class="dropdown-item" href="/sismuni_inventario/modules/estabilizadores.php">Estabilizadores</a></li>
                    </ul>
                </li>
            </ul>
            <span class="navbar-text me-3 text-white">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?> (<strong><?= htmlspecialchars($_SESSION['usuario_rol']) ?></strong>)
            </span>
            <a href="/sismuni_inventario/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </div>
</nav>
<div class="container-fluid py-4">