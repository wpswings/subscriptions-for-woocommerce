<?php
/**
 * Unit tests for the settings markup allowlist (wps_sfw_settings_allowed_html).
 *
 * Regression guard for the bug where settings tabs were printed through
 * wp_kses_post(), which silently strips <input>, <select>, and <option>. That
 * left toggles unclickable and stopped the form from submitting any values, so
 * settings could not be saved. wps_sfw_settings_allowed_html() must keep those
 * controls intact when the trusted markup is escaped with wp_kses().
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Test case for the shared settings renderer allowlist.
 */
class SettingsMarkupTest extends WP_UnitTestCase {

	/**
	 * Plugin object exposing the shared renderer + allowlist.
	 *
	 * @var Subscriptions_For_Woocommerce
	 */
	private $plugin;

	/**
	 * Representative settings definition covering each control type.
	 *
	 * @var array
	 */
	private $components;

	/**
	 * Set up test fixtures before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->plugin = $GLOBALS['sfw_wps_sfw_obj'];

		$this->components = array(
			array(
				'title' => 'Enable Tracking',
				'type'  => 'radio-switch',
				'id'    => 'wps_sfw_enable_tracking',
				'value' => 'on',
			),
			array(
				'title' => 'API Key',
				'type'  => 'text',
				'id'    => 'wps_sfw_api_key',
				'value' => 'abc123',
			),
			array(
				'title'   => 'Mode',
				'type'    => 'select',
				'id'      => 'wps_sfw_mode',
				'value'   => 'live',
				'options' => array(
					'live'    => 'Live',
					'sandbox' => 'Sandbox',
				),
			),
			array(
				'type'        => 'button',
				'id'          => 'wps_sfw_save_general_settings',
				'button_text' => 'Save',
			),
		);
	}

	/**
	 * The renderer itself must emit the raw form controls.
	 *
	 * @return void
	 */
	public function test_renderer_emits_form_controls() {
		$html = $this->plugin->wps_sfw_plug_generate_html( $this->components );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'id="wps_sfw_api_key"', $html );
		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( '<option', $html );
	}

	/**
	 * Allowlist must preserve every control when escaping with wp_kses().
	 *
	 * @return void
	 */
	public function test_allowlist_preserves_controls() {
		$html     = $this->plugin->wps_sfw_plug_generate_html( $this->components );
		$filtered = wp_kses( $html, $this->plugin->wps_sfw_settings_allowed_html() );

		// Toggle checkbox + its id survive, so the label can toggle it and POST it.
		$this->assertStringContainsString( 'type="checkbox"', $filtered );
		$this->assertStringContainsString( 'id="wps_sfw_enable_tracking"', $filtered );

		// Text input survives.
		$this->assertStringContainsString( 'id="wps_sfw_api_key"', $filtered );
		$this->assertStringContainsString( 'value="abc123"', $filtered );

		// Select + options survive with the selected value.
		$this->assertStringContainsString( '<select', $filtered );
		$this->assertStringContainsString( '<option value="live"', $filtered );
		$this->assertStringContainsString( 'selected', $filtered );

		// Save button survives (it always did — <button> is allowed by default).
		$this->assertStringContainsString( '<button', $filtered );
	}

	/**
	 * Documents the original bug: wp_kses_post() drops the input controls.
	 *
	 * @return void
	 */
	public function test_wp_kses_post_strips_controls() {
		$html     = $this->plugin->wps_sfw_plug_generate_html( $this->components );
		$stripped = wp_kses_post( $html );

		// The regression: inputs and selects vanish under the default post allowlist.
		$this->assertStringNotContainsString( 'type="checkbox"', $stripped );
		$this->assertStringNotContainsString( '<input', $stripped );
		$this->assertStringNotContainsString( '<select', $stripped );
	}
}
