<?php
// Configurar límites dinámicamente
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');

// CONEXIÓN A LA BASE DE DATOS
$host = 'localhost';
$db   = 'bd_invmuni'; // Reemplaza por el nombre de tu BD[cite: 5]
$user = 'root';                        // Reemplaza por tu usuario de MySQL[cite: 5]
$pass = '';                            // Reemplaza por tu contraseña de MySQL[cite: 5]
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Error en la conexión a la base de datos: " . $e->getMessage());
}

require_once '../includes/header.php';

$mensaje = '';
$tipo_alerta = '';

// 1. PROCESAR GUARDADO (NUEVO REGISTRO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar') {
    try {
        $sql = "INSERT INTO inventario_perifericos (
            sede, area, usuario_responsable, tipo_bien, marca, modelo,
            serie, estado, ip, condicion, codigo_patrimonial, inventario_2021,
            inventario_2022, inventario_2023, inventario_2024, inventario_2025,
            observacion, mac
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['sede'] ?? '', $_POST['area'] ?? '', $_POST['usuario_responsable'] ?? '',
            $_POST['tipo_bien'] ?? '', $_POST['marca'] ?? '', $_POST['modelo'] ?? '', $_POST['serie'] ?? '',
            $_POST['estado'] ?? '', $_POST['ip'] ?? '', $_POST['condicion'] ?? '', $_POST['codigo_patrimonial'] ?? '',
            $_POST['inventario_2021'] ?? '', $_POST['inventario_2022'] ?? '', $_POST['inventario_2023'] ?? '',
            $_POST['inventario_2024'] ?? '', $_POST['inventario_2025'] ?? '', $_POST['observacion'] ?? '',
            $_POST['mac'] ?? ''
        ]);
        header("Location: perifericos.php?msg=guardado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 2. PROCESAR EDICIÓN DE REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    try {
        $sql = "UPDATE inventario_perifericos SET
            sede = ?, area = ?, usuario_responsable = ?, tipo_bien = ?, 
            marca = ?, modelo = ?, serie = ?, estado = ?, ip = ?, condicion = ?, 
            codigo_patrimonial = ?, inventario_2021 = ?, inventario_2022 = ?, 
            inventario_2023 = ?, inventario_2024 = ?, inventario_2025 = ?, 
            observacion = ?, mac = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['sede'] ?? '', $_POST['area'] ?? '', $_POST['usuario_responsable'] ?? '',
            $_POST['tipo_bien'] ?? '', $_POST['marca'] ?? '', $_POST['modelo'] ?? '', $_POST['serie'] ?? '',
            $_POST['estado'] ?? '', $_POST['ip'] ?? '', $_POST['condicion'] ?? '', $_POST['codigo_patrimonial'] ?? '',
            $_POST['inventario_2021'] ?? '', $_POST['inventario_2022'] ?? '', $_POST['inventario_2023'] ?? '',
            $_POST['inventario_2024'] ?? '', $_POST['inventario_2025'] ?? '', $_POST['observacion'] ?? '',
            $_POST['mac'] ?? '', $_POST['id']
        ]);
        header("Location: perifericos.php?msg=editado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 3. PROCESAR IMPORTACIÓN CSV/EXCEL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'importar_csv') {
    if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] == 0) {
        $fileName = $_FILES['archivo_excel']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $mensaje = "Error: Por favor, guarde su Excel como 'CSV (delimitado por comas)' antes de subirlo.";
            $tipo_alerta = "danger";
        } else {
            $file = $_FILES['archivo_excel']['tmp_name'];
            $handle = fopen($file, "r");
            
            // Detectar automáticamente el separador (coma o punto y coma)
            $firstLine = fgets($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            rewind($handle);

            $count = 0;
            
            // Omitir la primera fila (encabezado)
            fgetcsv($handle, 4096, $delimiter);

            $sql = "INSERT INTO inventario_perifericos (
                sede, area, usuario_responsable, tipo_bien, marca, modelo,
                serie, estado, ip, condicion, codigo_patrimonial, inventario_2021,
                inventario_2022, inventario_2023, inventario_2024, inventario_2025,
                observacion, mac
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);

            $pdo->beginTransaction();
            try {
                while (($data = fgetcsv($handle, 4096, $delimiter)) !== FALSE) {
                    if (empty(array_filter($data))) continue; 

                    // Normalizar a exactamente 18 elementos rellenos con cadena vacía
                    $params = array_pad(array_slice($data, 0, 18), 18, '');

                    // Convertir codificación a UTF-8
                    $params = array_map(function($field) {
                        return mb_convert_encoding(trim($field), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
                    }, $params);

                    $stmt->execute($params);
                    $count++;
                }
                $pdo->commit();
                fclose($handle);
                header("Location: perifericos.php?msg=importado&cant=$count");
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

// Control de mensajes por URL
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'guardado') { $mensaje = "Periférico registrado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'editado') { $mensaje = "Registro actualizado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'importado') { $cant = $_GET['cant'] ?? 0; $mensaje = "Se importaron $cant registros correctamente."; $tipo_alerta = "success"; }
}

// 4. FILTROS DE BÚSQUEDA
$f_sede = $_GET['f_sede'] ?? '';
$f_area = $_GET['f_area'] ?? '';
$f_usuario = $_GET['f_usuario'] ?? '';
$f_codigo = $_GET['f_codigo'] ?? '';

// Obtener valores únicos para las listas desplegables
$sedes = $pdo->query("SELECT DISTINCT sede FROM inventario_perifericos WHERE sede IS NOT NULL AND sede != '' ORDER BY sede")->fetchAll(PDO::FETCH_COLUMN);
$areas = $pdo->query("SELECT DISTINCT area FROM inventario_perifericos WHERE area IS NOT NULL AND area != '' ORDER BY area")->fetchAll(PDO::FETCH_COLUMN);
$usuarios = $pdo->query("SELECT DISTINCT usuario_responsable FROM inventario_perifericos WHERE usuario_responsable IS NOT NULL AND usuario_responsable != '' ORDER BY usuario_responsable")->fetchAll(PDO::FETCH_COLUMN);

// Construir consulta filtrada
$where = [];
$params = [];

if (!empty($f_sede)) {
    $where[] = "sede = ?";
    $params[] = $f_sede;
}
if (!empty($f_area)) {
    $where[] = "area = ?";
    $params[] = $f_area;
}
if (!empty($f_usuario)) {
    $where[] = "usuario_responsable = ?";
    $params[] = $f_usuario;
}
if (!empty($f_codigo)) {
    $where[] = "codigo_patrimonial LIKE ?";
    $params[] = "%$f_codigo%";
}

$sql_select = "SELECT * FROM inventario_perifericos";
if (!empty($where)) {
    $sql_select .= " WHERE " . implode(" AND ", $where);
}
$sql_select .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql_select);
$stmt->execute($params);
$perifericos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Estilos para vista de impresión / PDF -->
<style>
@media print {
    .no-print, .btn, nav, header, footer, .modal { display: none !important; }
    body { background-color: #fff !important; font-size: 8pt; }
    .card { border: none !important; box-shadow: none !important; }
    .table-responsive { overflow: visible !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #333 !important; padding: 3px !important; white-space: normal !important; }
    .print-header { display: block !important; text-align: center; margin-bottom: 15px; }
}
.print-header { display: none; }
</style>

<div class="container-fluid px-3">
    <!-- Encabezado de impresión -->
    <div class="print-header">
        <h2>Municipalidad Distrital de Pucusana</h2>
        <h3>Subgerencia de Tecnologías de la Información</h3>
        <h4>Inventario de Periféricos</h4>
        <hr>
    </div>

    <!-- Barra de acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2><i class="bi bi-keyboard"></i> Inventario de Periféricos</h2>
        <div>
            <button class="btn btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImportar">
                <i class="bi bi-file-earmark-excel"></i> Importar CSV/Excel
            </button>
            <button class="btn btn-danger me-1" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf"></i> Exportar a PDF / Imprimir
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                <i class="bi bi-plus-circle"></i> Nuevo Periférico
            </button>
        </div>
    </div>

    <!-- Notificaciones -->
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show no-print" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- PANEL DE FILTROS -->
    <div class="card mb-3 shadow-sm border-0 no-print">
        <div class="card-body bg-light">
            <form method="GET" action="perifericos.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold mb-1">Sede</label>
                    <select name="f_sede" class="form-select form-select-sm">
                        <option value="">-- Todas las Sedes --</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= $f_sede === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold mb-1">Área</label>
                    <select name="f_area" class="form-select form-select-sm">
                        <option value="">-- Todas las Áreas --</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= $f_area === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold mb-1">Usuario Resp.</label>
                    <select name="f_usuario" class="form-select form-select-sm">
                        <option value="">-- Todos los Usuarios --</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= htmlspecialchars($u) ?>" <?= $f_usuario === $u ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold mb-1">Cód. Patrimonial</label>
                    <input type="text" name="f_codigo" class="form-control form-select-sm" value="<?= htmlspecialchars($f_codigo) ?>" placeholder="Buscar código...">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100" title="Buscar"><i class="bi bi-search"></i></button>
                    <a href="perifericos.php" class="btn btn-secondary btn-sm" title="Limpiar"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="no-print text-center">Acciones</th>
                            <th>Encargado</th>
                            <th>Área</th>
                            <th>Usuario Resp.</th>
                            <th>Tipo Bien</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Serie</th>
                            <th>Estado</th>
                            <th>IP</th>
                            <th>MAC</th>
                            <th>Condición</th>
                            <th>Cód. Patrimonial</th>
                            <th>Inv 2021</th>
                            <th>Inv 2022</th>
                            <th>Inv 2023</th>
                            <th>Inv 2024</th>
                            <th>Inv 2025</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($perifericos)): ?>
                            <tr><td colspan="19" class="text-center py-3">No hay periféricos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($perifericos as $per): ?>
                            <tr>
                                <td class="no-print text-center">
                                    <button class="btn btn-sm btn-warning fw-bold" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditar" 
                                            onclick='cargarDatosEdicion(<?= json_encode($per, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        ✏️ Editar
                                    </button>
                                </td>
                                <td><?= htmlspecialchars($per['sede'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['area'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['usuario_responsable'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['tipo_bien'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['marca'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['modelo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['serie'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['estado'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['ip'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['mac'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-<?= ($per['condicion'] == 'Bueno' ? 'success' : ($per['condicion'] == 'Regular' ? 'warning' : 'danger')) ?>">
                                        <?= htmlspecialchars($per['condicion'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($per['codigo_patrimonial'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['inventario_2021'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['inventario_2022'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['inventario_2023'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['inventario_2024'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['inventario_2025'] ?? '') ?></td>
                                <td><?= htmlspecialchars($per['observacion'] ?? '') ?></td>
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
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="POST" action="perifericos.php">
        <input type="hidden" name="action" value="guardar">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Registrar Periférico</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Sede</label><input type="text" name="sede" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Área</label><input type="text" name="area" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Usuario Responsable</label><input type="text" name="usuario_responsable" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Tipo de Bien</label><input type="text" name="tipo_bien" class="form-control" placeholder="Ej. Mouse, Teclado, Monitor, Lector"></div>
            <div class="col-md-3"><label class="form-label">Marca</label><input type="text" name="marca" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Modelo</label><input type="text" name="modelo" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Serie</label><input type="text" name="serie" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Estado</label><input type="text" name="estado" class="form-control" placeholder="Ej. Operativo, Inoperativo"></div>
            <div class="col-md-3"><label class="form-label">IP</label><input type="text" name="ip" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">MAC</label><input type="text" name="mac" class="form-control"></div>
            <div class="col-md-3">
              <label class="form-label">Condición</label>
              <select name="condicion" class="form-select">
                <option value="Bueno">Bueno</option>
                <option value="Regular">Regular</option>
                <option value="Malo">Malo</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Código Patrimonial</label><input type="text" name="codigo_patrimonial" class="form-control"></div>

            <div class="col-12"><hr><h6 class="fw-bold">Histórico de Inventarios</h6></div>
            <div class="col-md-2"><label class="form-label">Inv 2021</label><input type="text" name="inventario_2021" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2022</label><input type="text" name="inventario_2022" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2023</label><input type="text" name="inventario_2023" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2024</label><input type="text" name="inventario_2024" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2025</label><input type="text" name="inventario_2025" class="form-control"></div>

            <div class="col-12"><label class="form-label">Observación</label><textarea name="observacion" class="form-control" rows="2"></textarea></div>
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
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="POST" action="perifericos.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Editar Registro de Periférico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Sede</label><input type="text" name="sede" id="edit_sede" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Área</label><input type="text" name="area" id="edit_area" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Usuario Responsable</label><input type="text" name="usuario_responsable" id="edit_usuario_responsable" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Tipo de Bien</label><input type="text" name="tipo_bien" id="edit_tipo_bien" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Marca</label><input type="text" name="marca" id="edit_marca" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Modelo</label><input type="text" name="modelo" id="edit_modelo" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Serie</label><input type="text" name="serie" id="edit_serie" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Estado</label><input type="text" name="estado" id="edit_estado" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">IP</label><input type="text" name="ip" id="edit_ip" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">MAC</label><input type="text" name="mac" id="edit_mac" class="form-control"></div>
            <div class="col-md-3">
              <label class="form-label">Condición</label>
              <select name="condicion" id="edit_condicion" class="form-select">
                <option value="Bueno">Bueno</option>
                <option value="Regular">Regular</option>
                <option value="Malo">Malo</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Código Patrimonial</label><input type="text" name="codigo_patrimonial" id="edit_codigo_patrimonial" class="form-control"></div>

            <div class="col-12"><hr><h6 class="fw-bold">Histórico de Inventarios</h6></div>
            <div class="col-md-2"><label class="form-label">Inv 2021</label><input type="text" name="inventario_2021" id="edit_inventario_2021" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2022</label><input type="text" name="inventario_2022" id="edit_inventario_2022" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2023</label><input type="text" name="inventario_2023" id="edit_inventario_2023" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2024</label><input type="text" name="inventario_2024" id="edit_inventario_2024" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2025</label><input type="text" name="inventario_2025" id="edit_inventario_2025" class="form-control"></div>

            <div class="col-12"><label class="form-label">Observación</label><textarea name="observacion" id="edit_observacion" class="form-control" rows="2"></textarea></div>
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

<!-- Modal Importar CSV/Excel -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="perifericos.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="importar_csv">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Importar Archivo CSV</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Seleccione el archivo CSV</label>
            <input type="file" name="archivo_excel" class="form-control" accept=".csv" required>
            <div class="form-text text-muted mt-2">
                <strong>📌 Indicaciones de importación:</strong>
                <ul>
                    <li>Guarde su libro de Excel como <code>CSV (delimitado por comas) (*.csv)</code>.</li>
                    <li>Las 18 columnas en su Excel/CSV deben ir exactamente en este orden (omitiendo la columna 'id'):
                    <br><small class="text-dark"><strong>sede, area, usuario_responsable, tipo_bien, marca, modelo, serie, estado, ip, condicion, codigo_patrimonial, inventario_2021, inventario_2022, inventario_2023, inventario_2024, inventario_2025, observacion, mac</strong></small></li>
                    <li>La primera fila (encabezado) será ignorada automáticamente.</li>
                </ul>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Procesar Archivo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Carga los datos de la fila dentro del Modal de Edición
function cargarDatosEdicion(per) {
    document.getElementById('edit_id').value = per.id || '';
    document.getElementById('edit_sede').value = per.sede || '';
    document.getElementById('edit_area').value = per.area || '';
    document.getElementById('edit_usuario_responsable').value = per.usuario_responsable || '';
    document.getElementById('edit_tipo_bien').value = per.tipo_bien || '';
    document.getElementById('edit_marca').value = per.marca || '';
    document.getElementById('edit_modelo').value = per.modelo || '';
    document.getElementById('edit_serie').value = per.serie || '';
    document.getElementById('edit_estado').value = per.estado || '';
    document.getElementById('edit_ip').value = per.ip || '';
    document.getElementById('edit_mac').value = per.mac || '';
    document.getElementById('edit_condicion').value = per.condicion || 'Bueno';
    document.getElementById('edit_codigo_patrimonial').value = per.codigo_patrimonial || '';
    document.getElementById('edit_inventario_2021').value = per.inventario_2021 || '';
    document.getElementById('edit_inventario_2022').value = per.inventario_2022 || '';
    document.getElementById('edit_inventario_2023').value = per.inventario_2023 || '';
    document.getElementById('edit_inventario_2024').value = per.inventario_2024 || '';
    document.getElementById('edit_inventario_2025').value = per.inventario_2025 || '';
    document.getElementById('edit_observacion').value = per.observacion || '';
}
</script>

<?php require_once '../includes/footer.php'; ?>