<?php
// load_records.php - Lee registros de la base de datos y los devuelve como JSON.

header('Content-Type: application/json');

// Conexión a la base de datos
try {
    $db = new PDO('sqlite:simulaciones.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la BD: ' . $e->getMessage()]);
    exit();
}

// Comprobar si se está pidiendo un folio específico
$folio = isset($_GET['folio']) ? $_GET['folio'] : null;

if ($folio) {
    // --- Devolver los detalles completos de UN registro ---
    try {
        $record = [];

        // Datos principales
        $stmt = $db->prepare("SELECT * FROM simulaciones WHERE Folio = ?");
        $stmt->execute([$folio]);
        $record['memoData'] = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record['memoData']) {
            echo json_encode(['success' => false, 'message' => 'No se encontró el folio.']);
            exit();
        }

        // Materiales No Incluidos
        $stmt = $db->prepare("SELECT * FROM materiales WHERE SimulacionFolio = ? AND TipoInclusion = 'NoIncluido' ORDER BY Item");
        $stmt->execute([$folio]);
        $record['materialNoIncluido'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Materiales Incluidos
        $stmt = $db->prepare("SELECT * FROM materiales WHERE SimulacionFolio = ? AND TipoInclusion = 'Incluido' ORDER BY Item");
        $stmt->execute([$folio]);
        $record['materialIncluido'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contenedores
        $stmt = $db->prepare("SELECT * FROM contenedores WHERE SimulacionFolio = ? ORDER BY Item");
        $stmt->execute([$folio]);
        $record['relacionContenedores'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Unidades Cargadas
        $stmt = $db->prepare("SELECT * FROM unidades_cargadas WHERE SimulacionFolio = ?");
        $stmt->execute([$folio]);
        $record['unidadesCargadas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'record' => $record]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al cargar detalles del registro: ' . $e->getMessage()]);
    }
} else {
    // --- Devolver una lista resumida de TODOS los registros ---
    try {
        $stmt = $db->query("
            SELECT Folio, Fecha, VehiculoTipo, CargaPesoTotal 
            FROM simulaciones 
            ORDER BY FechaCreacion DESC
        ");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'records' => $records]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al cargar el historial: ' . $e->getMessage()]);
    }
}
?>