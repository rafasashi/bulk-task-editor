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
	
	public $rescheduled = false;
	
	public $sc_items = 1000;
	
	public $images = array();
				
	public $terms = array();
			
	public $terms_meta = array();
			
	public $terms_image = array();
			
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
				$this->get_task_types(),
				'side'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-progress',
				__( 'Progress', 'rew-bulk-editor' ), 
				$this->get_task_types(),
				'side'
			);

			$this->admin->add_meta_box (
				
				'bulk-editor-filters',
				__( 'Filter', 'rew-bulk-editor' ), 
				$this->get_task_types(),
				'advanced'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-task',
				__( 'Task', 'rew-bulk-editor' ), 
				$this->get_task_types(),
				'advanced'
			);
		});
		
		add_filter('rewbe_post-type-task_custom_fields', function($fields=array()){
			
			global $post;
	
			$screen = get_current_screen();
			 
			if( !empty($screen) && $screen->base === 'post' && !empty($_POST['post_type']) ){
				
				$task = $this->sanitize_task_meta($_POST);
			}
			elseif( !empty($post->ID) ){

				$task = $this->get_task_meta($post->ID);
			}
			
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
					'style'			=> 'margin-right:5px;',
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
				
				// date
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'dates_rel',
					'label'       	=> 'Dates',
					'type'        	=> 'radio',
					'default'		=> 'and',
					'options'		=> $this->admin->get_relation_options(),
				);
				
				$fields[]=array(
				
					'metabox'		=> array('name'=>'bulk-editor-filters'),
					'id'			=> $this->_base . 'dates',
					'type'      	=> 'dates',
					'columns'		=> $this->admin->get_date_column_options('post'),
				);

				// TODO: comment count
				// TODO: stickyness
				
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
				
					'metabox' 	=> array('name'=>'bulk-editor-filters'),
					'id'        => $this->_base . 'meta',
					'type'      => 'meta',
				);
				
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
				
				// actions 
				
				$actions = $this->get_post_type_actions($post_type->name,$task);
				
				$options = array('none' => 'None');
			
				foreach( $actions as $action ){
					
					$options[$action['id']] = $action['label'];
					
					if( $action['id'] == $task[$this->_base.'action'] ){
						
						if( !empty($action['fields']) ){
							
							foreach( $action['fields'] as $field ){
								
								if( !empty($field['type']) && $field['type'] != 'html' ){
									
									// register without field
									
									$fields[]=array(
									
										'metabox' 	=> array('name'=>'bulk-editor-task'),
										'id'        => $field['name'],
									);
								}
							}
						}
					}
				}
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-task'),
					'id'          	=> $this->_base . 'action',
					'label'         => 'Action',
					'type'        	=> 'select',
					'options'     	=> $options,
				);
				
				if( $curr_action = $task[$this->_base.'action'] ){

					if( $actions = $this->get_post_type_actions($task[$this->_base.'post_type'],$task) ){
						
						foreach( $actions as $action ){
						
							if( $curr_action == $action['id'] && !empty($action['fields']) ){
								
								foreach( $action['fields'] as $field ){
									
									$field['metabox'] = array('name'=>'bulk-editor-task');
									
									$fields[] = $field;
								}
							}
						}
					}
				}
				
				// process
				
				$fields[]= $this->get_process_items_field('post_type');
				
				$fields[]= $this->get_per_process_field($task);
				
				$fields[]= $this->get_process_calling_field($task);
				
				$fields[]= $this->get_process_status_field($task);
				
				// progress
				
				if( !empty($task[$this->_base.'action']) && $task[$this->_base.'action'] != 'none' ){
					
					$total = $this->count_task_items($post->post_type,$task);
					
					$sc_steps = ceil($total/$this->sc_items);
					
					$fields[]= $this->get_progress_scheduled_field('post_type',$task,$sc_steps);
					
					$fields[]= $this->get_progress_processed_field($task);
				}
				else{
					
					$fields[]= $this->get_progress_notice_field('Select a task and update');
				}
			}
			else{

				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'post_type',
					'label'       	=> 'Type',
					'type'        	=> 'select',
					'options'	  	=> $this->get_post_type_options(),
				);
				
				$fields[]= $this->get_action_notice_field('Select a post type and save');
				
				$fields[]= $this->get_process_notice_field('Select a post type and save');
				
				$fields[]= $this->get_progress_notice_field('Select a post type and save');
			}
				
			return $fields;
		});	

		add_action('rewbe_post_type_actions',function($actions,$post_type){
			
			if( $post_type = get_post_type_object($post_type) ){
				
				global $wpdb;

				$taxonomies = $this->get_post_type_taxonomies($post_type);

				// post type
				
				$actions[] = array(
					
					'label' 	=> 'Edit Post Type',
					'id' 		=> 'edit_post_type',
					'fields' 	=> array(
						array(
							
							'name' 		=> 'name',
							'type'		=> 'select',
							'options'	=> $this->get_post_type_options(),
						),
					),
				);

				// duplicate
				
				$options = 	array(
					
					'post_title' 	=> 'Title',
					'post_content' 	=> 'Content',
					'post_date' 	=> 'Date',
					'post_password'	=> 'Password',
				);
				
				if( post_type_supports($post_type->name, 'excerpt')){
					
					$options['post_excerpt'] = 'Excerpt';
				}
				
				if( post_type_supports($post_type->name, 'author')){
					
					$options['post_author'] = 'Author';
				}
				
				if( $post_type->hierarchical ){
				
					$options['post_parent'] = 'Parent ID';
				}
				
				if( post_type_supports($post_type->name, 'page-attributes')){
				
					$options['menu_order'] = 'Order';
				}
				
				if( post_type_supports($post_type->name, 'comments')){
					
					$options['comment_status'] 	= 'Comment status';
				}
				
				if( post_type_supports($post_type->name, 'trackbacks')){
				
					$options['ping_status'] = 'Ping status';
				}
		
				if( !empty($taxonomies) ){
					
					foreach( $taxonomies as $taxonomy ){
						
						$taxonomy = get_taxonomy($taxonomy);
						
						if( current_user_can($taxonomy->cap->edit_terms) ){
							
							$options['tax_'.$taxonomy->name] = $taxonomy->labels->name;
						}
					}
				}
				
				$options['meta'] = 'Meta';
				
				$actions[] = array(
					
					'label' 	=> 'Duplicate ' . $post_type->labels->name,
					'id' 		=> 'duplicate_post',
					'fields' 	=> apply_filters('rewbe_duplicate_post_fields',array(
						array(
							
							'name' 			=> 'prefix',
							'type'			=> 'select',
							'label'			=> 'Database Prefix',
							'options'		=> $this->get_duplicate_prefix_options(),
							'default'		=> $wpdb->prefix,
						),
						array(
							
							'name' 			=> 'existing',
							'type'			=> 'select',
							'label'			=> 'Existing copy',
							'default' 		=> 'skip',
							'options'		=> array(
							
								'skip'		=> 'Skip',
								'overwrite'	=> 'Overwrite',
								'duplicate'	=> 'Duplicate',
							),
							
						),
						array(
							
							'name' 			=> 'status',
							'type'			=> 'select',
							'label'			=> $post_type->labels->singular_name . ' status',
							'default' 		=> 'original',
							'options'		=> array_merge(array(
							
								'original'	=> 'Same as original',
								
							),$this->get_post_type_statuses($post_type->name,array('trash'))),
							
						),
						array(
							
							'name' 		=> 'include',
							'type'		=> 'checkbox_multi',
							'label'		=> 'Include',
							'options'	=> $options,
							'default'	=> array_keys($options),
							'style'		=> 'display:block;',
						),
						array(
							
							'name' 			=> 'ex_meta',
							'type'			=> 'array',
							'keys'			=> false,
							'label'			=> 'Exclude Meta',
							'placeholder'	=> 'meta_name',
						),							
					),$post_type),
				);
				
				// delete
				
				$actions[] = array(
					
					'label' 	=> 'Delete ' . $post_type->labels->name,
					'id' 		=> 'delete_post',
					'fields' 	=> array(
						array(
							
							'name' 		=> 'action',
							'type'		=> 'radio',
							'options'	=> array(
							
								'trash' 	=> 'Move to Trash',
								'delete' 	=> 'Delete Permanently',
							),
							'default'	=> 'trash',
						),
						array(
							
							'name' 			=> 'confirm',
							'type'			=> 'text',
							'label'			=> 'Type "delete" to confirm',
							'placeholder'	=> 'delete',
							'description'	=> '',
						),					
					),
				);
				
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
					
					$actions[] = $this->get_parent_action_field();
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

				// meta
				
				$actions[] = array(
					
					'label' 	=> 'Edit Meta Values',
					'id' 		=> 'edit_meta',
					'fields' 	=> array(			
						array(
							
							'name' 			=> 'data',
							'type'			=> 'array',
							'keys'			=> true,
							'placeholder'	=> 'value',
						),					
					),
				);
				
				$actions[] = array(
					
					'label' 	=> 'Remove Meta',
					'id' 		=> 'remove_meta',
					'fields' 	=> array(			
						array(
							
							'name' 			=> 'data',
							'type'			=> 'array',
							'keys'			=> true,
							'placeholder'	=> 'matching value or empty',
						),					
					),
				);
				
				// taxonomies
				
				if( !empty($taxonomies) ){
					
					foreach( $taxonomies as $taxonomy ){
						
						if( $taxonomy = get_taxonomy($taxonomy) ){
					
							$fields = apply_filters('rewbe_post_taxonomy_action_fields', array(
							
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
							),$taxonomy,$post_type);
							
							$actions[] = array(
								
								'label' 	=> 'Edit ' . $taxonomy->label,
								'id' 		=> 'edit_tax_' . $taxonomy->name, // dropdown menu
								'fields' 	=> $fields,
							);
						}
					}
				}
			}
			
			return $actions;
			
		},0,2);
		
		add_filter('rewbe_post_taxonomy_action_fields',function($fields,$taxonomy,$post_type){
			
			if( in_array($post_type->name,array(
			
				'product',
				'product_variation',
			
			))){
			
				if( strpos($taxonomy->name,'pa_') === 0 && !class_exists('Woo_Bulk_Product_Editor') ){
					
					$fields = array(
						array(
							
							'type' 	=> 'upgrade',
							'type' 	=> 'html',
							'data' 	=> '<i style="display:block;padding:10px;background:#ff9b0536;color:#a16100;border-radius:5px;border:1px solid #a16100;"><b>Warning</b>: Editing WooCommerce product attributes requires a custom action. <a href="https://code.recuweb.com/get/bulk-product-editor/">Install Bulk Product Editor</a></i>',
						)
					);
				}
			}
			
			return $fields;
			
		},9999999,3);
		
		add_filter('rewbe_taxonomy-task_custom_fields', function($fields=array()){
			
			global $post;
	
			$screen = get_current_screen();
			 
			if( !empty($screen) && $screen->base === 'post' && !empty($_POST['post_type']) ){
				
				$task = $this->sanitize_task_meta($_POST);
			}
			elseif( !empty($post->ID) ){

				$task = $this->get_task_meta($post->ID);
			}
			
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
				
					'metabox'	=> array('name'=>'bulk-editor-filters'),
					'id'       	=> $this->_base . 'meta',
					'type'		=> 'meta',
				);
				
				// actions 
				
				$actions = $this->get_taxonomy_actions($taxonomy->name,$task);
				
				$options = array('none' => 'None');
			
				foreach( $actions as $action ){
					
					$options[$action['id']] = $action['label'];
					
					if( $action['id'] = $task[$this->_base.'action'] ){
						
						if( !empty($action['fields']) ){
							
							foreach( $action['fields'] as $field ){
								
								if( !empty($field['name']) ){
									
									// register without field
									
									$fields[]=array(
									
										'metabox' 	=> array('name'=>'bulk-editor-task'),
										'id'        => $field['name'],
									);
								}
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
				
				if( $curr_action = $task[$this->_base.'action'] ){

					if( $actions = $this->get_taxonomy_actions($task[$this->_base.'taxonomy'],$task) ){
						
						foreach( $actions as $action ){
						
							if( $curr_action == $action['id'] && !empty($action['fields']) ){
								
								foreach( $action['fields'] as $field ){
									
									$field['metabox'] = array('name'=>'bulk-editor-task');
									
									$fields[] = $field;
								}
							}
						}
					}
				}
				
				// process
				
				$fields[]= $this->get_process_items_field('taxonomy');
				
				$fields[]= $this->get_per_process_field($task);

				$fields[]= $this->get_process_calling_field($task);
				
				$fields[]= $this->get_process_status_field($task);
				
				// progress
				
				if( !empty($task[$this->_base.'action']) && $task[$this->_base.'action'] != 'none' ){
					
					$total = $this->count_task_items($post->post_type,$task);
					
					$sc_steps = ceil($total/$this->sc_items);
					
					$fields[]= $this->get_progress_scheduled_field('taxonomy',$task,$sc_steps);
					
					$fields[]= $this->get_progress_processed_field($task);
				}
				else{
					
					$fields[]= $this->get_progress_notice_field('Select a task and update');
				}
			}
			else{
				
				$fields[]=array(
				
					'metabox' 		=> array('name'=>'bulk-editor-filters'),
					'id'          	=> $this->_base . 'taxonomy',
					'label'       	=> 'Type',
					'type'        	=> 'select',
					'options'	  	=> $this->get_taxonomy_options(),
				);
				
				$fields[]= $this->get_action_notice_field('Select a taxonomy and save');
				
				$fields[]= $this->get_process_notice_field('Select a taxonomy and save');
				
				$fields[]= $this->get_progress_notice_field('Select a taxonomy and save');
			}
				
			return $fields;
		});
		
		add_action('rewbe_taxonomy_actions',function($actions,$taxonomy){
			
			$taxonomy = get_taxonomy($taxonomy);
			
			// parent
			
			if( $taxonomy->hierarchical ){
				
				$actions[] = $this->get_parent_action_field();
				
			}
			
			// meta
			
			$actions[] = array(
				
				'label' 	=> 'Edit Meta Values',
				'id' 		=> 'edit_meta',
				'fields' 	=> array(
					array(
						
						'name' 			=> 'data',
						'type'			=> 'array',
						'keys'			=> true,
						'placeholder'	=> 'value',
					),					
				),
			);
			
			$actions[] = array(
				
				'label' 	=> 'Remove Meta',
				'id' 		=> 'remove_meta',
				'fields' 	=> array(			
					array(
						
						'name' 			=> 'data',
						'type'			=> 'array',
						'keys'			=> true,
						'placeholder'	=> 'matching value or empty',
					),					
				),
			);

			$actions[] = array(
				
				'label' 	=> 'Delete ' . $taxonomy->labels->name,
				'id' 		=> 'delete_term',
				'fields' 	=> array(
					array(
						
						'name' 			=> 'confirm',
						'type'			=> 'text',
						'label'			=> 'Type "delete" to confirm',
						'placeholder'	=> 'delete',
						'description'	=> '',
					),					
				),
			);
			
			return $actions;
			
		},0,2);
	
		add_filter('rewbe_user-task_custom_fields', function($fields=array()){
			
			global $post;
	
			$screen = get_current_screen();
			 
			if( !empty($screen) && $screen->base === 'post' && !empty($_POST['post_type']) ){
				
				$task = $this->sanitize_task_meta($_POST);
			}
			elseif( !empty($post->ID) ){

				$task = $this->get_task_meta($post->ID);
			}
			
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

			$fields[]=array(
			
				'metabox'		=> array('name'=>'bulk-editor-filters'),
				'id'			=> $this->_base . 'search',
				'label'     	=> 'Search',
				'type'      	=> 'text',
				'placeholder'	=> 'Search terms',
				'style'			=> 'width:60%;',
			);
			
			$fields[]=array(
			
				'metabox' 		=> array('name'=>'bulk-editor-filters'),
				'id'          	=> $this->_base . 'search_col',
				'type'        	=> 'checkbox_multi',
				'options'		=> array(
					
					'user_login' 	=> 'Login',
					'user_nicename' => 'Nicename',
					'user_email' 	=> 'Email',
					'ID' 			=> 'ID',
					'user_url' 		=> 'URL',
					
				),
				'default' 		=> array('user_login','user_nicename'),
				'style'			=> 'margin-right:5px;',
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
			);
			
			// actions 
			
			$actions = $this->get_user_actions($task);
			
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
			
			if( $curr_action = $task[$this->_base.'action'] ){

				if( $actions = $this->get_user_actions($task) ){
					
					foreach( $actions as $action ){
					
						if( $curr_action == $action['id'] && !empty($action['fields']) ){
							
							foreach( $action['fields'] as $field ){
								
								$field['metabox'] = array('name'=>'bulk-editor-task');
								
								$fields[] = $field;
							}
						}
					}
				}
			}
			
			// process
			
			$fields[]= $this->get_process_items_field('user');
			
			$fields[]= $this->get_per_process_field($task);

			$fields[]= $this->get_process_calling_field($task);
			
			$fields[]= $this->get_process_status_field($task);
			
			// progress
			
			if( !empty($task[$this->_base.'action']) && $task[$this->_base.'action'] != 'none' ){
				
				$total = $this->count_task_items($post->post_type,$task);
				
				$sc_steps = ceil($total/$this->sc_items);
				
				$fields[]= $this->get_progress_scheduled_field('user',$task,$sc_steps);
				
				$fields[]= $this->get_progress_processed_field($task);
			}
			else{
				
				$fields[]= $this->get_progress_notice_field('Select a task and update');
			}
				
			return $fields;
		});
		
		add_action('rewbe_user_actions',function($actions){
			
			// role
			
			$actions[] = array(
				
				'label' 	=> 'Edit Role',
				'id' 		=> 'edit_role',
				'capability' 	=> 'promote_users',
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
						
						'name' 		=> 'roles',
						'type'		=> 'checkbox_multi',
						'options'	=> $this->get_roles_options(),
						'style'		=> 'display:block;',
					),					
				),
			);
			
			// delete
			
			$actions[] = array(
				
				'label' 	=> 'Delete Users',
				'id' 		=> 'delete_user',
				'capability' 	=> 'promote_users',
				'fields' 	=> array(
					array(
					
						'name' 	=> 'reassign',
						'label' => 'Reassign contents to',
						'type'	=> 'authors',
					),
					array(
						
						'name' 			=> 'confirm',
						'type'			=> 'text',
						'label'			=> 'Type "delete" to confirm',
						'placeholder'	=> 'delete',
						'description'	=> '',
					),					
				),
			);
		
			// meta
			
			$actions[] = array(
				
				'label' 	=> 'Edit Meta Values',
				'id' 		=> 'edit_meta',
				'fields' 	=> array(
					array(
						
						'name' 			=> 'data',
						'type'			=> 'array',
						'keys'			=> true,
						'placeholder'	=> 'value',
					),					
				),
			);
			
			$actions[] = array(
				
				'label' 	=> 'Remove Meta',
				'id' 		=> 'remove_meta',
				'fields' 	=> array(			
					array(
						
						'name' 			=> 'data',
						'type'			=> 'array',
						'keys'			=> true,
						'placeholder'	=> 'matching value or empty',
					),					
				),
			);
			
			return $actions;
			
		},0,2);
		
		add_action('pre_post_update', function($post_id,$data){
			
			if( !defined('DOING_AUTOSAVE') || DOING_AUTOSAVE === false ){
				
				if( in_array($data['post_type'],$this->get_task_types()) ){
					
					// check changes
					
					$new_task = $this->sanitize_task_meta($_POST);
					
					if( !empty($new_task[$this->_base.'process_status']) && $new_task[$this->_base.'process_status']  == 'reschedule' ){
						
						$this->rescheduled = true;
					}
					else{

						$old_task = $this->get_task_meta($post_id);
				
						$changes = $this->compare_arrays($old_task,$new_task,array(
							
							'rewbe_id',
							'rewbe_action',
							'rewbe_items',
							'rewbe_progress',
							'rewbe_process',
							'rewbe_processed',
							'rewbe_per_process',
							'rewbe_process_status',
							'rewbe_scheduled',
							'rewbe_call',
						));
						
						if( !empty($changes) ){
							
							$this->rescheduled = true;
						}
					}
				}
			}
			
			return $post_id;
			
		},99999999,2);
		
		add_action('save_post', function($post_id,$post){
			
			if( !empty($this->rescheduled) ){
				
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
			
			return $post_id;
			
		},99999999,2);
	
	} // End __construct ()
	
	public function get_per_process_field($task){
		
		return array(
			
			'metabox' 		=> array('name'=>'bulk-editor-process'),
			'id'        	=> $this->_base . 'per_process',
			'label'       	=> 'Items per process',
			'type'        	=> 'number',
			'default'       => 10,
		);
	}
	
	public function get_process_calling_field($task){
		
		$options = array(
		
			'ajax' 		=> 'AJAX',
			//'cron' 	=> 'CRON',
		);
		
		return array(
			
			'metabox' 		=> array('name'=>'bulk-editor-process'),
			'id'        	=> $this->_base . 'call',
			'label'       	=> 'Calling method',
			'type'        	=> 'radio',
			'options'       => $options,
			'default'       => 'ajax',
		);
	}
	
	public function get_process_items_field($type){

		return array(
		
			'metabox' 		=> array('name'=>'bulk-editor-process'),
			'id'        	=> $this->_base . 'items',
			'label'			=> 'Matching items',
			'type'        	=> 'html',
			'data'     		=> '<div id="rewbe_task_items" style="height:30px;" data-type="'.$type.'" class="loading"></div>',
		);
	}
	
	public function get_process_status_field($task){
		
		$status = !empty($task[$this->_base.'process_status']) ? $task[$this->_base.'process_status'] : 'pause';
		
		$options = array(
			
			'pause' 		=> 'Pause',
			'running' 		=> $status == 'pause' ? 'Start' :'Running',
			'reschedule' 	=> 'Reschedule',
		);
		
		return array(
			
			'metabox'	=> array('name'=>'bulk-editor-process'),
			'id'       	=> $this->_base . 'process_status',
			'label'   	=> 'Task Status',
			'type'		=> 'radio',
			'options'	=> $options,
			'data'		=> $status == 'reschedule' ? 'running' : $status,
		);
	}
	
	public function get_action_notice_field($notice){
		
		return array(
		
			'metabox' 		=> array('name'=>'bulk-editor-task'),
			'id'          	=> 'action-notice',
			'type'        	=> 'html',
			'data'        	=> '<i>'.$notice.'</i>',
		);
	}
				
	public function get_process_notice_field($notice){
		
		return array(
		
			'metabox' 		=> array('name'=>'bulk-editor-process'),
			'id'          	=> 'process-notice',
			'type'        	=> 'html',
			'data'        	=> '<i>'.$notice.'</i>',
		);
	}
	
	public function get_progress_notice_field($notice){
		
		return array(
		
			'metabox' 		=> array('name'=>'bulk-editor-progress'),
			'id'          	=> 'progress-notice',
			'type'        	=> 'html',
			'data'        	=> '<i>'.$notice.'</i>',
		);
	}
	
	public function get_progress_scheduled_field($type,$task,$steps){
	
		$prog = !empty($task[$this->_base.'scheduled']) ? 100 : 0;
		
		$status = !empty($task[$this->_base.'process_status']) ? $task[$this->_base.'process_status'] : 'pause';
		
		return array(
			
			'metabox' 		=> array('name'=>'bulk-editor-progress'),
			'id'		=> $this->_base . 'scheduler',
			'label'		=> 'Scheduled',
			'type'      => 'html',
			'data'      => $prog == 100 || $status == 'pause' ? $prog . '%' : '<span id="rewbe_task_scheduled" data-type="'.$type.'" data-steps="'.$steps.'" style="width:65px;display:block;">' . $prog . '%</span>',
		);
	}
		
	public function get_progress_processed_field($task){
	
		if( !empty($task[$this->_base.'process_status']) && $task[$this->_base.'process_status']  == 'pause' ){
			
			if( is_numeric($task['rewbe_progress']) ){
				
				$data = $task['rewbe_progress'] . '%';
			}
			else{
				
				$data = '0%';
			}
		}
		elseif( is_numeric($task['rewbe_progress']) && $task['rewbe_progress'] >= 100 ){
			
			$data = '100%';
		}
		else{
			
			$data = '<span id="rewbe_task_processed" style="width:65px;display:block;">' . ( is_numeric($task['rewbe_progress']) ? $task['rewbe_progress'] : 0 ) . '%</span>';
		}
		
		return array(
			
			'metabox' 	=> array('name'=>'bulk-editor-progress'),
			'id'		=> $this->_base . 'processor',
			'label'		=> 'Processed',
			'type'      => 'html',
			'data'      => $data,
		);
	}
	
	public function get_parent_action_field(){

		return array(
		
			'label' 	=> 'Edit Parent',
			'id' 		=> 'edit_parent',
			'fields' 	=> array(
				array(			
					
					'name'			=> 'id',
					'label'			=> 'Parent ID',
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
				
	public function compare_arrays($oldArray,$newArray,$ignoreKeys=array()) {

		$changes = array();

		foreach( $oldArray as $key => $value ){
			
			if( in_array($key,$ignoreKeys) ){
				
				continue;
			}

			if( !array_key_exists($key,$newArray) ){
				
				if( !empty($value) ){
				
					$changes['removed'][$key] = $value;
				}
			}
			elseif( $newArray[$key] !== $value ){
				
				$changes['changed'][$key] = array(
				
					'old' 	=> $value,
					'new'	=> $newArray[$key],
				);
			}
		}

		foreach( $newArray as $key => $value ){
			
			if( in_array($key,$ignoreKeys) ){
				
				continue;
			}

			if( !array_key_exists($key,$oldArray) ){
				
				$changes['added'][$key] = $value;
			}
		}

		return $changes;
	}

	public function get_duplicate_prefix_options(){
		
		global $wpdb;
		
		$prefixes = array(
		
			$wpdb->prefix => $wpdb->prefix,
		);

		$tables = $wpdb->get_col("SHOW TABLES LIKE '%_options'");

		foreach ($tables as $table) {
			
			$safe_table = esc_sql($table);

			$query = $wpdb->prepare(
				"SELECT option_name, option_value FROM `$safe_table` WHERE option_name = %s AND option_value = %s",
				'rewbe_multi_duplication',
				'on'
			);

			if( $row = $wpdb->get_row($query) ){
				
				$prefix = str_replace('_options','',$table).'_';
				
				if( !isset($prefixes[$prefix]) ){
					
					$prefixes[$prefix] = $prefix;
				}
			}
		}
		
		return $prefixes;
	}
	
	public function get_task_types(){
		
		return array(
			
			'post-type-task',
			'taxonomy-task',
			'user-task',
			'data-task',
		);
	}
	
	public function get_post_type_options(){
		
		$post_types = get_post_types('','objects');
		
		$options = array();
		
		foreach( $post_types as $post_type ){
			
			if( !in_array($post_type->name,array_merge($this->get_task_types(),array(

				'revision',
				'oembed_cache',
				'custom_css',
				'customize_changeset',
				'nav_menu_item',
				'user_request',
				'wp_block',
				'wp_template',
				'wp_template_part',
				'wp_global_styles',
				'wp_navigation',
				'wp_font_family',
				'wp_font_face',
				'patterns_ai_data',
				//'product',
				//'product_variation',
				//'shop_order',
				'shop_order_refund',
				//'shop_coupon',
				'shop_order_placehold',
			
			)))){
				
				$options[$post_type->name] = $post_type->labels->singular_name;
			}
		}
		
		return apply_filters('rewbe_post_type_options',$options);
	}
	
	public function get_taxonomy_options(){
	
		$taxonomies = get_taxonomies(array(),'objects');
		
		$options = array();

		foreach( $taxonomies as $taxonomy ){
			
			if( $taxonomy->publicly_queryable ){
				
				$options[$taxonomy->name] = $taxonomy->label;
			}
		}
		
		return $options;
	}
	
	public function get_roles_options(){
		
		$options = array();
		
		if( current_user_can('promote_users') ){
			
			$roles = get_editable_roles();
			
			foreach( $roles as $slug => $role ){
				
				$can_manage_role = true;
				
				if( !current_user_can('administrator') ){
				
					foreach( $role['capabilities'] as $capability => $enabled ){
						
						if( !current_user_can($capability) ){
							
							$can_manage_role = false;
							
							break;
						}
					}
				}
				
				if( $can_manage_role ){
				
					$options[$slug] = $role['name'];
				}
			}
		}
		
		return $options;
	}
	
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
	
	public function get_post_type_statuses($post_type,$exclude=array()){
		
		$post_statuses = apply_filters('rewbe_post_type_statuses',array(
					
			'publish' 	=> 'Published',
			'pending' 	=> 'Pending review',
			'private' 	=> 'Private',
			'draft' 	=> 'Draft',
			'trash' 	=> 'Trash',
		
		),$post_type);
		
		if( !empty($exclude) ){
			
			foreach( $exclude as $status ){
				
				if( isset($post_statuses[$status]) ){
					
					unset($post_statuses[$status]);
				}
			}
		}
		
		return $post_statuses;
	}
	
	public function sanitize_task_meta($meta,$level=1){
		
		$task=array();
		
		foreach($meta as $key => $value){
			
			if( strpos($key,'rewbe_') === 0 || $level > 1 ){
				
				if( is_array($value) ){
					
					$task[$key] = $this->sanitize_task_meta($value,$level+1);
				}
				elseif( is_string($value) ){
					
					$task[$key] = sanitize_meta($key,$value,'post');
				}
			}
		}

		return $task;
	}
	
	public function get_task_meta($post_id){

		$meta = array(
			
			'rewbe_id' 			=> $post_id,
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
	
	public function get_post_type_actions($slug,$task){
		
		return $this->parse_actions(apply_filters('rewbe_post_type_actions',array(),$slug,$task));
	}
	
	public function get_taxonomy_actions($slug,$task){
		
		return $this->parse_actions(apply_filters('rewbe_taxonomy_actions',array(),$slug,$task));
	}
	
	public function get_user_actions($task){
		
		return $this->parse_actions(apply_filters('rewbe_user_actions',array(),$task));
	}
	
	public function get_data_actions($slug,$task){
		
		return $this->parse_actions(apply_filters('rewbe_data_actions',array(),$slug,$task));
	}

	public function sanitize_content($content,$allowed_html=null,$allowed_protocols=null){
		
		if( is_null($allowed_html) ){
			
			$allowed_html = apply_filters('rewbe_sanitize_content_html',array(
			
				'a' => array(
					'href' => true,
					'title' => true,
					'rel' => true,
				),
				'abbr' => array(),
				'b' => array(),
				'blockquote' => array(
					'cite' => true,
				),
				'cite' => array(),
				'code' => array(),
				'del' => array(
					'datetime' => true,
				),
				'dd' => array(),
				'div' => array(
					'class' => true,
					'id' => true,
					'style' => true,
				),
				'span' => array(
					'style' => true,
				),
				'dl' => array(),
				'dt' => array(),
				'em' => array(),
				'i' => array(),
				'img' => array(
					'src' => true,
					'alt' => true,
					'width' => true,
					'height' => true,
					'title' => true,
					'style' => true,
				),
				'li' => array(),
				'ol' => array(),
				'p' => array(),
				'q' => array(
					'cite' => true,
				),
				'strong' => array(),
				'ul' => array(),
				'br' => array(),
				'h1' => array(),
				'h2' => array(),
				'h3' => array(),
				'h4' => array(),
				'h5' => array(),
				'h6' => array(),
				'table' => array(),
				'thead' => array(),
				'tbody' => array(),
				'tfoot' => array(),
				'tr' => array(),
				'td' => array(),
				'th' => array(),
				'caption' => array(),
				'iframe' => array(
					'src' 	=> true,
					'width' => true,
					'height' => true,
					'frameborder' => true,
					'allowfullscreen' => true,
					'sandbox' => true,
					'allow' => true,
					'referrerpolicy' => true,
					'loading' => true,
					'style' => true,
				),
			));
		}
		
		if( is_null($allowed_protocols) ){
			
			$allowed_protocols = apply_filters('rewbe_sanitize_content_protocols',wp_allowed_protocols());
		}
		
		return wp_kses($content,$allowed_html,$allowed_protocols);
	}
		
	public function parse_actions($actions){
		
		// validate & sanitize actions
		
		$validated = array();
		
		foreach( $actions as $i => $action ){
			
			if( empty($action['id']) ){
				
				continue;
			}
			elseif( empty($action['capability']) || current_user_can($action['capability']) ){
				
				$action_id = sanitize_title($action['id']);
				
				if( !empty($action['label']) ){
					
					$action['label'] = ucwords($action['label']);
				}
				
				if( is_array($action['fields']) && !empty($action['fields']) ){
					
					foreach( $action['fields'] as $j => $field ){
						
						if( !empty($field['type']) ){
							
							if( !empty($field['name']) ){
							
								$field_id = 'rewbe_act_' . $action_id . '__' . sanitize_title($field['name']);
							
								$action['fields'][$j]['id'] = $field_id;
							
								$action['fields'][$j]['name'] = $field_id;
							}
							elseif( $field['type'] != 'html' ){
								
								// unregistered field type
							}
						}
					}
				}
				else{
					
					$action['fields'] = array(); 
				}
				
				$validated[$action_id] = $action;
			}
		}
		
		return $validated;
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
		
		if( in_array($screen->id,$this->get_task_types())){
			
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
		
		if( in_array($screen->id,$this->get_task_types())){
		
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
		
		if( current_user_can('edit_posts')&& !empty($_GET['s']) && !empty($_GET['id']) ){
			
			if( $s =  apply_filters( 'get_search_query', $_GET['s'] ) ){
				
				$input_id = sanitize_title($_GET['id']);
				
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
							
							'id' 	=> $input_id,
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
					else{
						
						$results[] = array(

							'id'	=> -1,
							'name'	=> 'Nothing found',
						);
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

			$this->admin->display_field(array(
			
				'id'        	=> $this->_base . 'matching',
				'type'        	=> 'number',
				'data'      	=> $total_items,
				'default'		=> 0,
				'disabled'		=> true,
				
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
								
								'post_status' 		=> 'all',
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
								
								$args = $this->parse_action_parameters($post->post_type,$task);
								
								// register default actions
								
								if( $action == 'edit_post_type' ){
									
									add_action('rewbe_do_post_edit_post_type',array($this,'edit_post_type'),10,2);
								}	
								elseif( $action == 'duplicate_post' ){
									
									add_action('rewbe_do_post_duplicate_post',array($this,'duplicate_post'),10,2);
								}
								elseif( $action == 'delete_post' ){
									
									add_action('rewbe_do_post_delete_post',array($this,'delete_post'),10,2);
								}
								elseif( $action == 'edit_status' ){
									
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
								'hide_empty'		=> false,
								'meta_query' 		=> array(
							
									array(
										
										'key'     	=> $this->_base.$task_id,
										'value'   	=> 1,
										'type' 		=> 'NUMERIC',
										'compare' 	=> '!=',
									)
								),
							);
							
							$total_items = intval(wp_count_terms($args));
							
							if( $total_items > $per_process ){
							
								$remaining = $total_items - $per_process;
							}
							else{
								
								$remaining = $total_items;
							}
							
							$query = new WP_Term_Query($args);
							
							if( !empty($query->terms) ){
								
								$args = $this->parse_action_parameters($post->post_type,$task);
								
								// register default actions
								
								if( $action == 'edit_parent' ){
									
									add_action('rewbe_do_term_edit_parent',array($this,'edit_term_parent'),10,2);
								}						
								elseif( $action == 'edit_meta' ){
									
									add_action('rewbe_do_term_edit_meta',array($this,'edit_term_meta'),10,2);
								}
								elseif( $action == 'delete_term' ){
									
									add_action('rewbe_do_term_delete_term',array($this,'delete_term'),10,2);
								}
								
								foreach( $query->terms as $term ){
									
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
								
								$args = $this->parse_action_parameters($post->post_type,$task);
								
								// register default actions
								
								if( $action == 'edit_role' ){
								
									add_action('rewbe_do_user_edit_role',array($this,'edit_user_role'),10,2);
								}
								elseif( $action == 'delete_user' ){
								
									add_action('rewbe_do_user_delete_user',array($this,'delete_user'),10,2);
								}
								elseif( $action == 'edit_meta' ){
									
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
			
			$action = $task[$this->_base.'action'];
			
			$prog = 100;
			
			if( $total_items = $this->count_task_items($post->post_type,$task) ){
				
				/**	schedule task 
				*	0: scheduled
				*	t: processing
				*	1: done
				*/
				
				if( $post->post_type == 'post-type-task' ){
					
					$actions = $this->get_post_type_actions($task[$this->_base.'post_type'],$task);
					
					if( !empty($actions[$action]) ){
						
						$args = $this->parse_post_task_parameters($task,$this->sc_items,$step);
						
						$query = new WP_Query($args);

						if( $ids = $query->posts ){
							
							foreach( $ids as $id ){
								
								update_post_meta($id,$this->_base.$task_id,0);
							}
						}
					}
				}
				elseif( $post->post_type == 'taxonomy-task' ){
					
					$actions = $this->get_taxonomy_actions($task[$this->_base.'taxonomy'],$task);
					
					if( !empty($actions[$action]) ){
							
						$args = $this->parse_term_task_parameters($task,$this->sc_items,$step);
						
						if( $ids = get_terms($args) ){

							foreach( $ids as $id ){
								
								update_term_meta($id,$this->_base.$task_id,0);
							}
						}
					}
				}
				elseif( $post->post_type == 'user-task' ){
					
					$actions = $this->get_user_actions($task);
					
					if( !empty($actions[$action]) ){
						
						$args = $this->parse_user_task_parameters($task,$this->sc_items,$step);
					
						$query = new WP_User_Query($args);
						
						if( $ids = $query->get_results() ){
							
							foreach( $ids as $id ){
								
								update_user_meta($id,$this->_base.$task_id,0);
							}
						}
					}
				}
				elseif( $post->post_type == 'data-task' ){
					
					$actions = $this->get_data_actions($task[$this->_base.'data_type'],$task);
					
					if( !empty($actions[$action]) ){
						
						// TODO schedule data action
					}
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
	
	public function edit_post_type($post,$args){
		
		if( !empty($args['name']) ){
			
			$post_type = sanitize_title($args['name']);
			
			$types = $this->get_post_type_options();
			
			if( isset($types[$post_type]) ){
				
				$this->update_post(array(
					
					'ID' 			=> $post->ID,
					'post_type' 	=> $post_type,
				));
			}
		}
	}
	
	public function set_wpdb_prefix($prefix){
		
		global $wpdb;
		
		$wpdb->set_prefix($prefix,true);
		
		wp_cache_flush();
						
		remove_all_filters('option_siteurl');
		remove_all_filters('option_home');
	}

	public function duplicate_post($post,$args,$level=1){
	
		$postarr = array();
		
		if( in_array('post_title',$args['include']) ){
			
			$postarr['post_title'] = $post->post_title;
		}
		
		if( in_array('post_content',$args['include']) ){
			
			$postarr['post_content'] = $post->post_content;
		}
		
		if( in_array('post_excerpt',$args['include']) ){
			
			$postarr['post_excerpt'] = $post->post_excerpt;
		}
		
		if( in_array('post_date',$args['include']) ){
			
			$postarr['post_date'] = $post->post_date;
			
			$postarr['post_date_gmt'] = $post->post_date_gmt;
		}
		
		if( in_array('menu_order',$args['include']) ){
			
			$postarr['menu_order'] = $post->menu_order;
		}
		
		if( in_array('post_password',$args['include']) ){
			
			$postarr['post_password'] = $post->post_password;
		}
		
		if( in_array('comment_status',$args['include']) ){
			
			$postarr['comment_status'] = $post->comment_status;
		}
		
		if( in_array('ping_status',$args['include']) ){
			
			$postarr['ping_status'] = $post->ping_status;
		}
		
		if( $postarr = apply_filters('rewbe_before_duplicate_post',$postarr,$post) ){
			
			//get post type
			
			$post_type = get_post_type_object($post->post_type);
			
			// get author
			
			$author = !empty($post->post_author) ? get_user_by('id',$post->post_author) : false;
			
			// get parent
			
			$parent = !empty($post->post_parent) ? get_post($post->post_parent) : false;
		
			// get terms
			
			$taxonomies = $this->get_post_type_taxonomies($post_type);

			if( !empty($taxonomies) ){
				
				foreach( $taxonomies as $taxonomy ){
					
					if( in_array('tax_'.$taxonomy,$args['include']) ){
						
						if( $terms = wp_get_object_terms($post->ID,$taxonomy) ){
						
							foreach( $terms as $term ){
								
								$this->terms[$post->ID][$taxonomy][$term->term_id] = $term;
								
								if( $meta = get_term_meta($term->term_id) ){
								
									$this->terms_meta[$term->term_id] = $this->register_duplicated_meta($meta,$term);
								}
							}
						}
					}
				}
			}
			
			// get meta
			
			if( in_array('meta',$args['include']) ){
				
				$metadata = $this->register_duplicated_meta(get_post_meta($post->ID),$post);
			}
			
			// switch db prefix
			
			global $wpdb;
			
			$old_prefix = $wpdb->prefix;
			
			if( !empty($args['prefix']) ){
				
				$new_prefix = sanitize_title(str_replace(' ','',$args['prefix']));
				
				if( !empty($new_prefix) ){
					
					$this->set_wpdb_prefix($new_prefix);
				}
			}
			
			$postarr['post_type'] = $post->post_type;
			
			$postarr['post_status'] = !empty($args['status']) && $args['status'] != 'original' ? sanitize_title($args['status']) : $post->post_status;
			
			if( in_array('post_author',$args['include']) ){
			
				$post_author = 0;
			
				if( $old_prefix != $new_prefix ){
					
					if( !empty($author) ){
						
						// get author
						
						if( $remote_author = get_user_by('email',$author->user_email) ){
							
							$post_author = intval($remote_author->ID);
						}
						else{
							
							// add user
							
							$author_copy_id = wp_insert_user(array(
							
								'user_login'		=> $author->user_login,
								'user_email'		=> $author->user_email,
								'user_pass'			=> $author->user_pass,
								'user_nicename'		=> $author->user_nicename,
								'user_url'			=> $author->user_url,
								'display_name'  	=> $author->display_name,
								'user_status'  		=> $author->user_status,
								'user_registered'  	=> $author->user_registered,
								'user_nicename'  	=> $author->user_nicename,
							));
							
							if( !is_wp_error($author_copy_id) ){

								if( !empty($author->roles) ){
									
									foreach( $author->roles as $role ){
										
										$author_copy = new WP_User($author_copy_id);
										
										$author_copy->add_role($role);
									}
								}
								
								$post_author = intval($author_copy_id);
							}
						}
					}
				}
				else{
					
					$post_author = intval($post->post_author);
				}
				
				$postarr['post_author'] = $post_author;
			}
			
			if( in_array('post_parent',$args['include']) && !empty($parent) ){
				
				if( $old_prefix != $new_prefix ){

					$parent_copies = get_posts(array(
					
						'post_type'			=> apply_filters('rewbe_duplicate_parent_post_types',array($post->post_type),$post),
						'post_status'		=> array_keys($this->get_post_type_statuses($post->post_type,array('trash'))),
						'posts_per_page' 	=> -1,
						'order'				=> 'ASC',
						'orderby'			=> 'ID',
						'meta_query'		=> array(
						
							array(
								
								'key'     	=> 'rewbe_origin',
								'value'		=> serialize(array(
								
									$old_prefix => $post->post_parent,
								)),
								'compare' 	=> '=',
							)
						)
					));
			
					if( !empty($parent_copies) ){
						
						$postarr['post_parent'] = $parent_copies[0]->ID;
					}
					else{
						
						//$this->duplicate_post($parent,$args);
					}
				}
				else{
				
					$postarr['post_parent'] = $post->post_parent;
				}
			}
			
			$existing = !empty($args['existing']) ? sanitize_title($args['existing']) : 'skip';
			
			$post_ids = array();
			
			if( in_array($existing,array(
			
				'skip',
				'overwrite',
			))){
				
				$copies = get_posts(array(
				
					'post_type'			=> $post->post_type,
					'post_status'		=> array_keys($this->get_post_type_statuses($post->post_type,array('trash'))),
					'posts_per_page' 	=> -1,
					'order'				=> 'ASC',
					'orderby'			=> 'ID',
					'meta_query'		=> array(
					
						array(
							
							'key'     	=> 'rewbe_origin',
							'value'		=> serialize(array(
							
								$old_prefix => $post->ID,
							)),
							'compare' 	=> '=',
						)
					)
				));
				
				if( !empty($copies) ){
					
					if( $existing == 'overwrite' ){
						
						foreach( $copies as $copy ){
							
							// update copies
							
							$postarr['ID'] = $copy->ID;
							
							wp_update_post($postarr,false);
							
							$post_ids[$copy->ID] = $post->ID;
						}
					}
					else{
						
						// skip
					}
				}
				elseif( $post_id = wp_insert_post($postarr,false) ){
					
					$post_ids[$post_id] = $post->ID;
				}
			}
			elseif( $post_id = wp_insert_post($postarr,false) ){
				
				$post_ids[$post_id] = $post->ID;
			}
			
			if( !empty($post_ids) ){
				
				foreach( $post_ids as $post_id => $origin_id ){
					
					if( !empty($this->terms[$origin_id]) ){
						
						// update terms
						
						foreach( $this->terms[$origin_id] as $taxonomy => $terms ){
							
							$term_slugs = array();
							
							foreach( $terms as $term ){
								
								if( $old_prefix != $new_prefix ){
									
									$term_query = new WP_Term_Query(apply_filters('rewbe_duplicate_term_query',array(
									
										'taxonomy'   	=> $taxonomy,
										'hide_empty' 	=> false,
										'orderby'    	=> 'id',
										'order'      	=> 'ASC',
										'meta_query'	=> array(
										
											array(
												
												'key'     	=> 'rewbe_origin',
												'value'		=> serialize(array(
												
													$old_prefix => $term->term_id,
												)),
												'compare' 	=> '=',
											)
										)
									),$term));
									
									if( $term_copies = $term_query->get_terms() ){
										
										foreach( $term_copies as $term_copy ){
											
											if( !in_array($term_copy->slug,$term_slugs) ){
											
												$term_slugs[] = $term_copy->slug;
											}
										}
									}
									else{

										// copy term
										
										$term_parent = 0;
										
										if( !empty($term->parent) ){
											
											$parent_term_query = new WP_Term_Query(apply_filters('rewbe_duplicate_term_query',array(
											
												'taxonomy'   	=> $taxonomy,
												'hide_empty'	=> false,
												'orderby'    	=> 'id',
												'order'      	=> 'ASC',
												'meta_query'	=> array(
												
													array(
														
														'key'     	=> 'rewbe_origin',
														'value'		=> serialize(array(
														
															$old_prefix => $term->parent,
														)),
														'compare' 	=> '=',
													)
												)
											),$term));
											
											if( $parent_term_copies = $parent_term_query->get_terms() ){
												
												$term_parent = $parent_term_copies[0]->term_id;
											}
										}
										
										$inserted_term = wp_insert_term($term->name,$term->taxonomy,array(
										
											'description'	=> $term->description,
											'parent' 		=> $term_parent,
										));
										
										if( !is_wp_error($inserted_term) && !empty($inserted_term['term_id']) ){
										
											$term_copy = get_term($inserted_term['term_id']);
											
											if( !empty($this->terms_meta[$term->term_id]) ){
												
												// copy term meta
												
												foreach( $this->terms_meta[$term->term_id] as $name => $values ){
													
													$count = count($values);
													
													if( $count > 1 ){
													
														delete_term_meta($term_copy->term_id,$name);
													}
													
													foreach( $values as $e => $value ){
													
														$value = $this->parse_duplicated_meta($value,$name,$term_copy,$args,array($old_prefix=>$term->term_id));

														if( !is_null($value) ){
															
															if( $count > 1 ){
															
																add_term_meta($term_copy->term_id,$name,$value);
															}
															else{
																
																update_term_meta($term_copy->term_id,$name,$value);
															}
														}
													}
												}
											}
											
											update_term_meta($term_copy->term_id,'rewbe_origin',array(
											
												$old_prefix => $term->term_id,
											));
											
											$term_slugs[] = $term_copy->slug;
										}
										elseif( defined('REW_DEV_ENV') && REW_DEV_ENV === true && !in_array($term->taxonomy,array(
										
											'product_color',
										
										))){
											
											dump(array(
												'debugging duplicate term',
												$inserted_term,
												get_term_by('name',$term->name,$term->taxonomy),
											));
										}
									}
								}
								else{
									
									$term_slugs[] = $term->slug;
								}
							}
							
							if( !empty($term_slugs) ){
								
								wp_set_object_terms($post_id,$term_slugs,$taxonomy,false);
							}
						}
					}
					
					// update meta
					
					if( !empty($metadata) ){
						
						$ex_meta = isset($args['ex_meta']['value']) ? $args['ex_meta']['value'] : array();
						
						$post_copy = get_post($post_id);
						
						foreach( $metadata as $name => $values ){
							
							if( !empty($values) && !in_array($name,$ex_meta) ){
								
								$count = count($values);
								
								if( $count > 1 ){
								
									delete_post_meta($post_id,$name);
								}
								
								foreach( $values as $value ){
									
									$value = $this->parse_duplicated_meta($value,$name,$post_copy,$args,array($old_prefix=>$post->ID));
									
									if( !is_null($value) ){
										
										if( $count > 1 ){
										
											add_post_meta($post_id,$name,$value);
										}
										else{
											
											update_post_meta($post_id,$name,$value);
										}
									}
								}
							}
						}
					}
					
					update_post_meta($post_id,'rewbe_origin',array($old_prefix=>$post->ID));
				}
			}
			
			// switch back db prefix
			
			if( !empty($old_prefix) ){
			
				$this->set_wpdb_prefix($old_prefix);
			}

			do_action('rewbe_duplicated_posts',$post_ids,$args,$level);
			
			if( $level == 1 && defined('REW_DEV_ENV') && REW_DEV_ENV === true ){
				
				//dump('done');
			}
			
			return $post_ids;
		}
	}
	
	public function get_image_meta_names(){

		return apply_filters('rewbe_image_meta_names',array(
		
			'thumbnail_id' 	=> 'numeric',
			'_thumbnail_id' => 'numeric',
		));
	}		

	public function register_duplicated_meta($metadata,$object){
	
		// get images
		
		if( $image_names = $this->get_image_meta_names() ){
			
			foreach( $image_names as $name => $type ){
				
				$image_ids = array();
			
				if( !empty($metadata[$name][0]) ){
					
					if( $type == 'numeric' && is_numeric($metadata[$name][0]) ){
					
						$image_ids[] = intval($metadata[$name][0]);
					}
					elseif( $type == 'csv' && is_string($metadata[$name][0]) ){
						
						$image_ids = array_map('intval',explode(',',$metadata[$name][0]));
					}
					
					if( !empty($image_ids) ){
						
						foreach( $image_ids as $image_id ){
							
							if( !isset($this->images[$image_id]) ){
							
								if( $image_url = wp_get_attachment_url($image_id) ){
								
									$attr = get_post($image_id);
									
									$author = get_user_by('ID',intval($attr->post_author));
									
									$this->images[$image_id]['url'] = $image_url;
								
									$this->images[$image_id]['attr'] = (array) $attr;
									
									$this->images[$image_id]['author'] = !empty($author->user_email) ? $author->user_email : null;
								
									$this->images[$image_id]['meta'] = wp_get_attachment_metadata($image_id);
								}
							}
						}
					}
				}
			}
		}
		
		return apply_filters('rewbe_before_duplicate_meta',$metadata,$object);
	}
	
	public function parse_duplicated_meta($data,$name,$object,$args,$origin){
		
		$value = null;
		
		if( strpos($name,'rewbe_') === false ){
			
			$image_names = $this->get_image_meta_names();
			
			if( isset($image_names[$name]) ){
				
				$type = $image_names[$name];
				
				if( $type == 'numeric' ){
					
					$value = $this->get_duplicated_image_id(intval($data),$origin);
				}
				elseif( $type == 'csv' ){
					
					$image_ids = array_map('intval',explode(',',$data));
					
					foreach( $image_ids as $e => $image_id ){
						
						$image_ids[$e] = $this->get_duplicated_image_id($image_id,$origin);
					}
					
					$value = implode(',',$image_ids);
				}
			} 
			else{
				
				$value = apply_filters('rewbe_duplicate_meta_value',maybe_unserialize($data),$name,$object,$args,$origin);
			}
		}
			
		return $value;
	}

	public function get_duplicated_image_id($image_id,$origin){
		
		$attachment_id = 0;
		
		$image = isset($this->images[$image_id]) ? $this->images[$image_id] : false;
		
		if( !empty($image) && !empty($image['url']) && !empty($image['attr']) ){
			
			if( !$attachment_id = $this->get_post_by_guid($image['url'], 'attachment' ) ){
				
				if( !empty($image['author']) ){
					
					$user = get_user_by('email',$image['author']);
				}
				
				$attachment_id = wp_insert_attachment( array(
					
					'guid' 				=> $image['url'],
					'post_author' 		=> !empty($user->ID) ? $user->ID : 0,
					'post_mime_type' 	=> $image['attr']['post_mime_type'],
					'post_title' 		=> $image['attr']['post_title'],
				
				),null,null);
				
				update_post_meta($attachment_id,'rewbe_origin',$origin);
			}
			
			if( !empty($image['meta']) ){
				
				$image['meta']['sizes']['full'] = array(
							
					'width' 	=> $image['meta']['width'],
					'height' 	=> $image['meta']['height'],
					'file' 		=> wp_basename($image['url']),
					'mime' 		=> $image['attr']['post_mime_type'],
				);
				
				wp_update_attachment_metadata($attachment_id,$image['meta']);
			}						
		}
		
		return $attachment_id;
	}

	public function get_post_by_guid($guid, $post_type=null){
		
		global $wpdb;
		
		$post_id = 0;
		
		if( !empty($post_type) ){
		
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s AND post_type=%s", $guid, $post_type ) );
		}
		else{
			
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid ) );
		}
		
		return intval($post_id);
	}
	
	public function delete_post($post,$args){
		
		if( !empty($args['action']) && !empty($args['confirm']) ){
			
			$action = sanitize_title($args['action']);
			
			$confirm = sanitize_title($args['confirm']);
			
			if( $confirm == 'delete' ){
				
				if( $action == 'trash' ){
					
					wp_trash_post($post->ID);
				}
				elseif( $action == 'delete' ){
					
					wp_delete_post($post->ID,true);
				}
			}
		}
	}
	
	public function edit_post_status($post,$args){
		
		if( !empty($args['name']) ){
			
			$post_status = sanitize_title($args['name']);
			
			$statuses = $this->get_post_type_statuses($post->post_type);
			
			if( isset($statuses[$post_status]) ){
				
				$post_id = $this->update_post(array(
					
					'ID' 			=> $post->ID,
					'post_status' 	=> $post_status,
				));
				
				if( !empty($post_id) && $post_status != $post->post_status && $post_status == 'publish' ){
					
					do_action('rewbe_post_slug_updated',$post_id);
				}
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
		
		if( !empty($args['data']['key']) ){
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					update_post_meta($post->ID,$key,$value);
				}
			}
		}
	}

	public function remove_post_meta($post,$args){
		
		if( !empty($args['data']['key']) ){
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					delete_post_meta($post->ID,$key,$value);
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
					
					'parent' => apply_filters('rewbe_update_default_term_parent_id',$parent_id,$term),
				));
			}
		}
	}	
	
	public function edit_term_meta($term,$args){
		
		if( !empty($args['data']['key']) ){
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);

					update_term_meta($term->term_id,$key,$value);
				}
			}
		}
	}

	public function remove_term_meta($term,$args){
		
		if( !empty($args['data']['key']) ){
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					delete_term_meta($term->term_id,$key,$value);
				}
			}
		}
	}
	
	public function delete_term($term,$args){
		
		if( !empty($args['confirm']) && sanitize_title($args['confirm']) == 'delete' ){
			
			wp_delete_term($term->term_id,$term->taxonomy);
		}
	}
	
	public function edit_user_role($user,$args){
		
		if( !empty($args['action']) && !empty($args['roles']) ){
			
			$action = sanitize_title($args['action']);
			
			$roles = array_map('sanitize_title', $args['roles']);
			
			if( $action == 'add' ){
				
				foreach ($roles as $role) {
					
					$user->add_role($role);
				}
			}
			elseif( $action == 'replace' ){
				
				$user->set_role('');
				
				foreach ( $roles as $role ) {

					$user->add_role($role);
				}
			}
			elseif( $action == 'remove' ){
				
				foreach ($roles as $role) {
					
					$user->remove_role($role);
				}
			}
		}
	}

	public function delete_user($user,$args){
		
		if( !empty($args['confirm']) && sanitize_title($args['confirm']) == 'delete' ){
			
			$reassign = !empty($args['reassign'][1]) && is_numeric($args['reassign'][1]) ? intval($args['reassign'][1]) : 0;
			
			wp_delete_user($user->ID,$reassign);
		}
	}
	
	public function edit_user_meta($user,$args){
		
		if( !empty($args['action']) && !empty($args['data']['key']) ){
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);

					update_user_meta($user->ID,$key,$value);
				}
			}
		}
	}

	public function remove_user_meta($user,$args){
		
		if( !empty($args['data']['key']) ){
			
			$data = array();
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					delete_user_meta($user->ID,$key,$value);
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

				$items = intval(wp_count_terms($args));
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
			
			// filter dates
			
			if( !empty($task['rewbe_dates']) ){
			
				$dates = $task['rewbe_dates'];
				
				if( !empty($dates['type']) && is_array($dates['type']) ){
					
					$args['date_query'] = array( 
						
						'relation' => isset($task['rewbe_dates_rel']) ? strtoupper(sanitize_text_field($task['rewbe_dates_rel'])) : 'AND',
					);
					
					foreach( $dates['type'] as $e => $type ){
						
						$column = isset($dates['column'][$e]) ? sanitize_title($dates['column'][$e]) : '';
						
						$position = isset($dates['position'][$e]) ? sanitize_title($dates['position'][$e]) : 'before';
						
						$date = '';
						
						if( $type == 'date' ){
							
							$date = isset($dates['value'][$e]) ? $dates['value'][$e] : '';
						}
						elseif( $type == 'time' ){
							
							$value = isset($dates['value'][$e]) ? intval($dates['value'][$e]) : 0;
							
							if( !empty($value) ){
								
								$period = isset($dates['period'][$e]) ? sanitize_title($dates['period'][$e]) : 'days';
								
								$from = isset($dates['from'][$e]) ? sanitize_title($dates['from'][$e]) : 'ago';
								
								$date = ( $from == 'ago' ? '-' : '+' ) . $value . ' ' . $period;
							}
						}
						
						$inclusive = isset($dates['limit'][$e]) && sanitize_title($dates['limit'][$e]) == 'in' ? true : false;
						
						if( !empty($position) && !empty($date) && !empty($column) ){
							
							$args['date_query'][] = array(
							
								$position 	=> $date,
								'column'	=> $column,
								'inclusive' => $inclusive,
							);
						}
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
		
		if( isset($task['rewbe_term_parent']) ){
			
			if( $task['rewbe_term_parent'] === '0' ){
				
				$args['parent'] = 0;
			}
			else{
				
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
		
		if( !empty($task['rewbe_search']) && !empty($task['rewbe_search_col']) ){
		
			$args['search'] = '*' . apply_filters( 'get_search_query', sanitize_text_field($task['rewbe_search']) ) . '*';
			
			$args['search_columns'] = array_map('sanitize_title', $task['rewbe_search_col']);
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
						
						$type_options = $this->admin->get_data_type_options();
						
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
	
	public function parse_action_parameters($type,$task){
		
		$args = array();
		
		$curr_action = !empty($task[$this->_base.'action']) ? $task[$this->_base.'action'] : 'none';
		
		if( $curr_action != 'none' ){
			
			if( $type == 'post-type-task' ){
				
				$actions = $this->get_post_type_actions($task[$this->_base.'post_type'],$task);
			}
			elseif( $type == 'taxonomy-task' ){
				
				$actions = $this->get_taxonomy_actions($task[$this->_base.'taxonomy'],$task);
			}
			elseif( $type == 'user-task' ){
				
				$actions = $this->get_user_actions($task);
			}
			elseif( $type == 'data-task' ){
				
				$actions = $this->get_data_actions($task[$this->_base.'data_type'],$task);
			}
			
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
					
						$actions = $this->get_post_type_actions($task[$this->_base.'post_type'],$task);
					}
					elseif( $post->post_type == 'taxonomy-task' ){
					
						$actions = $this->get_taxonomy_actions($task[$this->_base.'taxonomy'],$task);
					}
					elseif( $post->post_type == 'user-task' ){
						
						$actions = $this->get_user_actions($task);
					}
					elseif( $post->post_type == 'data-task' ){
						
						$actions = $this->get_data_actions($task[$this->_base.'data_type'],$task);
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
