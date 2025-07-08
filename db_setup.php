<?php
// db_setup.php - Ejecútalo UNA SOLA VEZ para crear la base de datos y las tablas.

try {
    // 1. Crear (o abrir) la base de datos. Se creará un archivo 'simulaciones.sqlite'.
    $db = new PDO('sqlite:simulaciones.sqlite');
    // Habilitar el manejo de errores a través de excepciones
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Habilitar llaves foráneas para mantener la integridad de los datos
    $db->exec('PRAGMA foreign_keys = ON;');

    echo "Base de datos 'simulaciones.sqlite' creada/abierta con éxito.<br>";

    // 2. Crear la tabla principal para los datos del memorándum
    $db->exec("
        CREATE TABLE IF NOT EXISTS simulaciones (
            Folio TEXT PRIMARY KEY NOT NULL,
            Fecha TEXT,
            Para TEXT,
            De TEXT,
            Asunto TEXT,
            Elaboro TEXT,
            Reviso TEXT,
            Recibio TEXT,
            VehiculoTipo TEXT,
            VehiculoPesoMaximo REAL,
            VehiculoVolumenTotal REAL,
            CargaPesoTotal REAL,
            CargaVolumenTotal REAL,
            CargaUtilizacionVolumen REAL,
            FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Tabla 'simulaciones' creada con éxito.<br>";

    // 3. Crear la tabla para materiales (incluidos y no incluidos)
    $db->exec("
        CREATE TABLE IF NOT EXISTS materiales (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            SimulacionFolio TEXT NOT NULL,
            TipoInclusion TEXT NOT NULL, -- 'Incluido' o 'NoIncluido'
            Item INTEGER,
            NumeroParte TEXT,
            Descripcion TEXT,
            TipoRack TEXT,
            CantidadRacks INTEGER,
            TotalPiezas INTEGER,
            FOREIGN KEY (SimulacionFolio) REFERENCES simulaciones(Folio) ON DELETE CASCADE
        )
    ");
    echo "Tabla 'materiales' creada con éxito.<br>";

    // 4. Crear la tabla para la relación de contenedores
    $db->exec("
        CREATE TABLE IF NOT EXISTS contenedores (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            SimulacionFolio TEXT NOT NULL,
            Item INTEGER,
            TipoContenedor TEXT,
            ConMaterial INTEGER,
            Vacios INTEGER,
            TotalRacks INTEGER,
            FOREIGN KEY (SimulacionFolio) REFERENCES simulaciones(Folio) ON DELETE CASCADE
        )
    ");
    echo "Tabla 'contenedores' creada con éxito.<br>";

    // 5. Crear la tabla para las unidades 3D cargadas
    $db->exec("
        CREATE TABLE IF NOT EXISTS unidades_cargadas (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            SimulacionFolio TEXT NOT NULL,
            UnitTypeID TEXT,
            UnitTypeName TEXT,
            InstanceName TEXT,
            DimensionX REAL,
            DimensionY REAL,
            DimensionZ REAL,
            Weight REAL,
            PositionBaseX REAL,
            PositionBaseY REAL,
            PositionBaseZ REAL,
            FOREIGN KEY (SimulacionFolio) REFERENCES simulaciones(Folio) ON DELETE CASCADE
        )
    ");
    echo "Tabla 'unidades_cargadas' creada con éxito.<br>";

    echo "<hr><strong>¡Configuración completada!</strong> Ya puedes eliminar o renombrar este archivo.";

} catch (PDOException $e) {
    // Si algo sale mal, muestra el error.
    die("Error al conectar o configurar la base de datos: " . $e->getMessage());
}
?>