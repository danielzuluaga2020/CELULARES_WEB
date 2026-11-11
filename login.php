<?php
session_start();
include("conexion.php");

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    $sql = "SELECT * FROM USUARIO WHERE IDUSUARIO = '$usuario' AND CLAVE = '$clave'";
    $resultado = $conexion->query($sql);

    if ($resultado->num_rows == 1) {
        $row = $resultado->fetch_assoc();
        $_SESSION['usuario'] = $row['IDUSUARIO'];
        $_SESSION['categoria'] = $row['CATEGORIA']; // 'A' para Admin, 'V' para Vendedor
        header("location: index.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Venta de Celulares</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow p-4" style="width: 350px;">
        <h3 class="text-center mb-3">Login Celulares</h3>
        <?php if($error): ?>
            <div class="alert alert-danger p-2"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="clave" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
        <div class="mt-3 text-muted small text-center">
            Admin por defecto: <b>123</b> / <b>123</b><br>
            Vendedor: <b>vendedor</b> / <b>123</b>
        </div>
    </div>
</body>
</html>