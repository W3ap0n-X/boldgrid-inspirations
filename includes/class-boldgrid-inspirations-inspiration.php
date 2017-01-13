<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Inspiration
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspiration class.
 */
class Boldgrid_Inspirations_Inspiration extends Boldgrid_Inspirations {

	/**
	 * Boldgrid_Inspirations_External_Plugin object.
	 *
	 * @var Boldgrid_Inspirations_External_Plugin
	 */
	protected $external_plugin_helper;

	/**
	 * Boldgrid_Inspirations_Built object.
	 *
	 * @var Boldgrid_Inspirations_Built
	 */
	protected $boldgrid_layout_helper;

	/**
	 * Accessor for $external_plugin_helper.
	 *
	 * @return Boldgrid_Inspirations_Built Object for the Boldgrid_Inspirations_Built class.
	 */
	public function get_external_plugin_helper() {
		return $this->external_plugin_helper;
	}

	/**
	 * Pre-add hooks.
	 *
	 * @see Boldgrid_Inspirations_Api::add_hooks_to_prompt_for_api_key().
	 * @see Boldgrid_Inspirations_Api::passes_api_check();
	 * @see Boldgrid_Inspirations_Api::get_is_asset_server_available().
	 * @see Boldgrid_Inspirations_Api::get_site_hash().
	 */
	public function pre_add_hooks() {
		// Add hooks for users on the front end.
		if ( ! is_admin() ) {
			$this->add_wp_hooks();
		}

		// Include all files needed by BoldGrid in the admin panel.
		$this->include_admin_files();

		// Add hooks regardless of key validation.
		$this->add_hooks_always();

		// If POST is an API key activation call, then handle the callback.
		if ( isset( $_POST['action'] ) && 'set_api_key' === $_POST['action'] &&
		isset( $_POST['api_key'] ) ) {
			// Prompt for the API key.
			$this->api->add_hooks_to_prompt_for_api_key();
		} else {
			// Get the API hash from configs.
			$api_key_hash = (
				isset( $this->configs['api_key'] ) ? $this->configs['api_key'] : null
			);

			// Verify API key and add hooks, or prompt for api key.
			$passes_api_check = false;

			if ( ! empty( $api_key_hash ) ) {
				$passes_api_check = $this->api->passes_api_check( true );
			}

			// Get the site hash.
			$site_hash = Boldgrid_Inspirations_Api::get_site_hash();

			// If the last API status code was 401 (unauthorized), then prompt for a new key.
			if ( empty( $api_key_hash ) || 401 === $this->api->get_last_api_status() ) {
				$this->api->add_hooks_to_prompt_for_api_key();
			} elseif ( $passes_api_check ) {
				// API key check passed, add hooks.
				$this->add_hooks();
			}
		}
	}

	/**
	 * Add hooks regardless of key validation.
	 *
	 * @since 1.2.3
	 *
	 * @see Boldgrid_Inspirations_Dashboard::__construct().
	 * @see Boldgrid_Inspirations_Dashboard::add_hooks().
	 */
	public function add_hooks_always() {
		/* Add hooks for admin section pages. */
		if ( is_admin() ) {
			// Check PHP and WordPress versions for compatibility.
			add_action( 'admin_init',
				array(
					$this,
					'check_php_wp_version'
				)
			);

			// Add IMHWPB.configs to JavaScript.
			// @todo Only add configs for certain roles/capabilities.
			add_action( 'admin_head',
				array(
					$this,
					'add_boldgrid_configs_to_header',
				)
			);

			// BoldGrid help link in the WordPress Help context tab.
			add_action( 'admin_bar_menu',
				array(
					$this,
					'add_boldgrid_help_context_tab_link'
				)
			);

			// Dashboard.
			$dashboard = new Boldgrid_Inspirations_Dashboard();
			$dashboard->add_hooks();

			// Customizer.
			$customizer = new Boldgrid_Inspirations_Customizer();
			$customizer->add_hooks();

			// Javascript files per screen.
			$screen = new Boldgrid_Inspirations_Screen();
			$screen->add_hooks();

			// Plugin options.
			$plugin_options = new Boldgrid_Inspirations_Options();
			$plugin_options->add_hooks();

			// Admin notices.
			$boldgrid_admin_notices = new Boldgrid_Inspirations_Admin_Notices();
			$boldgrid_admin_notices->add_hooks();

			// Get configs.
			$configs = $this->get_configs();

			// Helper to find active BG Plugins.
			$this->external_plugin_helper = new Boldgrid_Inspirations_External_Plugin( $configs );

			// Load Javascript and CSS.
			add_action( 'admin_enqueue_scripts',
				array(
					$this,
					'boldgrid_style',
				)
			);

			// Boldgrid Layout section.
			$this->boldgrid_layout_helper = new Boldgrid_Inspirations_Built( $this );
			$this->boldgrid_layout_helper->add_hooks();

			// Check the connection to the asset server.
			add_action( 'wp_ajax_check_asset_server',
				array(
					$this->api,
					'check_asset_server_callback',
				)
			);
		}

		/* Load hooks for all pages. */

		$attribution_update = new Boldgrid_Inspirations_Attribution_Update();
		$attribution_update->add_hooks();

		$attribution = new Boldgrid_Inspirations_Attribution();
		$attribution->add_hooks();

		$attribution_page = new Boldgrid_Inspirations_Attribution_Page();
		$attribution_page->add_hooks();
	}

