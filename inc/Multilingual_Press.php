<?php # -*- coding: utf-8 -*-
/**
 * Class Multilingual_Press
 *
 * Kind of a front controller.
 *
 * @version 2014.07.16
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Multilingual_Press {

	/**
	 * The linked elements table
	 *
	 * @since  0.1
	 * @var    string
	 */
	private $link_table = '';

	/**
	 * Local path to plugin file.
	 *
	 * @var string
	 */
	private $plugin_file_path;

	/**
	 * Overloaded instance for plugin data.
	 *
	 * @needs-refactoring
	 * @var Inpsyde_Property_List_Interface
	 */
	private $plugin_data;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor
	 *
	 * @param Inpsyde_Property_List_Interface $data
	 * @param wpdb $wpdb
	 */
	public function __construct( Inpsyde_Property_List_Interface $data, wpdb $wpdb = NULL ) {

		/* Someone has an old Free version active and activates the new Pro on
		 * top of that. The old Free version tries now to create an instance of
		 * this new version of the class, and the second parameter is missing.
		 * This is where we stop.
		 */
		if ( NULL === $wpdb )
			return;

		$this->plugin_data = $data;
		$this->wpdb        = $wpdb;
	}

	/**
	 * Initial setup handler.
	 *
	 * @global	$wpdb wpdb WordPress Database Wrapper
	 * @global	$pagenow string Current Page Wrapper
	 * @return void
	 */
	public function setup() {

		$this->prepare_plugin_data();
		$this->load_assets();
		$this->prepare_helpers();
		$this->plugin_data->freeze(); // no changes allowed anymore

		require 'functions.php';

		if ( ! $this->is_active_site() )
			return;

		// Hooks and filters
		add_action( 'inpsyde_mlp_loaded', array ( $this, 'load_plugin_textdomain' ), 1 );

		// Load modules
		$this->load_features();

		/**
		 * First entry for MultilingualPress.
		 * Runs before internal actions are registered.
		 *
		 * @param Inpsyde_Property_List_Interface $plugin_data
		 * @param wpdb                            $wpdb
		 */
		do_action( 'inpsyde_mlp_init', $this->plugin_data, $this->wpdb );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array ( $this, 'admin_scripts' ) );

		// Cleanup upon blog delete
		add_filter( 'delete_blog', array ( $this, 'delete_blog' ), 10, 2 );

		// Check for errors
		add_filter( 'all_admin_notices', array ( $this, 'check_for_user_errors_admin_notice' ) );

		add_action( 'wp_loaded', array ( $this, 'late_load' ), 0 );

		/**
		 * Second entry for MultilingualPress
		 *
		 * Runs after internal actions are registered.
		 *
		 * @param Inpsyde_Property_List_Interface $plugin_data
		 * @param wpdb                            $wpdb
		 */
		do_action( 'inpsyde_mlp_loaded', $this->plugin_data, $this->wpdb );

		if ( is_admin() )
			$this->run_admin_actions();
		else
			$this->run_frontend_actions();
	}

	/**
	 * Check if the current context needs more MultilingualPress actions.
	 *
	 * @return bool
	 */
	private function is_active_site() {

		global $pagenow;

		if ( in_array( $pagenow, array ( 'admin-post.php', 'admin-ajax.php' ) ) )
			return TRUE;

		if ( is_network_admin() )
			return TRUE;

		$relations = get_site_option( 'inpsyde_multilingual', array () );

		if ( array_key_exists( get_current_blog_id(), $relations ) )
			return TRUE;

		return FALSE;
	}
	/**
	 * @return void
	 */
	public function late_load() {

		/**
		 * Late loading event for MLP.
		 *
		 * @param Inpsyde_Property_List_Interface $plugin_data
		 * @param wpdb                            $wpdb
		 */
		do_action( 'mlp_and_wp_loaded', $this->plugin_data, $this->wpdb );
	}

	/**
	 * Load the localization
	 *
	 * @since 0.1
	 * @uses load_plugin_textdomain, plugin_basename
	 * @return void
	 */
	public function load_plugin_textdomain() {

		$rel_path = dirname( plugin_basename( $this->plugin_file_path ) )
				. $this->plugin_data->text_domain_path;

		load_plugin_textdomain( 'multilingualpress', FALSE, $rel_path );
	}

	/**
	 * Register assets internally
	 *
	 * @return void
	 */
	public function load_assets() {

		/** @type Mlp_Assets $assets */
		$assets = $this->plugin_data->assets;
		$assets->add( 'mlp_backend_js',   'backend.js', array ( 'jquery' ) );
		$assets->add( 'mlp_backend_css',  'backend.css' );
		$assets->add( 'mlp_frontend_js',  'frontend.js', array ( 'jquery' ) );
		$assets->add( 'mlp_frontend_css', 'frontend.css' );

		add_action( 'init', array ( $assets, 'register' ), 0 );

	}

	/**
	 * Create network settings page.
	 *
	 * @return  void
	 */
	private function load_module_settings_page() {

		$settings = new Mlp_General_Settingspage( $this->plugin_data->module_manager, $this->plugin_data->assets );
		add_action( 'plugins_loaded', array ( $settings, 'setup' ), 8 );
	}

	/**
	 * Create site settings page.
	 *
	 * @return  void
	 */
	private function load_site_settings_page() {

		$settings = new Mlp_General_Settingspage( $this->plugin_data->site_manager, $this->plugin_data->assets );
		$settings->setup();
		add_action( 'plugins_loaded', array ( $settings, 'setup' ), 8 );
	}

	/**
	 * Find and load core and pro features.
	 *
	 * @access	public
	 * @since	0.1
	 * @return	array Files to include
	 */
	protected function load_features() {

		$found = array ();
		$dirs  = array (
			'core',
			'pro'
		);

		foreach ( $dirs as $dir ) {

			$path = $this->plugin_data->plugin_dir_path . "/inc/$dir";

			if ( ! is_readable( $path ) )
				continue;

			$files = glob( "$path/feature.*.php" );

			if ( empty ( $files ) )
				continue;

			foreach ( $files as $file ) {
				$found[] = $file;
				require $file;
			}
		}

		// We need the return value for tests.
		return $found;
	}

	/**
	 * Load admin javascript and CSS
	 *
	 * @global	$pagenow | current page identifier
	 * @return  void
	 */
	public function admin_scripts() {

		global $pagenow;

		// We only need our Scripts on our pages
		$pages = array (
			'site-info.php',
			'site-users.php',
			'site-themes.php',
			'site-settings.php',
			'settings.php',
			'post-new.php',
			'post.php'
		);

		if ( in_array ( $pagenow, $pages ) ) {
			//wp_enqueue_script( 'mlp-js', $this->plugin_data->js_url . 'multilingual_press.js' );
			wp_localize_script( 'mlp_backend_js', 'mlp_loc', $this->localize_script() );
			//wp_enqueue_style( 'mlp-admin-css' );
		}
	}

	/**
	 * Make localized strings available in javascript
	 *
	 * @access  public
	 * @since	0.1
	 * @uses	wp_create_nonce
	 * @global	$pagenow | current page identifier
	 * @return	array $loc | Array containing localized strings
	 */
	public function localize_script() {

		if ( isset( $_GET[ 'id' ] ) )
			$blog_id = $_GET[ 'id' ];
		else
			$blog_id = 0;

		$loc = array (
			'tab_label'							=> __( 'MultilingualPress', 'multilingualpress' ),
			'blog_id'							=> intval( $blog_id ),
			'ajax_tab_nonce'					=> wp_create_nonce( 'mlp_tab_nonce' ),
			'ajax_form_nonce'					=> wp_create_nonce( 'mlp_form_nonce' ),
			'ajax_select_nonce'					=> wp_create_nonce( 'mlp_select_nonce' ),
			'ajax_switch_language_nonce'		=> wp_create_nonce( 'mlp_switch_language_nonce' ),
			'ajax_check_single_nonce'			=> wp_create_nonce( 'mlp_check_single_nonce' )
		);

		return $loc;
	}

	/**
	 * Delete removed blogs from site_option 'inpsyde_multilingual'
	 * and cleanup linked elements table
	 *
	 * @param	int $blog_id
	 * @since	0.3
	 * @uses	get_site_option, update_site_option
	 * @global	$wpdb | WordPress Database Wrapper
	 * @return	void
	 */
	public function delete_blog( $blog_id ) {

		global $wpdb;

		$current_blog_id = $blog_id;

		// Update Blog Relationships
		// Get blogs related to the current blog
		$all_blogs = get_site_option( 'inpsyde_multilingual' );

		if ( ! $all_blogs )
			$all_blogs = array ();

		// The user defined new relationships for this blog. We add it's own ID
		// for internal purposes
		$data[ 'related_blogs' ][] = $current_blog_id;

		// Loop through related blogs
		foreach ( $all_blogs as $blog_id => $blog_data ) {

			if ( $current_blog_id != $blog_id )
				$this->plugin_data->site_relations->delete_relation( $blog_id );
		}

		// Update site_option
		$blogs = (array) get_site_option( 'inpsyde_multilingual', array () );

		if ( ! empty ( $blogs ) && array_key_exists( $current_blog_id, $blogs ) ) {
			unset( $blogs[ $current_blog_id ] );
			update_site_option( 'inpsyde_multilingual', $blogs );
		}

		// Cleanup linked elements table
		$wpdb->query(
			 $wpdb->prepare(
				  'DELETE FROM ' . $this->link_table . ' WHERE `ml_source_blogid` = %d OR `ml_blogid` = %d',
					$blog_id,
					$blog_id
			 )
		);
	}

	/**
	 * Checks for errors
	 *
	 * @access	public
	 * @since	0.8
	 * @uses
	 * @return	boolean
	 */
	public function check_for_user_errors() {

		return $this->check_for_errors();
	}

	/**
	 * Checks for errors
	 *
	 * @access	public
	 * @since	0.9
	 * @uses
	 * @return	void
	 */
	public function check_for_user_errors_admin_notice() {

		if ( TRUE == $this->check_for_errors() ) {
			?><div class="error"><p><?php _e( 'You didn\'t setup any site relationships. You have to setup these first to use MultilingualPress. Please go to Network Admin &raquo; Sites &raquo; and choose a site to edit. Then go to the tab MultilingualPress and set up the relationships.' , 'multilingualpress' ); ?></p></div><?php
		}
	}

	/**
	 * Checks for errors
	 *
	 * @return	boolean
	 */
	public function check_for_errors() {

		if ( defined( 'DOING_AJAX' ) )
			return FALSE;

		if ( is_network_admin() )
			return FALSE;

		// Get blogs related to the current blog
		$all_blogs = get_site_option( 'inpsyde_multilingual', array () );

		if ( 1 > count( $all_blogs ) && is_super_admin() )
			return TRUE;

		return FALSE;
	}

	/**
	 * @return void
	 */
	private function run_admin_actions() {

		if ( $this->plugin_data->module_manager->has_modules() )
			$this->load_module_settings_page();

		if ( $this->plugin_data->site_manager->has_modules() )
			$this->load_site_settings_page();

		new Mlp_Network_Site_Settings_Controller( $this->plugin_data );

		new Mlp_Network_New_Site_Controller( $this->plugin_data->language_api, $this->plugin_data->site_relations );
	}

	/**
	 * @return void
	 */
	private function run_frontend_actions() {

// frontend-hooks
		$hreflang = new Mlp_Hreflang_Header_Output( $this->plugin_data->language_api );
		add_action(
			'template_redirect',
			array (
				$hreflang,
				'http_header'
			)
		);
		add_action(
			'wp_head',
			array (
				$hreflang,
				'wp_head'
			)
		);
	}

	/**
	 * @return void
	 */
	private function prepare_plugin_data() {

		$this->link_table                     = $this->wpdb->base_prefix . 'multilingual_linked';
		$this->plugin_file_path               = $this->plugin_data->plugin_file_path;
		$this->plugin_data->module_manager    = new Mlp_Module_Manager( 'state_modules' );
		$this->plugin_data->site_manager      = new Mlp_Module_Manager( 'inpsyde_multilingual' );
		$this->plugin_data->table_list        = new Mlp_Db_Table_List( $this->wpdb );
		$this->plugin_data->link_table        = $this->link_table;
		$this->plugin_data->content_relations = new Mlp_Content_Relations(
			$this->wpdb,
			$this->plugin_data->site_relations,
			new Mlp_Db_Table_Name( $this->link_table, $this->plugin_data->table_list )
		);
		$this->plugin_data->language_api      = new Mlp_Language_Api(
			$this->plugin_data,
			'mlp_languages',
			$this->plugin_data->site_relations,
			$this->plugin_data->content_relations,
			$this->wpdb
		);
		$this->plugin_data->assets            = new Mlp_Assets( $this->plugin_data->locations );
	}

	/**
	 * @return void
	 */
	private function prepare_helpers() {

		Mlp_Helpers::$link_table = $this->link_table;
		Mlp_Helpers::insert_dependency( 'site_relations', $this->plugin_data->site_relations );
		Mlp_Helpers::insert_dependency( 'language_api', $this->plugin_data->language_api );
		Mlp_Helpers::insert_dependency( 'plugin_data', $this->plugin_data );
	}
}
