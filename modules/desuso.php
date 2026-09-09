<?php
// Configurar límites dinámicamente
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');

require_once '../includes/header.php';
require_once '../config/db.php';

// Crear y adaptar automáticamente la tabla al esquema exacto
try {
    $sql_tabla = "CREATE TABLE IF NOT EXISTS inventario_desuso (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sede VARCHAR(150) NULL,
        tipo_bien VARCHAR(150) NULL,
        marca VARCHAR(100) NULL,
        modelo VARCHAR(100) NULL,
        codigo_patrimonial VARCHAR(100) NULL,
        inv_2020 VARCHAR(100) NULL,
        inv_2021 VARCHAR(100) NULL,
        inv_2022 VARCHAR(100) NULL,
        inv_2023 VARCHAR(100) NULL,
        inv_2025 VARCHAR(100) NULL,
        observaciones TEXT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_tabla);

    // Asegurar que la columna 'sede' exista si la tabla ya había sido creada antes
    $columnas = $pdo->query("SHOW COLUMNS FROM inventario_desuso")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('sede', $columnas)) {
        if (in_array('sede_municipal', $columnas)) {
            $pdo->exec("ALTER TABLE inventario_desuso CHANGE COLUMN sede_municipal sede VARCHAR(150) NULL");
        } else {
            $pdo->exec("ALTER TABLE inventario_desuso ADD COLUMN sede VARCHAR(150) NULL AFTER id");
        }
    }
} catch (PDOException $e) {
    die("Error al verificar o crear la tabla de la base de datos: " . $e->getMessage());
}

$mensaje = '';
$tipo_alerta = '';

// 1. PROCESAR GUARDADO (NUEVO REGISTRO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar') {
    try {
        $sql = "INSERT INTO inventario_desuso (
            sede, tipo_bien, marca, modelo, codigo_patrimonial,
            inv_2020, inv_2021, inv_2022, inv_2023, inv_2025, observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['sede'] ?? '',
            $_POST['tipo_bien'] ?? '',
            $_POST['marca'] ?? '',
            $_POST['modelo'] ?? '',
            $_POST['codigo_patrimonial'] ?? '',
            $_POST['inv_2020'] ?? '',
            $_POST['inv_2021'] ?? '',
            $_POST['inv_2022'] ?? '',
            $_POST['inv_2023'] ?? '',
            $_POST['inv_2025'] ?? '',
            $_POST['observaciones'] ?? ''
        ]);
        header("Location: desuso.php?msg=guardado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 2. PROCESAR EDICIÓN DE REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    try {
        $sql = "UPDATE inventario_desuso SET
            sede = ?, tipo_bien = ?, marca = ?, modelo = ?, 
            codigo_patrimonial = ?, inv_2020 = ?, inv_2021 = ?, 
            inv_2022 = ?, inv_2023 = ?, inv_2025 = ?, observaciones = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['sede'] ?? '',
            $_POST['tipo_bien'] ?? '',
            $_POST['marca'] ?? '',
            $_POST['modelo'] ?? '',
            $_POST['codigo_patrimonial'] ?? '',
            $_POST['inv_2020'] ?? '',
            $_POST['inv_2021'] ?? '',
            $_POST['inv_2022'] ?? '',
            $_POST['inv_2023'] ?? '',
            $_POST['inv_2025'] ?? '',
            $_POST['observaciones'] ?? '',
            $_POST['id']
        ]);
        header("Location: desuso.php?msg=editado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 3. PROCESAR IMPORTACIÓN CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'importar_csv') {
    if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] == 0) {
        $fileName = $_FILES['archivo_excel']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $mensaje = "Error: Por favor, guarde su libro de Excel como 'CSV (delimitado por comas)' antes de subirlo.";
            $tipo_alerta = "danger";
        } else {
            $file = $_FILES['archivo_excel']['tmp_name'];
            $handle = fopen($file, "r");

            // Detectar separador (coma o punto y coma)
            $firstLine = fgets($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            rewind($handle);

            $count = 0;

            // Omitir cabecera
            fgetcsv($handle, 4096, $delimiter);

            $sql = "INSERT INTO inventario_desuso (
                sede, tipo_bien, marca, modelo, codigo_patrimonial,
                inv_2020, inv_2021, inv_2022, inv_2023, inv_2025, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);

            $pdo->beginTransaction();
            try {
                while (($data = fgetcsv($handle, 4096, $delimiter)) !== FALSE) {
                    if (empty(array_filter($data))) continue;

                    // Ajustar a exactamente 11 columnas
                    $params = array_pad(array_slice($data, 0, 11), 11, '');

                    // Convertir a UTF-8
                    $params = array_map(function($field) {
                        return mb_convert_encoding(trim($field), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
                    }, $params);

                    $stmt->execute($params);
                    $count++;
                }
                $pdo->commit();
                fclose($handle);
                header("Location: desuso.php?msg=importado&cant=$count");
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if (isset($handle) && is_resource($handle)) {
                    fclose($handle);
                }
                $mensaje = "Error en la fila " . ($count + 2) . ": " . $e->getMessage();
                $tipo_alerta = "danger";
            }
        }
    } else {
        $mensaje = "Error al cargar el archivo en el servidor.";
        $tipo_alerta = "danger";
    }
}

