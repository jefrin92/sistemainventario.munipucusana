<?php

// Configurar límites dinámicamente

ini_set('memory_limit', '512M');

ini_set('max_execution_time', '300');

ini_set('upload_max_filesize', '20M');

ini_set('post_max_size', '20M');



require_once '../includes/header.php';

require_once '../config/db.php';



// Crear tabla automáticamente con el esquema exacto solicitado

try {

    $sql_tabla = "CREATE TABLE IF NOT EXISTS inventario_impresoras (

        id INT AUTO_INCREMENT PRIMARY KEY,

        sede_municipal VARCHAR(150) NULL,

        gerencia_subgerencia VARCHAR(150) NULL,

        usuario_responsable VARCHAR(150) NULL,

        marca VARCHAR(100) NULL,

        modelo VARCHAR(100) NULL,

        conexion_red VARCHAR(100) NULL,

        ip VARCHAR(45) NULL,

        codigo_etiqueta VARCHAR(100) NULL,

        inv_2021 VARCHAR(100) NULL,

        referencia_imagen VARCHAR(255) NULL,

        codigo_patrimonial VARCHAR(100) NULL,

        observaciones TEXT NULL,

        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql_tabla);

} catch (PDOException $e) {

    die("Error al verificar o crear la tabla de la base de datos: " . $e->getMessage());

}



$mensaje = '';

$tipo_alerta = '';



// 1. PROCESAR GUARDADO (NUEVO REGISTRO)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar') {

    try {

        $sql = "INSERT INTO inventario_impresoras (

            sede_municipal, gerencia_subgerencia, usuario_responsable, marca, modelo,

            conexion_red, ip, codigo_etiqueta, inv_2021, referencia_imagen,

            codigo_patrimonial, observaciones

        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

       

        $stmt = $pdo->prepare($sql);

        $stmt->execute([

            $_POST['sede_municipal'] ?? '',

            $_POST['gerencia_subgerencia'] ?? '',

            $_POST['usuario_responsable'] ?? '',

            $_POST['marca'] ?? '',

            $_POST['modelo'] ?? '',

            $_POST['conexion_red'] ?? '',

            $_POST['ip'] ?? '',

            $_POST['codigo_etiqueta'] ?? '',

            $_POST['inv_2021'] ?? '',

            $_POST['referencia_imagen'] ?? '',

            $_POST['codigo_patrimonial'] ?? '',

            $_POST['observaciones'] ?? ''

        ]);

        header("Location: impresoras.php?msg=guardado");

        exit;

    } catch (PDOException $e) {

        $mensaje = "Error al guardar: " . $e->getMessage();

        $tipo_alerta = "danger";

    }

}



