<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("location: login.php");
    exit();
}
include("conexion.php");

$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'facturacion';
$rol = $_SESSION['categoria']; // 'A' para Admin, 'V' para Vendedor
$mensaje = "";

// --- LÓGICA DE PRODUCTOS (Solo Admin) ---
if (isset($_POST['guardar_producto']) && $rol == 'A') {
    $cod = $_POST['codarticulo'];
    $desc = $_POST['descripcion'];
    $cat = $_POST['idcategoria'];
    $precio = $_POST['preciouni'];
    $existencia = $_POST['existencia'];
    $min = $_POST['minexistencia'];
    $max = $_POST['maxexistencia'];

    $sql = "INSERT INTO articulo VALUES ('$cod', '$desc', '$cat', $precio, $existencia, $min, $max)
            ON DUPLICATE KEY UPDATE DESCRIPCION='$desc', IDCATEGORIA='$cat', PRECIOUNI=$precio, EXISTENCIA=$existencia, MINEXISTENCIA=$min, MAXEXISTENCIA=$max";
    if($conexion->query($sql)) {
        $mensaje = "Producto guardado con éxito.";
    }
}

// --- LÓGICA DE CLIENTES ---
if (isset($_POST['guardar_cliente'])) {
    $id = $_POST['identificacion'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $sexo = $_POST['sexo'];

    $sql = "INSERT INTO cliente VALUES ($id, '$nombre', '$apellido', '$sexo')
            ON DUPLICATE KEY UPDATE NOMBRE='$nombre', APELLIDO='$apellido', SEXO='$sexo'";
    if($conexion->query($sql)) {
        $mensaje = "Cliente guardado con éxito.";
    }
}

// --- LÓGICA DE CATEGORÍAS (Solo Admin) ---
if (isset($_POST['guardar_categoria']) && $rol == 'A') {
    $cod = $_POST['cod'];
    $desc = $_POST['descripcion'];

    $sql = "INSERT INTO CATEGORIA VALUES ($cod, '$desc')";
    if($conexion->query($sql)) {
        $mensaje = "Categoría guardada con éxito.";
    }
}

// --- LÓGICA DE FACTURACIÓN (Regla: No dejar vender más de la existencia) ---
if (isset($_POST['generar_factura'])) {
    $nrofac = trim($_POST['nrofactura']);
    $fecha = date('Y-m-d');
    $cliente = $_POST['idcliente'];
    $codart = $_POST['codarticulo'];
    $cant = intval($_POST['cantidad']);

    // Verificar si el número de factura ya existe
    $verificar_fac = $conexion->query("SELECT * FROM factura WHERE NROFACTURA = '$nrofac'");
    if ($verificar_fac->num_rows > 0) {
        $mensaje = "Error: El número de factura '$nrofac' ya existe. Use un número diferente.";
    } else {
        // Consultar stock real (existencia) en la base de datos
        $res_art = $conexion->query("SELECT * FROM articulo WHERE CODARTICULO = '$codart'");
        $art = $res_art->fetch_assoc();

        // VALIDACIÓN ESTRICTA: No permitir vender más de la existencia actual
        if ($cant > intval($art['EXISTENCIA'])) {
            $mensaje = "¡Error! No se puede vender más de la existencia actual. Stock disponible: " . $art['EXISTENCIA'];
        } else {
            $precio_uni = intval($art['PRECIOUNI']);
            $val_total = $precio_uni * $cant;
            $val_desc = 0; 

            // 1. Registrar la factura principal
            $conexion->query("INSERT INTO factura VALUES ('$nrofac', '$fecha', $cliente, $val_total, $val_desc)");

            // 2. Registrar el detalle de la factura
            $conexion->query("INSERT INTO detallefactura VALUES ('$nrofac', 1, '$codart', $cant, $precio_uni, $val_total, $val_desc)");
            
            // 3. Actualizar el inventario restando la cantidad vendida
            $nuevo_stock = intval($art['EXISTENCIA']) - $cant;
            $conexion->query("UPDATE articulo SET EXISTENCIA = $nuevo_stock WHERE CODARTICULO = '$codart'");

            $mensaje = "¡Factura generada y stock actualizado correctamente!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Venta de Celulares</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
        <a class="navbar-brand" href="#">Celulares Web</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <?php if($rol == 'A'): ?>
                    <li class="nav-item"><a class="nav-link" href="?seccion=productos">Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="?seccion=categorias">Categorías</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="?seccion=clientes">Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="?seccion=facturacion">Facturación</a></li>
            </ul>
            <span class="navbar-text text-white me-3">
                Usuario: <?php echo $_SESSION['usuario']; ?> 
                (<b><?php echo ($rol == 'A') ? 'Administrador' : 'Vendedor'; ?></b>)
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if($mensaje): ?>
            <div class="alert alert-warning fw-bold"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if($seccion == 'productos' && $rol == 'A'): ?>
            <h3>Gestión de Productos (CRUD)</h3>
            <form method="POST" class="row g-3 bg-white p-3 shadow-sm rounded mb-4">
                <div class="col-md-2"><input type="text" name="codarticulo" class="form-control" placeholder="Cod Artículo" required></div>
                <div class="col-md-3"><input type="text" name="descripcion" class="form-control" placeholder="Descripción" required></div>
                <div class="col-md-2">
                    <select name="idcategoria" class="form-control" required>
                        <option value="">Seleccione Categoría</option>
                        <?php 
                        $cats = $conexion->query("SELECT * FROM CATEGORIA");
                        while($c = $cats->fetch_assoc()) {
                            echo "<option value='".$c['COD']."'>".$c['DESCRIPCION']."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" name="preciouni" class="form-control" placeholder="Precio Unit" required></div>
                <div class="col-md-1"><input type="number" name="existencia" class="form-control" placeholder="Stock" required></div>
                <div class="col-md-1"><input type="number" name="minexistencia" class="form-control" placeholder="Mín" required></div>
                <div class="col-md-1"><input type="number" name="maxexistencia" class="form-control" placeholder="Máx" required></div>
                <div class="col-12"><button type="submit" name="guardar_producto" class="btn btn-success">Guardar Producto</button></div>
            </form>

            <table class="table table-striped bg-white shadow-sm">
                <tr><th>Código</th><th>Descripción</th><th>Categoría</th><th>Precio</th><th>Existencia</th><th>Mín/Máx</th></tr>
                <?php
                $res = $conexion->query("SELECT a.*, c.DESCRIPCION as cat_nom FROM articulo a JOIN CATEGORIA c ON a.IDCATEGORIA = c.COD");
                while($row = $res->fetch_assoc()){
                    echo "<tr><td>{$row['CODARTICULO']}</td><td>{$row['DESCRIPCION']}</td><td>{$row['cat_nom']}</td><td>\${$row['PRECIOUNI']}</td><td>{$row['EXISTENCIA']}</td><td>{$row['MINEXISTENCIA']} / {$row['MAXEXISTENCIA']}</td></tr>";
                }
                ?>
            </table>

        <?php elseif($seccion == 'clientes'): ?>
            <h3>Gestión de Clientes (CRUD)</h3>
            <form method="POST" class="row g-3 bg-white p-3 shadow-sm rounded mb-4">
                <div class="col-md-3"><input type="number" name="identificacion" class="form-control" placeholder="Identificación" required></div>
                <div class="col-md-3"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div>
                <div class="col-md-3"><input type="text" name="apellido" class="form-control" placeholder="Apellido"></div>
                <div class="col-md-2">
                    <select name="sexo" class="form-control" required>
                        <option value="">Sexo</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
                <div class="col-12"><button type="submit" name="guardar_cliente" class="btn btn-success">Guardar Cliente</button></div>
            </form>

            <table class="table table-striped bg-white shadow-sm">
                <tr><th>Identificación</th><th>Nombre</th><th>Apellido</th><th>Sexo</th></tr>
                <?php
                $res = $conexion->query("SELECT * FROM cliente");
                while($row = $res->fetch_assoc()){
                    echo "<tr><td>{$row['IDENTIFICACION']}</td><td>{$row['NOMBRE']}</td><td>{$row['APELLIDO']}</td><td>{$row['SEXO']}</td></tr>";
                }
                ?>
            </table>

        <?php elseif($seccion == 'categorias' && $rol == 'A'): ?>
            <h3>Gestión de Categorías (CRUD)</h3>
            <form method="POST" class="row g-3 bg-white p-3 shadow-sm rounded mb-4">
                <div class="col-md-4"><input type="number" name="cod" class="form-control" placeholder="Código Categoría" required></div>
                <div class="col-md-6"><input type="text" name="descripcion" class="form-control" placeholder="Descripción de la Categoría" required></div>
                <div class="col-2"><button type="submit" name="guardar_categoria" class="btn btn-success w-100">Guardar</button></div>
            </form>

            <table class="table table-striped bg-white shadow-sm">
                <tr><th>Código</th><th>Descripción</th></tr>
                <?php
                $res = $conexion->query("SELECT * FROM CATEGORIA");
                while($row = $res->fetch_assoc()){
                    echo "<tr><td>{$row['COD']}</td><td>{$row['DESCRIPCION']}</td></tr>";
                }
                ?>
            </table>

        <?php else: ?>
            <h3>Facturación y Actualización de Inventarios</h3>
            <form method="POST" class="row g-3 bg-white p-3 shadow-sm rounded mb-4">
                <div class="col-md-3"><input type="text" name="nrofactura" class="form-control" placeholder="Nro Factura (Ej: FAC-001)" required></div>
                <div class="col-md-4">
                    <select name="idcliente" class="form-control" required>
                        <option value="">Seleccione Cliente</option>
                        <?php 
                        $cl = $conexion->query("SELECT * FROM cliente");
                        while($c = $cl->fetch_assoc()) {
                            echo "<option value='".$c['IDENTIFICACION']."'>".$c['NOMBRE']." ".$c['APELLIDO']." (ID: ".$c['IDENTIFICACION'].")</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="codarticulo" class="form-control" required>
                        <option value="">Seleccione Celular / Artículo</option>
                        <?php 
                        $ar = $conexion->query("SELECT * FROM articulo WHERE EXISTENCIA > 0");
                        while($a = $ar->exitencia ?? $ar->fetch_assoc()) { // safe fetch
                            // Note: standard fetch loop below
                        }
                        // Re-query cleanly for the dropdown
                        $ar2 = $conexion->query("SELECT * FROM articulo WHERE EXISTENCIA > 0");
                        while($a = $ar2->fetch_assoc()) {
                            echo "<option value='".$a['CODARTICULO']."'>".$a['DESCRIPCION']." (Stock: ".$a['EXISTENCIA'].")</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" name="cantidad" class="form-control" placeholder="Cantidad" min="1" required></div>
                <div class="col-12"><button type="submit" name="generar_factura" class="btn btn-primary">Emitir Factura y Actualizar Stock</button></div>
            </form>

            <h4>Historial de Facturas Emitidas</h4>
            <table class="table table-striped bg-white shadow-sm">
                <tr><th>Nro Factura</th><th>Fecha</th><th>Cliente (ID)</th><th>Valor Total</th></tr>
                <?php
                $res = $conexion->query("SELECT * FROM factura");
                while($row = $res->fetch_assoc()){
                    echo "<tr><td>{$row['NROFACTURA']}</td><td>{$row['FECHA']}</td><td>{$row['IDCLIENTE']}</td><td>\${$row['VALORTOTAL']}</td></tr>";
                }
                ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>