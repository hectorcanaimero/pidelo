<?php

namespace MydPro\Includes\Custom_Fields;

use MydPro\Includes\Custom_Fields\Label;
use MydPro\Includes\Legacy\Legacy_Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Myd_Custom_Fiedls
 *
 * Implement custom fields to plugin.
 *
 * TODO: implement field->before and field->after para fazer vários tipos de inputs (table e outros).
 *
 * @since 1.9.5
 */
class Myd_Custom_Fields {
	/**
	 * Array with all data to create meta boxes and custom fields
	 */
	protected $fields = [];

	/**
	 * List all custom fields name
	 */
	protected $list_fields = [];

	/**
	 * List all screens used
	 */
	protected $screens = [];

	/**
	 * Construct class
	 *
	 * @since 1.9.5
	 * @param $fields
	 */
	public function __construct( array $fields ) {
		$this->fields = $fields;
		$this->list_fields = $this->get_list_fields();
		$this->screens = array_unique( array_column( $this->fields, 'screens' ) );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_fields' ], 10, 2 );
	}

	/**
	 * Add fields
	 *
	 * Add custom meta boxes and fileds.
	 *
	 * @since 1.9.5
	 */
	public function add_meta_box() {
		if( ! empty( $this->fields ) ) {
			foreach ( $this->fields as $arg ) {
				$context = isset( $arg['context'] ) ? $arg['context'] : 'normal';
				$priority = isset( $arg['priority'] ) ? $arg['priority'] : 'high';

				add_meta_box(
					$arg['id'],
					$arg['name'],
					array( $this, 'output_fields' ),
					$arg['screens'],
					$context,
					$priority
				);
			}
		}
	}

