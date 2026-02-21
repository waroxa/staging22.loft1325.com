<?php
/**
 * Plugin installation and activation for WordPress themes.
 *
 * Please note that this is a drop-in library for a theme or plugin.
 * The authors of this library (Thomas, Gary and Juliette) are NOT responsible
 * for the support of your plugin or theme. Please contact the plugin
 * or theme author for support.
 *
 * @package   TGM-Plugin-Activation
 * @version   2.6.1 for parent theme ong for publication on ThemeForest
 * @link      http://tgmpluginactivation.com/
 * @author    Thomas Griffin, Gary Jones, Juliette Reinders Folmer
 * @copyright Copyright (c) 2011, Thomas Griffin
 * @license   GPL-2.0+
 */

/*
	Copyright 2011 Thomas Griffin (thomasgriffinmedia.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'TGM_Plugin_Activation' ) ) {

	/**
	 * Automatic plugin installation and activation library.
	 *
	 * Creates a way to automatically install and activate plugins from within themes.
	 * The plugins can be either bundled, downloaded from the WordPress
	 * Plugin Repository or downloaded from another external source.
	 *
	 * @since 1.0.0
	 *
	 * @package TGM-Plugin-Activation
	 * @author  Thomas Griffin
	 * @author  Gary Jones
	 */
	class TGM_Plugin_Activation {
		/**
		 * TGMPA version number.
		 *
		 * @since 2.5.0
		 *
		 * @const string Version number.
		 */
		const TGMPA_VERSION = '2.6.1';

		/**
		 * Regular expression to test if a URL is a WP plugin repo URL.
		 *
		 * @const string Regex.
		 *
		 * @since 2.5.0
		 */
		const WP_REPO_REGEX = '|^http[s]?://wordpress\.org/(?:extend/)?plugins/|';

		/**
		 * Arbitrary regular expression to test if a string starts with a URL.
		 *
		 * @const string Regex.
		 *
		 * @since 2.5.0
		 */
		const IS_URL_REGEX = '|^http[s]?://|';

		/**
		 * Holds a copy of itself, so it can be referenced by the class name.
		 *
		 * @since 1.0.0
		 *
		 * @var TGM_Plugin_Activation
		 */
		public static $instance;

		/**
		 * Holds arrays of plugin details.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 the array has the plugin slug as an associative key.
		 *
		 * @var array
		 */
		public $plugins = array();

		/**
		 * Holds arrays of plugin names to use to sort the plugins array.
		 *
		 * @since 2.5.0
		 *
		 * @var array
		 */
		protected $sort_order = array();

		/**
		 * Whether any plugins have the 'force_activation' setting set to true.
		 *
		 * @since 2.5.0
		 *
		 * @var bool
		 */
		protected $has_forced_activation = false;

		/**
		 * Whether any plugins have the 'force_deactivation' setting set to true.
		 *
		 * @since 2.5.0
		 *
		 * @var bool
		 */
		protected $has_forced_deactivation = false;

		/**
		 * Name of the unique ID to hash notices.
		 *
		 * @since 2.4.0
		 *
		 * @var string
		 */
		public $id = 'tgmpa';

		/**
		 * Name of the query-string argument for the admin page.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $menu = 'tgmpa-install-plugins';

		/**
		 * Parent menu file slug.
		 *
		 * @since 2.5.0
		 *
		 * @var string
		 */
		public $parent_slug = 'themes.php';

		/**
		 * Capability needed to view the plugin installation menu item.
		 *
		 * @since 2.5.0
		 *
		 * @var string
		 */
		public $capability = 'edit_theme_options';

		/**
		 * Default absolute path to folder containing bundled plugin zip files.
		 *
		 * @since 2.0.0
		 *
		 * @var string Absolute path prefix to zip file location for bundled plugins. Default is empty string.
		 */
		public $default_path = '';

		/**
		 * Flag to show admin notices or not.
		 *
		 * @since 2.1.0
		 *
		 * @var boolean
		 */
		public $has_notices = true;

		/**
		 * Flag to determine if the user can dismiss the notice nag.
		 *
		 * @since 2.4.0
		 *
		 * @var boolean
		 */
		public $dismissable = true;

		/**
		 * Message to be output above nag notice if dismissable is false.
		 *
		 * @since 2.4.0
		 *
		 * @var string
		 */
		public $dismiss_msg = '';

		/**
		 * Flag to set automatic activation of plugins. Off by default.
		 *
		 * @since 2.2.0
		 *
		 * @var boolean
		 */
		public $is_automatic = false;

		/**
		 * Optional message to display before the plugins table.
		 *
		 * @since 2.2.0
		 *
		 * @var string Message filtered by wp_kses_post(). Default is empty string.
		 */
		public $message = '';

		/**
		 * Holds configurable array of strings.
		 *
		 * Default values are added in the constructor.
		 *
		 * @since 2.0.0
		 *
		 * @var array
		 */
		public $strings = array();

		/**
		 * Holds the version of WordPress.
		 *
		 * @since 2.4.0
		 *
		 * @var int
		 */
		public $wp_version;

		/**
		 * Holds the hook name for the admin page.
		 *
		 * @since 2.5.0
		 *
		 * @var string
		 */
		public $page_hook;

		/**
		 * Adds a reference of this object to $instance, populates default strings,
		 * does the tgmpa_init action hook, and hooks in the interactions to init.
		 *
		 * {@internal This method should be `protected`, but as too many TGMPA implementations
		 * haven't upgraded beyond v2.3.6 yet, this gives backward compatibility issues.
		 * Reverted back to public for the time being.}}
		 *
		 * @since 1.0.0
		 *
		 * @see TGM_Plugin_Activation::init()
		 */
		public function __construct() {
			// Set the current WordPress version.
			$this->wp_version = $GLOBALS['wp_version'];

			// Announce that the class is ready, and pass the object (for advanced use).
			do_action_ref_array( 'tgmpa_init', array( $this ) );

			/*
			 * Load our text domain and allow for overloading the fall-back file.
			 *
			 * {@internal IMPORTANT! If this code changes, review the regex in the custom TGMPA
			 * generator on the website.}}
			 */
			add_action( 'init', array( $this, 'load_textdomain' ), 5 );
			add_filter( 'load_textdomain_mofile', array( $this, 'overload_textdomain_mofile' ), 10, 2 );

			// When the rest of WP has loaded, kick-start the rest of the class.
			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Magic method to (not) set protected properties from outside of this class.
		 *
		 * {@internal hackedihack... There is a serious bug in v2.3.2 - 2.3.6  where the `menu` property
		 * is being assigned rather than tested in a conditional, effectively rendering it useless.
		 * This 'hack' prevents this from happening.}}
		 *
		 * @see https://github.com/TGMPA/TGM-Plugin-Activation/blob/2.3.6/tgm-plugin-activation/class-tgm-plugin-activation.php#L1593
		 *
		 * @since 2.5.2
		 *
		 * @param string $name  Name of an inaccessible property.
		 * @param mixed  $value Value to assign to the property.
		 * @return void  Silently fail to set the property when this is tried from outside of this class context.
		 *               (Inside this class context, the __set() method if not used as there is direct access.)
		 */
		public function __set( $name, $value ) {
			return;
		}

		/**
		 * Magic method to get the value of a protected property outside of this class context.
		 *
		 * @since 2.5.2
		 *
		 * @param string $name Name of an inaccessible property.
		 * @return mixed The property value.
		 */
		public function __get( $name ) {
			return $this->{$name};
		}

		/**
		 * Initialise the interactions between this class and WordPress.
		 *
		 * Hooks in three new methods for the class: admin_menu, notices and styles.
		 *
		 * @since 2.0.0
		 *
		 * @see TGM_Plugin_Activation::admin_menu()
		 * @see TGM_Plugin_Activation::notices()
		 * @see TGM_Plugin_Activation::styles()
		 */
		public function init() {
			/**
			 * By default TGMPA only loads on the WP back-end and not in an Ajax call. Using this filter
			 * you can overrule that behaviour.
			 *
			 * @since 2.5.0
			 *
			 * @param bool $load Whether or not TGMPA should load.
			 *                   Defaults to the return of `is_admin() && ! defined( 'DOING_AJAX' )`.
			 */
			if ( true !== apply_filters( 'tgmpa_load', ( is_admin() && ! defined( 'DOING_AJAX' ) ) ) ) {
				return;
			}

			// Load class strings.
			$this->strings = array(
				'page_title'                      => esc_html__( 'Install Required Plugins', 'marina' ),
				'menu_title'                      => esc_html__( 'Install Plugins', 'marina' ),
				/* translators: %s: plugin name. */
				'installing'                      => esc_html__( 'Installing Plugin: %s', 'marina' ),
				/* translators: %s: plugin name. */
				'updating'                        => esc_html__( 'Updating Plugin: %s', 'marina' ),
				'oops'                            => esc_html__( 'Something went wrong with the plugin API.', 'marina' ),
				'notice_can_install_required'     => _n_noop(
					/* translators: 1: plugin name(s). */
					'This theme requires the following plugin: %1$s.',
					'This theme requires the following plugins: %1$s.',
					'marina'
				),
				'notice_can_install_recommended'  => _n_noop(
					/* translators: 1: plugin name(s). */
					'This theme recommends the following plugin: %1$s.',
					'This theme recommends the following plugins: %1$s.',
					'marina'
				),
				'notice_ask_to_update'            => _n_noop(
					/* translators: 1: plugin name(s). */
					'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
					'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
					'marina'
				),
				'notice_ask_to_update_maybe'      => _n_noop(
					/* translators: 1: plugin name(s). */
					'There is an update available for: %1$s.',
					'There are updates available for the following plugins: %1$s.',
					'marina'
				),
				'notice_can_activate_required'    => _n_noop(
					/* translators: 1: plugin name(s). */
					'The following required plugin is currently inactive: %1$s.',
					'The following required plugins are currently inactive: %1$s.',
					'marina'
				),
				'notice_can_activate_recommended' => _n_noop(
					/* translators: 1: plugin name(s). */
					'The following recommended plugin is currently inactive: %1$s.',
					'The following recommended plugins are currently inactive: %1$s.',
					'marina'
				),
				'install_link'                    => _n_noop(
					'Begin installing plugin',
					'Begin installing plugins',
					'marina'
				),
				'update_link'                     => _n_noop(
					'Begin updating plugin',
					'Begin updating plugins',
					'marina'
				),
				'activate_link'                   => _n_noop(
					'Begin activating plugin',
					'Begin activating plugins',
					'marina'
				),
				'return'                          => esc_html__( 'Return to Required Plugins Installer', 'marina' ),
				'dashboard'                       => esc_html__( 'Return to the Dashboard', 'marina' ),
				'plugin_activated'                => esc_html__( 'Plugin activated successfully.', 'marina' ),
				'activated_successfully'          => esc_html__( 'The following plugin was activated successfully:', 'marina' ),
				/* translators: 1: plugin name. */
				'plugin_already_active'           => esc_html__( 'No action taken. Plugin %1$s was already active.', 'marina' ),
				/* translators: 1: plugin name. */
				'plugin_needs_higher_version'     => esc_html__( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'marina' ),
				/* translators: 1: dashboard link. */
				'complete'                        => esc_html__( 'All plugins installed and activated successfully. %1$s', 'marina' ),
				'dismiss'                         => esc_html__( 'Dismiss this notice', 'marina' ),
				'notice_cannot_install_activate'  => esc_html__( 'There are one or more required or recommended plugins to install, update or activate.', 'marina' ),
				'contact_admin'                   => esc_html__( 'Please contact the administrator of this site for help.', 'marina' ),
			);

			do_action( 'tgmpa_register' );

			/* After this point, the plugins should be registered and the configuration set. */

			// Proceed only if we have plugins to handle.
			if ( empty( $this->plugins ) || ! is_array( $this->plugins ) ) {
				return;
			}

			// Set up the menu and notices if we still have outstanding actions.
			if ( true !== $this->is_tgmpa_complete() ) {
				// Sort the plugins.
				array_multisort( $this->sort_order, SORT_ASC, $this->plugins );

				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
				add_action( 'admin_head', array( $this, 'dismiss' ) );

				// Prevent the normal links from showing underneath a single install/update page.
				add_filter( 'install_plugin_complete_actions', array( $this, 'actions' ) );
				add_filter( 'update_plugin_complete_actions', array( $this, 'actions' ) );

				if ( $this->has_notices ) {
					add_action( 'admin_notices', array( $this, 'notices' ) );
					add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
					add_action( 'admin_enqueue_scripts', array( $this, 'thickbox' ) );
				}
			}

			// If needed, filter plugin action links.
			add_action( 'load-plugins.php', array( $this, 'add_plugin_action_link_filters' ), 1 );

			// Make sure things get reset on switch theme.
			add_action( 'switch_theme', array( $this, 'flush_plugins_cache' ) );

			if ( $this->has_notices ) {
				add_action( 'switch_theme', array( $this, 'update_dismiss' ) );
			}

			// Setup the force activation hook.
			if ( true === $this->has_forced_activation ) {
				add_action( 'admin_init', array( $this, 'force_activation' ) );
			}

			// Setup the force deactivation hook.
			if ( true === $this->has_forced_deactivation ) {
				add_action( 'switch_theme', array( $this, 'force_deactivation' ) );
			}
		}

		/**
		 * Load translations.
		 *
		 * @since 2.6.0
		 *
		 * (@internal Uses `load_theme_textdomain()` rather than `load_plugin_textdomain()` to
		 * get round the different ways of handling the path and deprecated notices being thrown
		 * and such. For plugins, the actual file name will be corrected by a filter.}}
		 *
		 * {@internal IMPORTANT! If this function changes, review the regex in the custom TGMPA
		 * generator on the website.}}
		 */
		public function load_textdomain() {
			if ( is_textdomain_loaded( 'marina' ) ) {
				return;
			}

			if ( false !== strpos( __FILE__, WP_PLUGIN_DIR ) || false !== strpos( __FILE__, WPMU_PLUGIN_DIR ) ) {
				// Plugin, we'll need to adjust the file name.
				add_action( 'load_textdomain_mofile', array( $this, 'correct_plugin_mofile' ), 10, 2 );
				load_theme_textdomain( 'marina', dirname( __FILE__ ) . '/languages' );
				remove_action( 'load_textdomain_mofile', array( $this, 'correct_plugin_mofile' ), 10 );
			} else {
				load_theme_textdomain( 'marina', dirname( __FILE__ ) . '/languages' );
			}
		}

		/**
		 * Correct the .mo file name for (must-use) plugins.
		 *
		 * Themese use `/path/{locale}.mo` while plugins use `/path/{text-domain}-{locale}.mo`.
		 *
		 * {@internal IMPORTANT! If this function changes, review the regex in the custom TGMPA
		 * generator on the website.}}
		 *
		 * @since 2.6.0
		 *
		 * @param string $mofile Full path to the target mofile.
		 * @param string $domain The domain for which a language file is being loaded.
		 * @return string $mofile
		 */
		public function correct_plugin_mofile( $mofile, $domain ) {
			// Exit early if not our domain (just in case).
			if ( 'marina' !== $domain ) {
				return $mofile;
			}
			return preg_replace( '`/([a-z]{2}_[A-Z]{2}.mo)$`', '/tgmpa-$1', $mofile );
		}

		/**
		 * Potentially overload the fall-back translation file for the current language.
		 *
		 * WP, by default since WP 3.7, will load a local translation first and if none
		 * can be found, will try and find a translation in the /wp-content/languages/ directory.
		 * As this library is theme/plugin agnostic, translation files for TGMPA can exist both
		 * in the WP_LANG_DIR /plugins/ subdirectory as well as in the /themes/ subdirectory.
		 *
		 * This method makes sure both directories are checked.
		 *
		 * {@internal IMPORTANT! If this function changes, review the regex in the custom TGMPA
		 * generator on the website.}}
		 *
		 * @since 2.6.0
		 *
		 * @param string $mofile Full path to the target mofile.
		 * @param string $domain The domain for which a language file is being loaded.
		 * @return string $mofile
		 */
		public function overload_textdomain_mofile( $mofile, $domain ) {
			// Exit early if not our domain, not a WP_LANG_DIR load or if the file exists and is readable.
			if ( 'marina' !== $domain || false === strpos( $mofile, WP_LANG_DIR ) || @is_readable( $mofile ) ) {
				return $mofile;
			}

			// Current fallback file is not valid, let's try the alternative option.
			if ( false !== strpos( $mofile, '/themes/' ) ) {
				return str_replace( '/themes/', '/plugins/', $mofile );
			} elseif ( false !== strpos( $mofile, '/plugins/' ) ) {
				return str_replace( '/plugins/', '/themes/', $mofile );
			} else {
				return $mofile;
			}
		}

		/**
		 * Hook in plugin action link filters for the WP native plugins page.
		 *
		 * - Prevent activation of plugins which don't meet the minimum version requirements.
		 * - Prevent deactivation of force-activated plugins.
		 * - Add update notice if update available.
		 *
		 * @since 2.5.0
		 */
		public function add_plugin_action_link_filters() {
			foreach ( $this->plugins as $slug => $plugin ) {
				if ( false === $this->can_plugin_activate( $slug ) ) {
					add_filter( 'plugin_action_links_' . $plugin['file_path'], array( $this, 'filter_plugin_action_links_activate' ), 20 );
				}

				if ( true === $plugin['force_activation'] ) {
					add_filter( 'plugin_action_links_' . $plugin['file_path'], array( $this, 'filter_plugin_action_links_deactivate' ), 20 );
				}

				if ( false !== $this->does_plugin_require_update( $slug ) ) {
					add_filter( 'plugin_action_links_' . $plugin['file_path'], array( $this, 'filter_plugin_action_links_update' ), 20 );
				}
			}
		}

		/**
		 * Remove the 'Activate' link on the WP native plugins page if the plugin does not meet the
		 * minimum version requirements.
		 *
		 * @since 2.5.0
		 *
		 * @param array $actions Action links.
		 * @return array
		 */
		public function filter_plugin_action_links_activate( $actions ) {
			unset( $actions['activate'] );

			return $actions;
		}

		/**
		 * Remove the 'Deactivate' link on the WP native plugins page if the plugin has been set to force activate.
		 *
		 * @since 2.5.0
		 *
		 * @param array $actions Action links.
		 * @return array
		 */
		public function filter_plugin_action_links_deactivate( $actions ) {
			unset( $actions['deactivate'] );

			return $actions;
		}

		/**
		 * Add a 'Requires update' link on the WP native plugins page if the plugin does not meet the
		 * minimum version requirements.
		 *
		 * @since 2.5.0
		 *
		 * @param array $actions Action links.
		 * @return array
		 */
		public function filter_plugin_action_links_update( $actions ) {
			$actions['update'] = sprintf(
				'<a href="%1$s" title="%2$s" class="edit">%3$s</a>',
				esc_url( $this->get_tgmpa_status_url( 'update' ) ),
				esc_attr__( 'This plugin needs to be updated to be compatible with your theme.', 'marina' ),
				esc_html__( 'Update Required', 'marina' )
			);

			return $actions;
		}

		/**
		 * Handles calls to show plugin information via links in the notices.
		 *
		 * We get the links in the admin notices to point to the TGMPA page, rather
		 * than the typical plugin-install.php file, so we can prepare everything
		 * beforehand.
		 *
		 * WP does not make it easy to show the plugin information in the thickbox -
		 * here we have to require a file that includes a function that does the
		 * main work of displaying it, enqueue some styles, set up some globals and
		 * finally call that function before exiting.
		 *
		 * Down right easy once you know how...
		 *
		 * Returns early if not the TGMPA page.
		 *
		 * @since 2.1.0
		 *
		 * @global string $tab Used as iframe div class names, helps with styling
		 * @global string $body_id Used as the iframe body ID, helps with styling
		 *
		 * @return null Returns early if not the TGMPA page.
		 */
		public function admin_init() {
			if ( ! $this->is_tgmpa_page() ) {
				return;
			}

			if ( isset( $_REQUEST['tab'] ) && 'plugin-information' === $_REQUEST['tab'] ) {
				// Needed for install_plugin_information().
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

				wp_enqueue_style( 'plugin-install' );

				global $tab, $body_id;
				$body_id = 'plugin-information';
				// @codingStandardsIgnoreStart
				$tab     = 'plugin-information';
				// @codingStandardsIgnoreEnd

				install_plugin_information();

				exit;
			}
		}

		/**
		 * Enqueue thickbox scripts/styles for plugin info.
		 *
		 * Thickbox is not automatically included on all admin pages, so we must
		 * manually enqueue it for those pages.
		 *
		 * Thickbox is only loaded if the user has not dismissed the admin
		 * notice or if there are any plugins left to install and activate.
		 *
		 * @since 2.1.0
		 */
		public function thickbox() {
			if ( ! get_user_meta( get_current_user_id(), 'tgmpa_dismissed_notice_' . $this->id, true ) ) {
				add_thickbox();
			}
		}

		/**
		 * Adds submenu page if there are plugin actions to take.
		 *
		 * This method adds the submenu page letting users know that a required
		 * plugin needs to be installed.
		 *
		 * This page disappears once the plugin has been installed and activated.
		 *
		 * @since 1.0.0
		 *
		 * @see TGM_Plugin_Activation::init()
		 * @see TGM_Plugin_Activation::install_plugins_page()
		 *
		 * @return null Return early if user lacks capability to install a plugin.
		 */
		public function admin_menu() {
			// Make sure privileges are correct to see the page.
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$args = apply_filters(
				'tgmpa_admin_menu_args',
				array(
					'parent_slug' => $this->parent_slug,                     // Parent Menu slug.
					'page_title'  => $this->strings['page_title'],           // Page title.
					'menu_title'  => $this->strings['menu_title'],           // Menu title.
					'capability'  => $this->capability,                      // Capability.
					'menu_slug'   => $this->menu,                            // Menu slug.
					'function'    => array( $this, 'install_plugins_page' ), // Callback.
				)
			);

			$this->add_admin_menu( $args );
		}

		/**
		 * Add the menu item.
		 *
		 * {@internal IMPORTANT! If this function changes, review the regex in the custom TGMPA
		 * generator on the website.}}
		 *
		 * @since 2.5.0
		 *
		 * @param array $args Menu item configuration.
		 */
		protected function add_admin_menu( array $args ) {
			$this->page_hook = add_theme_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
		}

		/**
		 * Echoes plugin installation form.
		 *
		 * This method is the callback for the admin_menu method function.
		 * This displays the admin page and form area where the user can select to install and activate the plugin.
		 * Aborts early if we're processing a plugin installation action.
		 *
		 * @since 1.0.0
		 *
		 * @return null Aborts early if we're processing a plugin installation action.
		 */
		public function install_plugins_page() {
			// Store new instance of plugin table in object.
			$plugin_table = new TGMPA_List_Table;

			// Return early if processing a plugin installation action.
			if ( ( ( 'tgmpa-bulk-install' === $plugin_table->current_action() || 'tgmpa-bulk-update' === $plugin_table->current_action() ) && $plugin_table->process_bulk_actions() ) || $this->do_plugin_install() ) {
				return;
			}

			// Force refresh of available plugin information so we'll know about manual updates/deletes.
			wp_clean_plugins_cache( false );

			?>
			<div class="tgmpa wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<?php $plugin_table->prepare_items(); ?>

				<?php
				if ( ! empty( $this->message ) && is_string( $this->message ) ) {
					echo wp_kses_post( $this->message );
				}
				?>
				<?php $plugin_table->views(); ?>

				<form id="tgmpa-plugins" action="" method="post">
					<input type="hidden" name="tgmpa-page" value="<?php echo esc_attr( $this->menu ); ?>" />
					<input type="hidden" name="plugin_status" value="<?php echo esc_attr( $plugin_table->view_context ); ?>" />
					<?php $plugin_table->display(); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Installs, updates or activates a plugin depending on the action link clicked by the user.
		 *
		 * Checks the $_GET variable to see which actions have been
		 * passed and responds with the appropriate method.
		 *
		 * Uses WP_Filesystem to process and handle the plugin installation
		 * method.
		 *
		 * @since 1.0.0
		 *
		 * @uses WP_Filesystem
		 * @uses WP_Error
		 * @uses WP_Upgrader
		 * @uses Plugin_Upgrader
		 * @uses Plugin_Installer_Skin
		 * @uses Plugin_Upgrader_Skin
		 *
		 * @return boolean True on success, false on failure.
		 */
		protected function do_plugin_install() {
			if ( empty( $_GET['plugin'] ) ) {
				return false;
			}

			// All plugin information will be stored in an array for processing.
			$slug = $this->sanitize_key( urldecode( $_GET['plugin'] ) );

			if ( ! isset( $this->plugins[ $slug ] ) ) {
				return false;
			}

			// Was an install or upgrade action link clicked?
			if ( ( isset( $_GET['tgmpa-install'] ) && 'install-plugin' === $_GET['tgmpa-install'] ) || ( isset( $_GET['tgmpa-update'] ) && 'update-plugin' === $_GET['tgmpa-update'] ) ) {

				$install_type = 'install';
				if ( isset( $_GET['tgmpa-update'] ) && 'update-plugin' === $_GET['tgmpa-update'] ) {
					$install_type = 'update';
				}

				check_admin_referer( 'tgmpa-' . $install_type, 'tgmpa-nonce' );

				// Pass necessary information via URL if WP_Filesystem is needed.
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'plugin'                 => urlencode( $slug ),
							'tgmpa-' . $install_type => $install_type . '-plugin',
						),
						$this->get_tgmpa_url()
					),
					'tgmpa-' . $install_type,
					'tgmpa-nonce'
				);

				$method = ''; // Leave blank so WP_Filesystem can populate it as necessary.

				if ( false === ( $creds = request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, array() ) ) ) {
					return true;
				}

				if ( ! WP_Filesystem( $creds ) ) {
					request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, array() ); // Setup WP_Filesystem.
					return true;
				}

				/* If we arrive here, we have the filesystem. */

				// Prep variables for Plugin_Installer_Skin class.
				$extra         = array();
				$extra['slug'] = $slug; // Needed for potentially renaming of directory name.
				$source        = $this->get_download_url( $slug );
				$api           = ( 'repo' === $this->plugins[ $slug ]['source_type'] ) ? $this->get_plugins_api( $slug ) : null;
				$api           = ( false !== $api ) ? $api : null;

				$url = add_query_arg(
					array(
						'action' => $install_type . '-plugin',
						'plugin' => urlencode( $slug ),
					),
					'update.php'
				);

				if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
					require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				}

				$title     = ( 'update' === $install_type ) ? $this->strings['updating'] : $this->strings['installing'];
				$skin_args = array(
					'type'   => ( 'bundled' !== $this->plugins[ $slug ]['source_type'] ) ? 'web' : 'upload',
					'title'  => sprintf( $title, $this->plugins[ $slug ]['name'] ),
					'url'    => esc_url_raw( $url ),
					'nonce'  => $install_type . '-plugin_' . $slug,
					'plugin' => '',
					'api'    => $api,
					'extra'  => $extra,
				);
				unset(