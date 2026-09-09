<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// ==========================================
// 1. CONSULTAS Y CONSOLIDACIÓN DE DATOS
// ==========================================

// A. Conteo de registros para las tarjetas de resumen (KPIs)
$totales = [
    'general'         => 0,
    'perifericos'     => 0,
    'impresoras'      => 0,
    'telefonos'       => 0,
    'desuso'          => 0,
    'estabilizadores' => 0
];

// Mapeo seguro de tablas
$tablas = [
    'general'         => 'inventario_general',
    'perifericos'     => 'inventario_perifericos',
    'impresoras'      => 'inventario_impresoras',
    'telefonos'       => 'inventario_telefonos',
    'desuso'          => 'inventario_desuso',
    'estabilizadores' => 'inventario_estabilizadores'
];

foreach ($tablas as $clave => $tabla) {
    try {
        $totales[$clave] = (int)$pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
    } catch (Exception $e) {
        $totales[$clave] = 0;
    }
}

// Data agrupada para el Gráfico Donut de Inventarios
$totales_inventario = [
    'General PC'      => $totales['general'],
    'Periféricos'     => $totales['perifericos'],
    'Impresoras'      => $totales['impresoras'],
    'Teléfonos'       => $totales['telefonos'],
    'Estabilizadores' => $totales['estabilizadores'],
    'En Desuso'       => $totales['desuso']
];


// B. Agrupación por Área y Condición (PROPIO vs ALQUILADA)
$condicion_cpu_areas = [];
$total_propios = 0;
$total_alquilados = 0;

try {
    // Se utiliza 'condicion_equipo' como figura en la base de datos
    $stmt_cond = $pdo->query("
        SELECT 
            gerencia_subgerencia AS area,
            LOWER(TRIM(condicion_equipo)) AS condicion_val,
            COUNT(*) as cantidad
        FROM inventario_general
        WHERE gerencia_subgerencia IS NOT NULL 
          AND gerencia_subgerencia != ''
        GROUP BY gerencia_subgerencia, LOWER(TRIM(condicion_equipo))
    ");

    while ($r = $stmt_cond->fetch(PDO::FETCH_ASSOC)) {
        $area = trim($r['area']);
        $cond = $r['condicion_val'];
        $cant = (int)$r['cantidad'];

        if (!isset($condicion_cpu_areas[$area])) {
            $condicion_cpu_areas[$area] = ['propio' => 0, 'alquilada' => 0];
        }

        // Se evalúan las coincidencias para clasificar correctamente
        if (stripos($cond, 'propio') !== false || stripos($cond, 'propia') !== false) {
            $condicion_cpu_areas[$area]['propio'] += $cant;
            $total_propios += $cant;
        } elseif (stripos($cond, 'alquilad') !== false) {
            $condicion_cpu_areas[$area]['alquilada'] += $cant;
            $total_alquilados += $cant;
        }
    }
} catch (Exception $e) {
    $condicion_cpu_areas = [];
}

// Ordenar las áreas alfabéticamente para mejor lectura
ksort($condicion_cpu_areas);

// Arreglos de datos para JavaScript
$labels_areas_condicion = array_keys($condicion_cpu_areas);
$data_propios = array_column($condicion_cpu_areas, 'propio');
$data_alquilados = array_column($condicion_cpu_areas, 'alquilada');


// C. Consolidado Completo de Áreas / Gerencias con Equipamiento
$areas_map = [];

$sumar_areas = function($tabla, $columna = 'gerencia_subgerencia') use ($pdo, &$areas_map) {
    try {
        $stmt = $pdo->query("SELECT {$columna} AS area_val, COUNT(*) as total FROM {$tabla} WHERE {$columna} IS NOT NULL AND {$columna} != '' GROUP BY {$columna}");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $area = trim($row['area_val']);
            if (!empty($area)) {
                $areas_map[$area] = ($areas_map[$area] ?? 0) + (int)$row['total'];
            }
        }
    } catch (Exception $e) {}
};

$sumar_areas('inventario_general');
$sumar_areas('inventario_perifericos');
$sumar_areas('inventario_impresoras');
$sumar_areas('inventario_telefonos');
$sumar_areas('inventario_estabilizadores');
$sumar_areas('inventario_desuso', 'sede');

arsort($areas_map);
$todas_las_areas = $areas_map;
?>

