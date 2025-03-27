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
        
        add_action('admin_post_duplicate', array($this, 'duplicate_task') );
		
        add_action('wp_loaded',function(){
		
            add_action('wp_ajax_save_task', array($this,'save_task'));
			
            add_action('wp_ajax_render_task_suggestions', array($this,'render_task_suggestions') );
			add_action('wp_ajax_render_author_suggestions', array($this,'render_author_suggestions') );
			add_action('wp_ajax_render_term_suggestions', array($this,'render_term_suggestions') );

            add_action('wp_ajax_render_task_action', array($this,'render_task_action') );
			add_action('wp_ajax_render_task_process', array($this,'render_task_process') );
            add_action('wp_ajax_render_task_filters', array($this,'render_task_filters') );
			add_action('wp_ajax_render_task_preview', array($this,'render_task_preview') );
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

        add_action( 'init', function(){
            
            $this->register_post_type( 'post-type-task', __( 'Post tasks', 'bulk-task-editor' ), __( 'Post task', 'bulk-task-editor' ), '', array(

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
            
            $this->register_post_type( 'taxonomy-task', __( 'Term tasks', 'bulk-task-editor' ), __( 'Term task', 'bulk-task-editor' ), '', array(

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

            $this->register_post_type( 'user-task', __( 'User tasks', 'bulk-task-editor' ), __( 'User task', 'bulk-task-editor' ), '', array(

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

            $this->register_post_type( 'data-task', __( 'Data tasks', 'bulk-task-editor' ), __( 'Data task', 'bulk-task-editor' ), '', array(

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
            
            add_filter( 'cron_schedules', function($schedules){
                
                $schedules['one_minute'] = array(
                    
                    'interval' => 60,
                    'display'  => __('Every Minute'),
                );
                
                return $schedules;
            });
            
        },1);

        add_action('rewbe_process_cron_task',array($this,'process_cron_task'));
        
        add_action('transition_post_status', function($new_status, $old_status, $post) {

            if( in_array($post->post_type,$this->get_task_types()) && !in_array($new_status, ['publish', 'trash'])) {
                
                $new_status = 'publish';
            }
            
            return  $new_status;
            
        },10,3);
		
        add_filter('posts_where', array($this,'filter_posts_where'),999999999,2);
        
        add_filter('post_row_actions', array($this, 'filter_task_row_actions'), 10, 2 );
		
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
				
				'post-type' => __('Post Type', 'bulk-task-editor'),
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
						
						echo esc_html($post_type->label);
					}
				}
			}
			
			return $column;
			
		},10,2);
		
		add_filter('manage_taxonomy-task_posts_columns',function($columns){
			
			$new_columns = array(
				
				'taxonomy' 	=> __('Taxonomy', 'bulk-task-editor'),
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
						
						echo esc_html($taxonomy->label);
					}
				}
			}
			
			return $column;
			
		},10,2);

		add_filter('manage_data-type-task_posts_columns',function($columns){
			
			$new_columns = array(
				
				'data-type' => __('Data Type', 'bulk-task-editor'),
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
				
					echo esc_html(strtoupper($task['rewbe_post_type']));
				}
			}
			
			return $column;
			
		},10,2);
		
		add_action('add_meta_boxes', function(){
            
            global $post;
            
			remove_meta_box('submitdiv',$this->get_task_types(), 'side');
  
            $this->admin->add_meta_box (
				
				'bulk-editor-process',
				__( 'Process', 'bulk-task-editor' ), 
				$this->get_task_types(),
				'side'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-progress',
				__( 'Progress', 'bulk-task-editor' ), 
				$this->get_task_types(),
				'side'
			);

			$this->admin->add_meta_box (
				
				'bulk-editor-filters',
				$post->post_type == 'data-task' ? __( 'Dataset', 'bulk-task-editor' ) : __( 'Filter', 'bulk-task-editor' ), 
				$this->get_task_types(),
				'advanced'
			);
			
			$this->admin->add_meta_box (
				
				'bulk-editor-task',
				__( 'Task', 'bulk-task-editor' ), 
				$this->get_task_types(),
				'advanced'
			);
		});
        
		add_filter('rewbe_post-type-task_custom_fields', function($fields=array()){
            
            $task = $this->get_current_task('post-type-task');
			
			if( !empty($task[$this->_base.'post_type']) ){
                
				$item_type = $task[$this->_base.'post_type'];
                
                $filters = $this->get_post_type_filters($item_type,$task);
                
                foreach( $filters as $filter ){
                    
                    $filter['metabox'] = array('name'=>'bulk-editor-filters');
                    
                    $fields[] = $filter;
                }
				
				// actions 

                $fields[] = $this->get_actions_field('post-type-task',$item_type,$task);
                
				$actions = $this->get_post_type_actions($item_type,$task);

				foreach( $actions as $action ){
					
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

				if( $curr_action = $task[$this->_base.'action'] ){

					if( $actions = $this->get_post_type_actions($item_type,$task) ){
						
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
				
                $fields[]= $this->get_calling_interval_field($task);
                
				$fields[]= $this->get_process_calling_field($task);
				
				$fields[]= $this->get_process_status_field($task);
				
				// progress
				
                $total = $this->count_task_items('post-type-task',$task);
                
                $sc_steps = $this->get_schedule_steps('post-type-task',$total);
                
                $fields[]= $this->get_progress_scheduled_field('post_type',$task,$sc_steps);
                
                $fields[]= $this->get_progress_processed_field($task);
			}
			
			return $fields;
            
		});	
        
		add_action('rewbe_post_type_filters',function($filters,$item_type,$task=null){
            
            // post type
            
            $post_type = get_post_type_object($item_type);

            $filters[]=array(
            
                'id'          	=> $this->_base . 'post_type',
                'label'       	=> 'Type',
                'type'        	=> 'select',
                'options'	  	=> $this->get_post_type_options(),
            );

            // id
            
            $filters[]=array(
            
                'id'          	=> $this->_base . 'post_ids_op',
                'label'     	=> $post_type->label . ' ID',
                'type'        	=> 'radio',
                'options'		=> array(
                    
                    'in' 		=> 'IN',
                    'not-in' 	=> 'NOT IN',
                ),
                'default' 		=> 'in',
            );
            
            $filters[]=array(
            
                'id'			=> $this->_base . 'post_ids',
                'type'      	=> 'text',
                'placeholder'	=> 'Comma separated IDs',
                'style'			=> 'width:60%;',
            );

            // parent
            
            if( $post_type->hierarchical ){
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'post_parent_op',
                    'label'     	=> 'Parent ID',
                    'type'        	=> 'radio',
                    'options'		=> array(
                        
                        'in' 		=> 'IN',
                        'not-in' 	=> 'NOT IN',
                    ),
                    'default' 		=> 'in',
                );
                
                $filters[]=array(
                
                    'id'			=> $this->_base . 'post_parent',
                    'type'      	=> 'text',
                    'placeholder'	=> 'Comma separated IDs',
                    'style'			=> 'width:60%;',
                );
            }
            
            // status
            
            $filters[]=array(
            
                'id'          	=> $this->_base . 'post_status',
                'label'       	=> 'Status',
                'description' 	=> '',
                'type'        	=> 'checkbox_multi',
                'options'	  	=> $this->get_post_type_statuses($post_type->name),
                'default'     	=> '',
                'style'			=> 'margin-right:5px;float:left;',
            );

            // content
            
            if( post_type_supports($post_type->name, 'editor')){
                
                
                $filters[]=array(
            
                    'id'        => $this->_base . 'search_rel',
                    'label'     => 'Search',
                    'type'      => 'radio',
                    'default'   => 'and',
                    'options'   => $this->admin->get_relation_options(),
                );
                
                $filters[]= array(
                
                    'id'        => $this->_base . 'search',
                    'type'      => 'search',
                    'options'   => array(
                        
                        'post_content'	=> 'Content',
                        'post_excerpt'	=> 'Excerpt',
                        'post_title'	=> 'Title',
                    )
                );
            }
            
            // authors
            
            if( post_type_supports($post_type->name, 'author')){
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'post_authors_op',
                    'label'       	=> 'Authors',
                    'type'        	=> 'radio',
                    'options'		=> array(
                        
                        'in' 		=> 'IN',
                        'not-in' 	=> 'NOT IN',
                    ),
                    'default' 		=> 'in',
                );
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'post_authors',
                    'type'        	=> 'authors',
                    'placeholder'   => 'Search author...',
                    'multi'			=> true,
                );
            }
            
            // date
            
            $filters[]=array(
            
                'id'          	=> $this->_base . 'dates_rel',
                'label'       	=> 'Dates',
                'type'        	=> 'radio',
                'default'		=> 'and',
                'options'		=> $this->admin->get_relation_options(),
            );
            
            $filters[]=array(
            
                'id'			=> $this->_base . 'dates',
                'type'      	=> 'dates',
                'columns'		=> $this->admin->get_date_column_options('post'),
            );

            // TODO: comment count
            // TODO: stickyness
            
            // meta
            
            $filters[]=array(
            
                'id'          	=> $this->_base . 'meta_rel',
                'label'       	=> 'Meta',
                'type'        	=> 'radio',
                'default'		=> 'and',
                'options'		=> $this->admin->get_relation_options(),
            );
            
            $filters[]=array(
            
                'id'        => $this->_base . 'meta',
                'type'      => 'meta',
            );
            
            // taxonomies

            $taxonomies = $this->get_post_type_taxonomies($post_type);
            
            foreach( $taxonomies as $taxonomy ){
                
                if( $taxonomy = get_taxonomy($taxonomy) ){
                    
                    $filters[]=array(
                    
                        'id'          	=> 'rewbe_tax_rel_' . $taxonomy->name,
                        'label'       	=> $taxonomy->label,
                        'type'        	=> 'radio',
                        'default'		=> 'and',
                        'options'		=> $this->admin->get_relation_options(),
                    );
                    
                    $filters[]=array(
                    
                        'id'          	=> 'rewbe_tax_' . $taxonomy->name,
                        'type'        	=> 'terms',
                        'taxonomy'    	=> $taxonomy->name,
                        'hierarchical'	=> $taxonomy->hierarchical,
                        'operator'		=> true,
                        'context'		=> 'filter',
                    );
                }
            }

            return $filters;
			
		},0,3);
        
		add_action('rewbe_post_type_actions',function($actions,$post_type,$task){

			if( $post_type = get_post_type_object($post_type) ){
				
				global $wpdb;
				
				$action = !empty($task['rewbe_action']) ? $task['rewbe_action'] : 'none';
                
                // multitasking
                
				$actions[] = $this->get_multitask_action_field('post-type-task');
                
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
		
				if( $taxonomies = $this->get_post_type_taxonomies($post_type) ){
					
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
							
							'name' 			=> 'filter_content',
							'type'			=> 'select',
							'label'			=> 'Content filters',
							'description'	=> 'Apply shortcodes and filters through the_content hook',
							'default' 		=> 'enabled',
							'options'		=> array(
							
								'enabled'	=> 'Enabled',
								'disabled'	=> 'Disabled',
								
							),
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
                
                // append
                
				$actions[] = $this->get_insert_content_action_field(array(
							
                    'post_content'	=> 'Content',
                    'post_excerpt'	=> 'Excerpt',
                    'post_title'	=> 'Title',
                ));
                
				// replace
				
				$actions[] = $this->get_find_replace_action_field(array(
							
					'post_title'	=> 'Title',
					'post_content'	=> 'Content',
					'post_excerpt'	=> 'Excerpt',
				));
				
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
								
								'name' 		    => 'ids',
								'type'		    => 'authors',
                                'placeholder'   => 'Search author...',
								'multi'		    => false,
							),
						),
					);
				}

				// meta
				
				$actions[] = $this->get_edit_meta_action_field();
				
				$actions[] = $this->get_remove_meta_action_field();
				
				$actions[] = $this->get_rename_meta_action_field();
				
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
                
                // export csv
                
                $actions[] = array(
                    
                    'label' 	=> 'Export Data - CSV',
                    'id' 		=> 'export_data',
                    'fields' 	=> array(
                        array(
                            
                            'name'  => 'format',
                            'type'  => 'hidden',
                            'data'  => 'csv',
                        ),
                        array(
                            
                            'label'     => 'Separator',
                            'name'      => 'separator',
                            'type'      => 'select',
                            'options'   => $this->get_csv_separator_options(),
                            'default'   => $this->get_current_csv_separator(),
                        ),
                        array(
                            
                            'label'     => 'Path',
                            'name'      => 'path',
                            'type'      => 'text',
                            'default'   => trailingslashit(wp_upload_dir()['basedir']).'bte_exports/'.$task['rewbe_id'],
                        ),
                        array(
                            
                            'label'         => 'File Name',
                            'name'          => 'filename',
                            'type'          => 'text',
                            'default'       => 'exported_data',
                            'description'   => 'without .csv extension',
                        ),
                        array(
                            
                            'label'     => 'Fields',
                            'name'      => 'fields',
                            'type'      => 'checkbox_multi',
                            'style'     => 'width:250px;',
                            'options'   => $this->get_post_type_attributes(),
                            //'default'   => array_keys($this->get_post_type_attributes()),
                            'default'   => array(
                                
                                'ID',
                                'post_title',
                                'post_content',
                                'post_type',
                            ),
                        ),
                        array(
                            
                            'label'         => 'URLs',
                            'name' 			=> 'urls',
                            'type'			=> 'checkbox_multi',
                            'style'         => 'width:250px;',
                            'options'       => $this->get_post_url_options(),
                        ),
                        array(
                            
                            'label'         => 'Meta',
                            'name' 			=> 'meta',
                            'type'			=> 'array',
                            'keys'			=> false,
                            'placeholder'	=> 'meta_name',
                        ),
                    ),
                );
			}
			
			return $actions;
			
		},0,3);
        
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
			
			$task = $this->get_current_task('taxonomy-task');
			
			if( !empty($task[$this->_base.'taxonomy']) ){
				
				// taxonomy
				
                $item_type = $task[$this->_base.'taxonomy'];
                
                $filters = $this->get_taxonomy_filters($item_type,$task);
                
                foreach( $filters as $filter ){
                    
                    $filter['metabox'] = array('name'=>'bulk-editor-filters');
                    
                    $fields[] = $filter;
                }
                
				// actions 
				
				$actions = $this->get_taxonomy_actions($item_type,$task);
				
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

                $fields[]= $this->get_calling_interval_field($task);
                
				$fields[]= $this->get_process_calling_field($task);
				
				$fields[]= $this->get_process_status_field($task);
				
				// progress

                $total = $this->count_task_items('taxonomy-task',$task);
                
                $sc_steps = $this->get_schedule_steps('taxonomy-task',$total);
                
                $fields[]= $this->get_progress_scheduled_field('taxonomy',$task,$sc_steps);
                
                $fields[]= $this->get_progress_processed_field($task);
			}
				
			return $fields;
            
		},10,2);
		
		add_action('rewbe_taxonomy_filters',function($filters,$item_type,$task=null){

            if( $taxonomy = get_taxonomy($item_type) ){
                
                // taxonomy
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'taxonomy',
                    'label'       	=> 'Type',
                    'type'        	=> 'select',
                    'options'	  	=> $this->get_taxonomy_options(),
                );

                // id
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'term_ids_op',
                    'label'     	=> $taxonomy->label . ' ID',
                    'type'        	=> 'radio',
                    'options'		=> array(
                        
                        'in' 		=> 'IN',
                        'not-in' 	=> 'NOT IN',
                    ),
                    'default' 		=> 'in',
                );
                
                $filters[]=array(
                
                    'id'			=> $this->_base . 'term_ids',
                    'type'      	=> 'text',
                    'placeholder'	=> 'Comma separated IDs',
                    'style'			=> 'width:60%;',
                );

                // parent
                
                if( $taxonomy->hierarchical ){
                    
                    $filters[]=array(
                    
                        'id'          	=> $this->_base . 'term_parent_op',
                        'label'     	=> 'Parent ID',
                        'type'        	=> 'radio',
                        'options'		=> array(
                            
                            'in' 		=> 'IN',
                            //'not-in' 	=> 'NOT IN', // does not exists yet
                        ),
                        'default' 		=> 'in',
                    );
                    
                    $filters[]=array(
                    
                        'id'			=> $this->_base . 'term_parent',
                        'type'      	=> 'text',
                        'placeholder'	=> 'Comma separated IDs',
                        'style'			=> 'width:60%;',
                    );
                }
                
                // search

                $filters[]=array(
                
                    'id'          	=> $this->_base . 'search_loc',
                    'label'     	=> 'Search',
                    'type'        	=> 'radio',
                    'options'		=> array(
                        
                        'name' 			=> 'Name',
                        'description' 	=> 'Description',
                    ),
                    'default' 		=> 'name',
                );
                
                $filters[]=array(
                
                    'id'			=> $this->_base . 'search',
                    'type'      	=> 'text',
                    'placeholder'	=> 'Search keyword',
                    'style'			=> 'width:60%;',
                );
            
                // meta
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'meta_rel',
                    'label'       	=> 'Meta',
                    'type'        	=> 'radio',
                    'default'		=> 'and',
                    'options'		=> $this->admin->get_relation_options(),
                );
                
                $filters[]=array(
                
                    'id'       	=> $this->_base . 'meta',
                    'type'		=> 'meta',
                );
            }
            
            return $filters;
			
		},0,3);
        
		add_action('rewbe_taxonomy_actions',function($actions,$taxonomy){
			
			$taxonomy = get_taxonomy($taxonomy);
			
            // multitasking
                
            $actions[] = $this->get_multitask_action_field('taxonomy-task');
                
			// parent
			
			if( $taxonomy->hierarchical ){
				
				$actions[] = $this->get_parent_action_field();
				
			}
			
			// meta
			
			$actions[] = $this->get_edit_meta_action_field();
			
			$actions[] = $this->get_remove_meta_action_field();

			$actions[] = $this->get_rename_meta_action_field();

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

            $task = $this->get_current_task('user-task');
            
			if( !empty($task[$this->_base.'action']) ){
                
                $item_type = $task[$this->_base.'user_role'];
           
                $filters = $this->get_user_filters($item_type,$task);
                                                    
                foreach( $filters as $filter ){
                    
                    $filter['metabox'] = array('name'=>'bulk-editor-filters');
                    
                    $fields[] = $filter;
                }
                
				// actions 
				
				$actions = $this->get_user_actions($task);
				
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

                $fields[]= $this->get_calling_interval_field($task);
                
				$fields[]= $this->get_process_calling_field($task);
				
				$fields[]= $this->get_process_status_field($task);
				
				// progress

				$total = $this->count_task_items('user-task',$task);
					
				$sc_steps = $this->get_schedule_steps('user-task',$total);
					
				$fields[]= $this->get_progress_scheduled_field('user',$task,$sc_steps);
					
				$fields[]= $this->get_progress_processed_field($task);
			}
				
			return $fields;
            
		});
        
		add_action('rewbe_user_filters',function($filters,$item_type,$task=null){

            // role
            
			$filters[]=array(
			
				'id'        => $this->_base . 'user_role',
                'label'     => 'User Role',
				'type'      => 'select',
                'options'   => array(
                
                    'any' => 'Any'
                ),
			);
            
			// id
			
			$filters[]=array(
			
				'id'          	=> $this->_base . 'user_ids_op',
				'label'     	=> 'User ID',
				'type'        	=> 'radio',
				'options'		=> array(
					
					'in' 		=> 'IN',
					'not-in' 	=> 'NOT IN',
				),
				'default' 		=> 'in',
			);
			
			$filters[]=array(
			
				'id'			=> $this->_base . 'user_ids',
				'type'      	=> 'text',
				'placeholder'	=> 'Comma separated IDs',
				'style'			=> 'width:60%;',
			);
			
			// search

			$filters[]=array(
			
				'id'			=> $this->_base . 'search',
				'label'     	=> 'Search',
				'type'      	=> 'text',
				'placeholder'	=> 'Search terms',
				'style'			=> 'width:60%;',
			);
			
			$filters[]=array(
			
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
				'style'			=> 'margin-right:5px;float:left;',
			);
			
			// meta
			
			$filters[]=array(
			
				'id'          	=> $this->_base . 'meta_rel',
				'label'       	=> 'Meta',
				'type'        	=> 'radio',
				'default'		=> 'and',
				'options'		=> $this->admin->get_relation_options(),
			);
			
			$filters[]=array(
			
				'id'        	=> $this->_base . 'meta',
				'type'        	=> 'meta',
			);
			
            return $filters;
			
		},0,3);
        
		add_action('rewbe_user_actions',function($actions){
			
            // multitasking
                
			$actions[] = $this->get_multitask_action_field('user-task');
                
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
					
						'name' 	        => 'reassign',
						'label'         => 'Reassign contents to',
						'type'	        => 'authors',
                        'placeholder'   => 'Search author...',
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
			
			$actions[] = $this->get_edit_meta_action_field();
			
			$actions[] = $this->get_remove_meta_action_field();
			
			$actions[] = $this->get_rename_meta_action_field();
			
			return $actions;
			
		},0,2);
        
		add_filter('rewbe_data-task_custom_fields', function($fields=array()){
            
            $task = $this->get_current_task('data-task');
			
			if( !empty($task[$this->_base.'data_type']) ){
                
                $item_type = $task[$this->_base.'data_type'];
                
                $filters = $this->get_data_filters($item_type,$task);
                
                foreach( $filters as $filter ){
                    
                    $filter['metabox'] = array('name'=>'bulk-editor-filters');
                    
                    $fields[] = $filter;
                }
                
				// actions 
                
                $fields[] = $this->get_actions_field('data-task',$item_type,$task);

				$actions = $this->get_data_actions($item_type,$task);

				foreach( $actions as $action ){
					
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

				if( $curr_action = $task[$this->_base.'action'] ){

					if( $actions = $this->get_data_actions($item_type,$task) ){
						
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
				
				$fields[]= $this->get_process_items_field('data');
				
				$fields[]= $this->get_per_process_field($task);
				
                $fields[]= $this->get_calling_interval_field($task);
				
				$fields[]= $this->get_process_calling_field($task);
                
				$fields[]= $this->get_process_status_field($task);
				
				// progress
				
                $total = $this->count_task_items('data-task',$task);
               
                $sc_steps = $this->get_schedule_steps('data-task',$total);
                
                $fields[]= $this->get_progress_scheduled_field('data',$task,$sc_steps);
                
                $fields[]= $this->get_progress_processed_field($task);
			}
			
			return $fields;
            
		});	
        
		add_action('rewbe_data_filters',function($filters,$item_type,$task=null){

            // data type
            
            $filters[]=array(
            
                'id'          	=> $this->_base . 'data_type',
                'label'       	=> 'Type',
                'type'        	=> 'select',
                'options'	  	=> $this->get_data_type_options(),
            );
            
            if( $item_type == 'rest' ){
                
                $filters[]=array(
            
                    'id'          	=> $this->_base . 'data_source',
                    'label'       	=> 'Source URL',
                    'type'        	=> 'url',
                    'placeholder'	=> 'https://',
                    'description'   => 'REST API url where the data source is located',
                );
                
                $filters[]=array(
            
                    'id'          	=> $this->_base . 'data_method',
                    'label'       	=> 'Method',
                    'type'        	=> 'select',
                    'options'	    => array(
                    
                        'get'   => 'GET',
                        'post'  => 'POST',
                    ),
                    'default' => 'get',
                );
            }
            elseif( $item_type == 'directory' ){
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'data_source',
                    'label'       	=> 'Folder Path',
                    'type'        	=> 'text',
                    'placeholder'	=> '/path/folder/',
                    'description'   => 'Local directory path where the data is located',
                );
            }
            else{
                
                $filters[]=array(
                
                    'id'          	=> $this->_base . 'data_source',
                    'label'       	=> 'Source Path',
                    'type'        	=> 'text',
                    'placeholder'	=> '/path/file',
                    'description'   => 'Local file path where the data source is located',
                );
            }
           
            if( $item_type == 'csv' ){
                
                $filters[] = array(
                    
                    'id'        => $this->_base . 'data_separator',
                    'label'     => 'Separator',
                    'name'      => 'separator',
                    'type'      => 'select',
                    'options'   => $this->get_csv_separator_options(),
                    'default'   => $this->get_current_csv_separator(),
                );
            }
            
            return $filters;
			
		},0,3);
        
		add_action('rewbe_data_actions',function($actions,$post_type,$task){

            if( !empty($task[$this->_base.'data_type']) ){
                
                $type = sanitize_title($task[$this->_base.'data_type']);
               
                if( $args = $this->parse_data_task_parameters($task) ){
                    
                    if( $type == 'directory' ){
                        
                        $actions[] = array(
                            
                            'label' 	    => 'Import Featured Images',
                            'id' 		    => 'import_post_thumbnail',
                            'fields' 	    => array(
                                array(
            
                                    'name'          => 'type',
                                    'label'       	=> 'Post Type',
                                    'type'        	=> 'select',
                                    'options'	  	=> $this->get_post_type_options('thumbnail'),
                                ),
                                array(
                                    'name' 		    => 'name',
                                    'type'		    => 'text',
                                    'label'	        => 'Meta Key',
                                    'default'	    => '_thumb_name_',
                                    'description'   => 'Meta key used to find the post to be associated with the image.',
                                ),
                                array(
                                    'name' 		    => 'value',
                                    'type'		    => 'text',
                                    'label'	        => 'Meta Value',
                                    'default'	    => 'prefix-{%FILENAME%}-suffix',
                                    'description'   => 'Pattern to find the post via meta key. {%FILENAME%} is dynamically replaced.',
                                ),
                                array(
							
                                    'name' 			=> 'existing',
                                    'type'			=> 'select',
                                    'label'			=> 'Existing Thumbnail',
                                    'default' 		=> 'skip',
                                    'options'		=> array(
                                    
                                        'skip'		=> 'Skip',
                                        'replace'	=> 'Replace',
                                    ),
                                    'description'   => 'How to handle entries with an existing thumbnail',
                                ),
                            ),
                        );
                        
                        $actions[] = array(
                            
                            'label' 	    => 'Import Image Galleries',
                            'id' 		    => 'import_image_gallery',
                            'fields' 	    => array(
                                array(
            
                                    'name'          => 'type',
                                    'label'       	=> 'Post Type',
                                    'type'        	=> 'select',
                                    'options'	  	=> $this->get_post_type_options('thumbnail'),
                                ),
                                array(
                                    'name' 		    => 'name',
                                    'type'		    => 'text',
                                    'label'	        => 'Meta Key',
                                    'default'	    => '_thumb_name_',
                                    'description'   => 'Meta key used to find the post to be associated with the image.',
                                ),
                                array(
                                    'name' 		    => 'value',
                                    'type'		    => 'text',
                                    'label'	        => 'Meta Value',
                                    'default'	    => 'prefix-{%FILENAME%}-suffix',
                                    'description'   => 'Pattern to find the post via meta key. {%FILENAME%} is dynamically replaced.',
                                ),
                                array(
                                    'name' 		    => 'gallery',
                                    'type'		    => 'text',
                                    'label'	        => 'Gallery Name',
                                    'default'	    => '_product_image_gallery', // _product_image_gallery
                                    'description'   => 'Meta key used to store the ids of the gallery images.',
                                ),
                            ),
                        );
                    }
                    elseif( $items = $this->get_dataset($args) ){
                        
                        $item = reset($items);
                        
                        $options = array_keys($item);
                        
                        $fields = array();
                        
                        $fields[] = array(
                            
                            'name' 		    => 'primary',
                            'type'		    => 'select',
                            'label'	        => 'Primary Field',
                            'options'       => array('Select a field')+$options,
                            'description'   => 'This primary identifier should have distinct values for all entries to prevent duplicates',
                        );
                        
                        $fields[] = array(
							
							'name' 			=> 'existing',
							'type'			=> 'select',
							'label'			=> 'Existing Record',
							'default' 		=> 'skip',
							'options'		=> array(
							
								'skip'		=> 'Skip',
								'overwrite'	=> 'Overwrite',
							),
                            'description'   => 'How to handle entries when the primary identifier matches an existing record',
						);
                        
                        if( !isset($options['post_type']) ){
                            
                            // TODO select default post type
                        }
                        
                        if( !isset($options['post_status']) ){
                            
                            // TODO select default status
                        }
                        
                        $fields[] = array(
                            
                            'name' 		=> 'fields',
                            'type'		=> 'data_fields',
                            'label'	    => 'Data Mapping',
                            'options'   => $options,
                            'attrs'     => $this->get_post_type_attributes(true),
                            
                        );
                        
                        $actions[] = array(
                            
                            'label' 	    => 'Import Data - Post Type',
                            'id' 		    => 'import_post_type',
                            'fields' 	    => $fields,
                        );
                    }
                }
            }

			return $actions;
			
		},0,3);
        
        add_action('rewbe_post_type_statuses',function($statuses,$post_type){
            
            if( $post_type == 'attachment' ){
                
                $statuses = array(
                
                    'inherit'   => 'Inherit',
                    'private'   => 'Private',
                    'trash'     => 'Trash',
                );
            }
            
			return $statuses;
            
		},0,2);       
        

	} // End __construct ()
	
    public function get_current_task($task_type){
       
        global $post;
        
        if( !empty($post->ID) ){

            $task = $this->get_task_meta($post->ID);
            
            
        }
        else{

            $task = $this->get_default_task(0,$task_type);
        }
        
        return $task;
    }
    
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
		
			'ajax' 	=> 'AJAX',
			'cron' 	=> 'CRON',
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

	public function get_calling_interval_field($task){
		
		return array(
			
			'metabox' 		=> array('name'=>'bulk-editor-process'),
			'id'        	=> $this->_base . 'interval',
			'label'       	=> 'Calling interval (sec)',
			'type'        	=> 'number',
			'default'       => 0,
            'min'           => 0,
            'max'           => 10000,
            'step'          => 1,
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
			'type'		=> 'process_status',
			'options'	=> $options,
			'data'		=> $status == 'reschedule' ? 'running' : $status,
		);
	}
    
    public function get_task_actions($task_type,$item_type,$task){
        
        if( $task_type == 'post-type-task' ){
            
            $actions = $this->get_post_type_actions($item_type,$task);
        }
        elseif( $task_type == 'taxonomy-task' ){
            
            $actions = $this->get_taxonomy_actions($item_type,$task);
        }
        elseif( $task_type == 'user-task' ){
            
            $actions = $this->get_user_actions($task);
        }
        elseif( $task_type == 'data-task' ){
            
            $actions = $this->get_data_actions($item_type,$task);
        }
        
        if( is_array($actions) ){
        
            if( !empty($actions) ){
                
                ksort($actions);
            }
            
            return $actions;
        }
    }
    
    public function get_actions_field($task_type,$item_type,$task,$selected=null){

        $options = array('none' => 'None');
        
        if( $actions = $this->get_task_actions($task_type,$item_type,$task) ){
            
            foreach( $actions as $action ){
                
                $options[$action['id']] = $action['label'];
            }
        }
        
        return array(
        
            'metabox' 		=> array('name'=>'bulk-editor-task'),
            'id'          	=> $this->_base . 'action',
            'label'         => 'Action',
            'type'        	=> 'select',
            'options'     	=> $options,
            'data'          => !is_null($selected) ? $selected : $task[$this->_base . 'action']
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
		
        $steps = $status == 'pause' ? 0 : $steps;
       
		return array(
			
			'metabox' 	=> array('name'=>'bulk-editor-progress'),
			'id'		=> $this->_base . 'scheduler',
			'label'		=> 'Scheduled',
			'type'      => 'html',
			'data'      => '<div id="rewbe_task_scheduled" data-type="'.$type.'" data-steps="'.$steps.'" style="width:65px;display:block;">' . $prog . '%</div>',
		);
	}
		
	public function get_progress_processed_field($task){
        
        $prog = !empty($task['rewbe_progress']) && is_numeric($task['rewbe_progress']) ? ( $task['rewbe_progress'] > 100 ? 100 : $task['rewbe_progress'] ) : 0;

		return array(
			
			'metabox' 	=> array('name'=>'bulk-editor-progress'),
			'id'		=> $this->_base . 'processor',
			'label'		=> 'Processed',
			'type'      => 'html',
			'data'      => '<div id="rewbe_task_processed" style="width:65px;display:block;">' . $prog . '%</div>',
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
    
	public function get_insert_content_action_field($options){
		
		return array(
					
			'label' 	=> 'Insert Content',
			'id' 		=> 'insert_content',
			'fields' 	=> array(
                array(
					
					'name' 		=> 'insert',
					'label' 	=> 'Insert in',
					'type'		=> 'radio',
					'options'	=> $options,
					'style'		=> 'margin-right:5px;float:left;',
                    'default'   => key($options),
				),
                array(
					
					'name' 			=> 'content',
					//'label' 		=> 'Content',
					'type'			=> 'textarea',
					'placeholder'	=> 'Content to insert',
				),
                
				array(
					
					'name' 		=> 'position',
                    'label' 	=> 'Position',
					'type'		=> 'select',
					'options'	=> array(
						
                        'end'	=> 'At the end',
                        'start'	=> 'At the begining',
					),
					'default' => 'append',
				),
			),
		);
		
	}
    
	public function get_find_replace_action_field($options){
		
		return array(
					
			'label' 	=> 'Find and replace',
			'id' 		=> 'find_replace',
			'fields' 	=> array(
				array(
					
					'name' 			=> 'match',
					'label' 		=> 'Find',
					'type'			=> 'text',
					'placeholder'	=> 'text to find',
				),
				array(
					
					'name' 		=> 'fx',
					'type'		=> 'radio',
					'options'	=> array(
						
						'str_replace'	=> 'Exact Match',
						'str_ireplace'	=> 'Case Insensitive',
						'preg_replace'	=> 'Regular Expression',
					),
					'default' => 'str_replace',
				),
				array(
					
					'name' 			=> 'rep_with',
					'label' 		=> 'Replace with',
					'type'			=> 'text',
					'placeholder'	=> 'replacement',
					'description'	=> 'Leave empty to remove occurrences',
				),
				array(
					
					'name' 		=> 'contents',
					'label' 	=> 'Search in',
					'type'		=> 'checkbox_multi',
					'options'	=> $options,
					'style'		=> 'margin-right:5px;float:left;',
				),
				array(
							
					'name' 			=> 'meta',
					'type'			=> 'array',
					'keys'			=> false,
					'placeholder'	=> 'meta_name',
				),
			),
		);
	}
    
    public function get_multitask_action_field($task_type){
        
        return array(
            
            'label' 	=> 'Run Multiple Tasks',
            'id' 		=> 'run_multiple_tasks',
            'fields' 	=> array(
                array(
                    
                    'name' 		=> 'tasks',
                    'label'     => 'Tasks',
                    'type'		=> 'tasks',
                    'task_type'	=> $task_type,
                ),
                array(
                    
                    'name' 		=> 'per_process',
                    'label'     => 'Tasks per process',
                    'type'		=> 'number',
                    'default'   => 1,
                    'min'       => 1,
                    'step'      => 1,
                ),
            ),
        );
    }
	
	public function get_edit_meta_action_field(){
	
		return array(
			
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
	}	
	
	public function get_remove_meta_action_field(){
	
		return array(
			
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
	}
			
	public function get_rename_meta_action_field(){

		return array(
			
			'label' 	=> 'Rename Meta',
			'id' 		=> 'rename_meta',
			'fields' 	=> array(			
				array(
					
					'name' 			=> 'from',
					'label' 		=> 'From Meta Name',
					'type'			=> 'text',
					'placeholder'	=> 'meta_name',
				),	
				array(
					
					'name' 			=> 'to',
					'label' 		=> 'To Meta Name',
					'type'			=> 'text',
					'placeholder'	=> 'meta_name',
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
	
	public function get_task_types($prefix=null){
		
		$types = array(
			
			'post-type-task',
			'taxonomy-task',
			'user-task',
			'data-task',
		);
        
        if( !is_null($prefix) ){
            
            $types = array_map(function($type) use ($prefix) {
                    
                return $prefix . $type;
                    
            },$types);
        }
        
        return $types;
	}
    
    public function get_post_url_options(){
        
        return array(
        
            'permalink' => 'Permalink',
            'thumbnail' => 'Thumbnail',
        );
    }
	
	public function get_post_type_options($supports=false){
		
		$post_types = get_post_types('','objects');
		
		$options = array();
		
		foreach( $post_types as $post_type ){
			
            if( !empty($supports) && !post_type_supports($post_type->name,$supports) ){
                
                continue;
            }
            
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
    
	public function get_data_type_options(){
        
        return array(
        
            //'csv'     => 'CSV File',
            'json'      => 'JSON File',
            'directory' => 'Directory',
            //'rest'    => 'REST API',
        );
    }
    
	public function add_default_columns($columns){
		
		$new_columns = array(
			
			'task'    	=> __('Task', 'bulk-task-editor'),
			'progress'	=> __('Progress', 'bulk-task-editor'),
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
				
				echo '<code>'. esc_html($task['rewbe_action']) . '</code>';
			}
		}
		elseif( $column == 'progress' ){
			
			
			echo esc_html($task['rewbe_progress'] . '%');
		}
		
		return $column;
	}
    
	public function filter_task_row_actions( $actions, $post ){
	
		if( in_array($post->post_type,$this->get_task_types()) ){
		
			if( !isset($actions['duplicate']) ){
			
				// duplicate action
			
				$actions['duplicate'] = '<a href="#duplicateItem" data-toggle="dialog" data-type="post_type:' . $post->post_type . '" data-target="#duplicateItem" class="duplicate-button" data-id="' . $post->ID . '">Duplicate</a>';
			}
		}
        
        return $actions;
    }
    
    public function get_post_type_attributes($extended=false){
    
        $attrs = array(
        
            'ID'                    => 'Post ID',
            'post_title'            => 'Post Title',
            'post_name'             => 'Post Name (slug)',
            'post_author'           => 'Post Author ID',
            'post_parent'           => 'Parent Post ID',
            'post_status'           => 'Post Status',
            'post_type'             => 'Post Type',
            'post_date'             => 'Post Publish Date',
            'post_date_gmt'         => 'Post Publish Date (GMT)',
            'post_modified'         => 'Post Last Modified Date',
            'post_modified_gmt'     => 'Post Last Modified Date (GMT)',           
            'post_excerpt'          => 'Post Excerpt',
            'post_content'          => 'Post Content',
            'comment_status'        => 'Comment Status',           
            'comment_count'         => 'Comment Count',
            'ping_status'           => 'Ping Status',
            'post_password'         => 'Post Password',
            'to_ping'               => 'To Ping URLs',
            'pinged'                => 'Already Pinged URLs',
            'post_content_filtered' => 'Filtered Post Content',
            'guid'                  => 'Global Unique Identifier',
            'menu_order'            => 'Menu Order',
            'post_mime_type'        => 'Post MIME Type',
            'filter'                => 'Post Filter Type',
        );
        
        if( $extended === true ){
            
            $attrs += array(
            
                //'tax_input'       => 'Taxonomies > Array',
                //'meta_input'      => 'Metadata > Array',
                'meta'              => 'Meta:{name} > Value',
                'term_id'           => 'Taxonomy:{name} > Term id',
                'term_slug'         => 'Taxonomy:{name} > Term slug',
                'term_name'         => 'Taxonomy:{name} > Term name',
            );
        }
            
        return $attrs;
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
	
    public function get_default_task($post_id,$post_type){
        
        $task = array(
			
			'rewbe_id' 			=> $post_id,
			'rewbe_action' 		=> 'none',
			'rewbe_progress' 	=> 0,
		);

        if( $post_type == 'post-type-task' ){
        
            $task[$this->_base.'post_type'] = 'post';
        }
        elseif( $post_type == 'taxonomy-task' ){
        
            $task[$this->_base.'taxonomy'] = 'post_tag';
        }
        elseif( $post_type == 'user-task' ){
        
            $task[$this->_base.'user_role'] = 'any';
        }
        elseif( $post_type == 'data-task' ){
        
            $task[$this->_base.'data_type'] = 'json';
        }
        
        return $task;
    }
    
	public function get_task_meta($post_id){

        if( $post = get_post($post_id) ){

            $meta = $this->get_default_task($post_id,$post->post_type);
            
            if( $data = get_metadata('post',$post_id) ){
                
                foreach( $data as $key => $value ){
                    
                    if( strpos($key,$this->_base) === 0 ){
                    
                        $meta[$key] = maybe_unserialize($value[0]);
                    }
                }
            }
        }

		return $meta;
	}
	
	public function get_post_type_actions($task_type,$task){

		return $this->parse_actions(apply_filters('rewbe_post_type_actions',array(),$task_type,$task));
    }
	
	public function get_taxonomy_actions($task_type,$task){
		
		return $this->parse_actions(apply_filters('rewbe_taxonomy_actions',array(),$task_type,$task));
	}
	
	public function get_user_actions($task){
		
		return $this->parse_actions(apply_filters('rewbe_user_actions',array(),$task));
	}
	
	public function get_data_actions($task_type,$task){
		
		return $this->parse_actions(apply_filters('rewbe_data_actions',array(),$task_type,$task));
	}
    
  	public function get_post_type_filters($item_type,$task){
		
		return apply_filters('rewbe_post_type_filters',array(),$item_type,$task);
	}
    
	public function get_taxonomy_filters($item_type,$task){
		
		return apply_filters('rewbe_taxonomy_filters',array(),$item_type,$task);
	}
    
	public function get_user_filters($item_type,$task){
		
		return apply_filters('rewbe_user_filters',array(),$item_type,$task);
	}
    
	public function get_data_filters($item_type,$task){
		
		return apply_filters('rewbe_data_filters',array(),$item_type,$task);
	}
    
	public function sanitize_regex($pattern) {
		
		$first_char = substr($pattern, 0, 1);
		$last_char = substr($pattern, -1);

		if( $first_char === $last_char && strlen($pattern) > 2 ){
			
			$delimiter = $first_char;
		} 
		else{
			
			$delimiter = '/';
			
			$pattern = $delimiter . $pattern . $delimiter;
		}

		if( @preg_match($pattern, '') === false ){
			
			throw new InvalidArgumentException("Invalid regex pattern: $pattern");
		}

		return $pattern;
	}
    
    static public function sanitize_post_field($field_type, $value) {
        
        switch ($field_type) {
            case 'ID':
                return absint($value);
                
            case 'post_author':
                return absint($value);
                
            case 'post_date':
            case 'post_date_gmt':
            case 'post_modified':
            case 'post_modified_gmt':
                // Validate date format
                $timestamp = strtotime($value);
                return $timestamp ? sanitize_text_field($value) : null;
                
            case 'post_content':
                return wp_kses_post($value);
                
            case 'post_title':
                return sanitize_text_field($value);
                
            case 'post_excerpt':
                return sanitize_textarea_field($value);
                
            case 'post_status':
                return sanitize_key($value);
                
            case 'post_type':
                return sanitize_key($value);
                
            case 'post_name':
                return sanitize_title($value);
                
            case 'post_password':
                return sanitize_text_field($value);
                
            case 'post_parent':
                return absint($value);
                
            case 'menu_order':
                return absint($value);
                
            case 'post_mime_type':
                return sanitize_mime_type($value);
                
            case 'comment_status':
            case 'ping_status':
                return sanitize_key($value);
                
            default:
                return sanitize_text_field($value);
        }
    }
    
	static public function sanitize_content($content,$allowed_html=null,$allowed_protocols=null){
		
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
		
		if( in_array($screen->id,$this->get_task_types()) ){
			
			wp_dequeue_style('jquery-ui-core');
			wp_dequeue_style('jquery-ui-style');

			wp_register_style( 'jquery-ui-core', esc_url( $this->assets_url ) . 'css/jquery-ui.css', array(), $this->_version );
			wp_enqueue_style( 'jquery-ui-core' );
			
			wp_register_style( 'jquery-ui-dialog', esc_url( $this->assets_url ) . 'css/jquery-ui-dialog.css', array('jquery-ui-core'), $this->_version );
			wp_enqueue_style( 'jquery-ui-dialog' );
			
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
		
		if( in_array($screen->id,$this->get_task_types()) ){
			
			wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/task-editor.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog' ), $this->_version.time(), true );
			wp_enqueue_script( $this->_token . '-admin' );
		}
        elseif( in_array($screen->id,$this->get_task_types('edit-')) ){
            
            wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/task-list.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog' ), $this->_version.time(), true );
			wp_enqueue_script( $this->_token . '-admin' );
            
            add_filter('admin_footer',function(){
                
                echo '<div id="duplicateItem" style="display:none;" title="Duplicate">';
                    
                    echo '<div id="duplicateForm" style="min-width:300px;">';

                    echo '</div>';	
                    
                echo '</div>';
            });
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
		load_plugin_textdomain( 'bulk-task-editor', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'bulk-task-editor';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	public function duplicate_task(){
		
		if( current_user_can( 'administrator' ) ){
			
			if( !empty($_POST['id']) && !empty($_POST['title']) && !empty($_POST['type']) ){
				
				list($type,$type_value) = explode(':',sanitize_text_field($_POST['type']));
				
				if( $type == 'post_type' ){
					
					$post_id = intval($_POST['id']);
					
					if( $post = get_post($post_id,ARRAY_A) ){
						
						unset(
						
							$post['ID'],
							$post['post_name'],
							$post['post_author'],
							$post['post_date'],
							$post['post_date_gmt'],
							$post['post_modified'],
							$post['post_modified_gmt']
						);
						
						$post['post_title']     = sanitize_text_field($_POST['title']);
						$post['post_status'] 	= 'draft';
						
						if( $new_id = wp_insert_post($post) ){
							
							// duplicate all post meta
							
							if( $meta = get_post_meta($post_id) ){
					
								foreach($meta as $name => $value){
									
									if( isset($value[0]) ){
										
										update_post_meta( $new_id, $name, maybe_unserialize($value[0]) );
									}
								}
							}
							
							// duplicate all taxonomies
							
							if( $taxonomies = get_object_taxonomies($post['post_type']) ){
							
								foreach( $taxonomies as $taxonomy ) {
									
									if( $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs')) ){
									
										wp_set_object_terms($new_id, $terms, $taxonomy, false);
									}
								}
							}
                            
                            if( in_array($type_value,$this->get_task_types()) ){
                            
                                // pause the task
                                
                                update_post_meta( $new_id, 'rewbe_process_status', 'pause');
                            }
							
							// redirect to new post
							
							wp_redirect(get_admin_url().'post.php?post='.$new_id.'&action=edit');
							exit;
						}
					}
				}
				elseif( $type == 'taxonomy' ){
					
					$term_id = intval($_POST['id']);
					
					if( $term = get_term_by('id',$term_id,$type_value,ARRAY_A) ){
						
						if( $new_term = wp_insert_term( $_POST['title'], $type_value, array(
							
							'description'	=> $term['description'],
							'parent'		=> $term['parent'],
							'alias_of'		=> $term['term_group'],
						
						))){
							
							// duplicate all term meta
							
							if( $meta = get_term_meta($term_id) ){
								
								foreach($meta as $name => $value){
									
									if( isset($value[0]) ){
										
										update_term_meta( $new_term['term_id'], $name, maybe_unserialize($value[0]) );
									}
								}
							}
							
							// redirect to new term
							
							wp_redirect(get_admin_url().'term.php?tag_ID=' . $new_term['term_id'] . '&taxonomy=' . $type_value);
							exit;
						}
					}
				}
			}
		}
	}
    
    public function render_task_suggestions(){
		
		$results = array();
		
		if( current_user_can('edit_posts') && !empty($_GET['s']) && !empty($_GET['id']) && !empty($_GET['tt']) && !empty($_GET['it']) && !empty($_GET['tn']) ){
			
			if( $s =  apply_filters( 'get_search_query', sanitize_text_field($_GET['s']) ) ){
				
				$input_id = sanitize_title($_GET['id']);
                
                $task_type = sanitize_title($_GET['tt']);
                
                $item_type = sanitize_title($_GET['it']);
                
                $task_num = intval($_GET['tn']);
				
				$args = array(
					
                    'post_type'     => $task_type,
                    'post_status'   => 'publish',
                    'numberposts'   => 10,
                    's'             => $s,
				);
                
                if( $task_type == 'post-type-task' ){
                    
                    $meta_query = array(
                    
                        'key'   => 'rewbe_post_type',
                        'value' => $item_type,
                        'compare' 	=> '=',
                    );
                }
                elseif( $task_type == 'taxonomy-task' ){
                    
                    $meta_query = array(
                    
                        'key'   => 'rewbe_taxonomy',
                        'value' => $item_type,
                        'compare' 	=> '=',
                    );
                }
                elseif( $task_type == 'data-task' ){
                    
                    $meta_query = array(
                    
                        'key'       => 'rewbe_data_type',
                        'value'     => $item_type,
                        'compare' 	=> '=',
                    );
                }
                
                if( !empty($meta_query) ){
                    
                    $args['meta_query'] = array($meta_query);
                }
				
				$posts = get_posts($args);
				
				foreach ( $posts as $post ) {
					
					$name = $post->post_title . ' - #' . $post->ID;
					
					$results[] = array(
					
						'id' 	=> $post->ID,
						'name'	=> $name,
						'html'	=> $this->admin->display_field(array(
							
							'id' 	    => $input_id,
							'type' 	    => 'task',
                            'number'    => $task_num,
							'data' 	    => array(
							
								'id'		=> $post->ID,
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
    
	public function render_author_suggestions(){
		
		$results = array();
		
		if( current_user_can('edit_posts') && !empty($_GET['s']) && !empty($_GET['id']) ){
			
			if( $s =  apply_filters( 'get_search_query', sanitize_text_field($_GET['s']) ) ){
				
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
	
	public function render_term_suggestions(){
		
		$results = array();
		
		if( current_user_can('edit_posts') ){
			
            if( $s = apply_filters('get_search_query', sanitize_text_field($_GET['s']) ) ){
                
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
    
    public function save_task(){
        
        $results = false;
        
        if( current_user_can('edit_posts') && is_array($_POST['task']) && !empty($_POST['task']['post_ID']) ){
            
            $post_title = sanitize_text_field($_POST['task']['post_title']);
            
            $task = $this->sanitize_task_meta($_POST['task']);
            
            $post_id = intval($_POST['task']['post_ID']);
            
            $post_type = sanitize_title($_POST['task']['post_type']);
            
            $old_task = $this->get_task_meta($post_id);
            
            $changes = $this->compare_arrays($old_task,$task,array(
                
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
            
            // update task title
            
            wp_update_post(array(
            
                'ID'            => $post_id,
                'post_status'   => 'publish',
                'post_title'    => $post_title,
            
            ),false,false);
            

            foreach( $task as $meta => $value ){
                
                update_post_meta($post_id,$meta,$value);
            }
            
            if( !empty($changes['removed']) ){

                foreach( $changes['removed'] as $meta => $data ){
                    
                    delete_post_meta($post_id,$meta,$data);
                }
            }
            
            $sc_steps   = 0;
            $scheduled  = 0;
            $processed  = 0;
            
            $status = $task[$this->_base.'process_status'];
    
            if( $status != 'pause' ){
            
                $total = $this->count_task_items($post_type,$task);
            
                $sc_steps = $this->get_schedule_steps($post_type,$total);
            }
            else{
                
                wp_clear_scheduled_hook('rewbe_process_cron_task',array($post_id));
            }
            
            if( !empty($changes) || $status == 'reschedule' ){

                // delete schedule marks
                
                global $wpdb;
                
                if( $post_type == 'user-task' ){
                    
                    $table = $wpdb->usermeta;
                }
                elseif( $post_type == 'taxonomy-task' ){
                    
                    $table = $wpdb->termmeta;
                }
                elseif( $post_type == 'comment-task' ){
                    
                    $table = $wpdb->commentmeta;
                }
                else{
                    
                    $table = $wpdb->postmeta;
                }
                
                $wpdb->query(
                
                    $wpdb->prepare(
                    
                        "DELETE FROM $table WHERE meta_key LIKE %s", 
                        $this->_base . $post_id . '%'
                    )
                );
                
                // reset scheduler
                
                update_post_meta($post_id,$this->_base . 'scheduled',0);
                
                // reset progress
                
                update_post_meta($post_id,$this->_base.'progress',0);

                if( $status == 'reschedule' ){
                    
                    // reset status
                    
                    update_post_meta($post_id,$this->_base.'process_status','running');
                }
            }
            else{
                
                $scheduled  = intval(get_post_meta($post_id,$this->_base.'scheduled',true)) > 0 ? 100 : 0;
                $processed  = intval(get_post_meta($post_id,$this->_base.'progress',true));
            }
            
            do_action('rewbe_task_saved',$task);
            
            $results = array(
                
                'status'    => $status == 'reschedule' ? 'running' : $status,
                'steps'     => $sc_steps,
                'scheduled' => $scheduled,
                'processed' => $processed,
            );
        }
        
        wp_send_json($results);
		wp_die();
    }
    
    public function render_task_filters(){
		
		if( current_user_can('edit_posts') && !empty($_GET['pid']) && isset($_GET['type']) ){
			
            $post_id = intval($_GET['pid']);
            
            $item_type = sanitize_title($_GET['type']);
			
            if( $post_id > 0 ){

                if( $post = get_post($post_id) ){
					
					$task = $this->get_task_meta($post_id);
					
					if( $post->post_type == 'post-type-task' ){
                        
						$filters = $this->get_post_type_filters($item_type,$task);
					}
					elseif( $post->post_type == 'taxonomy-task' ){
                        
                        $filters = $this->get_taxonomy_filters($item_type,$task);
					}
					elseif( $post->post_type == 'user-task' ){

                        $filters = $this->get_user_filters($item_type,$task);
					}
					elseif( $post->post_type == 'data-task' ){
						
						$filters = $this->get_data_filters($item_type,$task);
					}
					
					if( !empty($filters) ){
						
						foreach( $filters as $i => $filter ){
                            
                            if( $i > 0 ){
                                
                                $this->admin->display_meta_box_field($filter,$post);
                            }
                        }
					}
				}              
			}
		}
		
		wp_die();
	}
    
	public function render_task_process(){
		
		if( !empty($_POST['task']) && is_array($_POST['task']) && !empty($_POST['task']['post_ID']) ){
			
			$task = $this->sanitize_task_meta($_POST['task']);

			$post_id = intval($_POST['task']['post_ID']);
			
			$post = get_post($post_id);
			
			$total_items = $this->count_task_items($post->post_type,$task);
            
			// render fields

			$this->admin->display_field(array(
			
				'id'		=> $this->_base . 'matching',
				'type'		=> 'number',
				'data'		=> $total_items,
				'default'	=> 0,
				'disabled'	=> true,
				
			),$post);
			
			if( $total_items > 0 ){
				
				$html = '<button id="rew_preview_button" class="button">Preview</button>';
				
				$this->admin->display_field(array(
				
					'type'	=> 'html',
					'data'	=> $html,
					
				),$post);
			}
		}

		wp_die();
	}

	public function render_task_preview(){
		
		if( !empty($_POST['task']) && is_array($_POST['task']) && !empty($_POST['task']['post_ID']) ){
			
			$task = $this->sanitize_task_meta($_POST['task']);
			
			$page = intval($_POST['page']);
			
			$post_id = intval($_POST['task']['post_ID']);
			
			$post = get_post($post_id);
			
			$items = $this->retrieve_task_items($post->post_type,$task,100);
			
			foreach( $items as $i => $item ){
                
                if( $post->post_type == 'user-task' ){
                    
                    $item = $item->data;
                    
                    unset($item->user_pass,$item->user_activation_key);
                }
                elseif($post->post_type == 'post-type-task'){
                    
                    unset($item->post_password);
                }
                
                $item = (array) $item;
                
                if( $i === 0 ){
                    
                    $cols = array_keys($item);
                    
                    echo '<tr>';
                        
                        foreach( $cols as $col ){
                            
                            echo '<th>';
                            
                                echo $col;
                            
                            echo '</th>';
                        }
                        
                    echo '</tr>';
                }
                
                $values = array_values($item);
                
                echo '<tr>';
                    
                    foreach( $values as $j => $val ){
                        
                        $val = is_array($val) ? json_encode($val) : $val;

                        echo '<td>';
                        
                            if( $j === 0 ){
                                        
                                if( $post->post_type == 'post-type-task' ){
                                
                                    $item_url   = get_permalink($item['ID']);
                                }
                                elseif( $post->post_type == 'taxonomy-task' ){
                                    
                                    $item_url = get_term_link($item['term_id']);
                                }
                                
                                if( !empty($item_url) ){
                                    
                                    echo '<a href="'.esc_url($item_url).'" target="_blank">';
                                }
                                
                                    echo $val;
                                
                                if( !empty($item_url) ){
                                    
                                    echo '</a>';
                                }
                            }
                            else{
                                
                                $len = strlen($val);
                                
                                $maxlen = 50;
                                
                                if( $len > $maxlen ){
                                
                                    echo '<textarea style="width:100%;background:transparent;border:none;height:40px;overflow:hidden;">';
                                }
                                
                                echo $val;
                                    
                                if( $len > $maxlen ){
                                    
                                    echo '</textarea>';
                                }
                            }
                        
                        echo '</td>';
                    }
                    
                echo '</tr>';
            
                
                /*
                else{
                        
                    if( $post->post_type == 'post-type-task' ){
                        
                        $item_id    = $item->ID;
                        $item_url   = get_permalink($item_id);
                        $item_name  = $item->post_title;
                    }
                    elseif( $post->post_type == 'taxonomy-task' ){
                        
                        $item_id    = $item->term_id;					
                        $item_url   = get_term_link($item_id);
                        $item_name  = $item->name;
                    }
                }
                */
			}
		}

		wp_die();
	}
	
	public function render_task_progress(){
		
		if( !empty($_GET['pid']) && is_numeric($_GET['pid']) ){
			
			$task_id = intval($_GET['pid']);
			
			if( $post = get_post($task_id) ){
				
				$task = $this->get_task_meta($task_id);
                
                $task_type = $post->post_type;
                
                $call_method = $task[$this->_base.'call'];
        
				if( $call_method == 'ajax' ){
					
                    $prog = $this->process_task($task_type,$task);
				}
                else{
                    
                    if ( !wp_next_scheduled('rewbe_process_cron_task',array($task_id)) ){
                        
                        wp_schedule_event(time(),'one_minute','rewbe_process_cron_task',array($task_id));
                    }
                    
                    $prog = floatval(get_post_meta($task_id,$this->_base.'progress',true));
                }
                
                echo esc_html($prog);
			}
		}
		
		wp_die();
	}
    
    public function process_cron_task($task_id){
                
        $prog = 100;

        if( $post = get_post($task_id) ){
            
            $task_type = $post->post_type;
            
            $task = $this->get_task_meta($task_id);
            
            $prog = $this->process_task($task_type,$task);
        }
  
        if( $prog >= 100 ){
        
            wp_clear_scheduled_hook('rewbe_process_cron_task',array($task_id));
        }
    }
    
    public function process_task($task_type,$task){
        
        $task_id = $task['rewbe_id'];
        
        $per_process = apply_filters('rewbe_items_per_process',intval($task[$this->_base.'per_process']),$task);

        $scheduled = $task[$this->_base.'scheduled'];
        
        $action = $task[$this->_base.'action'];
        
        $prog = 100;
        
        if( $action != 'none' ){

            if( $task_type == 'post-type-task' ){
                
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
                    
                    $args = $this->parse_action_parameters($task_type,$task);
                    
                    // register default actions
                    
                    if( $action == 'edit_post_type' ){
                        
                        add_action('rewbe_do_post_edit_post_type',array($this,'edit_post_type'),10,2);
                    }	
                    elseif( $action == 'duplicate_post' ){
                        
                        add_action('rewbe_do_post_duplicate_post',array($this,'duplicate_post'),10,2);
                    }
                    elseif( $action == 'insert_content' ){
                        
                        add_action('rewbe_do_post_insert_content',array($this,'insert_content'),10,2);
                    }
                    elseif( $action == 'find_replace' ){
                        
                        add_action('rewbe_do_post_find_replace',array($this,'find_replace'),10,2);
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
                    elseif( $action == 'remove_meta' ){
                        
                        add_action('rewbe_do_post_remove_meta',array($this,'remove_post_meta'),10,2);
                    }
                    elseif( $action == 'rename_meta' ){
                        
                        add_action('rewbe_do_post_rename_meta',array($this,'rename_post_meta'),10,2);
                    }
                    elseif( strpos($action,'edit_tax_') === 0 ){
                        
                        $taxonomy =  substr($action,strlen('edit_tax_'));
                        
                        $args['taxonomy'] = $taxonomy; 
                        
                        add_action('rewbe_do_post_edit_tax_'.$taxonomy,array($this,'edit_post_taxonomy'),10,2);
                    }
                    elseif( $action == 'export_data' ){
                        
                        add_action('rewbe_do_post_export_data',array($this,'export_post_data'),10,4);
                    }
                    
                    foreach( $query->posts as $iteration => $post ){
                        
                        if( $action == 'run_multiple_tasks' ){
                            
                            $this->process_subtask($task_type,$task_id,$args,$post,$iteration);
                        }
                        else{
                            
                            apply_filters('rewbe_do_post_'.$action,$post,$args,$task,$iteration);
                        
                            delete_post_meta($post->ID,$this->_base.$task_id);
                        }
                    }
                }
            }
            elseif( $task_type == 'taxonomy-task' ){
                
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
                    
                    $args = $this->parse_action_parameters($task_type,$task);
                    
                    // register default actions
                    
                    if( $action == 'edit_parent' ){
                        
                        add_action('rewbe_do_term_edit_parent',array($this,'edit_term_parent'),10,2);
                    }						
                    elseif( $action == 'edit_meta' ){
                        
                        add_action('rewbe_do_term_edit_meta',array($this,'edit_term_meta'),10,2);
                    }
                    elseif( $action == 'remove_meta' ){
                        
                        add_action('rewbe_do_term_remove_meta',array($this,'remove_term_meta'),10,2);
                    }
                    elseif( $action == 'rename_meta' ){
                        
                        add_action('rewbe_do_term_rename_meta',array($this,'rename_term_meta'),10,2);
                    }
                    elseif( $action == 'delete_term' ){
                        
                        add_action('rewbe_do_term_delete_term',array($this,'delete_term'),10,2);
                    }
                    
                    foreach( $query->terms as $iteration => $term ){
                        
                        if( $action == 'run_multiple_tasks' ){
                            
                            $this->process_subtask($task_type,$task_id,$args,$term,$iteration);
                        }
                        else{   
                        
                            apply_filters('rewbe_do_term_'.$action,$term,$args,$task,$iteration);
                         
                            delete_term_meta($term->term_id,$this->_base.$task_id);
                        }
                    }
                }
            }
            elseif( $task_type == 'user-task' ){
                
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
                    
                    $args = $this->parse_action_parameters($task_type,$task);
                    
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
                    elseif( $action == 'remove_meta' ){
                        
                        add_action('rewbe_do_user_remove_meta',array($this,'remove_user_meta'),10,2);
                    }
                    elseif( $action == 'rename_meta' ){
                        
                        add_action('rewbe_do_user_rename_meta',array($this,'rename_user_meta'),10,2);
                    }
                    
                    foreach ( $users as $iteration => $user ){
                        
                        if( $action == 'run_multiple_tasks' ){
                            
                            $this->process_subtask($task_type,$task_id,$args,$user,$iteration);
                        }
                        else{
                            
                            apply_filters('rewbe_do_user_'.$action,$user,$args,$task,$iteration);
                        
                            delete_user_meta($user->ID,$this->_base.$task_id);
                        }
                    }
                }
            }
            elseif( $task_type == 'data-task' ){
                
                $files = $this->get_data_files($task_id);
                
                $remaining = max(0,( count($files) - 1 ) * $per_process );
                
                if( !empty($files) ){
                    
                    $source = $files[0];
                    //$source = $files[array_rand($files)]; 
                    
                    if( file_exists($source) && is_readable($source) ){
                        
                        $body = file_get_contents($source);
                        
                        if( $items = $this->parse_dataset($body,$task[$this->_base.'data_type']) ){
                            
                            $args = $this->parse_action_parameters($task_type,$task);
                            
                            // register default actions
                            
                            if( $action == 'import_post_type' ){
                            
                                add_action('rewbe_do_data_import_post_type',array($this,'import_post_data'),10,4);
                            }
                            elseif( $action == 'import_post_thumbnail' ){
                            
                                add_action('rewbe_do_data_import_post_thumbnail',array($this,'import_post_thumbnail'),10,4);
                            }
                            elseif( $action == 'import_image_gallery' ){
                            
                                add_action('rewbe_do_data_import_image_gallery',array($this,'import_image_gallery'),10,4);
                            }
                            
                            foreach( $items as $iteration => $item ){
                                
                                if( $action == 'run_multiple_tasks' ){
                                    
                                    $this->process_subtask($task_type,$task_id,$args,$item,$iteration);
                                }
                                else{
                                    
                                    apply_filters('rewbe_do_data_'.$action,$item,$args,$task,$iteration);
                                }
                            }
                        }
                        
                        $wp_filesystem = $this->get_filesystem();
                        
                        $wp_filesystem->delete($source);
                    }
                }
            }
            
            $prog = round( ( $scheduled - $remaining ) / $scheduled * 100,2);
        }
        
        update_post_meta($task_id,$this->_base.'progress',$prog);
        
        return $prog;
    }
    
    public function process_subtask($task_type,$task_id,$args,$object,$iteration){
        
        if( !empty($args['tasks']) ){
            
            $subtasks = array_filter(array_map('intval',$args['tasks']), function($id){
                
                return ( $id > 0 );
            });
            
            if( !empty($subtasks) ){
                
                $per_subprocess = !empty($args['per_process']) ? intval($args['per_process']) : 1;
                
                $prefix = '';
                $prev = 0;
                
                if( $task_type == 'post-type-task' ){
                    
                    $prefix = 'post_';
                    
                    $prev = intval(get_post_meta($object->ID,$this->_base.$task_id.'_prev_subtask',true));
                }
                elseif( $task_type == 'taxonomy-task' ){
                    
                    $prefix = 'term_';
                    
                    $prev = intval(get_term_meta($object->term_id,$this->_base.$task_id.'_prev_subtask',true));
                }
                elseif( $task_type == 'user-task' ){
                    
                    $prefix = 'user_';
                    
                    $prev = intval(get_user_meta($object->ID,$this->_base.$task_id.'_prev_subtask',true));
                }
                elseif( $task_type == 'data-task' ){
                    
                    
                }
                
                $is_next = empty($prev) ? true : false; 
                
                $last = end($subtasks);
                
                $subprocess = 1;
                
                foreach( $subtasks as $subtask_id ){
                    
                    if( !$is_next && $subtask_id == $prev ){
                        
                        $is_next = true;
                    }
                    elseif( $is_next == true ){
                        
                        $subtask = $this->get_task_meta($subtask_id);
                        
                        $subaction = $subtask[$this->_base.'action'];
                        
                        $subargs = $this->parse_action_parameters($task_type,$subtask);
                        
                        apply_filters('rewbe_do_'.$prefix.$subaction,$object,$subargs,$subtask,$iteration);
                        
                        $prev = $subtask_id;
                        
                        if( $subprocess == $per_subprocess ){
                            
                            break;
                        }
                        else{
                            
                            ++$subprocess;
                        }
                    }
                }
                
                if( $prev != $last ){
                    
                    if( $task_type == 'post-type-task' ){
                        
                        update_post_meta($object->ID,$this->_base.$task_id.'_prev_subtask',$prev);
                    }
                    elseif( $task_type == 'taxonomy-task' ){
                        
                        update_term_meta($object->term_id,$this->_base.$task_id.'_prev_subtask',$prev);
                    }
                    elseif( $task_type == 'user-task' ){
                        
                        update_user_meta($object->ID,$this->_base.$task_id.'_prev_subtask',$prev);
                    }
                    elseif( $task_type == 'data-task' ){
                        
                        
                    }
                }
                elseif( $task_type == 'post-type-task' ){
                    
                    delete_post_meta($object->ID,$this->_base.$task_id);
                
                    delete_post_meta($object->ID,$this->_base.$task_id.'_prev_subtask');
                }
                elseif( $task_type == 'taxonomy-task' ){
                    
                    delete_term_meta($object->term_id,$this->_base.$task_id);
                
                    delete_term_meta($object->term_id,$this->_base.$task_id.'_prev_subtask');
                }
                elseif( $task_type == 'user-task' ){
                    
                    delete_user_meta($object->ID,$this->_base.$task_id);
                
                    delete_user_meta($object->ID,$this->_base.$task_id.'_prev_subtask');
                }
                elseif( $task_type == 'data-task' ){
                    
                    
                }
            }
        }
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
				
				$sc_steps = $this->get_schedule_steps($post->post_type,$total_items);
                
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
					
                    $type = $task[$this->_base.'data_type'];
                    
					$actions = $this->get_data_actions($type,$task);
					
					if( !empty($actions[$action]) ){
						
                        if( $args = $this->parse_data_task_parameters($task) ){
                            
                            $items = $this->get_dataset($args);
                            
                            if( !empty($items) ){
                                
                                $per_process = apply_filters('rewbe_items_per_process',intval($task[$this->_base.'per_process']),$task);
                                
                                $pr_steps = ceil($this->sc_items / $per_process); 

                                $start = ( $pr_steps * ($step - 1) ) + 1; 

                                $end = $start + $pr_steps - 1;
                                
                                for( $pr_step = $start; $pr_step <= $end; $pr_step++ ){
                                
                                    $offset = ($pr_step - 1) * $per_process;
                                    
                                    if( $data = array_slice($items,$offset,$per_process) ){
                                    
                                        $this->put_task_data($post->ID,$data,$pr_step,$type);
                                    }
                                    else{
                                        
                                        break;
                                    }
                                }
                            }
                        }
                    }
				}
				
				$prog = ceil( $step / $sc_steps * 100 );
			}
				
			if( $prog == 100 ){
				
				// scheduled
				
				update_post_meta($task_id,$this->_base.'scheduled',$total_items);
			}
				
			echo esc_html($prog);
		}
		
		wp_die();
	}
	
    public function get_filesystem(){
        
        require_once ABSPATH . 'wp-admin/includes/file.php';

        global $wp_filesystem;
        
        if( empty($wp_filesystem) ){
            
            WP_Filesystem();
        }
        
        return $wp_filesystem;
    }
    
    public function put_task_data($task_id,$items,$pr_step,$type='json'){
    
        $wp_filesystem = $this->get_filesystem();
        
        $path = $this->get_data_path($task_id,true);
        
        if( $pr_step == 1 ){
            
            if( $files = $this->get_data_files($task_id) ){
                
                foreach( $files as $file ){
                    
                    $wp_filesystem->delete($file);
                }
            }
        }
        
        $content = json_encode($items,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

        $file_path = trailingslashit($path) . $pr_step;

        $wp_filesystem->put_contents($file_path,$content,FS_CHMOD_FILE);
        
        return $file_path;
    }
    
    public function get_data_path($task_id,$create=false){
        
        $upload = wp_upload_dir();
        
        $root = $upload['basedir'] . '/rewbe_data';
        
        $path = trailingslashit($root).$task_id;
        
        if( $create === true ){
            
            $wp_filesystem = $this->get_filesystem();

            if( !$wp_filesystem->is_dir($path) ){
                    
                if( !$wp_filesystem->is_dir($root) ){
                    
                    $wp_filesystem->mkdir($root,0755,true);
                }
                
                $wp_filesystem->mkdir($path,0755,true);
            }
        }
        
        return $path;
    }
        
    public function get_data_files($task_id){
        
        $files = [];

        if( $path = $this->get_data_path($task_id) ){
            
            $wp_filesystem = $this->get_filesystem();
            
            if( $file_list = $wp_filesystem->dirlist($path)){
                
                foreach( $file_list as $file_name => $file_info ){
                    
                    $files[] = trailingslashit($path) . $file_name;
                }
            }
        }

        natsort($files);

        return array_values($files); // Reset array keys
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
			
			if( !empty($args['filter_content']) && $args['filter_content'] == 'enabled' ){
			
				$postarr['post_content'] = apply_filters('the_content',$post->post_content);
			}
			else{
				
				$postarr['post_content'] = $post->post_content;
			}
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
											
											if( $term_copy = get_term_by('name',$term->name,$term->taxonomy) ){
												
												$term_slugs[] = $term_copy->slug;
											}
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
			
			return $post_ids;
		}
	}
    
    public function insert_content($object,$args){
		
		$position = !empty($args['position']) ? sanitize_title($args['position']) : 'end';

		$insert = !empty($args['insert']) ? sanitize_title($args['insert']) : 'post_content';
		
		$content = !empty($args['content']) ? sanitize_textarea_field($args['content']) : '';
		
		if( !empty($content) && !empty($insert) && !empty($position) ){
			
			if( $object instanceof WP_Post ){
            
                // update post
                
                $post_args = array(
                
                    'ID' => $object->ID
                );
                
                if( $insert == 'post_title' ){
                    
                    if( $position == 'end' ){
                    
                        $post_args['post_title'] = $object->post_title . $content;
                    }
                    else{
                        
                        $post_args['post_title'] = $content . $object->post_title;
                    }
                }
                elseif( $insert == 'post_content' ){
                    
                    if( $position == 'end' ){
                    
                        $post_args['post_content'] = $object->post_content . $content;
                    }
                    else{
                        
                        $post_args['post_content'] = $content . $object->post_content;
                    }
                }
                elseif( $insert == 'post_excerpt' ){
                    
                    if( $position == 'end' ){
                    
                        $post_args['post_excerpt'] = $object->post_excerpt . $content;
                    }
                    else{
                        
                        $post_args['post_excerpt'] = $content . $object->post_excerpt;
                    }
                }
                
                wp_update_post($post_args);
				
				$object = get_post($object->ID);
			}
			elseif ($object instanceof WP_Term) {

                $term_args = array();
                
                if( $insert == 'name' ){
                    
                    if( $position == 'end' ){
                    
                        $term_args['name'] = $object->name . $content;
                    }
                    else{
                        
                        $term_args['name'] = $content . $object->name;
                    }
                }
                elseif( $insert == 'description' ){
                    
                    if( $position == 'end' ){
                    
                        $term_args['description'] = $object->description . $content;
                    }
                    else{
                        
                        $term_args['description'] = $content . $object->description;
                    }
                }
                
                if (!empty($term_args)) {
                    
                    wp_update_term($object->term_id, $object->taxonomy, $term_args);
                }
                
				$object = get_term($object->term_id);
			}
			elseif ($object instanceof WP_User) {
	
                $user_args = array(
                
                    'ID' => $object->ID,
                );
                
                // TODO append prepend user info
                
                //wp_update_user($user_args);
                    
				$object = get_user($object->ID);
			}
		}
		
		return $object;
	}
	
	public function find_replace($object,$args){
		
		$fx = !empty($args['fx']) ? sanitize_title($args['fx']) : 'str_replace';
		
		if( $fx == 'preg_match' ){
			
			$match = !empty($args['match']) ? $this->sanitize_regex($args['match']) : '';
		}
		else{
			
			$match = !empty($args['match']) ? sanitize_text_field($args['match']) : '';
		}
		
		$rep_with = !empty($args['rep_with']) ? sanitize_text_field($args['rep_with']) : '';
		
		$contents = !empty($args['contents']) ? array_map('sanitize_title', $args['contents']) : array();
		
		$meta = !empty($args['meta']) ? array_map('sanitize_text_field', $args['meta']) : array();

		if( !empty($match) && !empty($fx) ){
			
			if( $object instanceof WP_Post ){
				
				if( !empty($contents) ){
					
					// update post
					
					$post_args = array(
					
						'ID' => $object->ID
					);
					
					if( in_array('post_title',$contents) ){
						
						$post_args['post_title'] = $this->replace_in_content($match,$rep_with,$object->post_title,$fx);
					}
					
					if( in_array('post_content',$contents) ){
						
						$post_args['post_content'] = $this->replace_in_content($match,$rep_with,$object->post_content,$fx);
					}
					
					if( in_array('post_excerpt',$contents) ){
						
						$post_args['post_excerpt'] = $this->replace_in_content($match,$rep_with,$object->post_excerpt,$fx);
					}
					
					wp_update_post($post_args);
				}
				
				if( !empty($meta) ){
					
					// update post meta
					
					foreach( $meta as $meta_name ){
						
						$value = $this->replace_in_content($match,$rep_with,get_post_meta($object->ID,$meta_name,true),$fx);
					
						update_post_meta($object->ID,$meta_name,$value);
					}
				}
				
				$object = get_post($object->ID);
			}
			elseif ($object instanceof WP_Term) {
				
				if( !empty($contents) ){
					
					$term_args = array();
					
					if( in_array('name',$contents) ){
						
						$term_args['name'] = $this->replace_in_content($match,$rep_with,$object->name,$fx);
					}

					if( in_array('description',$contents) ){
						
						$term_args['description'] = $this->replace_in_content($match,$rep_with,$object->description,$fx);
					}
					
					if (!empty($term_args)) {
						
						wp_update_term($object->term_id, $object->taxonomy, $term_args);
					}
				}
				
				if( !empty($meta) ){
					
					// update term meta
					
					foreach( $meta as $meta_name ){
						
						$value = $this->replace_in_content($match,$rep_with,get_term_meta($object->term_id,$meta_name,true),$fx);
					
						update_term_meta($object->term_id,$meta_name,$value);
					}
				}
				
				$object = get_term($object->term_id);
			}
			elseif ($object instanceof WP_User) {
				
				if( !empty($contents) ){
					
					$user_args = array(
					
						'ID' => $object->ID,
					);
					
					if( in_array('nickname',$contents) ){
						
						$user_args['nickname'] = $this->replace_in_content($match,$rep_with,$object->nickname,$fx);
					}

					if( in_array('first_name',$contents) ){
						
						$user_args['first_name'] = $this->replace_in_content($match,$rep_with,$object->first_name,$fx);
					}
					
					if( in_array('last_name',$contents) ){
						
						$user_args['last_name'] = $this->replace_in_content($match,$rep_with,$object->last_name,$fx);
					}

					if( in_array('description',$contents) ){
						
						$user_args['description'] = $this->replace_in_content($match,$rep_with,$object->description,$fx);
					}
					
					if( in_array('user_url',$contents) ){
						
						$user_args['user_url'] = $this->replace_in_content($match,$rep_with,$object->user_url,$fx);
					}
					
					wp_update_user($user_args);
				}
				
				if( !empty($meta) ){
					
					// update user meta
					
					foreach( $meta as $meta_name ){
						
						$value = $this->replace_in_content($match,$rep_with,get_user_meta($object->ID,$meta_name,true),$fx);
					
						update_user_meta($object->ID,$meta_name,$value);
					}
				}
				
				$object = get_user($object->ID);
			}
		}
		
		return $object;
	}
	
	public function replace_in_content($match,$rep_with,$content,$fx){
		
		switch($fx){
			
			case 'str_replace':
			
				return str_replace($match,$rep_with,$content);
			
			case 'str_ireplace':
			
				return str_ireplace($match,$rep_with,$content);
			
			case 'preg_replace':
			
				if( @preg_match($match, '') === false ){
					
					throw new InvalidArgumentException("Invalid regex pattern: $match");
				}
				
				return preg_replace($match,$rep_with,$content);
			
			default:
			
				throw new InvalidArgumentException("Invalid function type: $fx");
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
			
			foreach( $args['data']['key'] as $i => $key ){
				
				if( isset($args['data']['value'][$i]) ){
					
					$key = sanitize_title($key);

					$value = sanitize_text_field($args['data']['value'][$i]);
					
					delete_post_meta($post->ID,$key,$value);
				}
			}
		}
	}
	
	public function rename_post_meta($post,$args){
		
		if( !empty($args['from']) && !empty($args['to']) ){
			
			$from = sanitize_text_field($args['from']);
			
			$to = sanitize_text_field($args['to']);
			
			 if( metadata_exists('post', $post->ID, $from) ){
				
				$value = get_post_meta($post->ID,$from,true);
				
				update_post_meta($post->ID,$to,$value);

				delete_post_meta($post->ID,$from);
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
    
    public function get_csv_separator_options(){
        
        return array(
        
            'semicolon' => 'Semicolon',
            'comma'     => 'Comma',
        );
    }
    
    public function get_current_csv_separator(){

        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

        $semicolon_locales = ['fr', 'de', 'it', 'es', 'nl', 'pl', 'sv', 'da', 'fi', 'pt', 'ru'];

        $languages = explode(',', $accept_language);
        $scores = [];

        foreach ($languages as $lang) {
            
            if (strpos($lang,';q=') !== false){
                
                list($lang_code,$q_value) = explode(';q=',$lang);
                
                $priority = (float) $q_value;
            } 
            else {
                
                $lang_code = trim($lang);
                
                $priority = 0.5; // Default q=0.5 if not specified
            }
            
            $lang_base = explode('-', $lang_code)[0];

            // Sum priority scores for each language
            
            if (!isset($scores[$lang_base])) {
                
                $scores[$lang_base] = 0;
            }
            
            $scores[$lang_base] += $priority;
        }

        arsort($scores);
        
        $top_language = array_key_first($scores);

        foreach ($semicolon_locales as $locale) {
            
            if( stripos($top_language, $locale) === 0 ){
                
                return 'semicolon';
            }
        }

        return 'comma';
    }
    
    public function import_post_data($data,$args,$task,$iteration=0){
        
        if( !empty($args['fields']) && !empty($args['fields']['key']) && !empty($args['fields']['value']) ){
            
            $keys = array_keys($data);
            
            $primary = intval($args['primary']);
            
            $primary_key = $keys[$primary];
            
            $duplicate = !empty($args['existing']) ? sanitize_title($args['existing']) : 'skip';
            
            $mapping = array_combine($args['fields']['key'],$args['fields']['value']);
            
            $post   = array();
            $meta   = array();
            $terms  = array();
            
            $existing_id = null;
            
            foreach( $mapping as $key => $type ){
                
                if( $type == 'meta' ){
                    
                    $meta_name = (strpos($key, ':') !== false) ? explode(':',$key,2)[1] : $key;
                    
                    $meta_name = sanitize_text_field($meta_name);
                    
                    $meta_value = sanitize_meta($meta_name,$data[$key],'post');
                    
                    $meta[$meta_name] = $meta_value;
                
                    if( $key == $primary_key ){
                        
                        $query = new WP_Query(array(
                            
                            'post_type'         => 'any',
                            'meta_key'          => $meta_name,
                            'meta_value'        => $meta_value,
                            'posts_per_page'    => 1
                        ));
                        
                        if( $query->have_posts() ) {
                            
                            $existing_id = $query->posts[0]->ID;
                        }
                    }
                }
                elseif( strpos($type,'term_') === 0  ){
                    
                    $taxonomy = (strpos($key, ':') !== false) ? explode(':',$key,2)[1] : $key;
                    
                    if( $type == 'term_id' ){
                        
                        $value = intval($data[$key]);
                        
                        $term = get_term_by('id', $value, $taxonomy);
                    }
                    elseif( $type == 'term_slug' ){
                        
                        $value = sanitize_title($data[$key]);
                        
                        $term = get_term_by('slug',$value,$taxonomy);
                    }
                    elseif( $type == 'term_name' ){
                        
                        $value = sanitize_text_field($data[$key]);
                        
                        $term = get_term_by('name', $value, $taxonomy);
                    }
                    
                    if( !empty($term) && !is_wp_error($term) ){
                        
                        $terms[] = $term;
                        
                        if( $key == $primary_key ){
                            
                            // TODO maybe prevent more than one item per term??
                        }
                    }
                }
                else{
                    
                    $value = self::sanitize_post_field($type,$data[$key]);
                    
                    $post[$type] = $value;
                    
                    if( $key == $primary_key ){
                        
                        if( $type == 'ID' ){
                            
                            if ( $existing_post = get_post($value) ) {
                                
                                $existing_id = $existing_post->ID;
                            }
                        } 
                        elseif ($type == 'post_name') {
                            
                            $query = new WP_Query(array(
                                
                                'name'              => $value,
                                'post_type'         => 'any',
                                'posts_per_page'    => 1
                            ));
                            
                            if( $query->have_posts() ){
                                
                                $existing_id = $query->posts[0]->ID;
                            }
                        }
                    }
                }
            }
            
            if( empty($post['post_type']) ){
                
                // TODO set default post type
                
                $post['post_type'] = 'post';
            }
            
            if( empty($post['post_status']) ){
                
                // TODO set default post status
                
                $post['post_status'] = 'draft';
            }
            
            if( empty($post['post_author']) ){
                
                // TODO set default post author
                
                $post['post_author'] = 0;
            }
            
            $post_id = null;
            
            if( $existing_id ){
                
                if( $duplicate == 'overwrite' ){
                    
                    $post['ID'] = $existing_id;
                
                    $post_id = wp_update_post($post);
                }
            } 
            else{
                
                $post_id = wp_insert_post($post);
            }
           
            if( !empty($post_id) ){
                
                foreach( $meta as $key => $value){
                    
                    update_post_meta($post_id,$key,$value);
                }
                
                foreach( $terms as $term ) {
                    
                    wp_set_object_terms($post_id,$term->term_id,$term->taxonomy,true);
                }
            }
        }
    }
    
    public function import_post_thumbnail($data,$args,$task,$iteration=0){
        
        if( !empty($data['path']) && !empty($args['name']) && !empty($args['value']) && !empty($args['type']) ){
            
            // get post
            
            $path = realpath($data['path']);
            
            $filename = pathinfo($path, PATHINFO_FILENAME);
            
            $meta_name = sanitize_text_field($args['name']);
            
            $meta_value = str_replace('{%FILENAME%}',$filename,sanitize_text_field($args['value']));
            
            $post_type = sanitize_text_field($args['type']);
            
            $existing = !empty($args['existing']) ? sanitize_title($args['existing']) : 'skip';
            
            $posts = get_posts(array(
                
                'post_status'   => 'any',
                'post_type'     => $post_type,
                'numberposts'   => -1,
                'meta_query'    => array(
                
                    array(
                    
                        'key'      => $meta_name,
                        'value'    => $meta_value,
                        'compare'  => '=',
                    ),
                ),
            ));
            
            if( !empty($posts) ){
                
                foreach( $posts as $post ){
                       
                    $this->put_post_image($post,$path,'thumbnail',$existing);
                }
            }
        }
    }

    public function import_image_gallery($data,$args,$task,$iteration=0){
        
        if( !empty($data['path']) && !empty($args['name']) && !empty($args['value']) && !empty($args['type']) ){
            
            // get post
            
            $path = realpath($data['path']);
            
            $filename = pathinfo($path,PATHINFO_FILENAME);
            
            if( str_contains($filename, '_') ){
                
                $parts = explode('_', $filename);
                
                $index = array_pop($parts);

                if( is_numeric($index) ){
                    
                    $filename = implode('_',$parts);
                }
            }
            
            $meta_name = sanitize_text_field($args['name']);
            
            $meta_value = str_replace('{%FILENAME%}',$filename,sanitize_text_field($args['value']));
            
            $gallery_name = sanitize_text_field($args['gallery']);
            
            $post_type = sanitize_text_field($args['type']);
            
            $posts = get_posts(array(
                
                'post_status'   => 'any',
                'post_type'     => $post_type,
                'numberposts'   => -1,
                'meta_query'    => array(
                
                    array(
                    
                        'key'      => $meta_name,
                        'value'    => $meta_value,
                        'compare'  => '=',
                    ),
                ),
            ));
            
            if( !empty($posts) ){
                
                foreach( $posts as $post ){
                    
                    $this->put_post_image($post,$path,$gallery_name,$index);
                }
            }
        }
    }
    
    public function put_post_image($post,$path,$location='thumbnail',$insert='skip'){
        
        if( is_numeric($post) ){
            
            $post = get_post($post);
        }
        
        if( !empty($post) && is_object($post)  ){
            
            if( file_exists($path) ){
                
                list($type,$ext) = explode('/',mime_content_type($path));
                
                if( $type == 'image' ){
                    
                    $filename = basename($path);
                    
                    $attachments = get_posts( array(
                    
                        'post_type'      => 'attachment',
                        'post_status'    => 'inherit',
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                        'meta_query'     => array(
                        
                            array(
                            
                                'key'   => $this->_base . 'imported_filename',
                                'value' => $filename,
                            )
                        ),
                    ));
                    
                    if( !empty($attachments) ){
                    
                        $attach_id = intval($attachments[0]);
                    }
                    else{
                        
                        if ( !function_exists('media_handle_sideload') ) {
                            
                            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
                            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
                        }			
            
                        $post_name = sanitize_title($post->post_title);
                        
                        $attach_id = media_handle_sideload( array(
                        
                            'name' 		=> $post_name . '.' . $ext,
                            'tmp_name' 	=> $path,
                        ), 
                        null, 
                        null, 
                        array(
                        
                            'post_title' 	=> $post->post_title,
                            'post_content'  => $post->post_content,
                            
                        ));
                        
                        if( !is_wp_error($attach_id) ){
                            
                            update_post_meta($attach_id,$this->_base.'imported_filename',$filename);
                        }
                        
                        if( function_exists('gc_collect_cycles') ){
                        
                            gc_collect_cycles(); // Force garbage collection
                        }
                    }
                    
                    if( is_wp_error($attach_id) ){
                        
                        $error = $attach_id->errors;
                       
                        if( isset($error['upload_error']) ){
                            
                            // skip
                        }
                        else{
                            
                            print_r($error);
                            die;
                        }
                    }
                    elseif( is_numeric($attach_id) && !empty($attach_id) ){
                        
                        if( $location == 'thumbnail' ){
                            
                            if( $insert == 'replace' || !has_post_thumbnail($post->ID) ){
                     
                                set_post_thumbnail($post->ID,$attach_id);
                            }
                        }
                        else{
                            
                            $gallery = get_post_meta($post->ID, $location, true);

                            $ids = !empty($gallery) && is_string($gallery) ? explode(',',$gallery) : array();
                            
                            $index = intval($insert);
                            
                            if( $index >= count($ids) ){
                                
                                $ids = array_pad($ids,$index + 1,''); // Pad with empty strings up to the index
                            }
                            
                            $ids[$index] = $attach_id;
                            
                            update_post_meta($post->ID,$location, implode(',',$ids));
                        }
                    }
                }
            }
        }
    }
    
    public function debug_image_processing($path) {
        
        $info = [
            'file_details' => [
                'path' => $path,
                'exists' => file_exists($path),
                'readable' => is_readable($path),
                'file_size' => filesize($path)
            ],
            'getimagesize' => getimagesize($path)
        ];

        // Log file details
        error_log('File Path: ' . $path);
        error_log('File Exists: ' . ($info['file_details']['exists'] ? 'Yes' : 'No'));
        error_log('File Readable: ' . ($info['file_details']['readable'] ? 'Yes' : 'No'));
        error_log('File Size: ' . $info['file_details']['file_size'] . ' bytes');

        // Imagick processing if available
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick($path);
                $info['imagick'] = [
                    'geometry' => $imagick->getImageGeometry(),
                    'format' => $imagick->getImageFormat(),
                    'color_space' => $imagick->getColorSpace(),
                    'compression' => $imagick->getImageCompression()
                ];
                error_log('Imagick Geometry: ' . print_r($info['imagick']['geometry'], true));
            } catch (Exception $e) {
                $info['imagick_error'] = $e->getMessage();
                error_log('Imagick Error: ' . $e->getMessage());
            }
        }

        // File header check
        $handle = fopen($path, 'rb');
        $header = fread($handle, 256);
        fclose($handle);
        
        $info['file_header'] = [
            'hex' => bin2hex($header),
            'first_20_chars' => substr($header, 0, 20)
        ];
        
        error_log('File Header (hex): ' . $info['file_header']['hex']);

        return $info;
    }
	public function export_post_data($post,$args,$task,$iteration=0) {
        
        if ( !empty($args['format']) && !empty($args['path'])  && !empty($args['filename']) && !empty($args['fields']) && is_array($args['fields']) ){
            
            $format     = sanitize_title($args['format']);
            $path       = trailingslashit(wp_normalize_path($args['path']));
            $filename   = sanitize_title($args['filename']);
            
            $prog = isset($task['rewbe_progress']) ? floatval($task['rewbe_progress']) : 0;
           
            $wp_filesystem = $this->get_filesystem();
            
            $parent = $path;

            $paths_to_create = [];

            // Traverse up the directory tree to find the first existing parent
            
            while( !$wp_filesystem->exists($parent) && $parent !== '/' && $parent !== '.' && $parent !== '' ){
                
                $paths_to_create[] = $parent;
                
                $parent = dirname($parent);
            }

            // Create directories in order
            
            while( !empty($paths_to_create) ){
                
                $dir_to_create = array_pop($paths_to_create);
                
                $wp_filesystem->mkdir($dir_to_create);
            }
            
            if( !$wp_filesystem->exists($path) ){
                
                die('Path could not be created: ' . $path);
            }
            
            if ( $format == 'csv' ) {
                
                $filename .= '.csv';
                
                $file_path = $path . $filename;
                
                if( $wp_filesystem->exists($file_path) ){
                    
                    if( $prog == 0 && $iteration == 0 ){
                        
                        if( !$wp_filesystem->delete($file_path) ){
                            
                            die('Error deleting existing file in: '.$file_path);
                        }
                        
                        $is_new = true;
                    }
                    else{
                        
                        $is_new = false;
                    }
                }
                else{
                    
                    $is_new = true;
                }

                $separator  = isset($args['separator']) && sanitize_title($args['separator']) == 'semicolon' ? ';' : ',';
            
                $enclosure = "\"";

                $post_data  = array();
                $fields     = array();
                
                // export fields
                
                foreach( $args['fields'] as $field ) {
                    
                    if( $field = sanitize_text_field($field) ){
                        
                        $fields[]   = $field;
                        $post_data[] = isset($post->$field) ? $this->sanitize_csv_field($post->$field,$separator,$enclosure) : '';
                    }
                }
                
                // export urls
                
                $urls = !empty($args['urls']) ? array_map('sanitize_title', $args['urls']) : array();
                
                $options = $this->get_post_url_options();
                
                if( !empty($urls) ){
                    
                    foreach( $urls as $type ){
                        
                        if( isset($options[$type]) ){
                            
                            $url = '';
                            
                            if( $type == 'permalink' ){
                                
                                $url = apply_filters('rew_prod_url',get_permalink($post));
                            }
                            elseif( $type == 'thumbnail' ){
                                
                                $url = apply_filters('rew_prod_url',get_the_post_thumbnail_url($post,'full'));
                            }
                            
                            $fields[] = $type;
                            
                            $post_data[] = apply_filters('rew_prod_url',$url);
                        }
                    }
                }
                
                // export metadata
                
                $meta = !empty($args['meta']['value']) ? array_map('sanitize_text_field', $args['meta']['value']) : array();
                
                if( !empty($meta) ){
                    
                    $metadata = get_post_meta($post->ID);
                    
                    foreach( $meta as $key ){
                        
                        $fields[] = $key;
                        
                        $post_data[] = isset($metadata[$key][0]) ? $this->sanitize_csv_field($metadata[$key][0],$separator,$enclosure) : '';
                    }
                }
                
                $file_handle = fopen($file_path,'a');

                if( $is_new ){
                    
                    fwrite($file_handle, "\xEF\xBB\xBF");
                    
                    fputcsv($file_handle, $fields,$separator,$enclosure);
                    
                    //fputs($file_handle,implode($separator,$fields).PHP_EOL);
                }
                
                fputcsv($file_handle, $post_data,$separator,$enclosure);
                
                //fputs($file_handle,implode($separator,$post_data).PHP_EOL);
                
                fclose($file_handle);   
            }
        }
    }
    
    private function sanitize_csv_field($str,$separator,$enclosure) {

        $str = (string)$str;
        
        // Remove leading and trailing spaces using regex
        
        $str = preg_replace('/^[\s]+|[\s]+$/u', '', $str);
        
        // Replace none breaking space
        
        $str = str_replace("\xC2\xA0", " ", $str); // UTF-8 NBSP
        //$str = str_replace("&nbsp;", " ", $str);   // HTML entity
        
        // Escape breaking space
        /*
        $str = preg_replace(
            ["/\r/", "/\n/", "/\t/", "/\f/", "/\v/"], 
            ["\\r", "\\n", "\\t", "\\f", "\\v"], 
            $str
        );
        */
        
        return $str;
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
	
	public function rename_term_meta($term,$args){
		
		if( !empty($args['from']) && !empty($args['to']) ){
			
			$from = sanitize_text_field($args['from']);
			
			$to = sanitize_text_field($args['to']);
			
			 if( metadata_exists('term', $term->term_id, $from) ){
				
				$value = get_post_meta($term->term_id,$from,true);
				
				update_term_meta($term->term_id,$to,$value);

				delete_term_meta($term->term_id,$from);
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
		
        if( !empty($args['data']['key']) ){

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
	
	public function rename_user_meta($user,$args){

		if( !empty($args['from']) && !empty($args['to']) ){

			$from = sanitize_text_field($args['from']);
			
			$to = sanitize_text_field($args['to']);

			if( metadata_exists('user', $user->ID, $from) ){

				$value = get_user_meta($user->ID, $from, true);

				update_user_meta($user->ID, $to, $value);

				delete_user_meta($user->ID, $from);
			}
		}
	}

	public function count_task_items($task_type,$task){
		
		$items = 0;
		
		if( $task_type == 'post-type-task' ){
			
			if( $args = $this->parse_post_task_parameters($task) ){
				
				$query = new WP_Query($args);
				
				$items = $query->found_posts;
			}
		}
		elseif( $task_type == 'taxonomy-task' ){
			
			if( $args = $this->parse_term_task_parameters($task) ){

				$items = intval(wp_count_terms($args));
			}
		}
		elseif( $task_type == 'user-task' ){
			
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
		elseif( $task_type == 'data-task' ){
            
            if( $args = $this->parse_data_task_parameters($task) ){
                
                $items = count($this->get_dataset($args));
            }
		}
		
		return $items;
	}
    
    public function get_schedule_steps($task_type,$total){
        
        return ceil($total/$this->sc_items);
    }
    
    public function parse_data_task_parameters($task,$number=-1,$paged=1,$fields='all'){
        
        $args = array(
        
            'number'    => $number,
            'paged'     => $paged,
            'fields'    => $fields,
            'query'     => array(),
        );
        
        if( !empty($task[$this->_base.'data_source']) ){
            
            $args['type'] = !empty($task[$this->_base.'data_type']) ? sanitize_title($task[$this->_base.'data_type']) : 'json';
            
            if( $args['type'] == 'rest' ){
                
                $args['source'] = sanitize_url($task[$this->_base . 'data_source']);
                
                $args['method'] = !empty($task[$this->_base.'data_method']) ? sanitize_title($task[$this->_base.'data_method']) : 'get';
                
                $args['timeout'] = !empty($task[$this->_base.'data_timeout']) ? intval($task[$this->_base.'data_timeout']) : 15;
            }
            else{
                
                $args['source'] = realpath($task[$this->_base . 'data_source']);
            }            
        }
        
        return $args;
    }
    
    public function get_dataset($args){
        
        $data = array();
        
        if( !empty($args['source']) ){
           
            $source = $args['source'];
            
            $type = $args['type'];
            
            $number = $args['number'];
            
            $paged = $args['paged'];
            
            $wp_filesystem = $this->get_filesystem();
            
            $data =array();
            
            if( $type == 'directory' ){
                
                $files = $wp_filesystem->dirlist($source);
                
                ksort($files, SORT_NATURAL);
                
                foreach( $files as $file_name => $file_info ){
                    
                    $data[] = array(
                    
                        'path' => trailingslashit($source) . $file_name,
                    );
                }
            }
            else{
                
                if( $type == 'rest' ){
                     
                    $method = $args['method'];
                    
                    $timeout = $args['timeout'];
                
                    $query = $args['query'];
                
                    if( $method == 'get' ){
                        
                        if( !empty($query) ){
                            
                            $source = add_query_arg($query,$source);
                        }
                        
                        $response = wp_remote_get($source,array(
                        
                            'timeout' => $timeout,
                        ));
                    }
                    elseif( $method == 'post' ){
                       
                        $response = wp_remote_post($source, array(
                        
                            'body'    => $query,
                            'timeout' => $timeout,
                        ));
                    }
                    
                    if( !empty($response) ){
                        
                        if( !is_wp_error($response) ){
                            
                            $body = wp_remote_retrieve_body($response);
                        }
                    }
                }
                elseif( file_exists($source) && is_readable($source) ){
                    
                    $body = file_get_contents($source);
                }
               
                if( !empty($body) ){
                    
                    $data = $this->parse_dataset($body,$type);
                }
            }

            if( !empty($data) && $number > 0 ){
                
                $offset = ($paged - 1) * $number;

                $data = array_slice($data,$offset,$number);
            }
        }
        
        return $data;
    }
    
    public function parse_dataset($body,$type){
        
        $data = array();
        
        if( $type == 'csv' ){
            
            $rows = array_map('str_getcsv',explode(PHP_EOL,$body));

            $rows = array_filter($rows);

            if( !empty($rows) ){
                   
                $headers = array_shift($rows);

                foreach( $rows as $row ){
                    
                    $data[] = array_combine($headers,$row);
                }
            }
        }
        else{
            
            $data = json_decode($body,true);
        }
        
        return $data;
    }
    
	public function retrieve_task_items($type,$task,$number=10,$paged=1,$fields='all'){
		
		$items = 0;
		
		if( $type == 'post-type-task' ){
			
			if( $args = $this->parse_post_task_parameters($task,$number,$paged,$fields) ){
				
				$query = new WP_Query($args);
				
				$items = $query->posts;
			}
		}
		elseif( $type == 'taxonomy-task' ){
			
			if( $args = $this->parse_term_task_parameters($task,$number,$paged,$number,$fields) ){

				$items = get_terms($args);
			}
		}
		elseif( $type == 'user-task' ){
			
			if( $args = $this->parse_user_task_parameters($task,$number,$paged,$fields) ){
				
				$query = new WP_User_Query($args);
				
				$items = $query->get_results();
			}
		}
		elseif( $type == 'data-task' ){
			
            if( $args = $this->parse_data_task_parameters($task,$number,$paged,$fields) ){
            
                $items = $this->get_dataset($args);
            }
		}
		
		return $items;
	}
	
	public function parse_post_task_parameters($task,$number=1,$paged=0,$fields='ids'){
		
		if( $post_type = get_post_type_object(sanitize_text_field($task['rewbe_post_type'])) ){
			
			$args = array(
				
				'post_type'				=> $post_type->name,
				'posts_per_page' 		=> $number,
				'paged' 				=> $paged,
				'order'					=> 'ASC',
				'orderby'				=> 'ID',
				'fields'				=> $fields,
				'ignore_sticky_posts' 	=> true,
			);
			
			// filter search
			
			if( !empty($task['rewbe_search']) ){
                
                if( !empty($task['rewbe_search']['s']) ){
                    
                    $task['rewbe_search']['s'] = array_map('sanitize_text_field',$task['rewbe_search']['s']);
                }
                
                if( !empty($task['rewbe_search']['in']) ){
                    
                    $task['rewbe_search']['in'] = array_map('sanitize_title',$task['rewbe_search']['in']);
                }
                
                if( !empty($task['rewbe_search']['op']) ){
                    
                    $task['rewbe_search']['op'] = array_map('sanitize_title',$task['rewbe_search']['op']);
                }
                
				$args['rewbe_search'] = $task['rewbe_search'];

                $args['rewbe_search_rel'] = isset($task['rewbe_search_rel']) && sanitize_title($task['rewbe_search_rel']) == 'or' ? 'OR' : 'AND';
            }
			
			// filter post_status
            
            if( !empty($task['rewbe_post_status']) && is_array($task['rewbe_post_status']) ){

				$args['post_status'] = array_map('sanitize_text_field', $task['rewbe_post_status']);
			}
            else{
            
                $args['post_status'] = apply_filters('rewbe_default_post_status','all',$post_type->name);
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

    public function filter_posts_where($where, $query) {
        
        global $wpdb;
        
        if( $keywords = $query->get('rewbe_search') ){
           
            $relation = $query->get('rewbe_search_rel');
            
            if( is_string($keywords) ){

                $where .= $this->parse_post_task_search(array(
                    
                    's'     => [$keywords],
                    'in'    => ['post_content'],
                    'op'    => ['like'],
                    
                ),$search_column,$relation);
            }
            elseif( is_array($keywords) ){
                
                $where .= $this->parse_post_task_search($keywords,$relation);
            }
        }

        return $where;
    }
        
    public function parse_post_task_search( $search_data, $relation = 'AND' ) {
        
        global $wpdb;

        $search = '';

        if ( !isset($search_data['s']) || !isset($search_data['op']) || !is_array($search_data['s']) || !is_array($search_data['op']) ) {
            
            return $search;
        }

        $search_conditions = [];
        
        foreach ( $search_data['s'] as $e => $term ) {
            
            if( !empty($term) ){
                
                $operation = isset($search_data['op'][$e]) && $search_data['op'][$e] == 'not-like' ? 'NOT LIKE' : 'LIKE';
                
                $search_column = isset($search_data['in'][$e]) ? $search_data['in'][$e] : 'post_content';
                
                $search_conditions[] = $wpdb->prepare("{$wpdb->posts}.$search_column $operation %s",'%' . $term . '%');
            }
        }
        
        if( !empty($search_conditions) ){
        
            $search = !empty($search_conditions) ? " AND (" . implode(" $relation ", $search_conditions) . ")" : '';
        }
        
        return $search;
    }
    
	public function parse_term_task_parameters($task,$number=1,$paged=0,$per_page=10,$fields='ids'){
		
		$taxonomy = sanitize_text_field($task['rewbe_taxonomy']);
		
		$args = array(
			
			'taxonomy'		=> $taxonomy,
			'number' 		=> $number,
			'offset ' 		=> $paged > 0 ? ($paged - 1) * $per_page : 0,
			'order'			=> 'ASC',
			'orderby'		=> 'id',
			'fields'		=> $fields,
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

	public function parse_user_task_parameters($task,$number=-1,$paged=1,$fields='ids'){
		
		$args = array(
			
			'number' 		=> $number,
			'order'			=> 'ASC',
			'orderby'		=> 'ID',
			'fields'		=> $fields,
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
		
		if( current_user_can('edit_posts') && !empty($_POST['task']) && !empty($_POST['pid']) && !empty($_POST['type']) && !empty($_POST['ba']) ){

			if( $bulk_action = sanitize_title($_POST['ba']) ){	
                
                $post_id = intval($_POST['pid']);
				
                if( $post = get_post($post_id) ){
                    
                    $task_type = $post->post_type;
                    
                    $item_type = sanitize_title($_POST['type']);

                    $task = $this->sanitize_task_meta($_POST['task']);
                    
                    if( $field = $this->get_actions_field($task_type,$item_type,$task,$bulk_action) ){
                        
                        $this->admin->display_meta_box_field($field,$post);
                    }
                   
					if( $actions = $this->get_task_actions($task_type,$item_type,$task) ){
						
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
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning of Rew_Bulk_Editor is forbidden', 'bulk-task-editor' ), esc_attr( $this->_version ) );

	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of Rew_Bulk_Editor is forbidden', 'bulk-task-editor' ), esc_attr( $this->_version ) );
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