	/**
	 * Add hooks when the BoldGrid Connect key has been validated.
	 *
	 * @return null
	 */
	public function add_hooks() {
		// Post Theme Install Hooks.
		require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-theme-install.php';
		$boldgrid_theme_install = new Boldgrid_Inspirations_Theme_Install( $this->configs );
		$boldgrid_theme_install->add_hooks();

		$is_wpcli = ( defined( 'WP_CLI' ) && WP_CLI );

		if ( is_admin() || $is_wpcli ) {
			require_once BOLDGRID_BASE_DIR .
			'/includes/class-boldgrid-inspirations-update.php';

			$plugin_update = new Boldgrid_Inspirations_Update( $this );
		}

		// If is a network admin update page, then return.
		if ( $this->is_network_update_page() ) {
			return;
		}

		// Add hooks for admin section, or non-admin pages.
		if ( is_admin() ) {
			// Allow users to search through stock photos.
			$stock_photography = new Boldgrid_Inspirations_Stock_Photography();
			$stock_photography->add_hooks();

			// Receipts.
			$boldgrid_receitps = new Boldgrid_Inspirations_Receipts();
			$boldgrid_receitps->add_hooks();

			// Purchase for publish.
			$purchase_for_publish = new Boldgrid_Inspirations_Purchase_For_Publish();
			$purchase_for_publish->add_hooks();

			// Dependency plugins.
			$dependency_plugins = new Boldgrid_Inspirations_Dependency_Plugins();
			$dependency_plugins->add_hooks();

			// Purchase Coins.
			$boldgrid_purchase_coins = new Boldgrid_Inspirations_Purchase_Coins();
			$boldgrid_purchase_coins->add_hooks();


			// Easy Attachment Preview Size.
			$boldgrid_easy_attachment_preview_size = new Boldgrid_Inspirations_Easy_Attachment_Preview_Size();
			$boldgrid_easy_attachment_preview_size->add_hooks();

			// Asset Manager.
			$boldgrid_asset_manager = new Boldgrid_Inspirations_Asset_Manager();
			$boldgrid_asset_manager->add_hooks();

			// Pages And Posts.
			$boldgrid_pages_and_posts = new Boldgrid_Inspirations_Pages_And_Posts();
			$boldgrid_pages_and_posts->add_hooks();

			// Include BoldGrid Inspirations Feedback.
			require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-feedback.php';
			$boldgrid_inspirations_feedback = new Boldgrid_Inspirations_Feedback();

			// GridBlock Sets - Admin Page.
			$gridblock_sets_admin = new Boldgrid_Inspirations_GridBlock_Sets_Admin( $this->configs );
			$gridblock_sets_admin->add_hooks();

			$milestones = new Boldgrid_Inspirations_Milestones();
			$milestones->add_hooks();

			$milestones_social = new Boldgrid_Inspirations_Milestones_Social();
			$milestones_social->add_hooks();

			$milestones_widget = new Boldgrid_Inspirations_Milestones_Widget();
			$milestones_widget->add_hooks();

			$deploy_cta = new Boldgrid_Inspirations_Deploy_Cta();
			$deploy_cta->add_hooks();
		}

		/* Classes to add_hooks for, regardless of is_admin. */

		// Adding gridblock assets to relevant pages.
		require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-gridblock.php';
		$boldgrid_inspirations_gridblock = new Boldgrid_Inspirations_Gridblock( $this->configs );
		$boldgrid_inspirations_gridblock->add_hooks();

		// GridBlock Sets - Preview Page.
		require_once BOLDGRID_BASE_DIR .
		'/includes/class-boldgrid-inspirations-gridblock-sets-preview-page.php';
		$gridblock_sets_preview_page = new Boldgrid_Inspirations_GridBlock_Sets_Preview_Page();
		$gridblock_sets_preview_page->add_hooks();
	}

/**
 * Add front end hooks.
 *
 * # Include the necessary class files.
 * # Instantiate / add applicable hooks.
 *
 * These hooks are triggered for users to the front end of the site. These hooks will run for all
 * users, regardless if they're logged in or not.
 *
 * @since 1.1.2
 */
public function add_wp_hooks() {
	$this->include_wp_files();

	Boldgrid_Inspirations_Attribution_Page::prevent_contamination();

	// $attribution = new Boldgrid_Inspirations_Attribution();
	// $attribution->add_wp_hooks();
}

/**
 *
 * @param array $buttons
 * @return array
 */
public function boldgrid_register_buttons( $buttons ) {
	array_push( $buttons, 'example' );
	return $buttons;
}

/**
 * WPB Admin Styles - Scripts to enqueue on all pages
 *
 * Loads: style.css
 * script.js
 */
public function boldgrid_style( $hook ) {
	// base-admin.js
	wp_enqueue_script( 'base-admin-js',
		plugins_url(
			'/assets/js/base-admin.js', BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
		),
		array(),
		BOLDGRID_INSPIRATIONS_VERSION,
		true
	);

	// base-admin.css
	wp_register_style( 'base-admin-css',
		plugins_url(
			'/assets/css/base-admin.css', BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
		),
		array(),
		BOLDGRID_INSPIRATIONS_VERSION
	);

	wp_enqueue_style( 'base-admin-css' );

	// ajax.js
	wp_enqueue_script( 'inspiration-ajax',
		plugins_url(
			'/assets/js/ajax/ajax.js', BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
		),
		array(),
		BOLDGRID_INSPIRATIONS_VERSION,
		true
	);

	// handlebars
	wp_enqueue_script( 'inspiration-handle-bars',
		plugins_url(
			'assets/js/handlebars/handlebars-v2.0.0.js',
			BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
		),
		array(),
		BOLDGRID_INSPIRATIONS_VERSION,
		true
	);

	wp_enqueue_script( 'inspiration-handle-helper',
		plugins_url(
			'assets/js/handlebars/handle-bar-helpers.js', BOLDGRID_BASE_DIR .
			'/boldgrid-inspirations.php'
		),
		array(),
		BOLDGRID_INSPIRATIONS_VERSION,
		true
	);

	/**
	 * Determine when to load our grid, grid.css.
	 */
	$hooks_to_load_grid = array(
		'toplevel_page_imh-wpb',
		'toplevel_page_boldgrid-inspirations',
		'transactions_page_boldgrid-cart',
		'settings_page_boldgrid-settings',
		'appearance_page_boldgrid-staging',
		'boldgrid_page_boldgrid-cart',
	);

	if ( in_array( $hook, $hooks_to_load_grid ) ) {
		// Thanks To https://github.com/zirafa/bootstrap-grid-only
		wp_register_style( 'boldgrid_admin',
			plugins_url(
				'/assets/css/grid.css', BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
			),
			array(),
			BOLDGRID_INSPIRATIONS_VERSION
		);

		wp_enqueue_style( 'boldgrid_admin' );
	}

	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );
}

