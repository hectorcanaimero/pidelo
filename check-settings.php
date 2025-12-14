<?php
/**
 * Diagnostic script to check free delivery settings
 * Access: https://your-site.com/wp-content/plugins/pidelo/check-settings.php
 */

// Load WordPress
require_once('../../../wp-load.php');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Free Delivery Settings Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa; }
        .success { border-left-color: #46b450; background: #ecf7ed; }
        .error { border-left-color: #dc3232; background: #fef7f7; }
        .warning { border-left-color: #ffb900; background: #fff8e5; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .label { font-weight: bold; color: #555; }
        .value { color: #0073aa; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Free Delivery Settings Diagnostic</h1>

        <div class="section">
            <h2>📊 Database Values (Raw)</h2>
            <p><span class="label">myd-free-delivery-enabled:</span>
               <span class="value"><?php var_dump(get_option('myd-free-delivery-enabled')); ?></span></p>
            <p><span class="label">myd-free-delivery-amount:</span>
               <span class="value"><?php var_dump(get_option('myd-free-delivery-amount')); ?></span></p>
        </div>

        <div class="section <?php echo (get_option('myd-free-delivery-enabled') === 'yes') ? 'success' : 'error'; ?>">
            <h2>✅ Processed Values (How JavaScript sees it)</h2>
            <?php
            $enabled = get_option('myd-free-delivery-enabled') === 'yes';
            $amount = floatval(get_option('myd-free-delivery-amount', 0));
            ?>
            <p><span class="label">enabled (boolean):</span>
               <span class="value"><?php echo $enabled ? 'true' : 'false'; ?></span></p>
            <p><span class="label">minimumAmount (float):</span>
               <span class="value"><?php echo $amount; ?></span></p>
        </div>

        <div class="section">
            <h2>🔧 What will be in mydStoreInfo.freeDelivery</h2>
            <pre><?php
$freeDelivery = array(
    'enabled' => get_option('myd-free-delivery-enabled') === 'yes',
    'minimumAmount' => floatval(get_option('myd-free-delivery-amount', 0)),
);
echo json_encode($freeDelivery, JSON_PRETTY_PRINT);
?></pre>
        </div>

        <div class="section <?php echo ($enabled && $amount > 0) ? 'success' : 'warning'; ?>">
            <h2>📝 Status</h2>
            <?php if ($enabled && $amount > 0): ?>
                <p style="color: #46b450; font-weight: bold;">✅ Free delivery is properly configured!</p>
                <p>When order subtotal is >= $<?php echo $amount; ?>, delivery will be free.</p>
            <?php elseif ($enabled && $amount == 0): ?>
                <p style="color: #ffb900; font-weight: bold;">⚠️ Free delivery is enabled but amount is 0</p>
                <p>Please set a minimum amount in Settings → Delivery</p>
            <?php else: ?>
                <p style="color: #dc3232; font-weight: bold;">❌ Free delivery is not enabled</p>
                <p>Please enable it in Settings → Delivery</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🎯 Next Steps</h2>
            <?php if (!$enabled): ?>
                <ol>
                    <li>Go to WordPress Admin → Settings → Delivery</li>
                    <li>Check the "Delivery gratis por monto mínimo" checkbox</li>
                    <li>Enter a minimum amount (e.g., 50)</li>
                    <li>Click "Save Changes"</li>
                    <li>Refresh this page to verify</li>
                </ol>
            <?php elseif ($amount == 0): ?>
                <ol>
                    <li>Go to WordPress Admin → Settings → Delivery</li>
                    <li>Enter a minimum amount greater than 0</li>
                    <li>Click "Save Changes"</li>
                    <li>Refresh this page to verify</li>
                </ol>
            <?php else: ?>
                <ol>
                    <li>✅ Settings are correct!</li>
                    <li>Clear your browser cache (Ctrl+Shift+R or Cmd+Shift+R)</li>
                    <li>Check the browser console for "[Free Delivery] Initializing..." message</li>
                    <li>Test with an order >= $<?php echo $amount; ?></li>
                </ol>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🔄 Test in Browser Console</h2>
            <p>Open browser console and type:</p>
            <pre>console.log(window.mydStoreInfo.freeDelivery);</pre>
            <p>Expected output:</p>
            <pre>{ enabled: <?php echo $enabled ? 'true' : 'false'; ?>, minimumAmount: <?php echo $amount; ?> }</pre>
        </div>
    </div>
</body>
</html>
