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
			__( 'Post Types', 'bulk-task-editor' ),
			__( 'Post Types', 'bulk-task-editor' ),
			'edit_pages',
			'edit.php?post_type=post-type-task'
		);
		
		add_submenu_page(
			$this->parent->_token . '_settings',
			__( 'Taxonomies', 'bulk-task-editor' ),
			__( 'Taxonomies', 'bulk-task-editor' ),
			'edit_pages',
			'edit.php?post_type=taxonomy-task'
		);
		
		add_submenu_page(
			$this->parent->_token . '_settings',
			__( 'Users', 'bulk-task-editor' ),
			__( 'Users', 'bulk-task-editor' ),
			'edit_pages',
			'edit.php?post_type=user-task'
		);
		/*
		add_submenu_page(
			$this->parent->_token . '_settings',
			__( 'Data', 'bulk-task-editor' ),
			__( 'Data', 'bulk-task-editor' ),
			'edit_pages',
			'edit.php?post_type=data-task'
		);
		*/
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
				'page_title'  => __( 'Bulk Task Editor', 'bulk-task-editor' ),
				'menu_title'  => __( 'Tasks', 'bulk-task-editor' ),
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
			$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'bulk-task-editor' ) . '</a>';
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
			
				'title'       => __( 'Overview', 'bulk-task-editor' ),
				'description' => '',
				'fields'      => array(),
			),
			'settings' => array(
			
				'title'       => __( 'Settings', 'bulk-task-editor' ),
				'description' => __( 'Bulk editor settings', 'bulk-task-editor'),
				'fields'      => array(
					
					array(
					
						'id'	=> 'multi_duplication',
						'label'	=> 'Enable multisite duplication',
						'type'	=> 'checkbox',
					),
				),
			),
			'addons' => array(
				'title'					=> __( 'Addons', 'bulk-task-editor' ),
				'description'			=> '',
				'class'					=> 'pull-right',
				'logo'					=> $this->parent->assets_url . '/images/addons-icon.png',
				'fields'				=> array(),
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

		//get addons
		
		$domain = parse_url(home_url(),PHP_URL_HOST);
		
		$campaign = basename(dirname(__FILE__,2));
		
		$this->addons = array(
			
			'woo-bulk-product-editor' 	=> array(
			
				'title' 		=> 'Bulk Product Editor',
				'addon_link' 	=> 'https://code.recuweb.com/get/bulk-product-editor/?utm_source='.$domain.'&utm_medium=referral&utm_campaign='.$campaign,
				'addon_name' 	=> 'woo-bulk-product-editor',
				'logo_url' 		=> $this->parent->assets_url . '/images/addons/bulk-product-editor-1-300x300.png',
				'description'	=> 'Bulk Product Editor is a WordPress plugin designed to streamline and accelerate your store management workflow all without overloading your server.',
				'author' 		=> 'Code Market',
				'author_link' 	=> 'https://code.recuweb.com/about-us/?utm_source='.$domain.'&utm_medium=referral&utm_campaign='.$campaign,
			),
			'openai-bulk-editor' 	=> array(
			
				'title' 		=> 'OpenAI Bulk Editor',
				'addon_link' 	=> 'https://code.recuweb.com/get/openai-bulk-editor/?utm_source='.$domain.'&utm_medium=referral&utm_campaign='.$campaign,
				'addon_name' 	=> 'openai-bulk-editor',
				'logo_url' 		=> $this->parent->assets_url . '/images/addons/openai-bulk-editor-300x300.png',
				'description'	=> 'Accelerate your content management workflow using OpenAI API to edit post types, taxonomies, users, and imported data, all while keeping your server load minimal.',
				'author' 		=> 'Code Market',
				'author_link' 	=> 'https://code.recuweb.com/about-us/?utm_source='.$domain.'&utm_medium=referral&utm_campaign='.$campaign,
			),			
			'language-switcher-everywhere' 	=> array(
			
				'title' 		=> 'Language Switcher',
				'addon_link' 	=> 'https://code.recuweb.com/get/language-switcher-everywhere/?utm_source='.$domain.'&utm_medium=referral&utm_campaign='.$campaign,
				'addon_name' 	=> 'language-switcher-everywhere',
				'logo_url' 		=> $this->parent->assets_url . '/images/addons/language-switcher-everywhere-squared-300x300.png',
				'description'	=> 'Extends Language Switcher to add languages to custom post types and taxonomies like WooCommerce products or tags',
				'author' 		=> 'Code Market',
				'author_link' 	=> 'https://code.recuweb.com/about-us/?utm_source='.$domain.'&utm_medium=referral&utm_campaign='.$campaign,
			),
		);
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		
		echo wp_kses($html,apply_filters('rewbe_allowed_admin_html',array())); //phpcs:ignore
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {
		
		// get tab name
		$tab = !empty($_GET['tab']) ? sanitize_title($_GET['tab']) : key($this->settings);
		
		// Build page HTML.
		$html      = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Bulk Task Editor', 'bulk-task-editor' ) . '</h2>' . "\n";

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				
				$class = 'nav-tab';
				
				if ( $section == $tab ) {
					
					$class .= ' nav-tab-active';
				}

				// Set tab link.
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				
				$tab_link = remove_query_arg( array(
					
					'settings-updated',
					'pt',
					'tax',
					
				), $tab_link );

				// Output tab.
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . ( !empty($data['logo']) ? '<img src="'.$data['logo'].'" alt="" style="margin-top: 4px;margin-right: 7px;float: left;">' : '' ) . esc_html( $data['title'] ) . '</a>' . "\n";

				++$c;
			}

			$html .= '</h2>' . "\n";
		}
		
		if( $tab == 'overview' ){
	
			$html .= '<div id="dashboard-widgets-wrap">' . "\n";
				$html .= '<div id="dashboard-widgets" class="metabox-holder">' . "\n";
					
					$html .= '<div class="postbox-container">' . "\n";
						$html .= '<div class="meta-box-sortables ui-sortable">' . "\n";
							
							$html .= '<div class="postbox ">' . "\n";
								
								$html .= '<div class="postbox-header">' . "\n";
									$html .= '<h2 class="hndle ui-sortable-handle">Processing Tasks</h2>' . "\n";
								$html .= '</div>' . "\n";
								
								$html .= '<div class="inside">' . "\n";
									$html .= '<div class="main">' . "\n";
									
										$html .= '<ul>' . "\n";
											
											$tasks = get_posts(array(
											
												'post_type' 		=> $this->parent->get_task_types(),
												'posts_per_page' 	=> -1,
												'order'				=> 'DESC',
												'orderby'			=> 'date',
												'meta_query'		=> array(
													
													'relation'	=> 'AND',
													array(
													
														'key' 		=> 'rewbe_scheduled',
														'value'		=> 0,
														'type' 		=> 'NUMERIC',
														'compare'	=> '>',
													),
													array(
													
														'key' 		=> 'rewbe_progress',
														'value'		=> 100,
														'type' 		=> 'NUMERIC',
														'compare'	=> '<',
													),
												)
											));
											
											if( !empty($tasks) ){
												
												foreach( $tasks as $task ){
													
													$html .= '<li>' . "\n";
													
														$html .= '<code>' . floatval(get_post_meta($task->ID,'rewbe_progress',true)) . '%</code> ' . "\n";
														
														$html .= '<a href="' . admin_url('post.php?post='.$task->ID.'&action=edit') . '">' . $task->post_title . '</a>' . "\n";
													
													$html .= '</li>' . "\n";
												}
											}
											else{
												
												$html .= '<li>' . "\n";
												
													$html .= __( 'No processing tasks', 'bulk-task-editor' );
												
												$html .= '</li>' . "\n";
											}
										
										$html .= '</ul>' . "\n";
									
									$html .= '</div>' . "\n";
								$html .= '</div>' . "\n";
							
							$html .= '</div>' . "\n";
							

							$html .= '<div class="postbox ">' . "\n";
								
								$html .= '<div class="postbox-header">' . "\n";
									$html .= '<h2 class="hndle ui-sortable-handle">Pending Tasks</h2>' . "\n";
								$html .= '</div>' . "\n";
								
								$html .= '<div class="inside">' . "\n";
									$html .= '<div class="main">' . "\n";
									
										$html .= '<ul>' . "\n";
											
											$tasks = get_posts(array(
											
												'post_type' 		=> $this->parent->get_task_types(),
												'posts_per_page' 	=> -1,
												'order'				=> 'DESC',
												'orderby'			=> 'date',
												'meta_query'		=> array(
													'relation'	=> 'OR',
													array(
													
														'key' 		=> 'rewbe_scheduled',
														'value'		=> 0,
														'type' 		=> 'NUMERIC',
														'compare'	=> '=',
													),
													array(
													
														'key' 		=> 'rewbe_scheduled',
														'compare'	=> 'NOT EXISTS',
													),
												)
											));
											
											if( !empty($tasks) ){
											
												foreach( $tasks as $task ){
													
													$html .= '<li>' . "\n";
													
														$html .= '<code>' . floatval(get_post_meta($task->ID,'rewbe_progress',true)) . '%</code> ' . "\n";
														
														$html .= '<a href="' . admin_url('post.php?post='.$task->ID.'&action=edit') . '">' . $task->post_title . '</a>' . "\n";
													
													$html .= '</li>' . "\n";
												}
											}
											else{
												
												$html .= '<li>' . "\n";
												
													$html .= __( 'No pending tasks', 'bulk-task-editor' );
												
												$html .= '</li>' . "\n";
											}
										
										$html .= '</ul>' . "\n";
									
									$html .= '</div>' . "\n";
								$html .= '</div>' . "\n";
							
							$html .= '</div>' . "\n";
							
							$html .= '<div class="postbox ">' . "\n";
								
								$html .= '<div class="postbox-header">' . "\n";
									$html .= '<h2 class="hndle ui-sortable-handle">Completed Tasks</h2>' . "\n";
								$html .= '</div>' . "\n";
								
								$html .= '<div class="inside">' . "\n";
									$html .= '<div class="main">' . "\n";
									
										$html .= '<ul>' . "\n";
											
											$tasks = get_posts(array(
											
												'post_type' 		=> $this->parent->get_task_types(),
												'posts_per_page' 	=> 10,
												'order'				=> 'DESC',
												'orderby'			=> 'date',
												'meta_query'		=> array(
 													array(
													
														'key' 		=> 'rewbe_progress',
														'value'		=> 100,
														'type' 		=> 'NUMERIC',
														'compare'	=> '=',
													),
												)
											));
											
											if( !empty($tasks) ){
											
												foreach( $tasks as $task ){
													
													$html .= '<li>' . "\n";
													
														$html .= '<code>' . floatval(get_post_meta($task->ID,'rewbe_progress',true)) . '%</code> ' . "\n";
														
														$html .= '<a href="' . admin_url('post.php?post='.$task->ID.'&action=edit') . '">' . $task->post_title . '</a>' . "\n";
													
													$html .= '</li>' . "\n";
												}
											}
											else{
												
												$html .= '<li>' . "\n";
												
													$html .= __( 'No completed tasks', 'bulk-task-editor' );
												
												$html .= '</li>' . "\n";
											}
										
										$html .= '</ul>' . "\n";
									
									$html .= '</div>' . "\n";
								$html .= '</div>' . "\n";
							
							$html .= '</div>' . "\n";
						
						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";
					
					$html .= '<div class="postbox-container">' . "\n";
						$html .= '<div class="meta-box-sortables ui-sortable">' . "\n";
							
							$html .= '<div class="postbox ">' . "\n";
								
								$html .= '<div class="postbox-header">' . "\n";
									$html .= '<h2 class="hndle ui-sortable-handle">New Task</h2>' . "\n";
								$html .= '</div>' . "\n";
								
								$html .= '<div class="inside">' . "\n";
									$html .= '<div class="main">' . "\n";
									
										$html .= '<ul>' . "\n";
											
											$html .= '<li>' . "\n";
											
												$html .= '<a href="'.admin_url('post-new.php?post_type=post-type-task').'">'.__('Post Type Task', 'bulk-task-editor').'</a>' . "\n";
											
											$html .= '</li>' . "\n";
											
											$html .= '<li>' . "\n";
											
												$html .= '<a href="'.admin_url('post-new.php?post_type=taxonomy-task').'">'.__('Taxonomy Task', 'bulk-task-editor').'</a>' . "\n";
											
											$html .= '</li>' . "\n";
											
											$html .= '<li>' . "\n";
											
												$html .= '<a href="'.admin_url('post-new.php?post_type=user-task').'">'.__('User Task', 'bulk-task-editor').'</a>' . "\n";
											
											$html .= '</li>' . "\n";
											
											/*
											$html .= '<li>' . "\n";
											
												$html .= '<a href="'.admin_url('post-new.php?post_type=data-task').'">'.__('Imported Data', 'bulk-task-editor').'</a>' . "\n";
											
											$html .= '</li>' . "\n";
											*/
											
										$html .= '</ul>' . "\n";
									
									$html .= '</div>' . "\n";
								$html .= '</div>' . "\n";
							
							$html .= '</div>' . "\n";
							
							
						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";
				$html .= '</div>' . "\n";
			$html .= '</div>' . "\n";
		}
		elseif( $tab == 'addons' ){
			
			$html .= '<h3 style="margin-bottom:25px;">' . esc_html($this->settings[$tab]['title']) . '</h3>' . PHP_EOL;

			$html .= '<div class="settings-form-wrapper" style="margin-top:25px;">';
				
				$html .= '<div id="the-list">';
				
					foreach( $this->addons as $addon ){
				
						$html .= '<div class="panel panel-default plugin-card plugin-card-akismet">';
						
							$html .= '<div class="panel-body plugin-card-top">';
								
								$html .= '<div class="name column-name">';
								
									$html .= '<h3>';
									
										$html .= '<a href="'.esc_url($addon['addon_link']).'" target="_blank" style="text-decoration:none;">';
											
											if( !empty($addon['logo_url']) ){
												
												$html .= '<img class="plugin-icon" src="'.esc_url($addon['logo_url']).'" />';
											}
											
											$html .= esc_html($addon['title']);	
											
										$html .= '</a>';
										
									$html .= '</h3>';
									
								$html .= '</div>';
								
								$html .= '<div class="desc column-description">';
							
									$html .= '<p>'.esc_html($addon['description']).'</p>';
									$html .= '<p class="authors"> <cite>By <a target="_blank" href="'.esc_url($addon['author_link']).'">'.esc_html($addon['author']).'</a></cite></p>';
								
								$html .= '</div>';
								
							$html .= '</div>';
							
							$html .= '<div class="panel-footer plugin-card-bottom text-right">';
								
								$plugin_file = $addon['addon_name'] . '/' . $addon['addon_name'] . '.php';
								
								if( !file_exists( WP_PLUGIN_DIR . '/' . $addon['addon_name'] . '/' . $addon['addon_name'] . '.php' ) ){
									
									if( !empty($addon['source_url']) ){
									
										$url = $addon['source_url'];
									}
									else{
										
										$url = $addon['addon_link'];
									}
									
									$html .= '<a href="' . esc_url($url) . '" class="button install-now" aria-label="Install">Install Now</a>';
								}
								else{
									
									$html .= '<span>Installed</span>';
								}
							
							$html .= '</div>';
						
						$html .= '</div>';
					}
				
				$html .= '</div>';

			$html .= '</div>';
		}
		elseif( !empty($this->settings[$tab]['fields']) ){
		
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields.
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();
				
				$html     .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'bulk-task-editor' ) ) . '" />' . "\n";
				$html     .= '</p>' . "\n";
			
			$html         .= '</form>' . "\n";
		}
		
		$html             .= '</div>' . "\n";
		
		echo wp_kses($html,apply_filters('rewbe_allowed_admin_html',array())); //phpcs:ignore
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
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning of Rew_Bulk_Editor_API is forbidden.', 'bulk-task-editor'  ), esc_attr( $this->parent->_version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of Rew_Bulk_Editor_API is forbidden.', 'bulk-task-editor'  ), esc_attr( $this->parent->_version ) );
	} // End __wakeup()

}
