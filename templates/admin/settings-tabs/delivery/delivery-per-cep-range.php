<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active = $delivery_mode === 'per-cep-range' ? 'myd-tabs-content--active' : '' ;
/**
 * TODO: check this later
 */
if ( isset( $delivery_mode_options['per-cep-range']['options'] ) ) {
	$delivery_mode_per_cep_range_options = $delivery_mode_options['per-cep-range']['options'];
}
?>
<div class="myd-delivery-type-content <?php echo esc_attr( $active );?>" id="myd-delivery-per-cep-range">
	<h2>
		<?php esc_html_e( 'Price per Zipcode range', 'myd-delivery-pro' ); ?>
	</h2>
	<p>
		<?php esc_html_e( 'Soon we will have this option to calculate shipping using the Google Maps API.', 'myd-delivery-pro' ); ?>
	</p>

	<table class="wp-list-table widefat fixed striped myd-options-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'From Zipcode', 'myd-delivery-pro' );?></th>
                <th><?php esc_html_e( 'To Zipcode', 'myd-delivery-pro' );?></th>
                <th><?php esc_html_e( 'Price', 'myd-delivery-pro' );?></th>
                <th class="myd-options-table__action"><?php esc_html_e( 'Action', 'myd-delivery-pro' );?></th>
            </tr>
        </thead>
        <tbody>
            <?php if( isset( $delivery_mode_per_cep_range_options ) && !empty( $delivery_mode_per_cep_range_options ) ): ?>

                <?php foreach( $delivery_mode_per_cep_range_options as $k => $v ): ?>
                    <tr class="myd-options-table__row-content" data-row-index='<?php echo esc_attr( $k );?>' data-row-field-base="myd-delivery-mode-options[per-cep-range][options]">
                        <td>
                            <input name="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][from]" data-data-index="from" type="number" id="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][from]" value="<?php echo esc_attr( $v['from'] );?>" class="regular-text myd-input-full">
                        </td>
                        <td>
                            <input name="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][to]" data-data-index="to" type="number" id="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][to]" value="<?php echo esc_attr( $v['to'] );?>" class="regular-text myd-input-full">
                        </td>
                        <td>
                            <input name="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][price]" data-data-index="price" type="number" step="0.001" id="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][price]" value="<?php echo esc_attr( $v['price'] );?>" class="regular-text myd-input-full">
                        </td>
                        <td>
                            <span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)"><?php esc_html_e( 'remove', 'myd-delivery-pro' );?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>

                <tr class="myd-options-table__row-content" data-row-index='0' data-row-field-base="myd-delivery-mode-options[per-cep-range][options]">
                    <td>
                        <input name="myd-delivery-mode-options[per-cep-range][options][0][from]" data-data-index="from" type="number" id="myd-delivery-mode-options[per-cep-range][options][0][from]" value="" class="regular-text myd-input-full">
                    </td>
                    <td>
                        <input name="myd-delivery-mode-options[per-cep-range][options][0][to]" data-data-index="to" type="number" id="myd-delivery-mode-options[per-cep-range][options][0][to]" value="" class="regular-text myd-input-full">
                    </td>
                    <td>
                        <input name="myd-delivery-mode-options[per-cep-range][options][0][price]" data-data-index="price" type="number" step="0.001" id="myd-delivery-mode-options[per-cep-range][options][0][price]" value="" class="regular-text myd-input-full">
                    </td>
                    <td>
                        <span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)"><?php esc_html_e( 'remove', 'myd-delivery-pro' );?></span>
                    </td>
                </tr>

            <?php endif;?>
        </tbody>
    </table>
    <a href="#" class="button button-small button-secondary myd-repeater-table__button" onclick="window.MydAdmin.mydRepeaterTableAddRow(event)"><?php esc_html_e( 'Add more', 'myd-delivery-pro' );?></a>
</div>
