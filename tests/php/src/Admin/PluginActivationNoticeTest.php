<?php
/**
 * Tests for PluginActivationNotice class.
 *
 * @package AMP
 */

namespace AmpProject\AmpWP\Tests\Admin;

use AmpProject\AmpWP\Admin\PluginActivationNotice;
use AmpProject\AmpWP\Option;
use AMP_Options_Manager;
use WP_UnitTestCase;

/**
 * Tests for PluginActivationNotice class.
 *
 * @group plugin-activation-notice
 *
 * @since 2.0
 *
 * @covers PluginActivationNotice
 */
class PluginActivationNoticeTest extends WP_UnitTestCase {

	/**
	 * Test instance.
	 *
	 * @var PluginActivationNotice
	 */
	private $plugin_activation_notice;

	public function setUp() {
		parent::setUp();

		$this->plugin_activation_notice = new PluginActivationNotice();
		delete_option( 'amp-options' );
	}

	/**
	 * Tests PluginActivationNotice::register
	 *
	 * @covers PluginActivationNotice::register
	 */
	public function test_register() {
		$this->plugin_activation_notice->register();
		$this->assertEquals( 10, has_action( 'admin_notices', [ $this->plugin_activation_notice, 'render_notice' ] ) );
	}

	/**
	 * @covers PluginActivationNotice::render_notice
	 */
	public function test_user_sees_notice() {
		set_current_screen( 'plugins' );
		$this->assertContains( 'class="amp-plugin-notice', get_echo( [ $this->plugin_activation_notice, 'render_notice' ] ) );

		set_current_screen( 'toplevel_page_' . AMP_Options_Manager::OPTION_NAME );
		$this->assertNotContains( 'class="amp-plugin-notice', get_echo( [ $this->plugin_activation_notice, 'render_notice' ] ) );
	}

	/**
	 * @covers PluginActivationNotice::render_notice
	 */
	public function test_user_can_dismiss_notice() {
		wp_set_current_user( 1 );
		update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', PluginActivationNotice::NOTICE_ID );

		set_current_screen( 'plugins' );
		$this->assertEmpty( get_echo( [ $this->plugin_activation_notice, 'render_notice' ] ) );

		set_current_screen( 'toplevel_page_' . AMP_Options_Manager::OPTION_NAME );
		$this->assertEmpty( get_echo( [ $this->plugin_activation_notice, 'render_notice' ] ) );

		delete_user_meta( get_current_user_id(), 'dismissed_wp_pointers' );

		$GLOBALS['current_screen'] = null;
	}

	/**
	 * @covers PluginActivationNotice::render_notice
	 */
	public function test_notice_doesnt_show_if_wizard_completed() {
		$original_option = AMP_Options_Manager::get_option( Option::PLUGIN_CONFIGURED );

		AMP_Options_Manager::update_option( Option::PLUGIN_CONFIGURED, true );

		set_current_screen( 'plugins' );
		$this->assertEmpty( get_echo( [ $this->plugin_activation_notice, 'render_notice' ] ) );

		set_current_screen( 'toplevel_page_' . AMP_Options_Manager::OPTION_NAME );
		$this->assertEmpty( get_echo( [ $this->plugin_activation_notice, 'render_notice' ] ) );

		AMP_Options_Manager::update_option( Option::PLUGIN_CONFIGURED, $original_option );

		$GLOBALS['current_screen'] = null;
	}
}
