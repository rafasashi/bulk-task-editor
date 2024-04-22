<?php
/**
 * Settings class file.
 *
 * @package REW Bulk Editor/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Rew_Bulk_Editor_Settings {

	/**
	 * The single instance of Rew_Bulk_Editor_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Constructor function.
	 *
	 * @param object $parent Parent object.
	 */
	public function __construct( $parent ) {
		
		$this->parent = $parent;

		$this->base = $this->parent->_base;

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		//add_filter( 'plugin_action_links',array($this,'add_settings_link'),10,2);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings', array( $this, 'configure_settings' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] ) && function_exists( 'add_' . $args['location'] . '_page' ) ) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
			add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
		}
		
		add_submenu_page(
			$this->parent->_token . '_settings',
			__( 'Post Types', 'rew-bulk-editor' ),
			__( 'Post Types', 'rew-bulk-editor' ),
			'edit_pages',
			'edit.php?post_type=post-type-task'
		);
		
		add_submenu_page(
			$this->parent->_token . '_settings',
			__( 'Taxonomies', 'rew-bulk-editor' ),
			__( 'Taxonomies', 'rew-bulk-editor' ),
			'edit_pages',
			'edit.php?post_type=taxonomy-task'
		);
		
		add_submenu_page(
			$this->parent->_token . '_settings',
			__( 'Users', 'rew-bulk-editor' ),
			__( 'Users', 'rew-bulk-editor' ),
			'edit_pages',
			'edit.php?post_type=user-task'
		);
		
		add_submenu_page(
			$this->parent->_token . '_settings',
			__( 'CSV', 'rew-bulk-editor' ),
			__( 'CSV', 'rew-bulk-editor' ),
			'edit_pages',
			'edit.php?post_type=csv-task'
		);
	}

	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location'    => 'menu', // Possible settings: options, menu, submenu.
				'parent_slug' => 'admin.php',
				'page_title'  => __( 'Bulk Editor', 'rew-bulk-editor' ),
				'menu_title'  => __( 'Bulk Editor', 'rew-bulk-editor' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent->_token . '_settings',
				'function'    => array( $this, 'settings_page' ),
				'icon_url'    => 'dashicons-list-view',
				'position'    => 75,
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Load settings JS & CSS
	 *
	 * @return void
	 */
	public function settings_assets() {

		//wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array('jquery'), '1.0.0', true );
		//wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links, $file ) {
		if( strpos( $file, basename( $this->parent->file ) ) !== false ) {
			$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'rew-bulk-editor' ) . '</a>';
			array_push( $links, $settings_link );
		}
		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		
		$settings = array(
		
			'overview' => array(
			
				'title'       => __( 'Overview', 'rew-bulk-editor' ),
				'description' => '',
				'fields'      => array(),
			),
			'settings' => array(
			
				'title'       => __( 'Settings', 'rew-bulk-editor' ),
				'description' => __( 'Bulk editor settings', 'rew-bulk-editor'),
				'fields'      => array(),
			),			
		);
		
		return apply_filters( $this->parent->_token . '_settings_fields', $settings );
	}
	
	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab.
			
			//phpcs:disable
			
			$current_section = '';
			
			if( isset( $_POST['tab'] ) ) {
				
				$current_section = sanitize_text_field($_POST['tab']);
			} 
			elseif( isset( $_GET['tab'] ) ) {
					
				$current_section = sanitize_text_field($_GET['tab']);
			}
			
			//phpcs:enable

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		
		echo wp_kses_normalize_entities($html); //phpcs:ignore
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML.
		$html      = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Bulk Editor', 'rew-bulk-editor' ) . '</h2>' . "\n";

			$tab = '';
		//phpcs:disable
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			
			$tab .= sanitize_text_field($_GET['tab']);
		}
		//phpcs:enable

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) { //phpcs:ignore
					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) { //phpcs:ignore
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				
				$tab_link = remove_query_arg( array(
					
					'settings-updated',
					'pt',
					'tax',
					
				), $tab_link );

				// Output tab.
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++$c;
			}

			$html .= '</h2>' . "\n";
		}
		
		if( empty($tab) || $tab == 'overview' ){
	
			$html .= '<div id="dashboard-widgets-wrap">' . "\n";
				$html .= '<div id="dashboard-widgets" class="metabox-holder">' . "\n";
					$html .= '<div class="postbox-container">' . "\n";
						$html .= '<div id="side-sortables" class="meta-box-sortables ui-sortable">' . "\n";
							
							$html .= '<div id="dashboard_right_now" class="postbox ">' . "\n";
								
								$html .= '<div class="postbox-header">' . "\n";
									$html .= '<h2 class="hndle ui-sortable-handle">Tasks</h2>' . "\n";
								$html .= '</div>' . "\n";
								
								$html .= '<div class="inside">' . "\n";
									$html .= '<div class="main">' . "\n";
									
										$html .= '<ul>' . "\n";
											
											if( !empty($tasks) ){
											
												foreach( $tasks as $task ){
													
													$html .= '<li>' . "\n";
													
														$html .= $task->post_title . "\n";
													
													$html .= '</li>' . "\n";
												}
											}
											else{
												
												$html .= '<li>' . "\n";
												
													$html .= __( 'No pending tasks', 'rew-bulk-editor' );
												
												$html .= '</li>' . "\n";
											}
										
										$html .= '</ul>' . "\n";
									
									$html .= '</div>' . "\n";
								$html .= '</div>' . "\n";
							
							$html .= '</div>' . "\n";
						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";
				$html .= '</div>' . "\n";
			$html .= '</div>' . "\n";
		}
		else{
		
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields.
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();
				
				$html     .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'rew-bulk-editor' ) ) . '" />' . "\n";
				$html     .= '</p>' . "\n";
			
			$html         .= '</form>' . "\n";
		}
		
		$html             .= '</div>' . "\n";

		echo wp_kses_normalize_entities($html); //phpcs:ignore
	}

	/**
	 * Main Rew_Bulk_Editor_Settings Instance
	 *
	 * Ensures only one instance of Rew_Bulk_Editor_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Rew_Bulk_Editor()
	 * @param object $parent Object instance.
	 * @return object Rew_Bulk_Editor_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of Rew_Bulk_Editor_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of Rew_Bulk_Editor_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	} // End __wakeup()

}