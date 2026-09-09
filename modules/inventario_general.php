<?php
// Configurar límites dinámicamente desde el propio script
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300'); // 5 minutos de tiempo límite
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');

require_once '../includes/header.php';
require_once '../config/db.php';

$mensaje = '';
$tipo_alerta = '';

// 1. PROCESAR GUARDADO (NUEVO REGISTRO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar') {
    try {
        $sql = "INSERT INTO inventario_general (
            sede, gerencia_subgerencia, tipo_bien, hostname, ip, usuario_responsable,
            disco_almacenamiento, procesador, condicion_equipo, repotenciado, ram, sistema_operativo,
            mac_address, conectividad, diagnostico, credencial, modelo_cpu, codigo_patrimonial,
            inv_2021, inv_2022, inv_2023, inv_2024, inv_2025, observaciones,
            area_origen, observaciones_2, situacion_actual, documento_sustento
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
       
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['sede'] ?? '', $_POST['gerencia_subgerencia'] ?? '', $_POST['tipo_bien'] ?? '', $_POST['hostname'] ?? '',
            $_POST['ip'] ?? '', $_POST['usuario_responsable'] ?? '', $_POST['disco_almacenamiento'] ?? '', $_POST['procesador'] ?? '',
            $_POST['condicion_equipo'] ?? '', $_POST['repotenciado'] ?? '', $_POST['ram'] ?? '', $_POST['sistema_operativo'] ?? '',
            $_POST['mac_address'] ?? '', $_POST['conectividad'] ?? '', $_POST['diagnostico'] ?? '', $_POST['credencial'] ?? '',
            $_POST['modelo_cpu'] ?? '', $_POST['codigo_patrimonial'] ?? '', $_POST['inv_2021'] ?? '', $_POST['inv_2022'] ?? '',
            $_POST['inv_2023'] ?? '', $_POST['inv_2024'] ?? '', $_POST['inv_2025'] ?? '', $_POST['observaciones'] ?? '',
            $_POST['area_origen'] ?? '', $_POST['observaciones_2'] ?? '', $_POST['situacion_actual'] ?? '', $_POST['documento_sustento'] ?? ''
        ]);
        header("Location: inventario_general.php?msg=guardado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 2. PROCESAR EDICIÓN DE REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    try {
        $sql = "UPDATE inventario_general SET
            sede = ?, gerencia_subgerencia = ?, tipo_bien = ?, hostname = ?, ip = ?,
            usuario_responsable = ?, disco_almacenamiento = ?, procesador = ?, condicion_equipo = ?,
            repotenciado = ?, ram = ?, sistema_operativo = ?, mac_address = ?, conectividad = ?,
            diagnostico = ?, credencial = ?, modelo_cpu = ?, codigo_patrimonial = ?,
            inv_2021 = ?, inv_2022 = ?, inv_2023 = ?, inv_2024 = ?, inv_2025 = ?,
            observaciones = ?, area_origen = ?, observaciones_2 = ?, situacion_actual = ?,
            documento_sustento = ?
            WHERE id = ?";
       
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['sede'] ?? '', $_POST['gerencia_subgerencia'] ?? '', $_POST['tipo_bien'] ?? '', $_POST['hostname'] ?? '',
            $_POST['ip'] ?? '', $_POST['usuario_responsable'] ?? '', $_POST['disco_almacenamiento'] ?? '', $_POST['procesador'] ?? '',
            $_POST['condicion_equipo'] ?? '', $_POST['repotenciado'] ?? '', $_POST['ram'] ?? '', $_POST['sistema_operativo'] ?? '',
            $_POST['mac_address'] ?? '', $_POST['conectividad'] ?? '', $_POST['diagnostico'] ?? '', $_POST['credencial'] ?? '',
            $_POST['modelo_cpu'] ?? '', $_POST['codigo_patrimonial'] ?? '', $_POST['inv_2021'] ?? '', $_POST['inv_2022'] ?? '',
            $_POST['inv_2023'] ?? '', $_POST['inv_2024'] ?? '', $_POST['inv_2025'] ?? '', $_POST['observaciones'] ?? '',
            $_POST['area_origen'] ?? '', $_POST['observaciones_2'] ?? '', $_POST['situacion_actual'] ?? '', $_POST['documento_sustento'] ?? '',
            $_POST['id']
        ]);
        header("Location: inventario_general.php?msg=editado");
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
           
            // Omitir la primera fila de cabecera
            fgetcsv($handle, 4096, $delimiter);

            $sql = "INSERT INTO inventario_general (
                sede, gerencia_subgerencia, tipo_bien, hostname, ip, usuario_responsable,
                disco_almacenamiento, procesador, condicion_equipo, repotenciado, ram,
                sistema_operativo, mac_address, conectividad, diagnostico, credencial,
                modelo_cpu, codigo_patrimonial, inv_2021, inv_2022, inv_2023, inv_2024,
                inv_2025, observaciones, area_origen, observaciones_2, situacion_actual, documento_sustento
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
           
            $stmt = $pdo->prepare($sql);

            $pdo->beginTransaction();
            try {
                while (($data = fgetcsv($handle, 4096, $delimiter)) !== FALSE) {
                    if (empty(array_filter($data))) continue;

                    $params = array_pad(array_slice($data, 0, 28), 28, '');

                    $params = array_map(function($field) {
                        return mb_convert_encoding(trim($field), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
                    }, $params);

                    $stmt->execute($params);
                    $count++;
                }
                $pdo->commit();
                fclose($handle);
                header("Location: inventario_general.php?msg=importado&cant=$count");
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
    if ($_GET['msg'] === 'guardado') { $mensaje = "Registro guardado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'editado') { $mensaje = "Registro actualizado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'importado') { $cant = $_GET['cant'] ?? 0; $mensaje = "Se importaron $cant registros correctamente."; $tipo_alerta = "success"; }
}

// ==========================================
// 4. LÓGICA DE FILTROS BÚSQUEDA
// ==========================================
$f_sede         = trim($_GET['f_sede'] ?? '');
$f_gerencia     = trim($_GET['f_gerencia'] ?? '');
$f_hostname     = trim($_GET['f_hostname'] ?? '');
$f_ip           = trim($_GET['f_ip'] ?? '');
$f_responsable  = trim($_GET['f_responsable'] ?? '');

$where_clauses = [];
$params_query = [];

if ($f_sede !== '') {
    $where_clauses[] = "sede LIKE ?";
    $params_query[] = "%$f_sede%";
}
if ($f_gerencia !== '') {
    $where_clauses[] = "gerencia_subgerencia LIKE ?";
    $params_query[] = "%$f_gerencia%";
}
if ($f_hostname !== '') {
    $where_clauses[] = "hostname LIKE ?";
    $params_query[] = "%$f_hostname%";
}
if ($f_ip !== '') {
    $where_clauses[] = "ip LIKE ?";
    $params_query[] = "%$f_ip%";
}
if ($f_responsable !== '') {
    $where_clauses[] = "usuario_responsable LIKE ?";
    $params_query[] = "%$f_responsable%";
}

$sql_select = "SELECT * FROM inventario_general";
if (!empty($where_clauses)) {
    $sql_select .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql_select .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql_select);
$stmt->execute($params_query);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Carga explícita de Bootstrap JS para asegurar el funcionamiento de los modales -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
    <div class="print-header">
        <h2>Municipalidad Distrital de Pucusana</h2>
        <h3>Subgerencia de Tecnologías de la Información</h3>
        <h4>Inventario General de Cómputo</h4>
        <hr>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2><i class="bi bi-laptop"></i> Inventario General de Cómputo</h2>
        <div>
            <button type="button" class="btn btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImportar">
                <i class="bi bi-file-earmark-excel"></i> Importar CSV/Excel
            </button>
            <button type="button" class="btn btn-danger me-1" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf"></i> Exportar a PDF / Imprimir
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
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

    <!-- PANEL DE FILTROS -->
    <div class="card shadow-sm border-0 mb-3 no-print">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-funnel"></i> Filtrar Inventario
        </div>
        <div class="card-body">
            <form method="GET" action="inventario_general.php" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Sede</label>
                    <input type="text" name="f_sede" class="form-control form-control-sm" placeholder="Buscar Sede..." value="<?= htmlspecialchars($f_sede) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Gerencia / Subgerencia</label>
                    <input type="text" name="f_gerencia" class="form-control form-control-sm" placeholder="Buscar área..." value="<?= htmlspecialchars($f_gerencia) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Hostname</label>
                    <input type="text" name="f_hostname" class="form-control form-control-sm" placeholder="Buscar Hostname..." value="<?= htmlspecialchars($f_hostname) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">IP</label>
                    <input type="text" name="f_ip" class="form-control form-control-sm" placeholder="Buscar IP..." value="<?= htmlspecialchars($f_ip) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Responsable</label>
                    <input type="text" name="f_responsable" class="form-control form-control-sm" placeholder="Buscar usuario..." value="<?= htmlspecialchars($f_responsable) ?>">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100" title="Filtrar">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="inventario_general.php" class="btn btn-sm btn-secondary w-100" title="Limpiar Filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="no-print text-center">Acciones</th>
                            <th>Sede</th>
                            <th>Gerencia/Subgerencia</th>
                            <th>Tipo Bien</th>
                            <th>Hostname</th>
                            <th>IP</th>
                            <th>Responsable</th>
                            <th>Disco</th>
                            <th>Procesador</th>
                            <th>Condición</th>
                            <th>Repotenciado</th>
                            <th>RAM</th>
                            <th>S.O.</th>
                            <th>MAC Address</th>
                            <th>Conectividad</th>
                            <th>Diagnóstico</th>
                            <th>Credencial</th>
                            <th>Modelo CPU</th>
                            <th>Cód. Patrimonial</th>
                            <th>Inv 2021</th>
                            <th>Inv 2022</th>
                            <th>Inv 2023</th>
                            <th>Inv 2024</th>
                            <th>Inv 2025</th>
                            <th>Observaciones</th>
                            <th>Área Origen</th>
                            <th>Observaciones 2</th>
                            <th>Situación Actual</th>
                            <th>Doc. Sustento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($equipos)): ?>
                        <tr>
                            <td colspan="29" class="text-center text-muted py-4">No se encontraron registros que coincidan con los filtros aplicados.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($equipos as $eq): ?>
                            <tr>
                                <td class="no-print text-center">
                                    <button type="button" class="btn btn-sm btn-warning fw-bold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditar"
                                            onclick='cargarDatosEdicion(<?= json_encode($eq, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        ✏️ Editar
                                    </button>
                                </td>
                                <td><?= htmlspecialchars($eq['sede'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['gerencia_subgerencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['tipo_bien'] ?? '') ?></td>
                                <td><strong><?= htmlspecialchars($eq['hostname'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($eq['ip'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['usuario_responsable'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['disco_almacenamiento'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['procesador'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-<?= ($eq['condicion_equipo'] == 'Bueno' ? 'success' : ($eq['condicion_equipo'] == 'Regular' ? 'warning' : 'danger')) ?>">
                                        <?= htmlspecialchars($eq['condicion_equipo'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($eq['repotenciado'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['ram'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['sistema_operativo'] ?? '') ?></td>
                                <td><small><?= htmlspecialchars($eq['mac_address'] ?? '') ?></small></td>
                                <td><?= htmlspecialchars($eq['conectividad'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['diagnostico'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['credencial'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['modelo_cpu'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['codigo_patrimonial'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2021'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2022'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2023'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2024'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['inv_2025'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['observaciones'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['area_origen'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['observaciones_2'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['situacion_actual'] ?? '') ?></td>
                                <td><?= htmlspecialchars($eq['documento_sustento'] ?? '') ?></td>
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
      <form method="POST" action="inventario_general.php">
        <input type="hidden" name="action" value="guardar">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Registrar Nuevo Equipo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Sede</label><input type="text" name="sede" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Gerencia / Subgerencia</label><input type="text" name="gerencia_subgerencia" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Tipo de Bien</label><input type="text" name="tipo_bien" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Hostname</label><input type="text" name="hostname" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Dirección IP</label><input type="text" name="ip" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Usuario Responsable</label><input type="text" name="usuario_responsable" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Disco Almacenamiento</label><input type="text" name="disco_almacenamiento" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Procesador</label><input type="text" name="procesador" class="form-control"></div>
            <div class="col-md-3">
              <label class="form-label">Condición del Equipo</label>
              <select name="condicion_equipo" class="form-select">
                <option value="Bueno">Bueno</option>
                <option value="Regular">Regular</option>
                <option value="Malo">Malo</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Repotenciado</label>
              <select name="repotenciado" class="form-select">
                <option value="No">No</option>
                <option value="Sí">Sí</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Memoria RAM</label><input type="text" name="ram" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Sistema Operativo</label><input type="text" name="sistema_operativo" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">MAC Address</label><input type="text" name="mac_address" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Conectividad</label><input type="text" name="conectividad" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Credencial</label><input type="text" name="credencial" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Modelo CPU</label><input type="text" name="modelo_cpu" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Código Patrimonial</label><input type="text" name="codigo_patrimonial" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Área Origen</label><input type="text" name="area_origen" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Situación Actual</label><input type="text" name="situacion_actual" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Documento Sustento</label><input type="text" name="documento_sustento" class="form-control"></div>

            <div class="col-12"><hr><h6 class="fw-bold">Histórico de Inventarios</h6></div>
            <div class="col-md-2"><label class="form-label">Inv 2021</label><input type="text" name="inv_2021" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2022</label><input type="text" name="inv_2022" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2023</label><input type="text" name="inv_2023" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2024</label><input type="text" name="inv_2024" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2025</label><input type="text" name="inv_2025" class="form-control"></div>

            <div class="col-12"><hr><h6 class="fw-bold">Observaciones y Diagnósticos</h6></div>
            <div class="col-md-4"><label class="form-label">Diagnóstico</label><textarea name="diagnostico" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">Observaciones 2</label><textarea name="observaciones_2" class="form-control" rows="2"></textarea></div>
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
      <form method="POST" action="inventario_general.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Editar Registro de Equipo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Sede</label><input type="text" name="sede" id="edit_sede" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Gerencia / Subgerencia</label><input type="text" name="gerencia_subgerencia" id="edit_gerencia_subgerencia" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Tipo de Bien</label><input type="text" name="tipo_bien" id="edit_tipo_bien" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Hostname</label><input type="text" name="hostname" id="edit_hostname" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Dirección IP</label><input type="text" name="ip" id="edit_ip" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Usuario Responsable</label><input type="text" name="usuario_responsable" id="edit_usuario_responsable" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Disco Almacenamiento</label><input type="text" name="disco_almacenamiento" id="edit_disco_almacenamiento" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Procesador</label><input type="text" name="procesador" id="edit_procesador" class="form-control"></div>
            <div class="col-md-3">
              <label class="form-label">Condición del Equipo</label>
              <select name="condicion_equipo" id="edit_condicion_equipo" class="form-select">
                <option value="Bueno">Bueno</option>
                <option value="Regular">Regular</option>
                <option value="Malo">Malo</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Repotenciado</label>
              <select name="repotenciado" id="edit_repotenciado" class="form-select">
                <option value="No">No</option>
                <option value="Sí">Sí</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Memoria RAM</label><input type="text" name="ram" id="edit_ram" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Sistema Operativo</label><input type="text" name="sistema_operativo" id="edit_sistema_operativo" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">MAC Address</label><input type="text" name="mac_address" id="edit_mac_address" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Conectividad</label><input type="text" name="conectividad" id="edit_conectividad" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Credencial</label><input type="text" name="credencial" id="edit_credencial" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Modelo CPU</label><input type="text" name="modelo_cpu" id="edit_modelo_cpu" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Código Patrimonial</label><input type="text" name="codigo_patrimonial" id="edit_codigo_patrimonial" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Área Origen</label><input type="text" name="area_origen" id="edit_area_origen" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Situación Actual</label><input type="text" name="situacion_actual" id="edit_situacion_actual" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Documento Sustento</label><input type="text" name="documento_sustento" id="edit_documento_sustento" class="form-control"></div>

            <div class="col-12"><hr><h6 class="fw-bold">Histórico de Inventarios</h6></div>
            <div class="col-md-2"><label class="form-label">Inv 2021</label><input type="text" name="inv_2021" id="edit_inv_2021" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2022</label><input type="text" name="inv_2022" id="edit_inv_2022" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2023</label><input type="text" name="inv_2023" id="edit_inv_2023" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2024</label><input type="text" name="inv_2024" id="edit_inv_2024" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Inv 2025</label><input type="text" name="inv_2025" id="edit_inv_2025" class="form-control"></div>

            <div class="col-12"><hr><h6 class="fw-bold">Observaciones y Diagnósticos</h6></div>
            <div class="col-md-4"><label class="form-label">Diagnóstico</label><textarea name="diagnostico" id="edit_diagnostico" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">Observaciones</label><textarea name="observaciones" id="edit_observaciones" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">Observaciones 2</label><textarea name="observaciones_2" id="edit_observaciones_2" class="form-control" rows="2"></textarea></div>
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
      <form method="POST" action="inventario_general.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="importar_csv">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Importar desde Excel / CSV</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Seleccione el archivo CSV</label>
            <input type="file" name="archivo_excel" class="form-control" accept=".csv" required>
            <div class="form-text text-muted mt-2">
                <strong>📌 Indicaciones importantes para importar:</strong>
                <ul>
                    <li>Guarde su libro de Excel como <code>CSV (delimitado por comas) (*.csv)</code>.</li>
                    <li>Las 28 columnas en su Excel/CSV deben ir estrictamente en este orden (omitir la columna 'id'):
                    <br><small class="text-dark"><strong>sede, gerencia_subgerencia, tipo_bien, hostname, ip, usuario_responsable, disco_almacenamiento, procesador, condicion_equipo, repotenciado, ram, sistema_operativo, mac_address, conectividad, diagnostico, credencial, modelo_cpu, codigo_patrimonial, inv_2021, inv_2022, inv_2023, inv_2024, inv_2025, observaciones, area_origen, observaciones_2, situacion_actual, documento_sustento</strong></small></li>
                    <li>Se omitirá automáticamente la primera fila (encabezados del Excel).</li>
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
// Pasa los datos de la fila al Modal de Edición
function cargarDatosEdicion(eq) {
    document.getElementById('edit_id').value = eq.id || '';
    document.getElementById('edit_sede').value = eq.sede || '';
    document.getElementById('edit_gerencia_subgerencia').value = eq.gerencia_subgerencia || '';
    document.getElementById('edit_tipo_bien').value = eq.tipo_bien || '';
    document.getElementById('edit_hostname').value = eq.hostname || '';
    document.getElementById('edit_ip').value = eq.ip || '';
    document.getElementById('edit_usuario_responsable').value = eq.usuario_responsable || '';
    document.getElementById('edit_disco_almacenamiento').value = eq.disco_almacenamiento || '';
    document.getElementById('edit_procesador').value = eq.procesador || '';
    document.getElementById('edit_condicion_equipo').value = eq.condicion_equipo || 'Bueno';
    document.getElementById('edit_repotenciado').value = eq.repotenciado || 'No';
    document.getElementById('edit_ram').value = eq.ram || '';
    document.getElementById('edit_sistema_operativo').value = eq.sistema_operativo || '';
    document.getElementById('edit_mac_address').value = eq.mac_address || '';
    document.getElementById('edit_conectividad').value = eq.conectividad || '';
    document.getElementById('edit_diagnostico').value = eq.diagnostico || '';
    document.getElementById('edit_credencial').value = eq.credencial || '';
    document.getElementById('edit_modelo_cpu').value = eq.modelo_cpu || '';
    document.getElementById('edit_codigo_patrimonial').value = eq.codigo_patrimonial || '';
    document.getElementById('edit_inv_2021').value = eq.inv_2021 || '';
    document.getElementById('edit_inv_2022').value = eq.inv_2022 || '';
    document.getElementById('edit_inv_2023').value = eq.inv_2023 || '';
    document.getElementById('edit_inv_2024').value = eq.inv_2024 || '';
    document.getElementById('edit_inv_2025').value = eq.inv_2025 || '';
    document.getElementById('edit_observaciones').value = eq.observaciones || '';
    document.getElementById('edit_area_origen').value = eq.area_origen || '';
    document.getElementById('edit_observaciones_2').value = eq.observaciones_2 || '';
    document.getElementById('edit_situacion_actual').value = eq.situacion_actual || '';
    document.getElementById('edit_documento_sustento').value = eq.documento_sustento || '';
}
</script>