<?php
/**
 * Main plugin class file.
 *
 * @package REW Bulk Editor/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Rew_Bulk_Editor {

	/**
	 * The single instance of Rew_Bulk_Editor.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * Local instance of Rew_Bulk_Editor_Admin_API
	 *
	 * @var Rew_Bulk_Editor_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version; //phpcs:ignore
	
	/**
	 * The base.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_base; //phpcs:ignore
	
	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token; //phpcs:ignore

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor funtion.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		
		$this->_version = $version;
		$this->_base 	= 'rewbe_';
		$this->_token   = 'rew_bulk_editor';

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		add_action('wp_ajax_search_taxonomy_terms' , array($this,'search_taxonomy_terms') );
		add_action('wp_ajax_get_bulk_action_form' , array($this,'get_bulk_action_form') );
		add_action('wp_ajax_get_task_process' , array($this,'get_task_process') );

		// Load API for generic admin functions.
		if ( is_admin() ) {
			$this->admin = new Rew_Bulk_Editor_Admin_API($this);
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
		
		$this->register_post_type( 'post-type-task', __( 'Post tasks', 'rew-bulk-editor' ), __( 'Post task', 'rew-bulk-editor' ), '', array(

			'public' 				=> false,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> '',
		));		
		
		$this->register_post_type( 'taxonomy-task', __( 'Term tasks', 'rew-bulk-editor' ), __( 'Term task', 'rew-bulk-editor' ), '', array(

			'public' 				=> false,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> '',
		));		

		$this->register_post_type( 'user-task', __( 'User tasks', 'rew-bulk-editor' ), __( 'User task', 'rew-bulk-editor' ), '', array(

			'public' 				=> false,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> '',
		));	

		$this->register_post_type( 'csv-task', __( 'CSV tasks', 'rew-bulk-editor' ), __( 'CSV task', 'rew-bulk-editor' ), '', array(

			'public' 				=> false,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> '',
		));	

		add_action( 'add_meta_boxes', function(){
			
			$this->admin->add_meta_box (
				
				'bulk-editor-process',
				__( 'Process', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task','csv-task'),
				'side'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-post-type',
				__( 'Post Type', 'rew-bulk-editor' ), 
				array('post-type-task'),
				'advanced'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-taxonomy',
				__( 'Taxonomy', 'rew-bulk-editor' ), 
				array('taxonomy-task'),
				'advanced'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-filters',
				__( 'Filter', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task'),
				'advanced'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-action',
				__( 'Action', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task'),
				'advanced'
			);
		});
		
		add_filter('post-type-task_custom_fields', function($fields=array()){
			
			global $post;
			
			// process
			
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-process'),
				'id'        	=> $this->_base . 'process',
				'label'       	=> '',
				'description' 	=> '',
				'type'        	=> 'html',
				'data'     		=> '<div id="rewbe_task_process" class="loading"></div>',
			);
			
			// post type
			
			$post_types = get_post_types('','objects');
			
			$options = array();

			foreach( $post_types as $post_type ){
				
				if( $post_type->publicly_queryable ){
					
					$options[$post_type->name] = $post_type->labels->singular_name;
				}
			}
			
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-post-type'),
				'id'          	=> $this->_base . 'post_type',
				'label'       	=> '',
				'description' 	=> '',
				'type'        	=> 'select',
				'options'	  	=> $options,
				'default'     	=> '',
				'placeholder' 	=> '',
			);	
			
			$slug = get_post_meta($post->ID,$this->_base . 'post_type',true);
			
			if( $post_type = get_post_type_object($slug) ){
				
				// status
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'post_status',
					'label'       	=> '<b>Status</b>',
					'description' 	=> '',
					'type'        	=> 'checkbox_multi',
					'options'	  	=> array(
					
						'publish' 	=> 'Publish',
						'pending' 	=> 'Pending',
						'draft' 	=> 'Draft',
						'trash' 	=> 'Trash',
					),
					'default'     	=> '',
					'placeholder' 	=> '',
				);
				
				// filters
				
				$taxonomies = get_object_taxonomies($slug);

				foreach( $taxonomies as $taxonomy ){
					
					if( $taxonomy = get_taxonomy($taxonomy) ){
						
						$fields[]=array(
						
							'metabox' 		=> array('name'=>'bulk-editor-filters'),
							'id'          	=> $this->_base . 'tax_' . $taxonomy->name,
							'label'       	=> '<b>'.$taxonomy->label.'</b>',
							'description' 	=> '',
							'type'        	=> 'terms',
							'taxonomy'    	=> $taxonomy->name,				
							'placeholder' 	=> 'One ' . $taxonomy->labels->singular_name . ' name, slug, or ID per line',
						);
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'        	=> $this->_base . 'meta',
					'label'       	=> '<b>Meta</b>',
					'description' 	=> '',
					'type'        	=> 'key_value',
					'default'     	=> '',
					'placeholder' 	=> '',
				);
				
				// actions 
				
				$actions = $this->get_post_type_actions($slug);
				
				$bulk_action = get_post_meta($post->ID,$this->_base . 'action',true);
				
				$options = array('-1' => 'None');
			
				foreach( $actions as $action ){
					
					$options[$action['id']] = $action['label'];
					
					if( $action['id'] = $bulk_action){
						
						if( !empty($action['fields']) ){
							
							foreach( $action['fields'] as $field ){
								
								// register without field
								
								$fields[]=array(
								
									'metabox' 		=> array('name'=>'bulk-editor-action'),
									'id'          	=> $field['name'],
									'label'       	=> '',
									'description' 	=> '',
									'type'        	=> '',
									'placeholder' 	=> '',
								);
							}
						}
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-action'),
					'id'          	=> $this->_base . 'action',
					'label'       	=> '',
					'description' 	=> '',
					'type'        	=> 'select',
					'options'     	=> $options,
					'placeholder' 	=> '',
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-action'),
					'id'          	=> 'action_fields',
					'label' 		=> '',
					'description' 	=> '',
					'type'        	=> 'html',
					'data'        	=> '<div id="rewbe_action_fields" class="loading"></div>',
				);
			}
			else{
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> 'notice',
					'label' 		=> '',
					'description' 	=> '',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a post type</i>',
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-action'),
					'id'          	=> 'notice',
					'label' 		=> '',
					'description' 	=> '',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a post type</i>',
				);
			}
				
			return $fields;
		});	
		
		add_action('rewbe_post_type_actions',function($actions,$post_type){
			
			$taxonomies = get_object_taxonomies($post_type);
			
			foreach( $taxonomies as $taxonomy ){
				
				if( $taxonomy = get_taxonomy($taxonomy) ){
					
					$actions[] = array(
						
						'label' 	=> 'Edit ' . $taxonomy->label,
						'id' 		=> 'tax_' . $taxonomy->name, // dropdown menu
						'fields' 	=> array(
							array(
								
								'id' 		=> 'action', // dynamic field
								'type'		=> 'radio',
								'options' 	=> array(
								
									'add' 		=> 'Add',
									'remove' 	=> 'Remove',
								),
								'default' => 'add',
							),
							array(
								
								'id' 		=> 'terms', // dynamic field
								'label' 	=> $taxonomy->label,
								'type'		=> 'terms',
								'taxonomy' 	=> $taxonomy->name,
							),						
						),
					);
				}
			}
			
			return $actions;
			
		},0,2);
		
	} // End __construct ()
	
	public function get_post_task_terms($post_id,$taxonomy){
		
		$terms = array();
		
		if( $term_ids = get_post_meta($post_id,$this->_base . 'tax_'.$taxonomy,true) ){
			
			$terms = get_terms( array(
				
				'taxonomy' 	=> $taxonomy,
				'include' 	=> array_map('intval', $term_ids),
			) );
		}
		
		return $terms;
	}

	public function get_post_type_actions($slug){
		
		$actions = apply_filters('rewbe_post_type_actions',array(),$slug);
		
		// validate & sanitize actions
		
		foreach( $actions as $i => $action ){
			
			$action_id = sanitize_title($action['id']);
			
			$actions[$i]['id'] = $action_id;
			
			if( is_array($action['fields']) && !empty($action['fields']) ){
				
				foreach( $action['fields'] as $j => $field ){
				
					$field_id = sanitize_title($field['id']);
				
					$actions[$i]['fields'][$j]['name'] = 'rewbe_act_' . $action_id . '__' . $field_id;
				}
			}
			else{
				
				$actions[$i]['fields'] = array(); 
			}
		}
		
		return $actions;
	}
	
	public function get_taxonomy_actions($slug){
		
		return apply_filters('rewbe_'.$slug.'_tax_actions',array());
	}
	
	public function get_user_actions(){
		
		return apply_filters('rewbe_user_actions',array());
	}
	
	/**
	 * Register post type function.
	 *
	 * @param string $post_type Post Type.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param string $description Description.
	 * @param array  $options Options array.
	 *
	 * @return bool|string|Rew_Bulk_Editor_Post_Type
	 */
	public function register_post_type( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) {
			return false;
		}

		$post_type = new Rew_Bulk_Editor_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param array  $post_types Post types to register this taxonomy for.
	 * @param array  $taxonomy_args Taxonomy arguments.
	 *
	 * @return bool|string|Rew_Bulk_Editor_Taxonomy
	 */
	public function register_taxonomy( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) {
			return false;
		}

		$taxonomy = new Rew_Bulk_Editor_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 *
	 * @access  public
	 * @return void
	 * @since   1.0.0
	 */
	public function enqueue_styles() {
		//wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		//wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueue_scripts() {
		//wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		//wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Admin enqueue style.
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles( $hook = '' ) {
		
		$screen = get_current_screen();
		
		if( in_array($screen->id,array(
			
			'post-type-task',
			'taxonomy-task',
			'user-task',
			'csv-task',
		))){
			
			wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version . time() );
			wp_enqueue_style( $this->_token . '-admin' );
		}
		
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts( $hook = '' ) {
		
		$screen = get_current_screen();
		
		if( in_array($screen->id,array(
			
			'post-type-task',
			'taxonomy-task',
			'user-task',
			'csv-task',
		))){
		
			wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin.js', array( 'jquery' ), $this->_version . time(), true );
			wp_enqueue_script( $this->_token . '-admin' );
		}
		
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'rew-bulk-editor', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'rew-bulk-editor';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()
	
	public function search_taxonomy_terms(){
		
		$results = array();
		
		if( $s =  apply_filters( 'get_search_query', $_GET['s'] ) ){
			
			$taxonomy = sanitize_title($_GET['taxonomy']);
			
			if( current_user_can('edit_posts') ){
				
				if( $terms = get_terms(array(
					
					'orderby' 		=> 'count',
					'order' 		=> 'DESC',
					'taxonomy'		=> $taxonomy,
					'hide_empty' 	=> false,
					'search' 		=> $s,
					'number' 		=> 100,
				))){
					
					foreach( $terms as $term ){
						
						$results[] = array(

							'id'	=> $term->term_id,
							'name'	=> $term->name,
						);
					}
				}
			}
		}
		
		wp_send_json($results);
		wp_die();
	}
	
	public function get_task_process(){
		
		$html = '';
		
		if( !empty($_GET['task']) && is_array($_GET['task']) ){
			
			global $wpdb;
			
			$task = $_GET['task'];

			$post_id = intval($task['post_ID']);
			
			$post = get_post($post_id);
			
			$post_type = sanitize_text_field($task['rewbe_post_type']);
			
			$sql = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s";
			
			$vars = array($post_type);

			// filtered items

			if( !empty($task['rewbe_post_status']) && is_array($task['rewbe_post_status']) ){
				
				$post_statuses = array_map('sanitize_text_field', $task['rewbe_post_status']);

				$sql .= " AND post_status IN (".implode(', ', array_fill(0, count($post_statuses), '%s')).")";
				
				$vars = array_merge($vars,$post_statuses);
			}
			
			$query = $wpdb->prepare($sql,$vars);

			$total_items = $wpdb->get_var($query);
			
			$progress = 0;

			$html .= '<p class="form-field">';
				
				$html .= '<label><b>Matching items</b></label>';
				
				$html .= '<br>';

				$html .= $this->admin->display_field(array(
				
					'id'        	=> $this->_base . 'per_process',
					'label'       	=> '',
					'description' 	=> '',
					'type'        	=> 'number',
					'data'      	=> $total_items,
					'disable'		=> true,
					'style'			=> 'background:#eee;color:#888;font-weight:bold;',
				
				),$post,false);

			$html .= '</p>' . "\n";
			
			$html .= '<p class="form-field">';
				
				$html .= '<label><b>Items per process</b></label>';
				
				$html .= $this->admin->display_field(array(
				
					'id'        	=> $this->_base . 'per_process',
					'label'       	=> '',
					'description' 	=> '',
					'type'        	=> 'number',
					'default'       => 10,
				
				),$post,false);

			$html .= '</p>' . "\n";

			$html .= '<p class="form-field">';
				
				$html .= '<label><b>Calling method</b></label>';
				$html .= '<br>';
				$html .= $this->admin->display_field(array(
				
					'id'        	=> $this->_base . 'call',
					'label'       	=> '',
					'description' 	=> '',
					'type'        	=> 'radio',
					'options'       => array(
					
						'ajax' 	=> 'AJAX',
						'cron' 	=> 'CRON',
					),
					'default'        => 'ajax',
				
				),$post,false);

			$html .= '</p>' . "\n";
			
			$html .= '<p class="form-field">';
				
				$html .= '<label><b>Progress</b></label>';
				
				$html .= '<br>';
				
				$html .= $progress . '%';

			$html .= '</p>' . "\n";
		}
		
		echo $html;
		
		wp_die();
	}
	
	public function get_bulk_action_form(){
		
		$html = '';
		
		if( current_user_can('edit_posts') ){
			
			if( $post_id = intval($_GET['pid']) ){
				
				$post = get_post($post_id);
				
				if( $post_type = sanitize_title($_GET['pt']) ){
				
					if( $bulk_action = sanitize_title($_GET['ba']) ){
					
						$actions = $this->get_post_type_actions($post_type);
						
						foreach( $actions as $action ){
							
							if( $bulk_action == $action['id'] && !empty($action['fields']) ){
								
								$html .= '<p class="form-field">';
									
									foreach( $action['fields'] as $field ){
										
										if( !empty($field['label']) ){
											
											$html .= '<label for="' . $field['id'] . '"><b>' . $field['label'] . '</b></label>';
										}
										
										$html .= $this->admin->display_field($field,$post,false);
									}
								
								$html .= '</p>' . "\n";
							}
						}
					}
				}
			}
		}
		
		echo $html;
		
		wp_die();
	}
	
	/**
	 * Main Rew_Bulk_Editor Instance
	 *
	 * Ensures only one instance of Rew_Bulk_Editor is loaded or can be loaded.
	 *
	 * @param string $file File instance.
	 * @param string $version Version parameter.
	 *
	 * @return Object Rew_Bulk_Editor instance
	 * @see Rew_Bulk_Editor()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of Rew_Bulk_Editor is forbidden' ) ), esc_attr( $this->_version ) );

	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of Rew_Bulk_Editor is forbidden' ) ), esc_attr( $this->_version ) );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install() {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _log_version_number() { //phpcs:ignore
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
