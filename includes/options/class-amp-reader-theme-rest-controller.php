<?php
/**
 * Reader theme management.
 *
 * @package AMP
 * @since 1.6.0
 */

/**
 * AMP reader theme manager class.
 *
 * @since 1.6.0
 */
final class AMP_Reader_Theme_REST_Controller extends WP_REST_Controller {

	/**
	 * Undocumented variable
	 *
	 * @since 1.6.0
	 *
	 * @var AMP_Reader_Themes
	 */
	private $reader_themes;

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param AMP_Reader_Themes $reader_themes AMP_Reader_Themes instance to provide theme data.
	 */
	public function __construct( $reader_themes ) {
		$this->reader_themes = $reader_themes;
		$this->namespace     = 'amp-wp/v1';
		$this->rest_base     = 'reader-themes';
	}

	/**
	 * Registers routes for the controller.
	 *
	 * @since 1.6.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'  => WP_REST_SERVER::READABLE,
					'callback' => [ $this, 'get_items' ],
					'args'     => [],
				],
				'schema' => $this->get_public_item_schema(),
			]
		);
	}

	/**
	 * Retrieves all AMP plugin options specified in the endpoint schema.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object.
	 */
	public function get_items( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return rest_ensure_response( $this->reader_themes->get_themes() );
	}
}