// 2. PROCESAR EDICIÓN DE REGISTRO

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {

    try {

        $sql = "UPDATE inventario_impresoras SET

            sede_municipal = ?, gerencia_subgerencia = ?, usuario_responsable = ?, marca = ?,

            modelo = ?, conexion_red = ?, ip = ?, codigo_etiqueta = ?,

            inv_2021 = ?, referencia_imagen = ?, codigo_patrimonial = ?, observaciones = ?

            WHERE id = ?";

       

        $stmt = $pdo->prepare($sql);

        $stmt->execute([

            $_POST['sede_municipal'] ?? '',

            $_POST['gerencia_subgerencia'] ?? '',

            $_POST['usuario_responsable'] ?? '',

            $_POST['marca'] ?? '',

            $_POST['modelo'] ?? '',

            $_POST['conexion_red'] ?? '',

            $_POST['ip'] ?? '',

            $_POST['codigo_etiqueta'] ?? '',

            $_POST['inv_2021'] ?? '',

            $_POST['referencia_imagen'] ?? '',

            $_POST['codigo_patrimonial'] ?? '',

            $_POST['observaciones'] ?? '',

            $_POST['id']

        ]);

        header("Location: impresoras.php?msg=editado");

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



            // Detectar automáticamente el separador (coma o punto y coma)

            $firstLine = fgets($handle);

            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            rewind($handle);



            $count = 0;



            // Omitir la primera fila de encabezados

            fgetcsv($handle, 4096, $delimiter);



            $sql = "INSERT INTO inventario_impresoras (

                sede_municipal, gerencia_subgerencia, usuario_responsable, marca, modelo,

                conexion_red, ip, codigo_etiqueta, inv_2021, referencia_imagen,

                codigo_patrimonial, observaciones

            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

           

            $stmt = $pdo->prepare($sql);



            $pdo->beginTransaction();

            try {

                while (($data = fgetcsv($handle, 4096, $delimiter)) !== FALSE) {

                    if (empty(array_filter($data))) continue;



                    // Normalizar a exactamente 12 columnas

                    $params = array_pad(array_slice($data, 0, 12), 12, '');



                    // Convertir codificación a UTF-8 para evitar errores con caracteres especiales

                    $params = array_map(function($field) {

                        return mb_convert_encoding(trim($field), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');

                    }, $params);



                    $stmt->execute($params);

                    $count++;

                }

                $pdo->commit();

                fclose($handle);

                header("Location: impresoras.php?msg=importado&cant=$count");

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



// Consultar registros

$stmt = $pdo->query("SELECT * FROM inventario_impresoras ORDER BY id DESC");

$impresoras = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>



<!-- Estilos para impresión y exportación en formato PDF -->

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

    <!-- Encabezado para vista de impresión / PDF -->

    <div class="print-header">

        <h2>Municipalidad Distrital de Pucusana</h2>

        <h3>Subgerencia de Tecnologías de la Información</h3>

        <h4>Inventario de Impresoras</h4>

        <hr>

    </div>



    <!-- Barra de acciones superiores -->

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">

        <h2><i class="bi bi-printer"></i> Inventario de Impresoras</h2>

        <div>

            <button class="btn btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImportar">

                <i class="bi bi-file-earmark-excel"></i> Importar CSV

            </button>

            <button class="btn btn-danger me-1" onclick="window.print()">

                <i class="bi bi-file-earmark-pdf"></i> Exportar / Imprimir

            </button>

            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">

                <i class="bi bi-plus-circle"></i> Nuevo Registro

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



    <!-- Tabla Principal -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-striped table-hover table-bordered align-middle text-nowrap mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th class="no-print text-center">Acciones</th>

                            <th>Sede Municipal</th>

                            <th>Gerencia / Subgerencia</th>

                            <th>Usuario Responsable</th>

                            <th>Marca</th>

                            <th>Modelo</th>

                            <th>Conexión a la Red</th>

                            <th>IP</th>

                            <th>Código Etiqueta</th>

                            <th>INV. 2021</th>

                            <th>Referencia Imagen</th>

                            <th>Código Patrimonial</th>

                            <th>Observaciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($impresoras)): ?>

                            <tr><td colspan="13" class="text-center py-3">No hay datos registrados.</td></tr>

                        <?php else: ?>

                            <?php foreach ($impresoras as $imp): ?>

                            <tr>

                                <td class="no-print text-center">

                                    <button class="btn btn-sm btn-warning fw-bold"

                                            data-bs-toggle="modal"

                                            data-bs-target="#modalEditar"

                                            onclick='cargarDatosEdicion(<?= json_encode($imp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>

                                        ✏️ Editar

                                    </button>

                                </td>

                                <td><?= htmlspecialchars($imp['sede_municipal'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['gerencia_subgerencia'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['usuario_responsable'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['marca'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['modelo'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['conexion_red'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['ip'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['codigo_etiqueta'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['inv_2021'] ?? '') ?></td>

                                <td>

                                    
                        <?php if (!empty($imp['referencia_imagen'])): 
        $nombre_archivo = trim($imp['referencia_imagen']);
        
        // Si no es un enlace HTTP externo, construir la ruta relativa de la carpeta
        if (!preg_match("~^(?:f|ht)tps?://~i", $nombre_archivo)) {
            // Elimina barras o rutas absolutas pegadas por error
            $nombre_limpio = ltrim(str_replace(['E:\xampp\htdocs', 'E:/xampp/htdocs'], '', $nombre_archivo), '/\\');
            
            // Si solo guardaron el nombre del archivo (ej: impresora1.jpg)
            if (!str_contains($nombre_limpio, '/')) {
                $url_img = "/sismuni_inventario/assets/img/REFERENCIA_IMAGEN/" . $nombre_limpio;
            } else {
                $url_img = "/" . ltrim($nombre_limpio, '/\\');
            }
        } else {
            $url_img = $nombre_archivo;
        }
    ?>
        <a href="<?= htmlspecialchars($url_img) ?>" target="_blank" class="btn btn-sm btn-outline-info no-print">
            <i class="bi bi-image"></i> Ver Imagen
        </a>
        <span class="d-none print-only"><?= htmlspecialchars($url_img) ?></span>
    <?php endif; ?>
                                

                                </td>

                                <td><?= htmlspecialchars($imp['codigo_patrimonial'] ?? '') ?></td>

                                <td><?= htmlspecialchars($imp['observaciones'] ?? '') ?></td>

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

      <form method="POST" action="impresoras.php">

        <input type="hidden" name="action" value="guardar">

        <div class="modal-header bg-dark text-white">

          <h5 class="modal-title">Registrar Nueva Impresora</h5>

          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

        </div>

        <div class="modal-body">

          <div class="row g-3">

            <div class="col-md-6">

              <label class="form-label">Sede Municipal</label>

              <input type="text" name="sede_municipal" class="form-control">

            </div>

            <div class="col-md-6">

              <label class="form-label">Gerencia / Subgerencia</label>

              <input type="text" name="gerencia_subgerencia" class="form-control">

            </div>

            <div class="col-md-6">

              <label class="form-label">Usuario Responsable</label>

              <input type="text" name="usuario_responsable" class="form-control">

            </div>

            <div class="col-md-3">

              <label class="form-label">Marca</label>

              <input type="text" name="marca" class="form-control">

            </div>

            <div class="col-md-3">

              <label class="form-label">Modelo</label>

              <input type="text" name="modelo" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">Conexión a la Red</label>

              <input type="text" name="conexion_red" class="form-control" placeholder="Sí / No / Red Local">

            </div>

            <div class="col-md-4">

              <label class="form-label">IP</label>

              <input type="text" name="ip" class="form-control" placeholder="192.168.X.X">

            </div>

            <div class="col-md-4">

              <label class="form-label">Código Etiqueta</label>

              <input type="text" name="codigo_etiqueta" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">INV. 2021</label>

              <input type="text" name="inv_2021" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">Referencia Imagen</label>

              <input type="text" name="referencia_imagen" class="form-control" placeholder="URL o Ruta de la foto">

            </div>

            <div class="col-md-4">

              <label class="form-label">Código Patrimonial</label>

              <input type="text" name="codigo_patrimonial" class="form-control">

            </div>

            <div class="col-12">

              <label class="form-label">Observaciones</label>

              <textarea name="observaciones" class="form-control" rows="2"></textarea>

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

      <form method="POST" action="impresoras.php">

        <input type="hidden" name="action" value="editar">

        <input type="hidden" name="id" id="edit_id">

        <div class="modal-header bg-warning text-dark">

          <h5 class="modal-title">Editar Registro</h5>

          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

        </div>

        <div class="modal-body">

          <div class="row g-3">

            <div class="col-md-6">

              <label class="form-label">Sede Municipal</label>

              <input type="text" name="sede_municipal" id="edit_sede_municipal" class="form-control">

            </div>

            <div class="col-md-6">

              <label class="form-label">Gerencia / Subgerencia</label>

              <input type="text" name="gerencia_subgerencia" id="edit_gerencia_subgerencia" class="form-control">

            </div>

            <div class="col-md-6">

              <label class="form-label">Usuario Responsable</label>

              <input type="text" name="usuario_responsable" id="edit_usuario_responsable" class="form-control">

            </div>

            <div class="col-md-3">

              <label class="form-label">Marca</label>

              <input type="text" name="marca" id="edit_marca" class="form-control">

            </div>

            <div class="col-md-3">

              <label class="form-label">Modelo</label>

              <input type="text" name="modelo" id="edit_modelo" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">Conexión a la Red</label>

              <input type="text" name="conexion_red" id="edit_conexion_red" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">IP</label>

              <input type="text" name="ip" id="edit_ip" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">Código Etiqueta</label>

              <input type="text" name="codigo_etiqueta" id="edit_codigo_etiqueta" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">INV. 2021</label>

              <input type="text" name="inv_2021" id="edit_inv_2021" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">Referencia Imagen</label>

              <input type="text" name="referencia_imagen" id="edit_referencia_imagen" class="form-control">

            </div>

            <div class="col-md-4">

              <label class="form-label">Código Patrimonial</label>

              <input type="text" name="codigo_patrimonial" id="edit_codigo_patrimonial" class="form-control">

            </div>

            <div class="col-12">

              <label class="form-label">Observaciones</label>

              <textarea name="observaciones" id="edit_observaciones" class="form-control" rows="2"></textarea>

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



<!-- Modal Importar CSV -->

<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">

  <div class="modal-dialog">

    <div class="modal-content">

      <form method="POST" action="impresoras.php" enctype="multipart/form-data">

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

                <strong>📌 Estructura requerida de las 12 columnas en el CSV:</strong>

                <br><small class="text-dark"><strong>1. sede_municipal | 2. gerencia_subgerencia | 3. usuario_responsable | 4. marca | 5. modelo | 6. conexion_red | 7. ip | 8. codigo_etiqueta | 9. inv_2021 | 10. referencia_imagen | 11. codigo_patrimonial | 12. observaciones</strong></small>

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

function cargarDatosEdicion(imp) {

    document.getElementById('edit_id').value = imp.id || '';

    document.getElementById('edit_sede_municipal').value = imp.sede_municipal || '';

    document.getElementById('edit_gerencia_subgerencia').value = imp.gerencia_subgerencia || '';

    document.getElementById('edit_usuario_responsable').value = imp.usuario_responsable || '';

    document.getElementById('edit_marca').value = imp.marca || '';

    document.getElementById('edit_modelo').value = imp.modelo || '';

    document.getElementById('edit_conexion_red').value = imp.conexion_red || '';

    document.getElementById('edit_ip').value = imp.ip || '';

    document.getElementById('edit_codigo_etiqueta').value = imp.codigo_etiqueta || '';

    document.getElementById('edit_inv_2021').value = imp.inv_2021 || '';

    document.getElementById('edit_referencia_imagen').value = imp.referencia_imagen || '';

    document.getElementById('edit_codigo_patrimonial').value = imp.codigo_patrimonial || '';

    document.getElementById('edit_observaciones').value = imp.observaciones || '';

}

</script>



<?php require_once '../includes/footer.php'; ?>