/**
 * Include all files needed by BoldGrid in the admin panel
 */
public function include_admin_files() {
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-external-plugin.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-stock-photography.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-purchase-for-publish.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-dashboard.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-screen.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-update.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-options.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-built.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-dependency-plugins.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-receipts.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-purchase-coins.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-admin-notices.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-easy-attachment-preview-size.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-customizer.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-asset-manager.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-pages-and-posts.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-gridblock-sets-admin.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-start-over.php';

	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-attribution.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-attribution-asset.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-attribution-update.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-attribution-page.php';

	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-milestones.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-milestones-social.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-milestones-widget.php';

	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-deploy-cta.php';
}

/**
 * Include front end files.
 *
 * @since 1.1.2
 */
public function include_wp_files() {
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-attribution.php';
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-attribution-page.php';
}

/**
 * Run the deploy Script.
 */
public function deploy_script() {
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-deploy.php';
	include BOLDGRID_BASE_DIR . '/pages/deploy.php';
}

/**
 * Because many scripts will need our configs, let's go ahead and put them right in the header.
 *
 * @global $post WordPress post variable.
 * @global $pagenow WordPress pagenow variable (the current page filename).
 */
public function add_boldgrid_configs_to_header() {
	global $post;
	global $pagenow;

	$configs = $this->get_configs();

	$boldgrid_post_id = ( isset( $post->ID ) ? intval( $post->ID ) : "''" );

	// If we don't have a post id, try getting it from the URL.
	if ( ! is_numeric( $boldgrid_post_id ) ) {
		$boldgrid_post_id = ( isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : "''" );
	}

	/*
	 * If we are not allowing ALL configs to be displayed in the header, create an array of configs
	 * that are needed and safe to print on every admin page.
	 */
	if( false === $this->allow_header_configs() ) {
		$configs = array(
			'settings' => array(
				'boldgrid_menu_option' => $configs['settings']['boldgrid_menu_option'],
			),
		);
	}

	$oneliner = '
		var IMHWPB = IMHWPB || {};
		IMHWPB.post_id = ' . $boldgrid_post_id . ';
		IMHWPB.page_now = "' . $pagenow . '";
		IMHWPB.configs = ' . json_encode( $configs ) . '
	';

	Boldgrid_Inspirations_Utility::inline_js_oneliner( $oneliner );
}

	/**
	 * Determine if configs are allowed to be printed in head.
	 *
	 * @since 1.2.3
	 *
	 * @global pagenow.
	 *
	 * @return bool.
	 */
	public function allow_header_configs() {
		global $pagenow;

		$page =	( isset( $_GET['page'] ) ? $_GET['page'] : null );
		$tab =	( isset( $_GET['tab'] ) ? $_GET['tab'] : null );

		// Inspirations, design first.
		if( 'admin.php' === $pagenow && 'boldgrid-inspirations' === $page && current_user_can( 'edit_pages' ) ) {
			return true;
		}

		// Transactions, receipts.
		if( 'admin.php' === $pagenow && 'boldgrid-transactions' === $page && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Transactions, cart.
		if( 'admin.php' === $pagenow && 'boldgrid-cart' === $page && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// BoldGrid Connect Search.
		$valid_tabs = array( 'insert_layout', 'image_search' );
		if( 'media-upload.php' === $pagenow && in_array( $tab, $valid_tabs, true ) && current_user_can( 'upload_files' ) ) {
			return true;
		}

		// Pages > All Pages.
		if( 'edit.php' === $pagenow && current_user_can( 'edit_posts' ) ) {
			return true;
		}

		// Editing a page.
		if( 'post.php' === $pagenow && current_user_can( 'edit_posts' ) ) {
			return true;
		}

		// New Page.
		if( 'post-new.php' === $pagenow && current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return false;
	}

/**
 * Add BoldGrid help link in the WordPress Help context tab
 */
public function add_boldgrid_help_context_tab_link() {
	// Get the current screen:
	$screen = get_current_screen();

	// Variable to toggle BoldGrid help tabs: (true|false):
	$show_boldgrid_help_tabs = false;

	// Add new tab id screen is the dashboard, a boldgrid page, or editing a page or post:
	if ( preg_match( '/^(dashboard|page|post|.+boldgrid-.+|.+imh-wpb|transactions_page_.+)$/',
	$screen->id ) ) {
		if ( $show_boldgrid_help_tabs ) {
			// Select content for the BoldGrid help tab:
			switch ( $screen->id ) {
				case 'page' :
					$help_tab = array (
						'title' => 'BoldGrid Help',
						'content' => 'This is a BoldGrid help section for editing pages.  Feel free to visit <a target="_blank" href="http://www.boldgrid.com/">BoldGrid.com</a>'
					);
					break;

				case 'post' :
					$help_tab = array (
						'title' => 'BoldGrid Help',
						'content' => 'This is a BoldGrid help section for editing posts.  Feel free to visit <a target="_blank" href="http://www.boldgrid.com/">BoldGrid.com</a>'
					);
					break;

				case 'transactions_page_cart' :
					$help_tab = array (
						'title' => 'BoldGrid Help',
						'content' => 'This is a BoldGrid help section for cart/checkout.  Feel free to visit <a target="_blank" href="http://www.boldgrid.com/">BoldGrid.com</a>'
					);
					break;

				case 'transactions_page_boldgrid-receipts' :
					$help_tab = array (
						'title' => 'BoldGrid Help',
						'content' => 'This is a BoldGrid help section for receipts/transaction history.  Feel free to visit <a target="_blank" href="http://www.boldgrid.com/">BoldGrid.com</a>'
					);
					break;

				case 'transactions_page_boldgrid-purchase-coins' :
					$help_tab = array (
						'title' => 'BoldGrid Help',
						'content' => 'This is a BoldGrid help section for purchasing coins.  Feel free to visit <a target="_blank" href="http://www.boldgrid.com/">BoldGrid.com</a>'
					);
					break;

				default :
					$help_tab = array (
						'title' => 'BoldGrid Help',
						'content' => 'This is a BoldGrid help section.  Feel free to visit <a target="_blank" href="http://www.boldgrid.com/">BoldGrid.com</a>'
					);
					break;
			}

			// Add the link:
			$screen->add_help_tab(
				array (
					'id' => 'boldgrid-inspirations-help',
					'title' => __( $help_tab['title'] ),
					'content' => __( $help_tab['content'] )
				) );
		}

		// Get the help sidebar content:
		$help_sidebar_content = $screen->get_help_sidebar();

		// Add help sidebar content:
		$screen->set_help_sidebar(
			$help_sidebar_content . '<a target="_blank" href="http://www.boldgrid.com/">' .
				 'BoldGrid.com' . '</a>' );
	}
}
}