<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid px-2 py-2">
    <!-- Encabezado del Dashboard -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-primary"></i> Panel Control de Inventario Informático</h2>
            <p class="text-muted mb-0">Subgerencia de Tecnologías de la Información - Estadísticas Consolidadas</p>
        </div>
        <a href="modules/inventario_general.php" class="btn btn-success shadow-sm"><i class="bi bi-plus-lg me-1"></i> Nuevo Registro PC</a>
    </div>

    <!-- Tarjetas de Resumen (KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-lg-2"> 
            <div class="card bg-primary text-white shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <h6 class="card-title text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Inventario General PC</h6>
                    <h2 class="display-6 fw-bold mb-2"><?= number_format($totales['general']) ?></h2>
                    <a href="modules/inventario_general.php" class="text-white text-decoration-underline small">Ver detalles &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card bg-info text-white shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <h6 class="card-title text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Periféricos Asignados</h6>
                    <h2 class="display-6 fw-bold mb-2"><?= number_format($totales['perifericos']) ?></h2>
                    <a href="modules/perifericos.php" class="text-white text-decoration-underline small">Ver detalles &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card bg-success text-white shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <h6 class="card-title text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Consolidado Impresoras</h6>
                    <h2 class="display-6 fw-bold mb-2"><?= number_format($totales['impresoras']) ?></h2>
                    <a href="modules/impresoras.php" class="text-white text-decoration-underline small">Ver detalles &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card bg-warning text-dark shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <h6 class="card-title text-dark-50 text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Teléfonos</h6>
                    <h2 class="display-6 fw-bold mb-2"><?= number_format($totales['telefonos']) ?></h2>
                    <a href="modules/telefonos.php" class="text-dark text-decoration-underline small">Ver detalles &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card bg-danger text-white shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <h6 class="card-title text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Equipos en Desuso</h6>
                    <h2 class="display-6 fw-bold mb-2"><?= number_format($totales['desuso']) ?></h2>
                    <a href="modules/desuso.php" class="text-white text-decoration-underline small">Ver detalles &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card bg-secondary text-white shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <h6 class="card-title text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Estabilizadores Energía</h6>
                    <h2 class="display-6 fw-bold mb-2"><?= number_format($totales['estabilizadores']) ?></h2>
                    <a href="modules/estabilizadores.php" class="text-white text-decoration-underline small">Ver detalles &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner con Total General Consolidado -->
    <div class="alert alert-dark shadow-sm d-flex justify-content-between align-items-center mb-4">
        <span class="fs-5"><i class="bi bi-box-seam me-2"></i><strong>Total de Bienes e Inventario Registrados:</strong></span>
        <span class="badge bg-success fs-5 px-3 py-2"><?= number_format(array_sum($totales)) ?> Unidades</span>
    </div>

    <!-- Panel Superior: Módulos y Condición de CPU -->
    <div class="row g-4 mb-4">
        <!-- Gráfica 1: Distribución por Tipo de Inventario -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-primary me-2"></i> Distribución de Módulos</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center p-3">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="chartTiposInventario"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica 2: Condición CPU por Área (Barra Horizontal con Scroll Dinámico) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-laptop text-primary me-2"></i> Condición CPU: Propio vs Alquilada por Área</h5>
                    <div>
                        <span class="badge bg-danger me-1">Propios: <?= $total_propios ?></span>
                        <span class="badge bg-warning text-dark">Alquiladas: <?= $total_alquilados ?></span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($condicion_cpu_areas)): ?>
                        <p class="text-muted text-center my-auto">No hay registros de CPUs clasificados como Propio o Alquilada.</p>
                    <?php else: ?>
                        <!-- Scroll contenedor ajustado para mostrar todas las áreas dinámicamente -->
                        <div style="position: relative; height: 320px; overflow-y: auto; overflow-x: hidden;">
                            <div style="position: relative; height: <?= max(300, count($condicion_cpu_areas) * 32) ?>px; width: 100%;">
                                <canvas id="chartCondicionCpu"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Inferior: Ranking Completo de Áreas Equipadas -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-steps text-info me-2"></i> Áreas Equipadas (Ranking Completo)</h5>
                    <span class="badge bg-info text-dark fs-6"><?= count($todas_las_areas) ?> Áreas Totales</span>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; height: <?= max(350, count($todas_las_areas) * 32) ?>px; width: 100%;">
                        <canvas id="chartTopAreas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // 1. Gráfica Doughnut: Total por Módulo
    const ctxTipos = document.getElementById('chartTiposInventario').getContext('2d');
    new Chart(ctxTipos, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($totales_inventario)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($totales_inventario)) ?>,
                backgroundColor: ['#0d6efd', '#0dcaf0', '#198754', '#ffc107', '#6c757d', '#dc3545'],
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // 2. Gráfica Horizontal de Condición CPU: Propio vs Alquilada por Área
    <?php if (!empty($condicion_cpu_areas)): ?>
    const ctxCondCpu = document.getElementById('chartCondicionCpu').getContext('2d');
    new Chart(ctxCondCpu, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_areas_condicion) ?>,
            datasets: [
                {
                    label: 'Propio',
                    data: <?= json_encode($data_propios) ?>,
                    backgroundColor: '#dc3545',
                    borderColor: '#b02a37',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Alquilada',
                    data: <?= json_encode($data_alquilados) ?>,
                    backgroundColor: '#ffc107',
                    borderColor: '#d39e00',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            indexAxis: 'y', // Orientación horizontal para visualización de etiquetas
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                x: { 
                    stacked: true,
                    beginAtZero: true,
                    ticks: { precision: 0 }
                },
                y: { 
                    stacked: true 
                }
            }
        }
    });
    <?php endif; ?>

    // 3. Gráfica Bar Horizontal: Ranking Completo de Áreas Equipadas
    const ctxAreas = document.getElementById('chartTopAreas').getContext('2d');
    new Chart(ctxAreas, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($todas_las_areas)) ?>,
            datasets: [{
                label: 'Cantidad de Equipos Registrados',
                data: <?= json_encode(array_values($todas_las_areas)) ?>,
                backgroundColor: '#0dcaf0',
                borderColor: '#0bacbe',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

});
</script>

<?php require_once 'includes/footer.php'; ?>