	/**
	 * Save field
	 *
	 * Action to check and save filed after $_POST.
	 *
	 * @since 1.9.5
	 */
	public function save_fields( int $post_id, $post ) {
		/**
		 * Check if is a valid nonce
		 */
		if ( ! isset( $_POST['myd_inner_meta_box_nonce'] ) ) {
			return;
		}

		$nonce = $_POST['myd_inner_meta_box_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'myd_inner_meta_box' ) ) {
			return;
		}

		/**
		 * Do not save if is auto save action
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		/**
		 * Check user permission
		 */
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/**
		 * Check is current screens is used
		 */
		if ( ! in_array( $post->post_type, $this->screens ) ) {
			return;
		}

		/**
		 * Check $_POST exist and update post meta
		 * invert to make foreach on $_POST and verify for scecific list_fields or some pattern to try get repeater values
		 */
		foreach ( $this->list_fields as $field_name ) {
			if ( array_key_exists( $field_name, $_POST ) ) {
				$value = wp_unslash( $_POST[ $field_name ] );
				if ( ! is_array( $value ) ) {
					$value = sanitize_text_field( $value );
				} else {
					/**
					 * Check for values in each key element and remove it if all internal items are empty.
					 */
					foreach ( $value as $key => $item_value ) {
						$it = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $item_value ) );
						$filtered_values = array();
						foreach ( $it as $v ) {
							if ( ! empty( $v ) ) {
								$filtered_values[] = $v;
							}
						}

						if ( empty( $filtered_values ) && isset( $value[ $key ] ) ) {
							unset( $value[ $key ] );
						}
					}
				}

				update_post_meta( $post_id, $field_name, $value );
			}
		}
	}

	/**
	 * Template field
	 *
	 * Implement template filed to custom post.
	 *
	 * @since 1.9.5
	 */
	public function output_fields( $post, $metabox ) {
		$fields = $this->fields[ $metabox['id'] ]['fields'];
		/**
		 * Render inputs by type
		 */
		$rendered_fields = array();
		foreach ( $fields as $field ) {
			$rendered_fields[] = $this->render_inputs( $field, $post->ID );
		}

		/**
		 * Add nonce to security check later.
		 */
		wp_nonce_field( 'myd_inner_meta_box', 'myd_inner_meta_box_nonce' );

		$metabox_wrapper = $this->fields[ $metabox['id'] ]['wrapper'] ?? '';
		if ( $metabox_wrapper === 'wide' ) {
			echo $rendered_fields[0]['input'];
			return;
		}

		?>
		<table class="form-table">
			<tbody>
				<?php foreach ( $rendered_fields as $field ) : ?>
					<tr>
						<th scope="row"><?php echo $field['label']; ?></th>
						<td><?php echo $field['input']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render inputs
	 *
	 * Render inputs by type
	 *
	 * @since 1.9.5
	 * @return array
	 */
	public function render_inputs( array $args, int $post_id ) {
		$field = $args;
		$storaged_value = $value = get_post_meta( $post_id, $field['name'], true );
		if ( empty( $storaged_value ) && isset( $field['value'] ) && ! empty( $field['value'] ) ) {
			$value = $field['value'];
		} else {
			$value = $storaged_value;
		}
		switch ( $field['type'] ) {
			case 'text':
				$input = $this->render_input_text( $field, $post_id, $value );
				break;

			case 'number':
				$input = $this->render_input_number( $field, $post_id, $value );
				break;

			case 'select':
				$input = $this->render_input_select( $field, $post_id, $value );
				break;

			case 'textarea':
				$input = $this->render_input_textarea( $field, $post_id, $value );
				break;

			case 'image':
				$input = $this->render_input_image( $field, $post_id, $value );
				break;

			case 'repeater':
				$input = $this->render_input_repeater( $field, $post_id, $value );
				break;

			case 'checkbox':
				$input = $this->render_input_checkbox( $field, $post_id, $value );
				break;
			case 'order-note':
				$input = $this->render_order_note( $field, $post_id, $value );
				break;
		}

		$label = new Label( $field );
		$rendered_fields = [
			'label' => $label->output(),
			'input' => $input
		];

		return $rendered_fields;
	}

	/**
	 * Build data-attr.
	 * Move it to abstract class and reuse it after.
	 */
	public function build_data_attr( array $data_attr ) {
		if ( ! is_array( $data_attr ) || empty( $data_attr ) ) {
			return '';
		}

		$output_data_attr = array();
		foreach ( $data_attr as $data_key => $data_value ) {
			$output_data_attr[] = $data_key . '="' . $data_value . '"';
		}

		return implode( $output_data_attr );
	}

	/**
	 * Render input type Text
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_text( array $args, int $post_id, string $value = '' ) {
		$required = isset( $args['required'] ) ? $args['required'] : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] . ' regular-text' : 'regular-text';

		return sprintf(
			'<input name="%s" type="text" id="%s" value="%s" class="%s" %s %s>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			esc_attr( $class ),
			$required === true ? 'required' : '',
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : ''
		);
	}

	/**
	 * Render input type Number
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_number( array $args, int $post_id, string $value = '' ) {
		$required = isset( $args['required'] ) ? $args['required'] : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] . ' regular-text' : 'regular-text';

		return sprintf(
			'<input name="%s" type="number" id="%s" value="%s" min="%s" max="%s" step="%s" class="%s" %s %s>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			isset( $args['min'] ) ? esc_attr( $args['min'] ) : '',
			isset( $args['max'] ) ? esc_attr( $args['max'] ) : '',
			isset( $args['step'] ) ? $args['step'] : 'any',
			esc_attr( $class ),
			$required === true ? 'required' : '',
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : ''
		);
	}

	/**
	 * Render input type Textarea
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_textarea( array $args, int $post_id, string $value = '' ) {
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] . ' large-text' : 'large-text';

		return sprintf(
			'<textarea name="%s" id="%s" cols="50" rows="5" class="%s" %s %s>%s</textarea>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $class ),
			isset( $args['required'] ) && $args['required'] === true ? 'required' : '',
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '',
			esc_attr( $value )
		);
	}

	/**
	 * Render input type Order Note
	 *
	 * @since 1.9.5
	 */
	public function render_order_note( array $args, int $post_id, array $value = array() ) {
		$output = array();
		foreach ( $value as $note ) {
			$output[] = sprintf(
				'<div class="order-note order-note--%s"><span class="order-note__text">%s</span><span class="order-note__date">%s</span></div>',
				esc_attr( $note['type'] ?? '' ),
				esc_html( $note['note'] ?? '' ),
				esc_html( $note['date'] ?? '' ),
			);
		}

		return implode( $output );
	}

	/**
	 * Render input type Image
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_image( array $args, int $post_id, string $value = '' ) {
		$img_url = wp_get_attachment_image_url( $value, 'medium' ) ?? '';
		$hidden_image_class = $img_url !== false ? '' : 'myd-admin-hidden';

		if( current_user_can('upload_files') ) {
			wp_enqueue_media();
			wp_enqueue_script( 'myd-admin-cf-media-library' );
		}

		$image_preview = sprintf(
			'<div class="myd-custom-field-image-wrapper"><img class="myd-custom-field__image-preview %s" src="%s" id="myd-cf-image-preview"></div>',
			esc_attr( $hidden_image_class ),
			esc_url( $img_url )
		);

		$image_input = sprintf(
			'<input type="hidden" id="myd-custom-field-image-id" name="%s" value="%s">',
			esc_attr( isset( $args['name'] ) ? $args['name'] : '' ),
			esc_attr( $value )
		);

		return sprintf(
			'%s %s <button href="#" class="button button-primary" id="myd-cf-chose-media">%s</button> <button href="#" class="button" id="myd-cf-remove-media">%s</button>',
			$image_preview,
			$image_input,
			esc_html__( 'Choose', 'myd-delivery-pro' ),
			esc_html__( 'Remove', 'myd-delivery-pro' )
		);
	}

	/**
	 * Render input type Text
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_checkbox( array $args, int $post_id, string $value = '' ) {
		$required = isset( $args['required'] ) ? $args['required'] : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';

		return sprintf(
			'<input name="%1$s" type="hidden" data-id="%2$s" value="0" class="%3$s" %4$s %5$s>
			<input name="%1$s" type="checkbox" id="%2$s" value="1" class="%3$s" %4$s %5$s %6$s>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $class ),
			$required === true ? 'required' : '',
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '',
			checked( $value, '1', false )
		);
	}

	/**
	 * Get repeater values from db (new implementation).
	 */
	public function build_repeater_object( $fields, $fields_value ) {
		if ( ! is_array( $fields_value ) || empty( $fields_value ) ) {
			return;
		}

		$builded_object = array();
		$size_of_array = count( $fields_value );
		$size_of_array = (int) $size_of_array - 1;

		for ( $limit = 0; $limit <= $size_of_array; $limit++ ) {
			foreach ( $fields as $field ) {
				if ( isset( $field['fields'] ) ) {
					$builded_object[ $limit ][ $field['name'] ] = $this->build_repeater_object( $field['fields'], $fields_value[ $limit ][ $field['name'] ] );
				} else {
					$builded_object[ $limit ][ $field['name'] ] = $field;
					$builded_object[ $limit ][ $field['name'] ]['value'] = $fields_value[ $limit ][ $field['name'] ] ?? '';
				}
			}
		}

		return $builded_object;
	}

	/**
	 * Render input type Repeater
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_repeater( array $args, int $post_id, $value = '' ) {
		$repeater_main_field = $args['name'];
		$repeater_legacy_main_field = $args['legacy'] ?? '';
		$repeater_main_field_value = get_post_meta( $post_id, $repeater_main_field, true );
		$repeater_legacy_main_field_value = get_post_meta( $post_id, $repeater_legacy_main_field, true );
		$to_render = array();

		/**
		 * TODO: move to other method.
		 * Build array of value when the value is empty to maintain the compatibility with other builds/checks of repeater method.
		 */
		if ( empty( $repeater_main_field_value ) && empty( $repeater_legacy_main_field_value ) ) {
			$new_value = array();
			foreach ( $args['fields'] as $value ) {
				if ( $value['type'] !== 'repeater' ) {
					$new_value[ $value['name'] ] = '';
				} else {
					$internal_new_value =  array();
					foreach ( $value['fields'] as $internal_value ) {
						$internal_new_value[ $internal_value['name'] ] = '';
					}
					$new_value[ $value['name'] ][] = $internal_new_value;
				}
			}

			$value = array( $new_value );
		}

		$update_db = Legacy_Repeater::need_update_db( $repeater_legacy_main_field_value, $repeater_main_field_value );
		if ( $update_db ) {
			$value = Legacy_Repeater::update_repeater_database( $repeater_legacy_main_field_value, $args, $post_id );
		}

		$args['fields'] = $this->build_repeater_object( $args['fields'], $value );
		$to_render = $args['fields'];

		if ( empty( $to_render ) ) {
			return;
		}

		$item_id = $args['id'] ?? '';

		wp_enqueue_script( 'myd-admin-cf-repeater' );

		ob_start();

		?>
			<div class="myd-repeater-wrapper" id="<?php echo esc_attr( $item_id ); ?>">
				<?php foreach ( $to_render as $key => $arg ) : ?>
					<?php $this->repeater_template( $arg, $post_id, $item_id, $key ); ?>
				<?php endforeach; ?>

				<button href="#" class="button button-primary myd-repeater-add-extra" id="myd-repeater-add-extra" data-row="<?php echo esc_attr( $item_id ); ?>"><?php esc_html_e( 'Add product extra', 'myd-delivery-pro' ); ?></button>
			</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Template to render repeater
	 *
	 * @param array $fields
	 * @param int $post_id
	 * @return void
	 */
	public function repeater_template( $fields, $post_id, $item_id = '', $loop_key ) {
		?>
			<div class="myd-repeater-container myd-repeater-container--top-level">
				<div class="myd-repeater-container__remove">X</div>
				<?php foreach ( $fields as $key => $field ) : ?>
					<?php if ( isset( $field['type'] ) ) : ?>
						<?php
							$class = ! empty( $field['custom_class'] ) ? 'myd-repeater-row' . ' ' . $field['custom_class'] : 'myd-repeater-row';
							$field['custom_class'] = 'myd-repeater-input';

							$field['data'] = array(
								'data-main-index' => $loop_key,
								'data-name' => $item_id . '[{{main-index}}]' . '[' . $field['name'] . ']',
							);

							$field['name'] = $item_id . '[' . $loop_key . ']' . '[' . $field['name'] . ']';
						?>
						<div class="<?php echo esc_attr( $class ); ?>">
							<?php echo implode( $this->render_inputs( $field, $post_id ) ); ?>
						</div>
					<?php else : ?>
							<?php foreach ( $field as $internal_key => $internal_field ) : ?>
								<details class="myd-repeater-container myd-repeater-container--internal" data-index="<?php echo esc_attr( $key ); ?>">
									<summary class="myd-repeater-summary">
										<span class="myd-repeater-summary__title"><?php echo esc_html( $internal_field['extra_option_name']['value'] ); ?></span>
										<span class="myd-repeater-summary__action--remove" data-row="<?php echo esc_attr( $item_id ); ?>"><?php esc_html_e( 'remove', 'myd-delivery-pro' ); ?></span>
									</summary>

									<div class="myd-repeater-container">
										<?php foreach ( $internal_field as $internal_field2 ) : ?>
											<?php
												$class = ! empty( $internal_field2['custom_class'] ) ? 'myd-repeater-row' . ' ' . $internal_field2['custom_class'] : 'myd-repeater-row';
												$internal_field2['custom_class'] = 'myd-repeater-input';

												$internal_field2['data'] = array(
													'data-main-index' => $loop_key,
													'data-name' => $item_id . '[{{main-index}}]' . '[' . $key . ']' . '[{{internal-index}}]' . '[' . $internal_field2['name'] . ']',
													'data-internal-index' => $internal_key,
												);

												$internal_field2['name'] = $item_id . '[' . $loop_key . ']' . '[' . $key . ']' . '[' . $internal_key . ']' . '[' . $internal_field2['name'] . ']';
											?>
											<div class="<?php echo esc_attr( $class ); ?>">
												<?php echo implode( $this->render_inputs( $internal_field2, $post_id ) ); ?>
											</div>
										<?php endforeach; ?>
									</div>
								</details>
							<?php endforeach; ?>

							<button href="#" class="button button-secondary myd-extra-option-button myd-repeater-add-option" id="myd-repeater-add-option"><?php _e( 'Add option', 'myd-delivery-pro' ); ?></button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php
	}

	/**
	 * Render input type Select
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_select( array $args, int $post_id, string $value = '' ) {
		if (
			empty( $value ) &&
			isset( $args['default_value'] ) &&
			! empty( $args['default_value'] )
		) {
			$value = $args['default_value'];
		}

		$options = array();
		foreach ( $args['select_options'] as $key => $option ) {
			$options[] = '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $value, false ) . '>' . esc_html( $option ) . '</option>';
		}

		$required = isset( $args['required'] ) && $args['required'] === true ? 'required' : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';

		return sprintf(
			'<select name="%s" id="%s" class="%s" %s %s><option value="">%s</option>%s</select>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $class ),
			esc_attr( $required ),
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '',
			esc_html__( 'Select', 'myd-delivery-pro' ),
			implode( $options )
		);
	}

	/**
	 * Get list fields
	 *
	 * @since 1.9.5
	 * @return array
	 */
	public function get_list_fields() {
		$fields = array_column( $this->fields, 'fields' );
		$list_fields = [];

		foreach ( $fields as $v ) {
			$list_fields = array_merge( $list_fields, array_column( $v, 'name' ) );
		}

		return $list_fields;
	}
}
