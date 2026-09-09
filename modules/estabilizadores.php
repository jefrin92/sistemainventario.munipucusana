<?php
// Configurar límites de rendimiento para la carga de archivos pesados
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');

require_once '../includes/header.php';
require_once '../config/db.php';

$mensaje = '';
$tipo_alerta = '';

// 1. PROCESAR GUARDADO (NUEVO REGISTRO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar') {
    try {
        $sql = "INSERT INTO inventario_estabilizadores (
            gerencia_subgerencia, tipo_bien, modelo, codigo_patrimonial,
            inventario_2020, inventario_2021, inventario_2022, inventario_2023, inventario_2025
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['gerencia_subgerencia'] ?? '',
            $_POST['tipo_bien'] ?? '',
            $_POST['modelo'] ?? '',
            $_POST['codigo_patrimonial'] ?? '',
            $_POST['inventario_2020'] ?? '',
            $_POST['inventario_2021'] ?? '',
            $_POST['inventario_2022'] ?? '',
            $_POST['inventario_2023'] ?? '',
            $_POST['inventario_2025'] ?? ''
        ]);
        header("Location: estabilizadores.php?msg=guardado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 2. PROCESAR EDICIÓN DE REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    try {
        $sql = "UPDATE inventario_estabilizadores SET
            gerencia_subgerencia = ?, tipo_bien = ?, modelo = ?, codigo_patrimonial = ?,
            inventario_2020 = ?, inventario_2021 = ?, inventario_2022 = ?, 
            inventario_2023 = ?, inventario_2025 = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['gerencia_subgerencia'] ?? '',
            $_POST['tipo_bien'] ?? '',
            $_POST['modelo'] ?? '',
            $_POST['codigo_patrimonial'] ?? '',
            $_POST['inventario_2020'] ?? '',
            $_POST['inventario_2021'] ?? '',
            $_POST['inventario_2022'] ?? '',
            $_POST['inventario_2023'] ?? '',
            $_POST['inventario_2025'] ?? '',
            $_POST['id']
        ]);
        header("Location: estabilizadores.php?msg=editado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 3. PROCESAR IMPORTACIÓN CSV / EXCEL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'importar_csv') {
    if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] == 0) {
        $fileName = $_FILES['archivo_excel']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $mensaje = "Error: Por favor, guarde su libro de Excel como 'CSV (delimitado por comas) (*.csv)' antes de subirlo.";
            $tipo_alerta = "danger";
        } else {
            $file = $_FILES['archivo_excel']['tmp_name'];
            $handle = fopen($file, "r");

            // Autodetectar delimitador (coma ',' o punto y coma ';')
            $firstLine = fgets($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            rewind($handle);

            $count = 0;

            // Omitir la fila de encabezado
            fgetcsv($handle, 4096, $delimiter);

            $sql = "INSERT INTO inventario_estabilizadores (
                gerencia_subgerencia, tipo_bien, modelo, codigo_patrimonial, 
                inventario_2020, inventario_2021, inventario_2022, inventario_2023, inventario_2025
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);

            $pdo->beginTransaction();
            try {
                while (($data = fgetcsv($handle, 4096, $delimiter)) !== FALSE) {
                    if (empty(array_filter($data))) continue;

                    // Ajustar a las 9 columnas requeridas completando con vacíos si es necesario
                    $params = array_pad(array_slice($data, 0, 9), 9, '');

                    // Convertir codificación a UTF-8 para evitar errores de caracteres especiales
                    $params = array_map(function($field) {
                        return mb_convert_encoding(trim($field), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
                    }, $params);

                    $stmt->execute($params);
                    $count++;
                }
                $pdo->commit();
                fclose($handle);
                header("Location: estabilizadores.php?msg=importado&cant=$count");
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

// Control de mensajes de respuesta
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'guardado') { $mensaje = "Estabilizador registrado correctamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'editado') { $mensaje = "Registro actualizado correctamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'importado') { $cant = $_GET['cant'] ?? 0; $mensaje = "Se importaron $cant registros correctamente."; $tipo_alerta = "success"; }
}

// Consultar registros
$stmt = $pdo->query("SELECT * FROM inventario_estabilizadores ORDER BY id DESC");
$estabilizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Estilos para vista de impresión / exportación a PDF -->
<style>
@media print {
    .no-print, .btn, nav, header, footer, .modal { display: none !important; }
    body { background-color: #fff !important; font-size: 9pt; }
    .card { border: none !important; box-shadow: none !important; }
    .table-responsive { overflow: visible !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #333 !important; padding: 4px !important; white-space: normal !important; }
    .print-header { display: block !important; text-align: center; margin-bottom: 15px; }
}
.print-header { display: none; }
</style>

<div class="container-fluid px-3">
    <!-- Encabezado para impresión -->
    <div class="print-header">
        <h2>Municipalidad Distrital de Pucusana</h2>
        <h3>Subgerencia de Tecnologías de la Información</h3>
        <h4>Inventario de Estabilizadores</h4>
        <hr>
    </div>

    <!-- Barra de acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2><i class="bi bi-lightning-charge"></i> Inventario de Estabilizadores</h2>
        <div>
            <button class="btn btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImportar">
                <i class="bi bi-file-earmark-excel"></i> Importar CSV/Excel
            </button>
            <button class="btn btn-danger me-1" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf"></i> Exportar a PDF / Imprimir
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                <i class="bi bi-plus-circle"></i> Nuevo Estabilizador
            </button>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show no-print" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de Resultados -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="no-print text-center">Acciones</th>
                            <th>Gerencia/Subgerencia</th>
                            <th>Tipo Bien</th>
                            <th>Modelo</th>
                            <th>Cód. Patrimonial</th>
                            <th>Inv 2020</th>
                            <th>Inv 2021</th>
                            <th>Inv 2022</th>
                            <th>Inv 2023</th>
                            <th>Inv 2025</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($estabilizadores)): ?>
                            <tr><td colspan="10" class="text-center py-3">No hay estabilizadores registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($estabilizadores as $est): ?>
                            <tr>
                                <td class="no-print text-center">
                                    <button class="btn btn-sm btn-warning fw-bold" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditar" 
                                            onclick='cargarDatosEdicion(<?= json_encode($est, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        ✏️ Editar
                                    </button>
                                </td>
                                <td><?= htmlspecialchars($est['gerencia_subgerencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['tipo_bien'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['modelo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['codigo_patrimonial'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['inventario_2020'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['inventario_2021'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['inventario_2022'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['inventario_2023'] ?? '') ?></td>
                                <td><?= htmlspecialchars($est['inventario_2025'] ?? '') ?></td>
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
      <form method="POST" action="estabilizadores.php">
        <input type="hidden" name="action" value="guardar">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Registrar Estabilizador</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Gerencia / Subgerencia</label>
              <input type="text" name="gerencia_subgerencia" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo de Bien</label>
              <input type="text" name="tipo_bien" class="form-control" placeholder="Ej. Estabilizador de Voltaje / UPS">
            </div>
            <div class="col-md-6">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Código Patrimonial</label>
              <input type="text" name="codigo_patrimonial" class="form-control">
            </div>

            <!-- Trazabilidad Histórica de Inventarios -->
            <div class="col-12"><hr><h6 class="fw-bold">Histórico de Inventarios</h6></div>
            <div class="col-md-4">
              <label class="form-label">Inventario 2020</label>
              <input type="text" name="inventario_2020" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Inventario 2021</label>
              <input type="text" name="inventario_2021" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Inventario 2022</label>
              <input type="text" name="inventario_2022" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Inventario 2023</label>
              <input type="text" name="inventario_2023" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Inventario 2025</label>
              <input type="text" name="inventario_2025" class="form-control">
            </div>
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
      <form method="POST" action="estabilizadores.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Editar Registro de Estabilizador</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Gerencia / Subgerencia</label>
              <input type="text" name="gerencia_subgerencia" id="edit_gerencia_subgerencia" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo de Bien</label>
              <input type="text" name="tipo_bien" id="edit_tipo_bien" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" id="edit_modelo" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Código Patrimonial</label>
              <input type="text" name="codigo_patrimonial" id="edit_codigo_patrimonial" class="form-control">
            </div>

            <!-- Trazabilidad Histórica de Inventarios -->
            <div class="col-12"><hr><h6 class="fw-bold">Histórico de Inventarios</h6></div>
            <div class="col-md-4">
              <label class="form-label">Inventario 2020</label>
              <input type="text" name="inventario_2020" id="edit_inventario_2020" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Inventario 2021</label>
              <input type="text" name="inventario_2021" id="edit_inventario_2021" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Inventario 2022</label>
              <input type="text" name="inventario_2022" id="edit_inventario_2022" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Inventario 2023</label>
              <input type="text" name="inventario_2023" id="edit_inventario_2023" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Inventario 2025</label>
              <input type="text" name="inventario_2025" id="edit_inventario_2025" class="form-control">
            </div>
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
      <form method="POST" action="estabilizadores.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="importar_csv">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Importar Archivo CSV / Excel</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Seleccione el archivo CSV</label>
            <input type="file" name="archivo_excel" class="form-control" accept=".csv" required>
            <div class="form-text text-muted mt-2">
                <strong>📌 Indicaciones para la importación:</strong>
                <ul>
                    <li>Guarde previamente su archivo Excel como <code>CSV (delimitado por comas) (*.csv)</code>.</li>
                    <li>Las 9 columnas del archivo deben mantener exactamente el siguiente orden (sin incluir el campo 'id'):
                    <br><small class="text-dark"><strong>Gerencia/Subgerencia, Tipo Bien, Modelo, Cód. Patrimonial, Inv 2020, Inv 2021, Inv 2022, Inv 2023, Inv 2025</strong></small></li>
                    <li>La primera fila (encabezados) no será importada a la base de datos.</li>
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
// Transfiere los datos de la fila seleccionada al Modal de Edición
function cargarDatosEdicion(est) {
    document.getElementById('edit_id').value = est.id || '';
    document.getElementById('edit_gerencia_subgerencia').value = est.gerencia_subgerencia || '';
    document.getElementById('edit_tipo_bien').value = est.tipo_bien || '';
    document.getElementById('edit_modelo').value = est.modelo || '';
    document.getElementById('edit_codigo_patrimonial').value = est.codigo_patrimonial || '';
    document.getElementById('edit_inventario_2020').value = est.inventario_2020 || '';
    document.getElementById('edit_inventario_2021').value = est.inventario_2021 || '';
    document.getElementById('edit_inventario_2022').value = est.inventario_2022 || '';
    document.getElementById('edit_inventario_2023').value = est.inventario_2023 || '';
    document.getElementById('edit_inventario_2025').value = est.inventario_2025 || '';
}
</script>

<?php require_once '../includes/footer.php'; ?>