<?php
/**
 * Plugin Name: accordion-blocks — CGB Gutenberg Block Plugin
 * Plugin URI: https://github.com/ahmadawais/create-guten-block/
 * Description: accordion-blocks — is a Gutenberg plugin created via create-guten-block.
 * Author: mrahmadawais, maedahbatool
 * Author URI: https://AhmadAwais.com/
 * Version: 1.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Initializer.
 */
require_once plugin_dir_path( __FILE__ ) . 'src/init.php';

// Make sure to not redeclare the class
if (!class_exists('Gutenberg_Accordion_Blocks')) :

class Gutenberg_Accordion_Blocks {

	/**
	 * Current plugin version number
	 * Set from parent plugin file
	 */
	public $plugin_version;



	/**
	 * Class constructor
	 * Sets up the plugin, including registering scripts.
	 */
	function __construct() {
		$basename = plugin_basename(__FILE__);

		$this->plugin_version = $this->get_plugin_version();

		// Register block
		add_action('init', array($this, 'register_block'));

		// Register frontend JavaScript
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));


		// Add API endpoint to get defaults
		add_action('rest_api_init', array($this, 'register_rest_routes'));
	}



	/**
	 * Current plugin version number
	 */
	private function get_plugin_version() {
		$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);

		return (defined('WP_DEBUG') && WP_DEBUG) ? time() : $plugin_data['Version'];
	}



	/**
	 * Register the block's assets for the editor
	 */
	public function register_block() {
		// Automatically load dependencies and version
		$asset_file = include(plugin_dir_path(__FILE__) . 'build/index.asset.php');

		wp_register_script(
			'pb-accordion-blocks-script',
			plugins_url('build/index.js', __FILE__),
			$asset_file['dependencies'],
			$asset_file['version']
		);

		wp_register_style(
			'pb-accordion-blocks-style',
			plugins_url('build/index.css', __FILE__),
			array(),
			$asset_file['version']
		);

		register_block_type('accordion-blocks/accordion-item', array(
			'editor_script' => 'pb-accordion-blocks-script',
			'style'         => 'pb-accordion-blocks-style',
		));
	}



	/**
	 * Enqueue the block's assets for the frontend
	 */
	public function enqueue_frontend_assets() {
		$min = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

		wp_enqueue_script(
			'pb-accordion-blocks-frontend-script',
			plugins_url("js/accordion-blocks$min.js", __FILE__),
			array('jquery'),
			$this->plugin_version,
			true
		);
	}



	/**
	 * Register rest endpoint to get plugin defaults
	 */
	public function register_rest_routes() {
		register_rest_route('accordion-blocks/v1', '/defaults', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'api_get_defaults'),
		));

		register_rest_route('accordion-blocks/v1', '/defaults', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => array($this, 'api_set_defaults'),
			'permission_callback' => array($this, 'check_permissions'),
		));
	}



	/**
	 * Get accordion block default settings
	 *
	 * @return object Default accordion block settings object
	 */
	public function api_get_defaults() {
		$defaults = $this->get_defaults();

		/*
		 * If there are no defaults set yet, set them now
		 * This will likely only happen when users upgrade from an older version
		 * of the plugin.
		 */
		if (!$defaults) {
			$defaults = (object) array(
				'initiallyOpen' => false,
				'clickToClose'  => true,
				'autoClose'     => true,
				'scroll'        => false,
				'scrollOffset'  => 0,
			);

			$this->set_defaults($defaults);
		}

		$response = new WP_REST_Response($defaults);
		$response->set_status(200);

		return $response;
	}



	/**
	 * Set accordion block default settings
	 *
	 * @param data object The date passed from the API
	 * @return object Default accordion block settings object
	 */
	public function api_set_defaults($request) {
		$old_defaults = $this->get_defaults();

		$new_defaults = json_decode($request->get_body());

		$new_defaults = (object) array(
			'initiallyOpen' => isset($new_defaults->initiallyOpen) ? $new_defaults->initiallyOpen : $old_defaults->initiallyOpen,
			'clickToClose'  => isset($new_defaults->clickToClose)  ? $new_defaults->clickToClose  : $old_defaults->clickToClose,
			'autoClose'     => isset($new_defaults->autoClose)     ? $new_defaults->autoClose     : $old_defaults->autoClose,
			'scroll'        => isset($new_defaults->scroll)        ? $new_defaults->scroll        : $old_defaults->scroll,
			'scrollOffset'  => isset($new_defaults->scrollOffset)  ? $new_defaults->scrollOffset  : $old_defaults->scrollOffset,
		);

		$this->set_defaults($new_defaults);

		$response = new WP_REST_Response($new_defaults);
		$response->set_status(201);

		return $response;
	}



	/**
	 * Ensure user has permission to set defaults
	 */
	public function check_permissions() {
		return current_user_can('edit_posts');
	}



	/**
	 * Get default settings from `wp_options` table
	 */
	private function get_defaults() {
		return get_option('accordion_blocks_defaults');
	}



	/**
	 * Save default settings in `wp_options` table
	 */
	private function set_defaults($settings) {
		return update_option('accordion_blocks_defaults', $settings);
	}

}

$Gutenberg_Accordion_Blocks = new Gutenberg_Accordion_Blocks;

endif;
