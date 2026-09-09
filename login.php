<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Permite ingreso por contraseña cifrada o por clave por defecto 'admin123'
            if ($user && (password_verify($password, $user['password']) || $password === 'admin123')) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['usuario_rol'] = $user['rol'];

                header("Location: index.php");
                exit;
            } else {
                $error = "Credenciales incorrectas.";
            }
        } catch (PDOException $e) {
            $error = "Error en la consulta: " . $e->getMessage();
        }
    } else {
        $error = "Por favor complete todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Inventario TI Municipalidad Pucusana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-bg {
            /* Reemplaza 'assets/img/fondo.jpg' por la ruta de tu imagen */
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/img/banner-Pucusana.jpg') no-repeat center center fixed;
            background-size: cover;
        }
    </style>
</head>
<body class="login-bg d-flex align-items-center vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h5 class="mb-0">Inventario TI - Municipalidad Pucusana</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger p-2 text-center"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="admin@muni.gob.pe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                    </form>
                </div>
                <div class="card-footer text-center text-muted small">
                    Acceso exclusivo para personal de Sistemas y Administración
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>