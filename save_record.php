<?php
// save_record.php - Recibe datos JSON y los guarda en la base de datos SQLite.

header('Content-Type: application/json');

// Conexión a la base de datos
try {
    $db = new PDO('sqlite:simulaciones.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la BD: ' . $e->getMessage()]);
    exit();
}

// Obtener los datos JSON enviados desde el frontend
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos o el formato JSON es inválido.']);
    exit();
}

// Iniciar una transacción. Si algo falla, se revierte todo.
$db->beginTransaction();

try {
    // 1. Insertar en la tabla principal 'simulaciones'
    $stmt = $db->prepare("
        INSERT INTO simulaciones (Folio, Fecha, Para, De, Asunto, Elaboro, Reviso, Recibio, VehiculoTipo, VehiculoPesoMaximo, VehiculoVolumenTotal, CargaPesoTotal, CargaVolumenTotal, CargaUtilizacionVolumen)
        VALUES (:Folio, :Fecha, :Para, :De, :Asunto, :Elaboro, :Reviso, :Recibio, :VehiculoTipo, :VehiculoPesoMaximo, :VehiculoVolumenTotal, :CargaPesoTotal, :CargaVolumenTotal, :CargaUtilizacionVolumen)
    ");
    $stmt->execute($data['memoData']);
    $folio = $data['memoData']['Folio'];

    // 2. Insertar en 'materiales' (No Incluidos)
    $stmtMaterial = $db->prepare("
        INSERT INTO materiales (SimulacionFolio, TipoInclusion, Item, NumeroParte, Descripcion, TipoRack, CantidadRacks, TotalPiezas)
        VALUES (?, 'NoIncluido', ?, ?, ?, ?, ?, ?)
    ");
    foreach ($data['materialNoIncluido'] as $item) {
        $stmtMaterial->execute([$folio, $item['Item'], $item['NumeroParte'], $item['Descripcion'], $item['TipoRack'], $item['CantidadRacks'], $item['TotalPiezas']]);
    }

    // 3. Insertar en 'materiales' (Incluidos)
    $stmtMaterial->closeCursor(); // Reset statement for next loop
    $stmtMaterial = $db->prepare("
        INSERT INTO materiales (SimulacionFolio, TipoInclusion, Item, NumeroParte, Descripcion, TipoRack, CantidadRacks, TotalPiezas)
        VALUES (?, 'Incluido', ?, ?, ?, ?, ?, ?)
    ");
    foreach ($data['materialIncluido'] as $item) {
        $stmtMaterial->execute([$folio, $item['Item'], $item['NumeroParte'], $item['Descripcion'], $item['TipoRack'], $item['CantidadRacks'], $item['TotalPiezas']]);
    }
    
    // 4. Insertar en 'contenedores'
    $stmtContenedor = $db->prepare("
        INSERT INTO contenedores (SimulacionFolio, Item, TipoContenedor, ConMaterial, Vacios, TotalRacks)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($data['relacionContenedores'] as $item) {
        $stmtContenedor->execute([$folio, $item['Item'], $item['TipoContenedor'], $item['ConMaterial'], $item['Vacios'], $item['TotalRacks']]);
    }

    // 5. Insertar en 'unidades_cargadas'
    $stmtUnidad = $db->prepare("
        INSERT INTO unidades_cargadas (SimulacionFolio, UnitTypeID, UnitTypeName, InstanceName, DimensionX, DimensionY, DimensionZ, Weight, PositionBaseX, PositionBaseY, PositionBaseZ)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($data['unidadesCargadas'] as $item) {
        $stmtUnidad->execute([
            $folio, $item['UnitTypeID'], $item['UnitTypeName'], $item['InstanceName'],
            $item['DimensionX'], $item['DimensionY'], $item['DimensionZ'], $item['Weight'],
            $item['PositionBaseX'], $item['PositionBaseY'], $item['PositionBaseZ']
        ]);
    }
    
    // Si todo fue exitoso, confirmar los cambios
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Simulación guardada con éxito. Folio: ' . $folio]);

} catch (Exception $e) {
    // Si hubo un error, revertir todos los cambios
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos: ' . $e->getMessage()]);
}
?>