<?php
/**
 * Reference site import class.
 *
 * @package AmpProject\AmpWP
 */

namespace AmpProject\AmpWP\Tests\Cli;

use Exception;
use Google\Cloud\Storage\StorageClient;
use RuntimeException;
use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils;

final class ReferenceSiteImportCommand extends WP_CLI_Command {

	/**
	 * Imports content from a reference site definition.
	 *
	 * Uses the WordPress Importer plugin behind the scenes for performing data
	 * migrations.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the reference site definition to import.
	 *
	 * [--empty-content]
	 * : Empty the site content (posts, comments, and terms) before importing the reference content.
	 *
	 * [--empty-uploads]
	 * : Empty the site uploads folder before importing the reference content.

	 * ## EXAMPLES
	 *
	 *     # Import content from a reference site definition file
	 *     $ wp amp reference-site import example-site.json
	 *     Starting the import process...
	 *     Processing post #1 ("Hello world!") (post_type: post)
	 *     -- 1 of 1
	 *     -- Tue, 21 Jun 2016 05:31:12 +0000
	 *     -- Imported post as post_id #1
	 *     Success: Finished importing from 'example-site.json' file.
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		try {
			( new WpImporterCompat() )->prepare_import();
		} catch ( Exception $exception ) {
			WP_CLI::error( 'Unable to import reference site: ' . $exception->getMessage() );
		}

		$storage = new StorageClient();
		$storage->registerStreamWrapper();

		list( $site_definition_file ) = $args;
		$empty_content = Utils\get_flag_value( $assoc_args, 'empty-content', false );
		$empty_uploads = Utils\get_flag_value( $assoc_args, 'empty-uploads', false );

		if ( 0 !== substr_compare( $site_definition_file, '.json', -5 ) ) {
			$site_definition_file .= '.json';
		}

		if ( ! path_is_absolute( $site_definition_file ) ) {
			$site_definition_file = ReferenceSiteCommandNamespace::REFERENCE_SITES_ROOT . $site_definition_file;
		}

		if ( ! file_exists( $site_definition_file )
			|| ! is_readable( $site_definition_file ) ) {
			WP_CLI::error( "The provided site definition file '{$site_definition_file}' could not be read." );
		}

		WP_CLI::log( 'Starting the reference site import process...' );

		try {
			$site_definition = $this->load_site_definition( $site_definition_file );
		} catch ( Exception $exception ) {
			WP_CLI::error( "The provided site definition file '{$site_definition_file}' could not be parsed: {$exception->getMessage()}" );
		}

		if ( $empty_content ) {
			$this->empty_site( $empty_uploads );
		}

		WP_CLI::log(
			'Importing reference site: '
			. WP_CLI::colorize( "%y{$site_definition->get_name()}%n (%gv{$site_definition->get_version()}%n)" )
		);

		WP_CLI::log( $site_definition->get_description() );

		foreach ( $site_definition->get_attributions() as $attribution ) {
			WP_CLI::log( WP_CLI::colorize( "%b{$attribution}%n" ) );
		}

		$this->import_site( $site_definition );
	}

	/**
	 * Load and parse the site definition file.
	 *
	 * @param string $site_definition_file
	 * @return SiteDefinition Parsed site definition.
	 */
	private function load_site_definition( $site_definition_file ) {
		try {
			$site_definition_json = file_get_contents( $site_definition_file );
		} catch ( Exception $exception ) {
			$site_definition_json = false;
		}

		if ( empty( $site_definition_json ) ) {
			throw new RuntimeException( 'Failed to load the site definition file into memory.' );
		}

		$site_definition = json_decode( $site_definition_json, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			throw new RuntimeException( 'Failed to parse the site definition JSON data - ' . json_last_error_msg() );
		}

		return new SiteDefinition( $site_definition );
	}

	/**
	 * Import an entire site.
	 *
	 * @param SiteDefinition $site_definition Site definition of the site to
	 *                                        import.
	 */
	private function import_site( SiteDefinition $site_definition ) {
		foreach ( $site_definition->get_import_steps() as $import_step ) {
			switch ( $import_step['type'] ) {
				case 'activate_theme':
					( new Import\ActivateTheme( $import_step['theme'] ) )->process();
					break;
				case 'activate_plugin':
					( new Import\ActivatePlugin( $import_step['plugin'] ) )->process();
					break;
				case 'import_wxr_file':
					$wxr_path = $import_step['filename'];

					if ( ! path_is_absolute( $wxr_path ) ) {
						$wxr_path = ReferenceSiteCommandNamespace::REFERENCE_SITES_ROOT . $wxr_path;
					}
					( new Import\ImportWxrFile( $wxr_path ) )->process();
					break;
				case 'import_options':
					( new Import\ImportOptions( $import_step['options'] ) )->process();
					break;
				case 'import_widgets':
					( new Import\ImportWidgets( $import_step['widgets'] ) )->process();
					break;
				case 'import_customizer_settings':
					( new Import\ImportCustomizerSettings( $import_step['settings'] ) )->process();
					break;
			}
		}
	}

	/**
	 * Empty the site content.
	 *
	 * @param bool $empty_uploads Whether to empty the uploads folder as well.
	 */
	private function empty_site( $empty_uploads )
	{
		$command = 'site empty --yes';

		if ( $empty_uploads ) {
			$command .= ' --uploads';
		}

		WP_CLI::log(
			WP_CLI::colorize(
				$empty_uploads ? 'Emptying the site content and uploads...' : 'Emptying the site content...'
			)
		);

		WP_CLI::runcommand( $command );
	}
}