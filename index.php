<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Conteo de registros para tarjetas principales
$totales = [
    'general' => $pdo->query("SELECT COUNT(*) FROM inventario_general")->fetchColumn(),
    'perifericos' => $pdo->query("SELECT COUNT(*) FROM inventario_perifericos")->fetchColumn(),
    'impresoras' => $pdo->query("SELECT COUNT(*) FROM inventario_impresoras")->fetchColumn(),
    'telefonos' => $pdo->query("SELECT COUNT(*) FROM inventario_telefonos")->fetchColumn(),
    'desuso' => $pdo->query("SELECT COUNT(*) FROM inventario_desuso")->fetchColumn(),
    'estabilizadores' => $pdo->query("SELECT COUNT(*) FROM inventario_estabilizadores")->fetchColumn()
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Panel Control de Inventario Informático</h2>
    <a href="modules/inventario_general.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nuevo Registro PC</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"> 
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Inventario General PC</h5>
                <h2 class="display-6 font-weight-bold"><?= $totales['general'] ?></h2>
                <a href="modules/inventario_general.php" class="text-white text-decoration-underline">Ver detalles &rarr;</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Periféricos Asignados</h5>
                <h2 class="display-6 font-weight-bold"><?= $totales['perifericos'] ?></h2>
                <a href="modules/perifericos.php" class="text-white text-decoration-underline">Ver detalles &rarr;</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Consolidado Impresoras</h5>
                <h2 class="display-6 font-weight-bold"><?= $totales['impresoras'] ?></h2>
                <a href="modules/impresoras.php" class="text-white text-decoration-underline">Ver detalles &rarr;</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Teléfonos </h5>
                <h2 class="display-6 font-weight-bold"><?= $totales['telefonos'] ?></h2>
                <a href="modules/telefonos.php" class="text-dark text-decoration-underline">Ver detalles &rarr;</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Equipos en Desuso</h5>
                <h2 class="display-6 font-weight-bold"><?= $totales['desuso'] ?></h2>
                <a href="modules/desuso.php" class="text-white text-decoration-underline">Ver detalles &rarr;</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-white shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Estabilizadores de Energía</h5>
                <h2 class="display-6 font-weight-bold"><?= $totales['estabilizadores'] ?></h2>
                <a href="modules/estabilizadores.php" class="text-white text-decoration-underline">Ver detalles &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>