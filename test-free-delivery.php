<?php
/**
 * Test Free Delivery Configuration
 *
 * Visita este archivo en el navegador: https://tu-sitio.com/wp-content/plugins/pidelo/test-free-delivery.php
 */

// Load WordPress
require_once('../../../wp-load.php');

echo '<h1>Free Delivery Configuration Test</h1>';

echo '<h2>Database Values:</h2>';
echo '<pre>';
echo 'myd-free-delivery-enabled: ';
var_dump(get_option('myd-free-delivery-enabled'));
echo "\n";

echo 'myd-free-delivery-amount: ';
var_dump(get_option('myd-free-delivery-amount'));
echo "\n";
echo '</pre>';

echo '<h2>How it will appear in JavaScript:</h2>';
echo '<pre>';
$free_delivery_config = array(
    'enabled' => get_option('myd-free-delivery-enabled') === 'yes',
    'minimumAmount' => floatval(get_option('myd-free-delivery-amount', 0)),
);
echo 'freeDelivery: ';
print_r($free_delivery_config);
echo '</pre>';

echo '<h2>Expected Result:</h2>';
echo '<p>If configured correctly, you should see:</p>';
echo '<pre>';
echo 'enabled: true (boolean)
minimumAmount: [your configured amount] (float)';
echo '</pre>';

echo '<hr>';
echo '<h2>Fix Instructions:</h2>';
echo '<ol>';
echo '<li>Go to WordPress Admin → Settings → Delivery</li>';
echo '<li>Check the "Delivery gratis por monto mínimo" checkbox</li>';
echo '<li>Enter the minimum amount (e.g., 20)</li>';
echo '<li>Click "Save Changes"</li>';
echo '<li>Reload this page to verify</li>';
echo '</ol>';
