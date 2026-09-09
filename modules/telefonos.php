<?php
// Configurar límites dinámicamente
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
        $sql = "INSERT INTO inventario_telefonos (
            gerencia_subgerencia, bien, marca, modelo, cod_interno, cod_patrimonial, inv_2025
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['gerencia_subgerencia'] ?? '',
            $_POST['bien'] ?? '',
            $_POST['marca'] ?? '',
            $_POST['modelo'] ?? '',
            $_POST['cod_interno'] ?? '',
            $_POST['cod_patrimonial'] ?? '',
            $_POST['inv_2025'] ?? ''
        ]);
        header("Location: telefonos.php?msg=guardado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// 2. PROCESAR EDICIÓN DE REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    try {
        $sql = "UPDATE inventario_telefonos SET
            gerencia_subgerencia = ?, bien = ?, marca = ?, modelo = ?, 
            cod_interno = ?, cod_patrimonial = ?, inv_2025 = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['gerencia_subgerencia'] ?? '',
            $_POST['bien'] ?? '',
            $_POST['marca'] ?? '',
            $_POST['modelo'] ?? '',
            $_POST['cod_interno'] ?? '',
            $_POST['cod_patrimonial'] ?? '',
            $_POST['inv_2025'] ?? '',
            $_POST['id']
        ]);
        header("Location: telefonos.php?msg=editado");
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

            // Detectar automáticamente el separador (coma ',' o punto y coma ';')
            $firstLine = fgets($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            rewind($handle);

            $count = 0;

            // Omitir la primera fila (encabezado)
            fgetcsv($handle, 4096, $delimiter);

            $sql = "INSERT INTO inventario_telefonos (
                gerencia_subgerencia, bien, marca, modelo, cod_interno, cod_patrimonial, inv_2025
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);

            $pdo->beginTransaction();
            try {
                while (($data = fgetcsv($handle, 4096, $delimiter)) !== FALSE) {
                    if (empty(array_filter($data))) continue;

                    // Normalizar a exactamente 7 columnas rellenando vacíos
                    $params = array_pad(array_slice($data, 0, 7), 7, '');

                    // Convertir codificación a UTF-8 para corregir tildes o caracteres especiales de Excel
                    $params = array_map(function($field) {
                        return mb_convert_encoding(trim($field), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
                    }, $params);

                    $stmt->execute($params);
                    $count++;
                }
                $pdo->commit();
                fclose($handle);
                header("Location: telefonos.php?msg=importado&cant=$count");
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
    if ($_GET['msg'] === 'guardado') { $mensaje = "Teléfono registrado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'editado') { $mensaje = "Registro actualizado exitosamente."; $tipo_alerta = "success"; }
    elseif ($_GET['msg'] === 'importado') { $cant = $_GET['cant'] ?? 0; $mensaje = "Se importaron $cant registros correctamente."; $tipo_alerta = "success"; }
}

// Consultar registros
$stmt = $pdo->query("SELECT * FROM inventario_telefonos ORDER BY id DESC");
$telefonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Estilos para vista de impresión / PDF -->
<style>
@media print {
    .no-print, .btn, nav, header, footer, .modal { display: none !important; }
    body { background-color: #fff !important; font-size: 10pt; }
    .card { border: none !important; box-shadow: none !important; }
    .table-responsive { overflow: visible !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #333 !important; padding: 4px !important; white-space: normal !important; }
    .print-header { display: block !important; text-align: center; margin-bottom: 15px; }
}
.print-header { display: none; }
</style>

<div class="container-fluid px-3">
    <!-- Encabezado de impresión -->
    <div class="print-header">
        <h2>Municipalidad Distrital de Pucusana</h2>
        <h3>Subgerencia de Tecnologías de la Información</h3>
        <h4>Inventario de Teléfonos</h4>
        <hr>
    </div>

    <!-- Barra de acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2><i class="bi bi-telephone"></i> Inventario de Teléfonos</h2>
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

    <!-- Notificaciones -->
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
                            <th>Gerencia / Subgerencia</th>
                            <th>Bien</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Cód. Interno</th>
                            <th>Cód. Patrimonial</th>
                            <th>Inv. 2025</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($telefonos)): ?>
                            <tr><td colspan="8" class="text-center py-3">No hay teléfonos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($telefonos as $tel): ?>
                            <tr>
                                <td class="no-print text-center">
                                    <button class="btn btn-sm btn-warning fw-bold" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditar" 
                                            onclick='cargarDatosEdicion(<?= json_encode($tel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        ✏️ Editar
                                    </button>
                                </td>
                                <td><?= htmlspecialchars($tel['gerencia_subgerencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tel['bien'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tel['marca'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tel['modelo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tel['cod_interno'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tel['cod_patrimonial'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tel['inv_2025'] ?? '') ?></td>
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
      <form method="POST" action="telefonos.php">
        <input type="hidden" name="action" value="guardar">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Registrar Nuevo Teléfono</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Gerencia / Subgerencia</label>
              <input type="text" name="gerencia_subgerencia" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Bien</label>
              <input type="text" name="bien" class="form-control" placeholder="Ej. Teléfono IP / Análogo">
            </div>
            <div class="col-md-6">
              <label class="form-label">Marca</label>
              <input type="text" name="marca" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Cód. Interno</label>
              <input type="text" name="cod_interno" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Cód. Patrimonial</label>
              <input type="text" name="cod_patrimonial" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Inv. 2025</label>
              <input type="text" name="inv_2025" class="form-control">
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
      <form method="POST" action="telefonos.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Editar Registro de Teléfono</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Gerencia / Subgerencia</label>
              <input type="text" name="gerencia_subgerencia" id="edit_gerencia_subgerencia" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Bien</label>
              <input type="text" name="bien" id="edit_bien" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Marca</label>
              <input type="text" name="marca" id="edit_marca" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" id="edit_modelo" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Cód. Interno</label>
              <input type="text" name="cod_interno" id="edit_cod_interno" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Cód. Patrimonial</label>
              <input type="text" name="cod_patrimonial" id="edit_cod_patrimonial" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Inv. 2025</label>
              <input type="text" name="inv_2025" id="edit_inv_2025" class="form-control">
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
      <form method="POST" action="telefonos.php" enctype="multipart/form-data">
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
                <strong>📌 Indicaciones de importación:</strong>
                <ul>
                    <li>Guarde su libro de Excel como <code>CSV (delimitado por comas) (*.csv)</code>.</li>
                    <li>Las 7 columnas deben ir exactamente en este orden (omitiendo la columna 'id'):
                    <br><small class="text-dark"><strong>gerencia_subgerencia, bien, marca, modelo, cod_interno, cod_patrimonial, inv_2025</strong></small></li>
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
// Carga los datos de la fila seleccionada dentro del Modal de Edición
function cargarDatosEdicion(tel) {
    document.getElementById('edit_id').value = tel.id || '';
    document.getElementById('edit_gerencia_subgerencia').value = tel.gerencia_subgerencia || '';
    document.getElementById('edit_bien').value = tel.bien || '';
    document.getElementById('edit_marca').value = tel.marca || '';
    document.getElementById('edit_modelo').value = tel.modelo || '';
    document.getElementById('edit_cod_interno').value = tel.cod_interno || '';
    document.getElementById('edit_cod_patrimonial').value = tel.cod_patrimonial || '';
    document.getElementById('edit_inv_2025').value = tel.inv_2025 || '';
}
</script>

<?php require_once '../includes/footer.php'; ?>