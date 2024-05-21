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
	
	public $sc_items = 1000;

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
		add_action('wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS.
		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		add_action('wp_ajax_render_taxonomy_terms' , array($this,'render_taxonomy_terms') );
		add_action('wp_ajax_render_post_type_action' , array($this,'render_post_type_action') );
		add_action('wp_ajax_render_post_type_process' , array($this,'render_post_type_process') );
		add_action('wp_ajax_render_post_type_schedule' , array($this,'render_post_type_schedule') );
		add_action('wp_ajax_render_post_type_progress' , array($this,'render_post_type_progress') );

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
				
				'bulk-editor-progress',
				__( 'Progress', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task','csv-task'),
				'side'
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
	
			$post_id = !empty($post->ID) ? $post->ID : 0; 
			
			$task = $this->get_task_meta($post_id);
			
			if( !empty($task[$this->_base.'post_type']) ){
				
				// post type
				
				$post_type = get_post_type_object($task[$this->_base.'post_type']);
				
				$fields[]=array(
				
					'metabox'	=> array('name'=>'bulk-editor-filters'),
					'id'		=> $this->_base . 'post_type',
					'type'      => 'hidden',
				);
				
				// search
				
				$fields[]=array(
				
					'metabox'		=> array('name'=>'bulk-editor-filters'),
					'id'			=> $this->_base . 'search',
					'label'     	=> 'Content',
					'type'      	=> 'text',
					'placeholder'	=> 'Search keyword',
					'style'			=> 'width:60%;',
				);
				
				// status
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'post_status',
					'label'       	=> 'Status',
					'description' 	=> '',
					'type'        	=> 'checkbox_multi',
					'options'	  	=> array(
					
						'publish' 	=> 'Publish',
						'pending' 	=> 'Pending',
						'draft' 	=> 'Draft',
						'trash' 	=> 'Trash',
					),
					'default'     	=> '',
				);
				
				// TODO: date
				// TODO: author IN/NOT IN
				// TODO: parent IN/NOT IN
				// TODO: comment count
				// TODO: stickyness
				
				// taxonomies
				
				$taxonomies = get_object_taxonomies($post_type->name);

				foreach( $taxonomies as $taxonomy ){
					
					if( $taxonomy = get_taxonomy($taxonomy) ){
						
						$fields[]=array(
						
							'metabox' 		=> array('name'=>'bulk-editor-filters'),
							'id'          	=> 'rewbe_tax_rel_' . $taxonomy->name,
							'label'       	=> $taxonomy->label,
							'type'        	=> 'radio',
							'default'		=> 'and',
							'options'		=> $this->admin->get_relation_options(),
						);
						
						$fields[]=array(
						
							'metabox' 		=> array('name'=>'bulk-editor-filters'),
							'id'          	=> 'rewbe_tax_' . $taxonomy->name,
							'type'        	=> 'terms',
							'taxonomy'    	=> $taxonomy->name,
							'hierarchical'	=> $taxonomy->hierarchical,
							'operator'		=> true,
						);
					}
				}
				
				// meta
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'meta_rel',
					'label'       	=> 'Meta',
					'type'        	=> 'radio',
					'default'		=> 'and',
					'options'		=> $this->admin->get_relation_options(),
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'        	=> $this->_base . 'meta',
					'type'        	=> 'meta',
				);
				
				// actions 
				
				$actions = $this->get_post_type_actions($post_type->name);
				
				$options = array('none' => 'None');
			
				foreach( $actions as $action ){
					
					$options[$action['id']] = $action['label'];
					
					if( $action['id'] = $task[$this->_base.'action'] ){
						
						if( !empty($action['fields']) ){
							
							foreach( $action['fields'] as $field ){
								
								// register without field
								
								$fields[]=array(
								
									'metabox' 	=> array('name'=>'bulk-editor-action'),
									'id'        => $field['name'],
								);
							}
						}
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-action'),
					'id'          	=> $this->_base . 'action',
					'type'        	=> 'select',
					'options'     	=> $options,
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-action'),
					'id'          	=> 'action_fields',
					'type'        	=> 'html',
					'data'        	=> '<div id="rewbe_action_fields" class="loading"></div>',
				);
				
				// process
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'process',
					'type'        	=> 'html',
					'data'     		=> '<div id="rewbe_task_process" class="loading"></div>',
				);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'per_process',
				);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'call',
				);
				
				if( $task[$this->_base.'action'] != 'none' ){
					
					$total = $this->count_task_items($task);
					
					$sc_steps = ceil($total/$this->sc_items);
					
					$fields[]=array(
						
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'		=> $this->_base . 'scheduled',
						'label'		=> 'Scheduled',
						'type'      => 'html',
						'data'      => !empty($task[$this->_base.'scheduled']) ? '100%' : '<span id="rewbe_task_scheduled" data-steps="'.$sc_steps.'" style="width:65px;display:block;">0%</span>',
					);
					
					$fields[]=array(
						
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'		=> $this->_base . 'processed',
						'label'		=> 'Processed',
						'type'      => 'html',
						'data'      => !empty($progress) ? '100%' : '<span id="rewbe_task_processed" style="width:65px;display:block;">0%</span>',
					);
				}
				else{
					
					$fields[]=array(
					
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'          	=> 'progress-notice',
						'type'        	=> 'html',
						'data'        	=> '<i>Select an action and update</i>',
					);
				}
			}
			else{

				$post_types = get_post_types('','objects');
				
				$options = array();

				foreach( $post_types as $post_type ){
					
					if( $post_type->publicly_queryable ){
						
						$options[$post_type->name] = $post_type->labels->singular_name;
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'post_type',
					'label'       	=> 'Type',
					'type'        	=> 'select',
					'options'	  	=> $options,
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-action'),
					'id'          	=> 'action-notice',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a post type and save</i>',
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'          	=> 'process-notice',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a post type and save</i>',
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-progress'),
					'id'          	=> 'progress-notice',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a post type and save</i>',
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
						'id' 		=> 'edit_tax_' . $taxonomy->name, // dropdown menu
						'fields' 	=> array(
							array(
								
								'id' 		=> 'action', // dynamic field
								'type'		=> 'radio',
								'options' 	=> array(
								
									'add' 		=> 'Add',
									'replace' 	=> 'Replace',
									'remove' 	=> 'Remove',
								),
								'default' => 'add',
							),
							array(
								
								'id' 			=> 'terms', // dynamic field
								'label' 		=> $taxonomy->label,
								'type'			=> 'terms',
								'taxonomy' 		=> $taxonomy->name,
								'hierarchical'	=> false,
								'operator'		=> false,
							),						
						),
					);
				}
			}
			
			return $actions;
			
		},0,2);
		
		add_action('save_post', function($post_id,$post,$update){
				
			if( !defined('DOING_AUTOSAVE') || DOING_AUTOSAVE === false ){
				
				if( in_array($post->post_type,array(
			
					'post-type-task',
					'taxonomy-task',
					'user-task',
					'csv-task',
				))){
					
					// delete schedule marks
					
					global $wpdb;
					
					$wpdb->query(
						
						$wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $this->_base.$post_id)
					);

					// reset scheduler
					
					update_post_meta($post_id,$this->_base . 'scheduled',0);
					
					// reset progress
					
					update_post_meta($post_id,$this->_base.'processed',0);
				}
			}
			
			return $post_id;
			
		},99999,3);
		
	} // End __construct ()

	public function get_task_meta($post_id){
		
		$meta = array();
		
		if( $data = get_metadata('post',$post_id) ){
			
			foreach( $data as $key => $value ){
				
				if( strpos($key,$this->_base) === 0 ){
				
					$meta[$key] = maybe_unserialize($value[0]);
				}
			}
		}
		
		return $meta;
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
	
	public function render_taxonomy_terms(){
		
		$results = array();
		
		if( current_user_can('edit_posts') ){
			
			if( $s =  apply_filters( 'get_search_query', $_GET['s'] ) ){
				
				$taxonomy = sanitize_title($_GET['taxonomy']);
				
				$name = sanitize_title($_GET['name']);
				
				if( $taxonomy = get_taxonomy($taxonomy) ){
					
					if( $terms = get_terms(array(
						
						'orderby' 		=> 'count',
						'order' 		=> 'DESC',
						'taxonomy'		=> $taxonomy->name,
						'hide_empty' 	=> false,
						'search' 		=> $s,
						'number' 		=> 100,
					))){
						
						foreach( $terms as $term ){
							
							$results[] = array(

								'id'	=> $term->term_id,
								'name'	=> $term->name,
								'html'	=> $this->admin->display_field(array(
									
									'id'    => 'rewbe_tax_' . $term->taxonomy,
									'name'	=> $name,
									'type' 	=> 'term',
									'data' 	=> array(
									
										'term'		=> $term,
										'operator' 	=> 'in',
										'children' 	=> 'in',
									),
									'hierarchical'	=> false,
									'operator'		=> false,
								
								),null,false),
							);
						}
					}
				}
			}
		}
	
		wp_send_json($results);
		wp_die();
	}
	
	public function render_post_type_process(){
		
		if( !empty($_GET['task']) && is_array($_GET['task']) ){
			
			$task = $_GET['task'];
			
			$post_id = intval($task['post_ID']);
			
			$post = get_post($post_id);
			
			$total_items = $this->count_task_items($task);
			
			// render fields

			$this->admin->display_meta_box_field(array(
			
				'id'        	=> $this->_base . 'matching',
				'label'       	=> 'Matching items',
				'type'        	=> 'number',
				'data'      	=> $total_items,
				'default'		=> 0,
				'disabled'		=> true,
				
			),$post);
			
			$this->admin->display_meta_box_field(array(
			
				'id'        	=> $this->_base . 'per_process',
				'label'       	=> 'Items per process',
				'type'        	=> 'number',
				'default'       => 10,
			
			),$post);

			$this->admin->display_meta_box_field(array(
			
				'id'        	=> $this->_base . 'call',
				'label'       	=> 'Calling method',
				'type'        	=> 'radio',
				'options'       => array(
				
					'ajax' 	=> 'AJAX',
					//'cron' 	=> 'CRON',
				),
				'default'       => 'ajax',
			
			),$post);
		}

		wp_die();
	}
	
	public function render_post_type_schedule(){
		
		if( !empty($_GET['step']) && is_numeric($_GET['step']) && !empty($_GET['pid']) && is_numeric($_GET['pid']) ){
			
			$step = intval($_GET['step']);
			
			$task_id = intval($_GET['pid']);
			
			$task = $this->get_task_meta($task_id);
			
			$args = $this->parse_task_parameters($task,$this->sc_items,$step);

			$query = new WP_Query($args);

			$ids = $query->posts;
			
			foreach( $ids as $id ){
				
				/**	schedule task 
				*	0: scheduled
				*	t: processing
				*	1: done
				*/
				
				update_post_meta($id,$this->_base.$task_id,0);
			}
			
			$sc_steps = ceil( $query->found_posts / $this->sc_items );

			$prog = ceil( $step / $sc_steps * 100 );
			
			if( $prog == 100 ){
				
				// scheduled
				
				update_post_meta($task_id,$this->_base.'scheduled',$query->found_posts);
			}
			
			echo $prog;
		}
		
		wp_die();
	}

	public function render_post_type_progress(){
		
		if( !empty($_GET['pid']) && is_numeric($_GET['pid']) ){
			
			$task_id = intval($_GET['pid']);
			
			$task = $this->get_task_meta($task_id);
			
			$post_type = $task[$this->_base.'post_type'];
			
			$per_process = $task[$this->_base.'per_process'];
			
			$call_method = $task[$this->_base.'call'];
			
			$scheduled = $task[$this->_base.'scheduled'];
			
			$action = $task[$this->_base.'action'];

			if( 1==1 || $call_method == 'ajax' ){
				
				if( $action != 'none' ){
					
					$query = new WP_Query(array(
					
						'post_type' 		=> $post_type,
						'posts_per_page' 	=> $per_process,
						'order'				=> 'ASC',
						'orderby'			=> 'ID',
						'meta_query' 		=> array(
					
							array(
								
								'key'     	=> $this->_base.$task_id,
								'value'   	=> 1,
								'type' 		=> 'NUMERIC',
								'compare' 	=> '!=',
							)
						),
					));
					
					if( $query->found_posts > $per_process ){
					
						$remaining = $query->found_posts - $per_process;
					}
					else{
						
						$remaining = $query->found_posts;
					}
					
					if( !empty($query->posts) ){
						
						$args = $this->parse_action_parameters($task);
						
						// register default actions
						
						if( strpos($action,'edit_tax_') === 0 ){
							
							$taxonomy =  substr($action,strlen('edit_tax_'));
							
							$args['taxonomy'] = $taxonomy; 
							
							add_action('rewbe_do_post_edit_tax_'.$taxonomy,array($this,'edit_post_taxonomy'),10,2);
						}
							
						foreach( $query->posts as $post ){
							
							//update_post_meta($post->ID,$this->_base.$task_id,time());
			
							apply_filters('rewbe_do_post_'.$action,$post,$args);
							
							delete_post_meta($post->ID,$this->_base.$task_id);
						}
					}
					
					$prog = round( ( $scheduled - $remaining ) / $scheduled * 100,2);
				}
				else{
					
					$prog = 100;
				}
				
				if( $prog == 100 ){
					
					// processed
				
					update_post_meta($task_id,$this->_base.'processed',$scheduled);
				}
				
				echo $prog;
			}
		}
		
		wp_die();
	}
	
	public function edit_post_taxonomy($post,$args){
		
		if( !empty($args['action']) && !empty($args['taxonomy']) ){
			
			$action = sanitize_title($args['action']);
			
			$taxonomy = sanitize_title($args['taxonomy']);
			
			if( !empty($args['terms']['term']) ){
				
				$term_ids = array();
				
				foreach( $args['terms']['term'] as $term_id ){
					
					if( is_numeric($term_id) ){
						
						$term_id = intval($term_id);
						
						if( $term_id > 0 && !in_array($term_id,$term_ids) && term_exists($term_id,$taxonomy) ) {
							
							$term_ids[] = $term_id;
						}
					}
				}
				
				if( !empty($term_ids) ){
					
					if( $action == 'add' ){
					
						wp_set_post_terms($post->ID, $term_ids, $taxonomy, true);
					}
					elseif( $action == 'replace' ){
					
						wp_set_post_terms($post->ID, $term_ids, $taxonomy, false);
					}
					elseif( $action == 'remove' ){
						
						wp_remove_object_terms($post->ID, $term_ids, $taxonomy);
					}
					
					if( $post->post_type == 'product' && strpos($taxonomy,'pa_') === 0 && function_exists('wc_get_product') ){
					
						add_action('rewbe_add_product_terms',array($this,'woo_add_product_attributes'),10,4);
						add_action('rewbe_replace_product_terms',array($this,'woo_replace_product_attributes'),10,4);
						add_action('rewbe_remove_product_terms',array($this,'woo_remove_product_attributes'),10,4);
					}
					
					do_action($this->_base.$action.'_'.$post->post_type.'_terms', $post, $term_ids, $taxonomy, $args);
				}
			}
		}
		
	}
	
	public function woo_add_product_attributes($post,$term_ids,$taxonomy,$args){
		
		$existing_terms = wp_get_post_terms($post->ID,$taxonomy,array('fields' => 'ids'));

		$merged_terms = array_merge($existing_terms,$term_ids);

		$merged_terms = array_unique($merged_terms);
		
		$this->woo_set_product_attributes($post->ID,$merged_terms,$taxonomy);
	}
	
	public function woo_replace_product_attributes($post,$term_ids,$taxonomy,$args){
		
		$this->woo_set_product_attributes($post->ID,$term_ids,$taxonomy);
	}
	
	public function woo_remove_product_attributes($post,$term_ids,$taxonomy,$args){
		
		$existing_terms = wp_get_post_terms($post->ID,$taxonomy,array('fields' => 'ids'));
		
		$diff_terms = array_diff($existing_terms,$term_ids);
		
		$this->woo_set_product_attributes($post->ID,$diff_terms,$taxonomy);
	}
	
	public function woo_set_product_attributes($post_id,$term_ids,$taxonomy){
		
		$product = wc_get_product($post_id);
		
		$atts = $product->get_attributes();

		if( isset($atts[$taxonomy]) ){
			
			$att = $atts[$taxonomy];
		}
		elseif( $tax_id = wc_attribute_taxonomy_id_by_name($taxonomy) ){
			
			$pos = count($atts) + 1;
			
			$att = new WC_Product_Attribute();
			
			$att->set_id($tax_id);
			$att->set_name($taxonomy);
			$att->set_position($pos);
			$att->set_visible(true);
			$att->set_variation(false);
		}
		
		if( !empty($att) ){
		
			$att->set_options($term_ids);
			
			$atts[$taxonomy] = $att;
			
			$product->set_attributes($atts);
			
			$product->save();
		}
	}
	
	public function count_task_items($task){
		
		$items = 0;
		
		if( $args = $this->parse_task_parameters($task) ){
			
			$query = new WP_Query($args);
			
			$items = $query->found_posts;
		}
		
		return $items;
	}
	
	public function parse_task_parameters($task,$number=1,$paged=0){
		
		$post_type = sanitize_text_field($task['rewbe_post_type']);
		
		$args = array(
			
			'post_type'			=> $post_type,
			'posts_per_page' 	=> $number,
			'paged' 			=> $paged,
			'order'				=> 'ASC',
			'orderby'			=> 'ID',
			'fields'			=> 'ids',
		);
		
		// filter post_status

		if( !empty($task['rewbe_post_status']) && is_array($task['rewbe_post_status']) ){
			
			$post_status = array_map('sanitize_text_field', $task['rewbe_post_status']);
			
			$args['post_status'] = $post_status;
		}
		
		// filter search
		
		if( !empty($task['rewbe_search']) ){
		
			$args['s'] = apply_filters( 'get_search_query', sanitize_text_field($task['rewbe_search']) );
		}
		
		// filter taxonomies
		
		$relation = $this->admin->get_relation_options();
		
		$operators = $this->admin->get_operator_options();
		
		$taxonomies = get_object_taxonomies($post_type);
		
		foreach( $taxonomies as $taxonomy ){

			if( !empty($task['rewbe_tax_'.$taxonomy]['term']) && is_array($task['rewbe_tax_'.$taxonomy]['term']) ){
				
				$term_ids = array_map('floatval', $task['rewbe_tax_'.$taxonomy]['term']);
				
				$terms = array();
				
				foreach( $term_ids as $k => $v ){
					
					if( $v > 0 ){
						
						$operator = isset($task['rewbe_tax_'.$taxonomy]['operator'][$k]) ? sanitize_text_field($task['rewbe_tax_'.$taxonomy]['operator'][$k]) : 'in';
						
						$children = isset($task['rewbe_tax_'.$taxonomy]['children'][$k]) ? sanitize_text_field($task['rewbe_tax_'.$taxonomy]['children'][$k]) : 'in';
						
						$terms[] = array(
						
							'id' 		=> $v,
							'operator' 	=> isset($operators[$operator]) ? $operators[$operator] : 'IN',
							'children'	=> $children == 'ex' ? false : true,
						);
					}
				}
				
				if( !empty($terms) ){
					
					$tax_rel = isset($task['rewbe_tax_rel_'.$taxonomy]) ? sanitize_text_field($task['rewbe_tax_rel_'.$taxonomy]) : 'and';
					
					$args['tax_query'] = array( 
						
						'relation' => isset($relation[$tax_rel]) ? $relation[$tax_rel] : 'AND',
					);
					
					foreach( $terms as $term ){

						$args['tax_query'][] = array(
						
							'taxonomy' 			=> $taxonomy,
							'field'    			=> 'term_id',
							'terms'    			=> $term['id'],
							'operator' 			=> $term['operator'],
							'include_children'	=> $term['children'],
						);
					}
				}
			}
		}
		
		// filter meta
		
		if( !empty($task['rewbe_meta']) && is_array($task['rewbe_meta']) ){
	
			$meta_rel = isset($task['rewbe_meta_rel']) ? sanitize_text_field($task['rewbe_meta_rel']) : 'or';
			
			$args['meta_query'] = array( 
				
				'relation' => isset($relation[$meta_rel]) ? $relation[$meta_rel] : 'OR',
			);
			
			foreach( $task['rewbe_meta']['key'] as $i => $key ){
				
				if( isset($task['rewbe_meta']['value'][$i]) ){
					
					$key = sanitize_text_field($key);
					
					if( !empty($key) ){
						
						$value = sanitize_text_field($task['rewbe_meta']['value'][$i]);
						
						$type = sanitize_text_field($task['rewbe_meta']['type'][$i]);
						
						$type_options = $this->admin->get_type_options();
						
						$compare = sanitize_text_field($task['rewbe_meta']['compare'][$i]);
						
						$compare_options = $this->admin->get_compare_options();
						
						$args['meta_query'][] = array(
							
							'key'     	=> $key,
							'value'   	=> $value,
							'type' 		=> isset($type_options[$type]) ? $type_options[$type] : 'CHAR',
							'compare' 	=> isset($compare_options[$compare]) ? $compare_options[$compare] : '=',
						);
					}
				}
			}
		}
		
		return $args;
	}
	
	public function parse_action_parameters($task){
		
		$args = array();
		
		$action = $task[$this->_base.'action'];
		
		if( $action != 'none' ){
			
			$prefix = 'rewbe_act_' . $action . '__';
			
			foreach( $task as $key => $value ){
				
				if( strpos($key,$prefix) === 0 ){
					
					$slug = substr($key,strlen($prefix));
					
					$args[$slug] = $value;
				}
			}
		}
		
		return $args;
	}
	
	public function render_post_type_action(){
		
		if( current_user_can('edit_posts') ){
			
			if( $post_id = intval($_GET['pid']) ){
				
				$post = get_post($post_id);
				
				if( $post_type = sanitize_title($_GET['pt']) ){
				
					if( $bulk_action = sanitize_title($_GET['ba']) ){
					
						$actions = $this->get_post_type_actions($post_type);
						
						foreach( $actions as $action ){
							
							if( $bulk_action == $action['id'] && !empty($action['fields']) ){
								
								foreach( $action['fields'] as $field ){

									$this->admin->display_meta_box_field($field,$post);
								}
							}
						}
					}
				}
			}
		}
		
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
