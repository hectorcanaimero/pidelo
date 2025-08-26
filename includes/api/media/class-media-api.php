<?php

namespace MydPro\Includes\Api\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Upload API endpoints
 */
class Media_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_media_routes' ] );
	}

	/**
	 * Register media routes
	 */
	public function register_media_routes() {
		// POST /media/upload - Upload image as base64
		\register_rest_route(
			'myd-delivery/v1',
			'/media/upload',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'upload_base64_image' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'filename' => array(
							'description' => __( 'Image filename', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
						'file_data' => array(
							'description' => __( 'Base64 encoded image data', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
						'title' => array(
							'description' => __( 'Image title', 'myd-delivery-pro' ),
							'type' => 'string',
						),
						'alt_text' => array(
							'description' => __( 'Image alt text', 'myd-delivery-pro' ),
							'type' => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Upload base64 image
	 */
	public function upload_base64_image( $request ) {
		$filename = sanitize_file_name( $request['filename'] );
		$file_data = $request['file_data'];
		$title = sanitize_text_field( $request['title'] ?? '' );
		$alt_text = sanitize_text_field( $request['alt_text'] ?? '' );

		// Validate base64 data
		if ( strpos( $file_data, 'data:image/' ) !== 0 ) {
			return new \WP_Error( 'invalid_format', __( 'Invalid base64 image format', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Extract image data
		$data = explode( ',', $file_data );
		if ( count( $data ) !== 2 ) {
			return new \WP_Error( 'invalid_data', __( 'Invalid base64 data', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		$header = $data[0];
		$image_data = base64_decode( $data[1] );

		if ( ! $image_data ) {
			return new \WP_Error( 'decode_failed', __( 'Failed to decode base64 data', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Get mime type from header
		preg_match( '/data:image\/(\w+);base64/', $header, $matches );
		$image_type = $matches[1] ?? 'jpeg';
		
		// Validate file extension
		$allowed_types = array( 'jpeg', 'jpg', 'png', 'gif', 'webp' );
		if ( ! in_array( $image_type, $allowed_types ) ) {
			return new \WP_Error( 'invalid_type', __( 'Invalid image type', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Ensure filename has correct extension
		$file_extension = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( empty( $file_extension ) ) {
			$filename .= '.' . ( $image_type === 'jpeg' ? 'jpg' : $image_type );
		}

		// Upload to WordPress
		$upload = \wp_upload_bits( $filename, null, $image_data );

		if ( $upload['error'] ) {
			return new \WP_Error( 'upload_failed', $upload['error'], array( 'status' => 500 ) );
		}

		// Create attachment
		$attachment_data = array(
			'post_mime_type' => 'image/' . $image_type,
			'post_title' => $title ?: sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content' => '',
			'post_status' => 'inherit',
		);

		$attachment_id = \wp_insert_attachment( $attachment_data, $upload['file'] );

		if ( is_wp_error( $attachment_id ) ) {
			return new \WP_Error( 'attachment_failed', __( 'Failed to create attachment', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		// Generate metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_metadata = \wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		\wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

		// Set alt text
		if ( $alt_text ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		$response = array(
			'id' => $attachment_id,
			'url' => $upload['url'],
			'filename' => $filename,
			'title' => $title,
			'alt_text' => $alt_text,
			'mime_type' => 'image/' . $image_type,
			'file_size' => strlen( $image_data ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Check admin permissions
	 */
	public function check_admin_permissions() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You do not have permission to upload files.', 'myd-delivery-pro' ), array( 'status' => 403 ) );
		}
		return true;
	}
}

new Media_Api();