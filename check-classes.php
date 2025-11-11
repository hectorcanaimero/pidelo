<?php
/**
 * Script de verificación de clases del sistema de notificaciones
 *
 * Ejecutar desde wp-cli:
 * wp eval-file check-classes.php
 */

// Define ABSPATH if not defined
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/../../../' );
}

echo "=== Verificación de Clases de Notificaciones ===\n\n";

// Array de clases a verificar
$classes = array(
	'MydPro\Includes\Plugin_Update\Plugin_Update',
	'MydPro\Includes\Plugin_Update\Update_Dashboard_Widget',
	'MydPro\Includes\Plugin_Update\Update_Email_Notification',
	'MydPro\Includes\Plugin_Update\Update_History',
	'MydPro\Includes\Plugin_Update\Auto_Updater',
	'MydPro\Includes\Plugin_Update\Update_Menu_Badge',
	'MydPro\Includes\Plugin_Update\Update_Settings_Page',
);

$all_ok = true;

foreach ( $classes as $class ) {
	$exists = class_exists( $class );
	$status = $exists ? '✓ OK' : '✗ FALTA';
	echo "$status - $class\n";

	if ( ! $exists ) {
		$all_ok = false;
	}
}

echo "\n";

if ( $all_ok ) {
	echo "✓ Todas las clases están cargadas correctamente!\n\n";

	// Test básico de funcionalidad
	echo "=== Tests Básicos ===\n\n";

	// Test Update_History
	echo "1. Test Update_History::get_history()\n";
	$history = \MydPro\Includes\Plugin_Update\Update_History::get_history();
	echo "   Entradas en historial: " . count( $history ) . "\n";

	// Test Email Notification status
	echo "\n2. Test Update_Email_Notification::is_enabled()\n";
	$email_enabled = \MydPro\Includes\Plugin_Update\Update_Email_Notification::is_enabled();
	echo "   Email notifications: " . ( $email_enabled ? 'Habilitado' : 'Deshabilitado' ) . "\n";

	// Test Auto_Updater status
	echo "\n3. Test Auto_Updater::is_enabled()\n";
	$auto_enabled = \MydPro\Includes\Plugin_Update\Auto_Updater::is_enabled();
	echo "   Auto-update: " . ( $auto_enabled ? 'Habilitado' : 'Deshabilitado' ) . "\n";

	echo "\n✓ Sistema de notificaciones funcional!\n";
} else {
	echo "✗ Algunas clases no están cargadas. Verifica los includes en class-plugin.php\n";
	exit( 1 );
}

echo "\n=== Fin de Verificación ===\n";
