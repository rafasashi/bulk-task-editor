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

		add_action('wp_loaded',function(){

			add_action('wp_ajax_render_authors', array($this,'render_authors') );
			add_action('wp_ajax_render_taxonomy_terms', array($this,'render_taxonomy_terms') );
			
			add_action('wp_ajax_render_task_action', array($this,'render_task_action') );
			add_action('wp_ajax_render_task_process', array($this,'render_task_process') );
			add_action('wp_ajax_render_task_schedule', array($this,'render_task_schedule') );
			add_action('wp_ajax_render_task_progress', array($this,'render_task_progress') );
			
		});
		
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

		$this->register_post_type( 'data-task', __( 'Data tasks', 'rew-bulk-editor' ), __( 'Data task', 'rew-bulk-editor' ), '', array(

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

		add_filter('manage_post-type-task_posts_columns',array($this,'add_default_columns'));
		add_filter('manage_taxonomy-task_posts_columns',array($this,'add_default_columns'));
		add_filter('manage_user-task_posts_columns',array($this,'add_default_columns'));
		add_filter('manage_data-task_posts_columns',array($this,'add_default_columns'));
		
		add_action('manage_post-type-task_posts_custom_column',array($this,'filter_default_columns'),10,2);
		add_action('manage_taxonomy-task_posts_custom_column',array($this,'filter_default_columns'),10,2);
		add_action('manage_user-task_posts_custom_column',array($this,'filter_default_columns'),10,2);
		add_action('manage_data-task_posts_custom_column',array($this,'filter_default_columns'),10,2);
		
		add_filter('manage_post-type-task_posts_columns',function($columns){
			
			$new_columns = array(
				
				'post-type' => __('Post Type', 'rew-bulk-editor'),
			);

			$first_part  = array_slice($columns, 0, 2, true);
			
			$second_part = array_slice($columns, 2, null, true);

			$columns = array_merge($first_part, $new_columns, $second_part);
			
			return $columns;
			
		},99999);
		
		add_action('manage_post-type-task_posts_custom_column',function($column, $post_id){
			
			$task = $this->get_task_meta($post_id);
			
			if( $column == 'post-type' ){
				
				if( !empty($task['rewbe_post_type']) ){
				
					$post_type = get_post_type_object($task['rewbe_post_type']);
				
					if( !empty($post_type) ){
						
						echo $post_type->label;
					}
				}
			}
			
			return $column;
			
		},10,2);
		
		add_filter('manage_taxonomy-task_posts_columns',function($columns){
			
			$new_columns = array(
				
				'taxonomy' 	=> __('Taxonomy', 'rew-bulk-editor'),
			);

			$first_part  = array_slice($columns, 0, 2, true);
			
			$second_part = array_slice($columns, 2, null, true);

			$columns = array_merge($first_part, $new_columns, $second_part);
			
			return $columns;
			
		},99999);
		
		add_action('manage_taxonomy-task_posts_custom_column',function($column, $post_id){
			
			$task = $this->get_task_meta($post_id);
			
			if( $column == 'taxonomy' ){
				
				if( !empty($task['rewbe_taxonomy']) ){
				
					$taxonomy = get_taxonomy($task['rewbe_taxonomy']);
				
					if( !empty($taxonomy) ){
						
						echo $taxonomy->label;
					}
				}
			}
			
			return $column;
			
		},10,2);

		add_filter('manage_data-type-task_posts_columns',function($columns){
			
			$new_columns = array(
				
				'data-type' => __('Data Type', 'rew-bulk-editor'),
			);

			$first_part  = array_slice($columns, 0, 2, true);
			
			$second_part = array_slice($columns, 2, null, true);

			$columns = array_merge($first_part, $new_columns, $second_part);
			
			return $columns;
			
		},99999);
		
		add_action('manage_data-type-task_posts_custom_column',function($column, $post_id){
			
			$task = $this->get_task_meta($post_id);
			
			if( $column == 'data-type' ){
				
				if( !empty($task['rewbe_data_type']) ){
				
					echo strtoupper($task['rewbe_post_type']);
				}
			}
			
			return $column;
			
		},10,2);
		
		add_action('add_meta_boxes', function(){
			
			$this->admin->add_meta_box (
				
				'bulk-editor-process',
				__( 'Process', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task','data-task'),
				'side'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-progress',
				__( 'Progress', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task','data-task'),
				'side'
			);

			$this->admin->add_meta_box (
				
				'bulk-editor-filters',
				__( 'Filter', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task'),
				'advanced'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-task',
				__( 'Task', 'rew-bulk-editor' ), 
				array('post-type-task','taxonomy-task','user-task'),
				'advanced'
			);
		});
		
		add_filter('rewbe_post-type-task_custom_fields', function($fields=array()){
			
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

				// id
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'post_ids_op',
					'label'     	=> $post_type->label . ' ID',
					'type'        	=> 'radio',
					'options'		=> array(
						
						'in' 		=> 'IN',
						'not-in' 	=> 'NOT IN',
					),
					'default' 		=> 'in',
				);
				
				$fields[]=array(
				
					'metabox'		=> array('name'=>'bulk-editor-filters'),
					'id'			=> $this->_base . 'post_ids',
					'type'      	=> 'text',
					'placeholder'	=> 'Comma separated IDs',
					'style'			=> 'width:60%;',
				);

				// parent
				
				if( $post_type->hierarchical ){
					
					$fields[]=array(
					
						'metabox' 		=> array('name'=>'bulk-editor-filters'),
						'id'          	=> $this->_base . 'post_parent_op',
						'label'     	=> 'Parent ID',
						'type'        	=> 'radio',
						'options'		=> array(
							
							'in' 		=> 'IN',
							'not-in' 	=> 'NOT IN',
						),
						'default' 		=> 'in',
					);
					
					$fields[]=array(
					
						'metabox'		=> array('name'=>'bulk-editor-filters'),
						'id'			=> $this->_base . 'post_parent',
						'type'      	=> 'text',
						'placeholder'	=> 'Comma separated IDs',
						'style'			=> 'width:60%;',
					);
				}
				
				// status
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'post_status',
					'label'       	=> 'Status',
					'description' 	=> '',
					'type'        	=> 'checkbox_multi',
					'options'	  	=> $this->get_post_type_statuses($post_type->name),
					'default'     	=> '',
				);

				// content
				
				if( post_type_supports($post_type->name, 'editor')){
					
					$fields[]=array(
					
						'metabox'		=> array('name'=>'bulk-editor-filters'),
						'id'			=> $this->_base . 'search',
						'label'     	=> 'Content',
						'type'      	=> 'text',
						'placeholder'	=> 'Search keyword',
						'style'			=> 'width:60%;',
					);
				}
				
				// authors
				
				if( post_type_supports($post_type->name, 'author')){
					
					$fields[]=array(
					
						'metabox' 		=> array('name'=>'bulk-editor-filters'),
						'id'          	=> $this->_base . 'post_authors_op',
						'label'       	=> 'Authors',
						'type'        	=> 'radio',
						'options'		=> array(
							
							'in' 		=> 'IN',
							'not-in' 	=> 'NOT IN',
						),
						'default' 		=> 'in',
					);
					
					$fields[]=array(
					
						'metabox' 		=> array('name'=>'bulk-editor-filters'),
						'id'          	=> $this->_base . 'post_authors',
						'type'        	=> 'authors',
						'multi'			=> true,
					);
				}
				
				// TODO: date
				// TODO: comment count
				// TODO: stickyness
				
				// taxonomies

				$taxonomies = $this->get_post_type_taxonomies($post_type);
				
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
							'context'		=> 'filter',
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
					'operator'		=> true,
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
								
									'metabox' 	=> array('name'=>'bulk-editor-task'),
									'id'        => $field['name'],
								);
							}
						}
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-task'),
					'id'          	=> $this->_base . 'action',
					'type'        	=> 'select',
					'options'     	=> $options,
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-task'),
					'id'          	=> 'action_fields',
					'type'        	=> 'html',
					'data'        	=> '<div id="rewbe_action_fields" data-type="post_type" class="loading"></div>',
				);
				
				// process
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'process',
					'type'        	=> 'html',
					'data'     		=> '<div id="rewbe_task_process" data-type="post_type" class="loading"></div>',
				);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'per_process',
				);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'call',
				);
				
				if( !empty($task[$this->_base.'action']) && $task[$this->_base.'action'] != 'none' ){
					
					$total = $this->count_task_items($post->post_type,$task);
					
					$sc_steps = ceil($total/$this->sc_items);
					
					$fields[]=array(
						
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'		=> $this->_base . 'scheduled',
						'label'		=> 'Scheduled',
						'type'      => 'html',
						'data'      => !empty($task[$this->_base.'scheduled']) ? '100%' : '<span id="rewbe_task_scheduled" data-type="post_type" data-steps="'.$sc_steps.'" style="width:65px;display:block;">0%</span>',
					);
					
					$fields[]=array(
						
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'		=> $this->_base . 'processed',
						'label'		=> 'Processed',
						'type'      => 'html',
						'data'      => '<span id="rewbe_task_processed" style="width:65px;display:block;">' . $task['rewbe_progress'] . '%</span>',
					);
				}
				else{
					
					$fields[]=array(
					
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'          	=> 'progress-notice',
						'type'        	=> 'html',
						'data'        	=> '<i>Select a task and update</i>',
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
				
					'metabox' 		=> array('name'=>'bulk-editor-task'),
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
			
			$post_type = get_post_type_object($post_type);
			
			// status
			
			$actions[] = array(
				
				'label' 	=> 'Edit Status',
				'id' 		=> 'edit_status',
				'fields' 	=> array(
					array(
						
						'name' 		=> 'name',
						'type'		=> 'select',
						'options'	=> $this->get_post_type_statuses($post_type->name),
					),
				),
			);
			
			// parent
			
			if( $post_type->hierarchical ){
				
				$actions[] = array(
				
					'label' 	=> 'Edit Parent',
					'id' 		=> 'edit_parent',
					'fields' 	=> array(
						array(			
							
							'name'			=> 'id',
							'type'      	=> 'number',
							'min'      		=> 1,
							'min'      		=> 0,
							'max'      		=> 1000000000000,
							'style'			=> 'width:120px;',
							'default' 		=> 0,
						),
					),
				);
			}
					
			// author
			
			if( post_type_supports($post_type->name,'author') ){
				
				$actions[] = array(
					
					'label' 	=> 'Edit Author',
					'id' 		=> 'edit_author',
					'fields' 	=> array(
						array(
							
							'name' 		=> 'ids',
							'type'		=> 'authors',
							'multi'		=> false,
						),
					),
				);
			}
			
			// taxonomies
			
			$taxonomies = $this->get_post_type_taxonomies($post_type);
			
			foreach( $taxonomies as $taxonomy ){
				
				if( $taxonomy = get_taxonomy($taxonomy) ){
					
					$actions[] = array(
						
						'label' 	=> 'Edit ' . $taxonomy->label,
						'id' 		=> 'edit_tax_' . $taxonomy->name, // dropdown menu
						'fields' 	=> array(
							array(
								
								'name' 		=> 'action',
								'type'		=> 'radio',
								'options' 	=> array(
								
									'add' 		=> 'Add',
									'replace' 	=> 'Replace',
									'remove' 	=> 'Remove',
								),
								'default' => 'add',
							),
							array(
								
								'name' 			=> 'terms',
								'label' 		=> $taxonomy->label,
								'type'			=> 'terms',
								'taxonomy' 		=> $taxonomy->name,
								'hierarchical'	=> false,
								'operator'		=> false,
								'context'		=> 'action',
							),						
						),
					);
				}
			}
			
			// meta
			
			$actions[] = array(
				
				'label' 	=> 'Edit Meta',
				'id' 		=> 'edit_meta',
				'fields' 	=> array(
					array(
						
						'name' 		=> 'action',
						'type'		=> 'radio',
						'options' 	=> array(
						
							'edit' 		=> 'Edit',
							'remove' 	=> 'Remove',
						),
						'default' => 'edit',
					),				
					array(
						
						'name' 		=> 'data',
						'type'		=> 'meta',
						'operator'	=> false,
					),					
				),
			);
			
			return $actions;
			
		},0,2);
		
		add_filter('rewbe_taxonomy-task_custom_fields', function($fields=array()){
			
			global $post;
	
			$post_id = !empty($post->ID) ? $post->ID : 0; 
			
			$task = $this->get_task_meta($post_id);
			
			if( !empty($task[$this->_base.'taxonomy']) ){
				
				// taxonomy
				
				$taxonomy = get_taxonomy($task[$this->_base.'taxonomy']);
				
				$fields[]=array(
				
					'metabox'	=> array('name'=>'bulk-editor-filters'),
					'id'		=> $this->_base . 'taxonomy',
					'type'      => 'hidden',
				);

				// id
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'term_ids_op',
					'label'     	=> $taxonomy->label . ' ID',
					'type'        	=> 'radio',
					'options'		=> array(
						
						'in' 		=> 'IN',
						'not-in' 	=> 'NOT IN',
					),
					'default' 		=> 'in',
				);
				
				$fields[]=array(
				
					'metabox'		=> array('name'=>'bulk-editor-filters'),
					'id'			=> $this->_base . 'term_ids',
					'type'      	=> 'text',
					'placeholder'	=> 'Comma separated IDs',
					'style'			=> 'width:60%;',
				);

				// parent
				
				if( $taxonomy->hierarchical ){
					
					$fields[]=array(
					
						'metabox' 		=> array('name'=>'bulk-editor-filters'),
						'id'          	=> $this->_base . 'term_parent_op',
						'label'     	=> 'Parent ID',
						'type'        	=> 'radio',
						'options'		=> array(
							
							'in' 		=> 'IN',
							//'not-in' 	=> 'NOT IN', // does not exists yet
						),
						'default' 		=> 'in',
					);
					
					$fields[]=array(
					
						'metabox'		=> array('name'=>'bulk-editor-filters'),
						'id'			=> $this->_base . 'term_parent',
						'type'      	=> 'text',
						'placeholder'	=> 'Comma separated IDs',
						'style'			=> 'width:60%;',
					);
				}
				
				// search

				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'search_loc',
					'label'     	=> 'Search',
					'type'        	=> 'radio',
					'options'		=> array(
						
						'name' 			=> 'Name',
						'description' 	=> 'Description',
					),
					'default' 		=> 'name',
				);
				
				$fields[]=array(
				
					'metabox'		=> array('name'=>'bulk-editor-filters'),
					'id'			=> $this->_base . 'search',
					'type'      	=> 'text',
					'placeholder'	=> 'Search keyword',
					'style'			=> 'width:60%;',
				);
			
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
					'operator'		=> true,
				);
				
				// actions 
				
				$actions = $this->get_taxonomy_actions($taxonomy->name);
				
				$options = array('none' => 'None');
			
				foreach( $actions as $action ){
					
					$options[$action['id']] = $action['label'];
					
					if( $action['id'] = $task[$this->_base.'action'] ){
						
						if( !empty($action['fields']) ){
							
							foreach( $action['fields'] as $field ){
								
								// register without field
								
								$fields[]=array(
								
									'metabox' 	=> array('name'=>'bulk-editor-task'),
									'id'        => $field['name'],
								);
							}
						}
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-task'),
					'id'          	=> $this->_base . 'action',
					'type'        	=> 'select',
					'options'     	=> $options,
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-task'),
					'id'          	=> 'action_fields',
					'type'        	=> 'html',
					'data'        	=> '<div id="rewbe_action_fields" data-type="taxonomy" class="loading"></div>',
				);
				
				// process
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'process',
					'type'        	=> 'html',
					'data'     		=> '<div id="rewbe_task_process" data-type="taxonomy" class="loading"></div>',
				);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'per_process',
				);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'        	=> $this->_base . 'call',
				);
				
				if( !empty($task[$this->_base.'action']) && $task[$this->_base.'action'] != 'none' ){
					
					$total = $this->count_task_items($post->post_type,$task);
					
					$sc_steps = ceil($total/$this->sc_items);
					
					$fields[]=array(
						
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'		=> $this->_base . 'scheduled',
						'label'		=> 'Scheduled',
						'type'      => 'html',
						'data'      => !empty($task[$this->_base.'scheduled']) ? '100%' : '<span id="rewbe_task_scheduled" data-type="taxonomy" data-steps="'.$sc_steps.'" style="width:65px;display:block;">0%</span>',
					);
					
					$fields[]=array(
						
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'		=> $this->_base . 'processed',
						'label'		=> 'Processed',
						'type'      => 'html',
						'data'      => '<span id="rewbe_task_processed" style="width:65px;display:block;">' . $task['rewbe_progress'] . '%</span>',
					);
				}
				else{
					
					$fields[]=array(
					
						'metabox' 		=> array('name'=>'bulk-editor-progress'),
						'id'          	=> 'progress-notice',
						'type'        	=> 'html',
						'data'        	=> '<i>Select a task and update</i>',
					);
				}
			}
			else{

				$taxonomies = get_taxonomies(array(),'objects');
				
				$options = array();

				foreach( $taxonomies as $taxonomy ){
					
					if( $taxonomy->publicly_queryable ){
						
						$options[$taxonomy->name] = $taxonomy->label;
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'taxonomy',
					'label'       	=> 'Type',
					'type'        	=> 'select',
					'options'	  	=> $options,
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-task'),
					'id'          	=> 'action-notice',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a taxonomy and save</i>',
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-process'),
					'id'          	=> 'process-notice',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a taxonomy and save</i>',
				);
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-progress'),
					'id'          	=> 'progress-notice',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a taxonomy and save</i>',
				);
			}
				
			return $fields;
		});
		
		add_action('rewbe_taxonomy_actions',function($actions,$taxonomy){
			
			$taxonomy = get_taxonomy($taxonomy);
			
			// parent
			
			if( $taxonomy->hierarchical ){
				
				$actions[] = array(
				
					'label' 	=> 'Edit Parent',
					'id' 		=> 'edit_parent',
					'fields' 	=> array(
						array(			
							
							'name'			=> 'id',
							'type'      	=> 'number',
							'min'      		=> 1,
							'min'      		=> 0,
							'max'      		=> 1000000000000,
							'style'			=> 'width:120px;',
							'default' 		=> 0,
						),
					),
				);
			}
			
			// meta
			
			$actions[] = array(
				
				'label' 	=> 'Edit Meta',
				'id' 		=> 'edit_meta',
				'fields' 	=> array(
					array(
						
						'name' 		=> 'action',
						'type'		=> 'radio',
						'options' 	=> array(
						
							'edit' 		=> 'Edit',
							'remove' 	=> 'Remove',
						),
						'default' => 'edit',
					),				
					array(
						
						'name' 		=> 'data',
						'type'		=> 'meta',
						'operator'	=> false,
					),					
				),
			);
			
			return $actions;
			
		},0,2);

		add_filter('rewbe_user-task_custom_fields', function($fields=array()){
			
			global $post;
	
			$post_id = !empty($post->ID) ? $post->ID : 0; 
			
			$task = $this->get_task_meta($post_id);
			
			// id
			
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-filters'),
				'id'          	=> $this->_base . 'user_ids_op',
				'label'     	=> 'User ID',
				'type'        	=> 'radio',
				'options'		=> array(
					
					'in' 		=> 'IN',
					'not-in' 	=> 'NOT IN',
				),
				'default' 		=> 'in',
			);
			
			$fields[]=array(
			
				'metabox'		=> array('name'=>'bulk-editor-filters'),
				'id'			=> $this->_base . 'user_ids',
				'type'      	=> 'text',
				'placeholder'	=> 'Comma separated IDs',
				'style'			=> 'width:60%;',
			);
			
			// search
			/*
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-filters'),
				'id'          	=> $this->_base . 'search_loc',
				'label'     	=> 'Search',
				'type'        	=> 'radio',
				'options'		=> array(
					
					'name' 			=> 'Name',
					'description' 	=> 'Description',
				),
				'default' 		=> 'name',
			);
			
			$fields[]=array(
			
				'metabox'		=> array('name'=>'bulk-editor-filters'),
				'id'			=> $this->_base . 'search',
				'type'      	=> 'text',
				'placeholder'	=> 'Search keyword',
				'style'			=> 'width:60%;',
			);
			*/

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
				'operator'		=> true,
			);
			
			// actions 
			
			$actions = $this->get_user_actions();
			
			$options = array('none' => 'None');
		
			foreach( $actions as $action ){
				
				$options[$action['id']] = $action['label'];
				
				if( $action['id'] = $task[$this->_base.'action'] ){
					
					if( !empty($action['fields']) ){
						
						foreach( $action['fields'] as $field ){
							
							// register without field
							
							$fields[]=array(
							
								'metabox' 	=> array('name'=>'bulk-editor-task'),
								'id'        => $field['name'],
							);
						}
					}
				}
			}
			
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-task'),
				'id'          	=> $this->_base . 'action',
				'type'        	=> 'select',
				'options'     	=> $options,
			);
			
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-task'),
				'id'          	=> 'action_fields',
				'type'        	=> 'html',
				'data'        	=> '<div id="rewbe_action_fields" data-type="user" class="loading"></div>',
			);
			
			// process
			
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-process'),
				'id'        	=> $this->_base . 'process',
				'type'        	=> 'html',
				'data'     		=> '<div id="rewbe_task_process" data-type="user" class="loading"></div>',
			);
			
			$fields[]=array(
				
				'metabox' 		=> array('name'=>'bulk-editor-process'),
				'id'        	=> $this->_base . 'per_process',
			);
			
			$fields[]=array(
				
				'metabox' 		=> array('name'=>'bulk-editor-process'),
				'id'        	=> $this->_base . 'call',
			);
			
			if( !empty($task[$this->_base.'action']) && $task[$this->_base.'action'] != 'none' ){
				
				$total = $this->count_task_items($post->post_type,$task);
				
				$sc_steps = ceil($total/$this->sc_items);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-progress'),
					'id'		=> $this->_base . 'scheduled',
					'label'		=> 'Scheduled',
					'type'      => 'html',
					'data'      => !empty($task[$this->_base.'scheduled']) ? '100%' : '<span id="rewbe_task_scheduled" data-type="user" data-steps="'.$sc_steps.'" style="width:65px;display:block;">0%</span>',
				);
				
				$fields[]=array(
					
					'metabox' 		=> array('name'=>'bulk-editor-progress'),
					'id'		=> $this->_base . 'processed',
					'label'		=> 'Processed',
					'type'      => 'html',
					'data'      => '<span id="rewbe_task_processed" style="width:65px;display:block;">' . $task['rewbe_progress'] . '%</span>',
				);
			}
			else{
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-progress'),
					'id'          	=> 'progress-notice',
					'type'        	=> 'html',
					'data'        	=> '<i>Select a task and update</i>',
				);
			}
				
			return $fields;
		});
		
		add_action('rewbe_user_actions',function($actions){
			
			// meta
			
			$actions[] = array(
				
				'label' 	=> 'Edit Meta',
				'id' 		=> 'edit_meta',
				'fields' 	=> array(
					array(
						
						'name' 		=> 'action',
						'type'		=> 'radio',
						'options' 	=> array(
						
							'edit' 		=> 'Edit',
							'remove' 	=> 'Remove',
						),
						'default' => 'edit',
					),				
					array(
						
						'name' 		=> 'data',
						'type'		=> 'meta',
						'operator'	=> false,
					),					
				),
			);
			
			return $actions;
			
		},0,2);
		
		add_action('save_post', function($post_id,$post,$update){
				
			if( !defined('DOING_AUTOSAVE') || DOING_AUTOSAVE === false ){
				
				if( in_array($post->post_type,array(
			
					'post-type-task',
					'taxonomy-task',
					'user-task',
					'data-task',
				))){
					
					// delete schedule marks
					
					global $wpdb;
					
					$wpdb->query(
						
						$wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $this->_base.$post_id)
					);

					// reset scheduler
					
					update_post_meta($post_id,$this->_base . 'scheduled',0);
					
					// reset progress
					
					update_post_meta($post_id,$this->_base.'progress',0);
				}
			}
			
			return $post_id;
			
		},99999,3);
		
	} // End __construct ()
	
	public function add_default_columns($columns){
		
		$new_columns = array(
			
			'task'    	=> __('Task', 'rew-bulk-editor'),
			'progress'	=> __('Progress', 'rew-bulk-editor'),
		);

		$first_part  = array_slice($columns, 0, 2, true);
		
		$second_part = array_slice($columns, 2, null, true);

		$columns = array_merge($first_part, $new_columns, $second_part);
		
		return $columns;
	}
	
	public function get_post_type_taxonomies($post_type){
		
		$taxonomies = get_object_taxonomies($post_type->name);
		
		if( !in_array('post_format',$taxonomies) && post_type_supports($post_type->name,'post-formats') ){
			
			$taxonomies[] = 'post_format';
		}
		
		return $taxonomies;
	}
	
	public function filter_default_columns($column, $post_id){
		
		$task = $this->get_task_meta($post_id);
		
		if( $column == 'task' ){
			
			if( !empty($task['rewbe_action']) ){
				
				echo '<code>'. $task['rewbe_action'] . '</code>';
			}
		}
		elseif( $column == 'progress' ){
			
			
			echo $task['rewbe_progress'] . '%';
		}
		
		return $column;
	}
	
	public function get_post_type_statuses($post_type){
		
		return apply_filters('rewbe_post_type_statuses',array(
					
			'publish' 	=> 'Published',
			'pending' 	=> 'Pending',
			'private' 	=> 'Private',
			'draft' 	=> 'Draft',
			'trash' 	=> 'Trash',
		
		),$post_type);
	}
	
	public function get_task_meta($post_id){
		
		$meta = array(
		
			'rewbe_action' 		=> 'none',
			'rewbe_progress' 	=> 0,
		);
		
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
		
		return $this->sanitize_actions(apply_filters('rewbe_post_type_actions',array(),$slug));
	}
	
	public function get_taxonomy_actions($slug){
		
		return $this->sanitize_actions(apply_filters('rewbe_taxonomy_actions',array(),$slug));
	}
	
	public function get_user_actions(){
		
		return $this->sanitize_actions(apply_filters('rewbe_user_actions',array()));
	}
	
	public function get_data_actions($slug){
		
		return $this->sanitize_actions(apply_filters('rewbe_data_actions',array(),$slug));
	}
	
	public function sanitize_actions($actions){
		
		// validate & sanitize actions
		
		foreach( $actions as $i => $action ){
			
			$action_id = sanitize_title($action['id']);
			
			$actions[$i]['id'] = $action_id;
			
			if( is_array($action['fields']) && !empty($action['fields']) ){
				
				foreach( $action['fields'] as $j => $field ){
				
					$field_id = 'rewbe_act_' . $action_id . '__' . sanitize_title($field['name']);
					
					$actions[$i]['fields'][$j]['id'] = $field_id;
					
					$actions[$i]['fields'][$j]['name'] = $field_id;
				}
			}
			else{
				
				$actions[$i]['fields'] = array(); 
			}
		}
		
		return $actions;
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
			'data-task',
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
			'data-task',
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

	public function render_authors(){
		
		$results = array();
		
		if( current_user_can('edit_posts') ){
			
			if( $s =  apply_filters( 'get_search_query', $_GET['s'] ) ){
				
				$args = array(
					'search' 			=> '*' . $s . '*',
					'search_columns' 	=> array( 'user_login', 'user_email', 'user_nicename', 'ID' ),
					//'who' 			=> 'authors',
					'number' 			=> 10,
				);
				
				$query = new WP_User_Query( $args );
				
				foreach ( (array) $query->results as $user ) {
					
					$name = $user->display_name . ' (' . $user->user_email . ')';
					
					$results[] = array(
					
						'id' 	=> $user->ID,
						'name'	=> $name,
						'html'	=> $this->admin->display_field(array(
							
							'id' 	=> 'rewbe_act_edit_author__ids',
							'type' 	=> 'author',
							'data' 	=> array(
							
								'id'		=> $user->ID,
								'name' 		=> $name,
							),
						
						),null,false),
					);
				}
			}
		}
	
		wp_send_json($results);
		wp_die();
	}
	
	public function render_taxonomy_terms(){
		
		$results = array();
		
		if( current_user_can('edit_posts') ){
			
			if( $s =  apply_filters( 'get_search_query', $_GET['s'] ) ){
				
				$taxonomy = sanitize_title($_GET['taxonomy']);
				
				$hierarchical = !empty($_GET['h']) ? filter_var($_GET['h'], FILTER_VALIDATE_BOOLEAN) : false;
				
				$operator = !empty($_GET['o']) ? filter_var($_GET['o'], FILTER_VALIDATE_BOOLEAN) : false;
				
				$context = !empty($_GET['c']) ? sanitize_title($_GET['c']) : 'filter';
				
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
									'name'	=> $context == 'action' ? 'rewbe_act_edit_tax_' . $term->taxonomy . '__terms' : 'rewbe_tax_' . $term->taxonomy,
									'type' 	=> 'term',
									'data' 	=> array(
									
										'term'		=> $term,
										'operator' 	=> 'in',
										'children' 	=> 'in',
									),
									'hierarchical'	=> $hierarchical,
									'operator'		=> $operator,
								
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
	
	public function render_task_process(){
		
		if( !empty($_GET['task']) && is_array($_GET['task']) ){
			
			$task = $_GET['task'];
			
			$post_id = intval($task['post_ID']);
			
			$post = get_post($post_id);
			
			$total_items = $this->count_task_items($post->post_type,$task);
			
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
	
	public function render_task_progress(){
		
		if( !empty($_GET['pid']) && is_numeric($_GET['pid']) ){
			
			$task_id = intval($_GET['pid']);
			
			if( $post = get_post($task_id) ){
				
				$task = $this->get_task_meta($task_id);
				
				$per_process = $task[$this->_base.'per_process'];
				
				$call_method = $task[$this->_base.'call'];
				
				$scheduled = $task[$this->_base.'scheduled'];
				
				$action = $task[$this->_base.'action'];

				if( 1==1 || $call_method == 'ajax' ){
					
					if( $action != 'none' ){
						
						if( $post->post_type == 'post-type-task' ){
							
							$post_type = $task[$this->_base.'post_type'];
					
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
							
							$total_items = $query->found_posts;
							
							if( $total_items > $per_process ){
							
								$remaining = $total_items - $per_process;
							}
							else{
								
								$remaining = $total_items;
							}
							
							if( !empty($query->posts) ){
								
								$args = $this->parse_action_parameters($task);
								
								// register default actions
								
								if( $action == 'edit_status' ){
									
									add_action('rewbe_do_post_edit_status',array($this,'edit_post_status'),10,2);
								}						
								elseif( $action == 'edit_parent' ){
									
									add_action('rewbe_do_post_edit_parent',array($this,'edit_post_parent'),10,2);
								}						
								elseif( $action == 'edit_author' ){
									
									add_action('rewbe_do_post_edit_author',array($this,'edit_post_author'),10,2);
								}
								elseif( $action == 'edit_meta' ){
									
									add_action('rewbe_do_post_edit_meta',array($this,'edit_post_meta'),10,2);
								}
								elseif( strpos($action,'edit_tax_') === 0 ){
									
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
						}
						elseif( $post->post_type == 'taxonomy-task' ){
							
							$taxonomy = $task[$this->_base.'taxonomy'];
							
							$args = array(
								
								'taxonomy'			=> $taxonomy,
								'number'			=> $per_process,
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
							);
							
							$total_items = wp_count_terms($args);
							
							if( $total_items > $per_process ){
							
								$remaining = $total_items - $per_process;
							}
							else{
								
								$remaining = $total_items;
							}
							
							if( $terms = get_terms($args) ){
								
								$args = $this->parse_action_parameters($task);
								
								// register default actions
								
								if( $action == 'edit_parent' ){
									
									add_action('rewbe_do_term_edit_parent',array($this,'edit_term_parent'),10,2);
								}						
								elseif( $action == 'edit_meta' ){
									
									add_action('rewbe_do_term_edit_meta',array($this,'edit_term_meta'),10,2);
								}
								
								foreach( $terms as $term ){
									
									//update_term_meta($term->term_id,$this->_base.$task_id,time());
					
									apply_filters('rewbe_do_term_'.$action,$term,$args);
									 
									delete_term_meta($term->term_id,$this->_base.$task_id);
								}
							}
						}
						elseif( $post->post_type == 'user-task' ){
							
							$query = new WP_User_Query(array(
							
								'number' 			=> $per_process,
								'order'				=> 'ASC',
								'orderby'			=> 'ID',
								'count_total'		=> true,
								'meta_query' 		=> array(
							
									array(
										
										'key'     	=> $this->_base.$task_id,
										'value'   	=> 1,
										'type' 		=> 'NUMERIC',
										'compare' 	=> '!=',
									)
								),
							));

							$total_items = $query->get_total();
							
							if( $total_items > $per_process ){
							
								$remaining = $total_items - $per_process;
							}
							else{
								
								$remaining = $total_items;
							}
							
							if( $users = $query->get_results() ){
								
								$args = $this->parse_action_parameters($task);
								
								// register default actions
								
								if( $action == 'edit_meta' ){
									
									add_action('rewbe_do_user_edit_meta',array($this,'edit_user_meta'),10,2);
								}
								
								foreach ( $users as $user ){
									
									//update_user_meta($user->ID,$this->_base.$task_id,time());
					
									apply_filters('rewbe_do_user_'.$action,$user,$args);
									
									delete_user_meta($user->ID,$this->_base.$task_id);
								}
							}
						}
						elseif( $post->post_type == 'data-task' ){
							
							
						}
						
						$prog = round( ( $scheduled - $remaining ) / $scheduled * 100,2);
					}
					else{
						
						$prog = 100;
					}
					
					update_post_meta($task_id,$this->_base.'progress',$prog);
					
					echo $prog;
				}
			}
		}
		
		wp_die();
	}
	
	public function render_task_schedule(){
		
		if( !empty($_GET['step']) && is_numeric($_GET['step']) && !empty($_GET['pid']) && is_numeric($_GET['pid']) ){
			
			$step = intval($_GET['step']);
			
			$task_id = intval($_GET['pid']);
			
			$post = get_post($task_id);
			
			$task = $this->get_task_meta($task_id);
			
			$prog = 100;
			
			if( $total_items = $this->count_task_items($post->post_type,$task) ){
				
				/**	schedule task 
				*	0: scheduled
				*	t: processing
				*	1: done
				*/
				
				if( $post->post_type == 'post-type-task' ){
					
					$args = $this->parse_post_task_parameters($task,$this->sc_items,$step);

					$query = new WP_Query($args);

					if( $ids = $query->posts ){
						
						foreach( $ids as $id ){
							
							update_post_meta($id,$this->_base.$task_id,0);
						}
					}
				}
				elseif( $post->post_type == 'taxonomy-task' ){

					if( $ids = get_terms($args) ){
						
						foreach( $ids as $id ){
							
							update_term_meta($id,$this->_base.$task_id,0);
						}
					}
				}
				elseif( $post->post_type == 'user-task' ){
					
					$args = $this->parse_user_task_parameters($task,$this->sc_items,$step);
				
					$query = new WP_User_Query($args);
					
					if( $ids = $query->get_results() ){
						
						foreach( $ids as $id ){
							
							update_user_meta($id,$this->_base.$task_id,0);
						}
					}
				}
				elseif( $post->post_type == 'data-task' ){
					
					
				}
				
				$sc_steps = ceil( $total_items / $this->sc_items );

				$prog = ceil( $step / $sc_steps * 100 );
			}
				
			if( $prog == 100 ){
				
				// scheduled
				
				update_post_meta($task_id,$this->_base.'scheduled',$total_items);
			}
				
			echo $prog;
		}
		
		wp_die();
	}
	
	public function update_post($args) {
		
		// unregister save_post
		
		global $wp_filter;
		
		$actions = array();

		if (isset($wp_filter['save_post'])) {
			
			foreach ($wp_filter['save_post']->callbacks as $priority => $callbacks) {
				
				foreach ($callbacks as $callback) {
					
					$actions[$priority][] = $callback;
					
					remove_action('save_post', $callback['function'], $priority);
				}
			}
		}

		// update post

		$result = wp_update_post($args);

		// re-register save_post
		
		if (!empty($actions)) {
			
			foreach ($actions as $priority => $callbacks) {
				
				foreach ($callbacks as $callback) {

					add_action('save_post', $callback['function'], $priority, $callback['accepted_args']);
				}
			}
		}

		return $result;
	}
	
	public function edit_post_status($post,$args){
		
		if( !empty($args['name']) ){
			
			$post_status = sanitize_title($args['name']);
			
			$statuses = $this->get_post_type_statuses($post->post_type);
			
			if( isset($statuses[$post_status]) ){
				
				$this->update_post(array(
					
					'ID' 			=> $post->ID,
					'post_status' 	=> $post_status,
				));
			}
		}
	}
	
	public function edit_post_parent($post,$args){
		
		if( isset($args['id']) && is_numeric($args['id']) ){
			
			$parent_id = intval($args['id']);
			
			if( $parent_id > 0 ){
				
				$parent = get_post($parent_id);
				
				if( !empty($parent) && $post->post_type == $parent->post_type ){
					
					$this->update_post(array(
						
						'ID' 			=> $post->ID,
						'post_parent' 	=> $parent->ID,
					));
				}
			}
			else{
				
				// remove parent
				
				$this->update_post(array(
					
					'ID' 			=> $post->ID,
					'post_parent' 	=> $parent_id,
				));
			}
		}
	}
		
	public function edit_post_author($post,$args){
		
		if( !empty($args['ids'][1]) && is_numeric($args['ids'][1]) ){
			
			$author_id = intval($args['ids'][1]);
			
			if( $author_id > 0 ){
				
				$this->update_post(array(
					
					'ID' 			=> $post->ID,
					'post_author' 	=> $author_id,
				));
			}
		}
	}
	
	public function edit_post_meta($post,$args){
		
		if( !empty($args['action']) && !empty($args['data']['key']) ){
			
			$action = sanitize_title($args['action']);
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					if( $action == 'edit' ){
						
						update_post_meta($post->ID,$key,$value);
					}
					elseif( $action == 'remove' ){
					
						delete_post_meta($post->ID,$key,$value);
					}
				}
			}
		}
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
	
	public function edit_term_parent($term,$args){
		
		if( isset($args['id']) && is_numeric($args['id']) ){
			
			$parent_id = intval($args['id']);
			
			if( $parent_id > 0 ){
				
				$parent = get_term($parent_id);
				
				if( !empty($parent) && $term->taxonomy == $parent->taxonomy ){
					
					wp_update_term($term->term_id,$term->taxonomy,array(
						
						'parent' => $parent_id
					));
				}
			}
			else{
				
				// remove parent
				
				wp_update_term($term->term_id,$term->taxonomy,array(
					
					'parent' => $parent_id
				));
			}
		}
	}	

	public function edit_term_meta($term,$args){
		
		if( !empty($args['action']) && !empty($args['data']['key']) ){
			
			$action = sanitize_title($args['action']);
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					if( $action == 'edit' ){
						
						update_term_meta($term->term_id,$key,$value);
					}
					elseif( $action == 'remove' ){
					
						delete_term_meta($term->term_id,$key,$value);
					}
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

	public function edit_user_meta($user,$args){
		
		if( !empty($args['action']) && !empty($args['data']['key']) ){
			
			$action = sanitize_title($args['action']);
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					if( $action == 'edit' ){
						
						update_user_meta($user->ID,$key,$value);
					}
					elseif( $action == 'remove' ){
					
						delete_user_meta($user->ID,$key,$value);
					}
				}
			}
		}
	}
	
	public function count_task_items($type,$task){
		
		$items = 0;
		
		if( $type == 'post-type-task' ){
			
			if( $args = $this->parse_post_task_parameters($task) ){
				
				$query = new WP_Query($args);
				
				$items = $query->found_posts;
			}
		}
		elseif( $type == 'taxonomy-task' ){
			
			if( $args = $this->parse_term_task_parameters($task) ){

				$items = wp_count_terms($args);
			}
		}
		elseif( $type == 'user-task' ){
			
			if( $args = $this->parse_user_task_parameters($task) ){
				
				$query = new WP_User_Query($args);
				
				if( isset($args['meta_query']['relation']) ){
					
					// fix counting bug
					
					$items = count($query->get_results());
				}
				else{
					
					$items = $query->get_total();
				}
			}
		}
		elseif( $type == 'data-task' ){
			
			
		}
		
		return $items;
	}
	
	public function parse_post_task_parameters($task,$number=1,$paged=0){
		
		if( $post_type = get_post_type_object(sanitize_text_field($task['rewbe_post_type'])) ){
			
			$args = array(
				
				'post_type'				=> $post_type->name,
				'posts_per_page' 		=> $number,
				'paged' 				=> $paged,
				'order'					=> 'ASC',
				'orderby'				=> 'ID',
				'fields'				=> 'ids',
				'ignore_sticky_posts' 	=> true,
			);
			
			// filter search
			
			if( !empty($task['rewbe_search']) ){
			
				$args['s'] = apply_filters( 'get_search_query', sanitize_text_field($task['rewbe_search']) );
			}
			
			// filter post_status

			if( !empty($task['rewbe_post_status']) && is_array($task['rewbe_post_status']) ){
				
				$post_status = array_map('sanitize_text_field', $task['rewbe_post_status']);
				
				$args['post_status'] = $post_status;
			}
			
			// filters ids
			
			if( !empty($task['rewbe_post_ids']) ){
			
				$ids = array_filter(array_map('intval',explode(',',$task['rewbe_post_ids'])), function($id){
					
					return ( $id > 0 );
				});
				
				if( !empty($ids) ){
					
					$operator = !empty($task['rewbe_post_ids_op']) ? sanitize_title($task['rewbe_post_ids_op']) : 'in';
					
					if( $operator == 'in' ){
					
						$args['post__in'] = $ids;
					}
					else{
						
						$args['post__not_in'] = $ids;
					}
				}
			}

			// filters parent
			
			if( !empty($task['rewbe_post_parent']) ){
			
				$ids = array_filter(array_map('intval',explode(',',$task['rewbe_post_parent'])), function($id){
					
					return ( $id > 0 );
				});
				
				if( !empty($ids) ){
					
					$operator = !empty($task['rewbe_post_parent_op']) ? sanitize_title($task['rewbe_post_parent_op']) : 'in';
					
					if( $operator == 'in' ){
					
						$args['post_parent__in'] = $ids;
					}
					else{
						
						$args['post_parent__not_in'] = $ids;
					}
				}
			}
			
			// filter authors
			
			if( !empty($task['rewbe_post_authors']) && is_array($task['rewbe_post_authors']) ){

				$authors = array_filter(array_map('intval',$task['rewbe_post_authors']), function($id){
					
					return ( $id > 0 );
				});
				
				$operator = !empty($task['rewbe_post_authors_op']) ? sanitize_title($task['rewbe_post_authors_op']) : 'in';
				
				if( $operator == 'in' ){
				
					$args['author__in'] = $authors;
				}
				else{
					
					$args['author__not_in'] = $authors;
				}
			}
			
			// filter taxonomies
			
			$relation = $this->admin->get_relation_options();
			
			$operators = $this->admin->get_operator_options();
			
			$taxonomies = $this->get_post_type_taxonomies($post_type);
			
			foreach( $taxonomies as $taxonomy ){

				if( !empty($task['rewbe_tax_'.$taxonomy]['term']) && is_array($task['rewbe_tax_'.$taxonomy]['term']) ){

					$term_ids = array_filter(array_map('intval',$task['rewbe_tax_'.$taxonomy]['term']), function($id){
						
						return ( $id > 0 );
					});
					
					$terms = array();
					
					foreach( $term_ids as $k => $v ){

						$operator = isset($task['rewbe_tax_'.$taxonomy]['operator'][$k]) ? sanitize_text_field($task['rewbe_tax_'.$taxonomy]['operator'][$k]) : 'in';
						
						$children = isset($task['rewbe_tax_'.$taxonomy]['children'][$k]) ? sanitize_text_field($task['rewbe_tax_'.$taxonomy]['children'][$k]) : 'in';
						
						$terms[] = array(
						
							'id' 		=> $v,
							'operator' 	=> isset($operators[$operator]) ? $operators[$operator] : 'IN',
							'children'	=> $children == 'ex' ? false : true,
						);
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
			
			if( $meta_query = $this->parse_task_meta_query($task) ){
				
				$args['meta_query'] = $meta_query;
			}
		}
		
		return $args;
	}

	public function parse_term_task_parameters($task,$number=1,$paged=0,$per_page=10){
		
		$taxonomy = sanitize_text_field($task['rewbe_taxonomy']);
		
		$args = array(
			
			'taxonomy'		=> $taxonomy,
			'number' 		=> $number,
			'offset ' 		=> $paged > 0 ? ($paged - 1) * $per_page : 0,
			'order'			=> 'ASC',
			'orderby'		=> 'id',
			'fields'		=> 'ids',
			'hide_empty' 	=> false,
		);
		
		// filter search
		
		if( !empty($task['rewbe_search']) ){
		
			$search = apply_filters('get_search_query', sanitize_text_field($task['rewbe_search']));
			
			$location = !empty($task['rewbe_search_loc']) ? sanitize_title($task['rewbe_search_loc']) : 'name';
			
			if( $location == 'description' ){
				
				$args['description__like'] = $search;
			}
			else{
				
				$args['name__like'] = $search;
			}
		}
		
		// filters ids
		
		if( !empty($task['rewbe_term_ids']) ){
		
			$ids = array_filter(array_map('intval',explode(',',$task['rewbe_term_ids'])), function($id){
				
				return ( $id > 0 );
			});
			
			if( !empty($ids) ){
				
				$operator = !empty($task['rewbe_term_ids_op']) ? sanitize_title($task['rewbe_term_ids_op']) : 'in';
				
				if( $operator == 'in' ){
				
					$args['include'] = $ids;
				}
				else{
					
					$args['exclude'] = $ids;
				}
			}
		}

		// filters parent
		
		if( !empty($task['rewbe_term_parent']) ){
		
			$ids = array_filter(array_map('intval',explode(',',$task['rewbe_term_parent'])), function($id){
				
				return ( $id > 0 );
			});
			
			if( !empty($ids) ){
				
				$operator = !empty($task['rewbe_term_parent_op']) ? sanitize_title($task['rewbe_term_parent_op']) : 'in';
				
				if( $operator == 'in' ){
				
					$args['parent'] = $ids;
				}
				else{
					
					//does not exist yet
				}
			}
		}
		
		// filter meta
		
		if( $meta_query = $this->parse_task_meta_query($task) ){
			
			$args['meta_query'] = $meta_query;
		}
		
		return $args;
	}

	public function parse_user_task_parameters($task,$number=-1,$paged=1){
		
		$args = array(
			
			'number' 		=> $number,
			'order'			=> 'ASC',
			'orderby'		=> 'ID',
			'fields'		=> 'ids',
		);
		
		if( $number > 0 ){
			
			$args['paged'] 			= $paged;
			$args['count_total'] 	= false;
		}
		else {
			
			$args['count_total'] = true;
		}
		
		// filter search
		
		if( !empty($task['rewbe_search']) ){
		
			$args['search'] = apply_filters( 'get_search_query', sanitize_text_field($task['rewbe_search']) );
		
			$args['search_columns'] = array(
				
				'ID',
				'user_login',
				'user_email',
				'user_url',
				'user_nicename',
				'display_name',
			);
		}
		
		// filters ids
		
		if( !empty($task['rewbe_user_ids']) ){
		
			$ids = array_filter(array_map('intval',explode(',',$task['rewbe_user_ids'])), function($id){
				
				return ( $id > 0 );
			});
			
			if( !empty($ids) ){
				
				$operator = !empty($task['rewbe_user_ids_op']) ? sanitize_title($task['rewbe_user_ids_op']) : 'in';
				
				if( $operator == 'in' ){
				
					$args['include'] = $ids;
				}
				else{
					
					$args['exclude'] = $ids;
				}
			}
		}
		
		// filter meta
		
		if( $meta_query = $this->parse_task_meta_query($task) ){
			
			$args['meta_query'] = $meta_query;
		}
		
		return $args;
	}
	
	public function parse_task_meta_query($task){
		
		$meta_query = array();
		
		if( !empty($task['rewbe_meta']) && is_array($task['rewbe_meta']) ){

			$meta_rel = isset($task['rewbe_meta_rel']) ? sanitize_text_field($task['rewbe_meta_rel']) : 'or';

			if( count($task['rewbe_meta']['key']) > 1 ){
				
				$meta_query['relation'] = isset($relation[$meta_rel]) ? $relation[$meta_rel] : 'OR';
			}
			
			foreach( $task['rewbe_meta']['key'] as $i => $key ){
				
				if( isset($task['rewbe_meta']['value'][$i]) ){
					
					$key = sanitize_text_field($key);
					
					if( !empty($key) ){
						
						$value = sanitize_text_field($task['rewbe_meta']['value'][$i]);
						
						$type = sanitize_text_field($task['rewbe_meta']['type'][$i]);
						
						$type_options = $this->admin->get_type_options();
						
						$compare = sanitize_text_field($task['rewbe_meta']['compare'][$i]);
						
						$compare_options = $this->admin->get_compare_options();
						
						$meta = array(
							
							'key'     	=> $key,
							'type' 		=> isset($type_options[$type]) ? $type_options[$type] : 'CHAR',
							'compare' 	=> isset($compare_options[$compare]) ? $compare_options[$compare] : '=',
						);
						
						if( !in_array($compare,array(
						
							'exists',
							'not-exists'
						
						))){
							
							$meta['value'] = $value;
						}
						
						$meta_query[] = $meta;
					}
				}
			}
		}
		
		return $meta_query;
	}
	
	public function parse_action_parameters($task){
		
		$args = array();
		
		$curr_action = $task[$this->_base.'action'];
		
		if( $curr_action != 'none' ){
			
			$post_type = $task[$this->_base.'post_type'];

			$actions = $this->get_post_type_actions($post_type);
			
			if( !empty($actions) ){
			
				foreach( $actions as $action ){
			
					if( $action['id'] == $curr_action ){
						
						if( !empty($action['fields']) ){
							
							$prefix = 'rewbe_act_' . $curr_action . '__';
							
							foreach( $action['fields'] as $field ){
								
								if( isset($task[$field['id']]) ){
									
									$key = substr($field['id'],strlen($prefix));
									
									$value = $task[$field['id']];
									
									$args[$key] = $value;
								}
							}
						}
						
						break;
					}
				}
			}
		}
		
		return $args;
	}
	
	public function render_task_action(){
		
		if( current_user_can('edit_posts') && !empty($_GET['pid']) && !empty($_GET['ba']) ){
			
			if( $post_id = intval($_GET['pid']) ){
				
				$post = get_post($post_id);
				
				if( $bulk_action = sanitize_title($_GET['ba']) ){
					
					$task = $this->get_task_meta($post_id);
					
					if( $post->post_type == 'post-type-task' ){
					
						$actions = $this->get_post_type_actions($task[$this->_base.'post_type']);
					}
					elseif( $post->post_type == 'taxonomy-task' ){
					
						$actions = $this->get_taxonomy_actions($task[$this->_base.'taxonomy']);
					}
					elseif( $post->post_type == 'user-task' ){
						
						$actions = $this->get_user_actions();
					}
					elseif( $post->post_type == 'data-task' ){
						
						$actions = $this->get_data_actions($task[$this->_base.'data_type']);
					}
					
					if( !empty($actions) ){
						
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