// Mensajes URL
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'guardado') { $mensaje = "Registro guardado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'editado') { $mensaje = "Registro actualizado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'importado') { $cant = $_GET['cant'] ?? 0; $mensaje = "Se importaron $cant registros correctamente."; $tipo_alerta = "success"; }
}

// Consulta de registros principales
$stmt = $pdo->query("SELECT * FROM inventario_desuso ORDER BY id DESC");
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener listas únicas para poblar los filtros dinámicos
$sedes = $pdo->query("SELECT DISTINCT sede FROM inventario_desuso WHERE sede IS NOT NULL AND sede != '' ORDER BY sede ASC")->fetchAll(PDO::FETCH_COLUMN);
$tipos = $pdo->query("SELECT DISTINCT tipo_bien FROM inventario_desuso WHERE tipo_bien IS NOT NULL AND tipo_bien != '' ORDER BY tipo_bien ASC")->fetchAll(PDO::FETCH_COLUMN);
$marcas = $pdo->query("SELECT DISTINCT marca FROM inventario_desuso WHERE marca IS NOT NULL AND marca != '' ORDER BY marca ASC")->fetchAll(PDO::FETCH_COLUMN);
$modelos = $pdo->query("SELECT DISTINCT modelo FROM inventario_desuso WHERE modelo IS NOT NULL AND modelo != '' ORDER BY modelo ASC")->fetchAll(PDO::FETCH_COLUMN);
$codigos = $pdo->query("SELECT DISTINCT codigo_patrimonial FROM inventario_desuso WHERE codigo_patrimonial IS NOT NULL AND codigo_patrimonial != '' ORDER BY codigo_patrimonial ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
@media print {
    .no-print, .btn, nav, header, footer, .modal { display: none !important; }
    body { background-color: #fff !important; font-size: 8pt; }
    .card { border: none !important; box-shadow: none !important; }
    .table-responsive { overflow: visible !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #333 !important; padding: 4px !important; white-space: normal !important; }
    .print-header { display: block !important; text-align: center; margin-bottom: 15px; }
}
.print-header { display: none; }
</style>

<div class="container-fluid px-3">
    <div class="print-header">
        <h2>Municipalidad Distrital de Pucusana</h2>
        <h3>Subgerencia de Tecnologías de la Información</h3>
        <h4>Equipos Informáticos en Desuso</h4>
        <hr>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2><i class="bi bi-trash"></i> Equipos Informáticos en Desuso</h2>
        <div>
            <button class="btn btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImportar">
                <i class="bi bi-file-earmark-excel"></i> Importar CSV/Excel
            </button>
            <button class="btn btn-danger me-1" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf"></i> Exportar a PDF / Imprimir
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                <i class="bi bi-plus-circle"></i> Nuevo Registro
            </button>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show no-print" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Panel de Filtros Específicos y Buscador -->
    <div class="card shadow-sm border-0 mb-3 no-print">
        <div class="card-body bg-light">
            <div class="row g-2">
                <div class="col-md-12 mb-1">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="inputBuscador" class="form-control" onkeyup="filtrarTabla()" placeholder="Búsqueda rápida en texto libre...">
                    </div>
                </div>

                <!-- Filtro Sede -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Sede</label>
                    <select id="filterSede" class="form-select form-select-sm" onchange="filtrarTabla()">
                        <option value="">-- Todas --</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro Tipo Bien -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Tipo Bien</label>
                    <select id="filterTipo" class="form-select form-select-sm" onchange="filtrarTabla()">
                        <option value="">-- Todos --</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro Marca -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Marca</label>
                    <select id="filterMarca" class="form-select form-select-sm" onchange="filtrarTabla()">
                        <option value="">-- Todas --</option>
                        <?php foreach ($marcas as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro Modelo -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Modelo</label>
                    <select id="filterModelo" class="form-select form-select-sm" onchange="filtrarTabla()">
                        <option value="">-- Todos --</option>
                        <?php foreach ($modelos as $mod): ?>
                            <option value="<?= htmlspecialchars($mod) ?>"><?= htmlspecialchars($mod) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro Código Patrimonial -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Código Patrimonial</label>
                    <select id="filterCodigo" class="form-select form-select-sm" onchange="filtrarTabla()">
                        <option value="">-- Todos --</option>
                        <?php foreach ($codigos as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Botón Limpiar -->
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-secondary btn-sm w-100" onclick="limpiarFiltros()">
                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="no-print text-center">Acciones</th>
                            <th>Sede</th>
                            <th>Tipo Bien</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Código Patrimonial</th>
                            <th>Inv 2020</th>
                            <th>Inv 2021</th>
                            <th>Inv 2022</th>
                            <th>Inv 2023</th>
                            <th>Inv 2025</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEquipos">
                        <?php if (empty($equipos)): ?>
                            <tr><td colspan="12" class="text-center py-3">No hay equipos registrados en desuso.</td></tr>
                        <?php else: ?>
                            <?php foreach ($equipos as $eq): ?>
                            <tr>
                                <td class="no-print text-center">
                                    <button class="btn btn-sm btn-warning fw-bold" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditar" 
                                            onclick='cargarDatosEdicion(<?= json_encode($eq, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        ✏️ Editar
                                    </button>
                                </td>
                                <td><?= htmlspecialchars($eq['sede'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['tipo_bien'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['marca'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['modelo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['codigo_patrimonial'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2020'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2021'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2022'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2023'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2025'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['observaciones'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Registro -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="desuso.php">
        <input type="hidden" name="action" value="guardar">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Registrar Equipo en Desuso</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Sede</label><input type="text" name="sede" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Tipo Bien</label><input type="text" name="tipo_bien" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Marca</label><input type="text" name="marca" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Modelo</label><input type="text" name="modelo" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Código Patrimonial</label><input type="text" name="codigo_patrimonial" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2020</label><input type="text" name="inv_2020" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2021</label><input type="text" name="inv_2021" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2022</label><input type="text" name="inv_2022" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Inv 2023</label><input type="text" name="inv_2023" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Inv 2025</label><input type="text" name="inv_2025" class="form-control"></div>
            <div class="col-12"><label class="form-label">Observación</label><textarea name="observaciones" class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Registro</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar Registro -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="desuso.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Editar Registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Sede</label><input type="text" name="sede" id="edit_sede" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Tipo Bien</label><input type="text" name="tipo_bien" id="edit_tipo_bien" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Marca</label><input type="text" name="marca" id="edit_marca" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Modelo</label><input type="text" name="modelo" id="edit_modelo" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Código Patrimonial</label><input type="text" name="codigo_patrimonial" id="edit_codigo_patrimonial" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2020</label><input type="text" name="inv_2020" id="edit_inv_2020" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2021</label><input type="text" name="inv_2021" id="edit_inv_2021" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2022</label><input type="text" name="inv_2022" id="edit_inv_2022" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Inv 2023</label><input type="text" name="inv_2023" id="edit_inv_2023" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Inv 2025</label><input type="text" name="inv_2025" id="edit_inv_2025" class="form-control"></div>
            <div class="col-12"><label class="form-label">Observación</label><textarea name="observaciones" id="edit_observaciones" class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning">Actualizar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Importar CSV -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="desuso.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="importar_csv">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Importar CSV</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Seleccione el archivo CSV</label>
            <input type="file" name="archivo_excel" class="form-control" accept=".csv" required>
            <div class="form-text text-muted mt-2">
                <strong>📌 Columnas requeridas en el CSV (11 campos):</strong>
                <br><small class="text-dark"><strong>1. sede | 2. tipo_bien | 3. marca | 4. modelo | 5. codigo_patrimonial | 6. inv_2020 | 7. inv_2021 | 8. inv_2022 | 9. inv_2023 | 10. inv_2025 | 11. observaciones</strong></small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Procesar Archivo CSV</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function cargarDatosEdicion(eq) {
    document.getElementById('edit_id').value = eq.id || '';
    document.getElementById('edit_sede').value = eq.sede || '';
    document.getElementById('edit_tipo_bien').value = eq.tipo_bien || '';
    document.getElementById('edit_marca').value = eq.marca || '';
    document.getElementById('edit_modelo').value = eq.modelo || '';
    document.getElementById('edit_codigo_patrimonial').value = eq.codigo_patrimonial || '';
    document.getElementById('edit_inv_2020').value = eq.inv_2020 || '';
    document.getElementById('edit_inv_2021').value = eq.inv_2021 || '';
    document.getElementById('edit_inv_2022').value = eq.inv_2022 || '';
    document.getElementById('edit_inv_2023').value = eq.inv_2023 || '';
    document.getElementById('edit_inv_2025').value = eq.inv_2025 || '';
    document.getElementById('edit_observaciones').value = eq.observaciones || '';
}

function filtrarTabla() {
    const texto = document.getElementById('inputBuscador').value.toLowerCase().trim();
    const valSede = document.getElementById('filterSede').value.toLowerCase().trim();
    const valTipo = document.getElementById('filterTipo').value.toLowerCase().trim();
    const valMarca = document.getElementById('filterMarca').value.toLowerCase().trim();
    const valModelo = document.getElementById('filterModelo').value.toLowerCase().trim();
    const valCodigo = document.getElementById('filterCodigo').value.toLowerCase().trim();

    const filas = document.querySelectorAll('#tablaEquipos tr');

    filas.forEach(fila => {
        if (fila.cells.length <= 1) return;

        const sede = fila.cells[1] ? fila.cells[1].textContent.toLowerCase().trim() : '';
        const tipoBien = fila.cells[2] ? fila.cells[2].textContent.toLowerCase().trim() : '';
        const marca = fila.cells[3] ? fila.cells[3].textContent.toLowerCase().trim() : '';
        const modelo = fila.cells[4] ? fila.cells[4].textContent.toLowerCase().trim() : '';
        const codPatrimonial = fila.cells[5] ? fila.cells[5].textContent.toLowerCase().trim() : '';
        const restoFila = fila.textContent.toLowerCase();

        // Validaciones por columna individual
        const matchSede = valSede === '' || sede === valSede;
        const matchTipo = valTipo === '' || tipoBien === valTipo;
        const matchMarca = valMarca === '' || marca === valMarca;
        const matchModelo = valModelo === '' || modelo === valModelo;
        const matchCodigo = valCodigo === '' || codPatrimonial === valCodigo;
        
        // Validación de búsqueda rápida
        const matchTexto = texto === '' || restoFila.includes(texto);

        // Cumplir todos los filtros simultáneamente
        if (matchSede && matchTipo && matchMarca && matchModelo && matchCodigo && matchTexto) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}

function limpiarFiltros() {
    document.getElementById('inputBuscador').value = '';
    document.getElementById('filterSede').value = '';
    document.getElementById('filterTipo').value = '';
    document.getElementById('filterMarca').value = '';
    document.getElementById('filterModelo').value = '';
    document.getElementById('filterCodigo').value = '';
    filtrarTabla();
}
</script>

<?php require_once '../includes/footer.php'; ?>