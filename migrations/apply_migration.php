<?php
/**
 * Script para aplicar la migración de ticket_historial
 * 
 * IMPORTANTE: Ejecutar este script UNA SOLA VEZ para crear la tabla ticket_historial
 * 
 * Uso:
 * - Desde línea de comandos: php migrations/apply_migration.php
 * - Desde navegador: Acceder a migrations/apply_migration.php (solo en desarrollo)
 */

// Solo permitir ejecución en desarrollo/local
// En producción, aplicar la migración manualmente via phpMyAdmin o MySQL CLI
$permitir_web = false; // Cambiar a true solo para pruebas en desarrollo

if (php_sapi_name() !== 'cli' && !$permitir_web) {
    die('Este script solo puede ejecutarse desde línea de comandos por seguridad.');
}

// Cargar configuración
require_once __DIR__ . '/../config/database.php';

echo "===========================================\n";
echo "MIGRACIÓN: Create ticket_historial table\n";
echo "===========================================\n\n";

try {
    // Conectar a la base de datos
    $db = Database::getInstance()->getConnection();
    echo "✓ Conexión a base de datos establecida\n\n";
    
    // Leer el archivo de migración
    $sql_file = __DIR__ . '/001_create_ticket_historial.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("Archivo de migración no encontrado: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    echo "✓ Archivo de migración cargado\n\n";
    
    // Ejecutar la migración
    echo "Ejecutando migración...\n";
    $db->exec($sql);
    echo "✓ Migración ejecutada exitosamente\n\n";
    
    // Verificar que la tabla se creó
    $stmt = $db->query("SHOW TABLES LIKE 'ticket_historial'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✓ Tabla 'ticket_historial' creada correctamente\n\n";
        
        // Mostrar estructura de la tabla
        echo "Estructura de la tabla:\n";
        echo "=======================\n";
        $stmt = $db->query("DESCRIBE ticket_historial");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo sprintf(
                "- %-20s %-20s %s\n",
                $column['Field'],
                $column['Type'],
                $column['Null'] === 'NO' ? 'NOT NULL' : 'NULL'
            );
        }
        
        echo "\n✅ MIGRACIÓN COMPLETADA CON ÉXITO\n\n";
        
    } else {
        throw new Exception("La tabla no se creó correctamente");
    }
    
} catch (PDOException $e) {
    echo "\n❌ ERROR DE BASE DE DATOS:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ ERROR:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}

echo "Notas:\n";
echo "======\n";
echo "- La tabla ticket_historial está lista para usar\n";
echo "- El sistema registrará automáticamente la creación de nuevos tickets\n";
echo "- Para tickets existentes, el historial comenzará a registrarse desde ahora\n";
echo "- Ver migrations/ejemplos_uso_historial.php para ejemplos de uso\n\n";
?>
