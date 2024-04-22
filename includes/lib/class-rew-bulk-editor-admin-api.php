<?php
/**
 * Post type Admin API file.
 *
 * @package REW Bulk Editor/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin API class.
 */
class Rew_Bulk_Editor_Admin_API {

	/**
	 * Constructor function
	 */
	public function __construct($parent) {
		
		$this->parent = $parent;
		
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );
	}

	/**
	 * Generate HTML for displaying fields.
	 *
	 * @param  array   $data Data array.
	 * @param  object  $post Post object.
	 * @param  boolean $echo  Whether to echo the field HTML or return it.
	 * @return string
	 */
	public function display_field( $data = array(), $post = null, $echo = true ) {

		// Get field info.
		if ( isset( $data['field'] ) ) {
			$field = $data['field'];
		} else {
			$field = $data;
		}
		
		// Check for prefix on option name.
		
		$option_name = ( isset( $data['prefix'] ) ? $data['prefix'] : '' ) . ( !empty($field['name']) ? $field['name'] : $field['id']);

		// Get default
			
		$default = isset($field['default']) ? $field['default'] : null;
			
		// Get saved data
		
		$data = '';
			
		if ( !empty( $field['data'] ) ) {
			
			$data = $field['data'];
		}
		elseif ( $post ) {

			// Get saved field data.
			
			$data = get_post_meta( $post->ID, $option_name, true );
			
			if( $field['type'] == 'terms' && !empty($field['taxonomy']) && is_array($data) ){
				
				$data = get_terms( array(
				
					'taxonomy' 	=> $field['taxonomy'],
					'include' 	=> array_map('intval',$data),
				) );
			}
		} 
		else {

			// Get saved option.
			
			$data = get_option( $option_name,$default );
		}

		// Show default data if no option saved and default is supplied

		if( $data === '' && !is_null($default) ) {
			
			$data = $default;
		} 
		elseif( $data === false ) {
			
			$data = '';
		}
		
		// get attributes
		
		$style = ( !empty($field['style']) ? ' style="'.$field['style'].'"' : '' );
		
		$disabled = ( ( isset($field['disabled']) && $field['disabled'] === true ) ? ' disabled="disabled"' : '' );

		$required = ( ( isset($field['required']) && $field['required'] === true ) ? ' required="true"' : '' );
		
		$placeholder = ( isset($field['placeholder']) ? esc_attr($field['placeholder']) : '' );
		
		// get html
		
		$html = '';

		switch ( $field['type'] ) {
			
			case 'html':
				$html .= $data;
			break;
			case 'text':
			case 'url':
			case 'color':
			case 'email':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . $placeholder . '" value="' . esc_attr( $data ) . '"' . $style . $required . $disabled . '/>' . "\n";
			break;
			case 'password':
			case 'number':
			case 'hidden':
				$min = '';
				if ( isset( $field['min'] ) ) {
					$min = ' min="' . esc_attr( $field['min'] ) . '"';
				}

				$max = '';
				if ( isset( $field['max'] ) ) {
					$max = ' max="' . esc_attr( $field['max'] ) . '"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . $placeholder . '" value="' . esc_attr( $data ) . '"' . $min . $max . $style . $required . $disabled . '/>' . "\n";
			break;

			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . $placeholder . '" value=""' . $style . $required . $disabled . '/>' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . $placeholder . '"' . $style . $required . $disabled . '>' . $data . '</textarea><br/>' . "\n";
			break;

			case 'checkbox':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
			break;

			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, (array) $data, true ) ) {
						$checked = true;
					}
					$html .= '<p><label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></p> ';
				}
			break;

			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( $k === $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" style="margin-right:5px;"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
			break;

			case 'select':
				
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '"' . $style . $required . $disabled . '>';
				
				foreach ( $field['options'] as $k => $v ) {
					
					$selected = false;
					
					if( is_numeric($data) && floatval($k) === floatval($data) ) {
						
						$selected = true;
					}
					elseif( $k === $data ){
						
						$selected = true;
					}
							
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				
				$html .= '</select> ';
				
			break;

			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple"' . $style . $required . $disabled . '>';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( in_array( $k, (array) $data, true ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
			break;

			case 'key_value':
				
				if( !isset($data['key']) || !isset($data['value']) ){

					$data = [
					
						'key' 	=> [ 0 => '' ], 
						'value' => [ 0 => '' ]
					];
				}

				$html .= '<div id="'.$field['id'].'">';
					
					$html .= ' <a href="#" class="add-input-group" data-target="'.$field['id'].'" style="line-height:40px;">Add field</a>';
				
					$html .= '<ul class="input-group">';
						
						foreach( $data['key'] as $e => $key) {

							$class='input-group-row';

							$value = str_replace('\\\'','\'',$data['value'][$e]);
									
							$html .= '<li class="'.$class.'" style="display:inline-block;width:100%;">';
								
								$html .= '<input type="text" placeholder="'.( !empty($field['placeholder']['key']) ? $field['placeholder']['key'] : 'key' ).'" name="'.$option_name.'[key][]" style="width:30%;float:left;" value="'.$data['key'][$e].'">';
								
								$html .= '<input type="text" placeholder="'.( !empty($field['placeholder']['value']) ? $field['placeholder']['value'] : 'value' ).'" name="'.$option_name.'[value][]" style="width:30%;float:left;" value="'.$value.'">';

								if( $e > 0 ){
									
									$html .= '<a class="remove-input-group" href="#">x</a> ';
								}

							$html .= '</li>';						
						}
					
					$html .= '</ul>';					
					
				$html .= '</div>';

			break;

			case 'terms':
				
				$html .= '<div class="tags-input" id="'.$field['id'].'" data-taxonomy="'.$field['taxonomy'].'">';
					
					$html .= '<span class="data">';
						
						// default empty value
						
						$html .= '<input type="hidden" value="-1" name="'.$option_name.'[]"/>';
						
						if( !empty($data) ){
							
							foreach( $data as $term ){
								
								if( !empty($term->name) ){
									
									$html .= '<span class="tag button button-default"><span class="text">' . $term->name . '</span><span class="close m-0 p-0 border-0 bg-transparent">&times;</span><input type="hidden" value="' . $term->term_id . '" name="'.$option_name.'[]"/></span>';
								}
							}
						}
						
					$html .= '</span>';

					$html .= '<span class="autocomplete">';
						$html .= '<input style="border:none;" type="text" placeholder="add item...">';
						$html .= '<div class="autocomplete-items"></div>';
					$html .= '</span>';
					
				$html .= '</div>';
				
			break;
			
			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}
				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image', 'rew-bulk-editor' ) . '" data-uploader_button_text="' . __( 'Use image', 'rew-bulk-editor' ) . '" class="image_upload_button button" value="' . __( 'Upload new image', 'rew-bulk-editor' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="' . __( 'Remove image', 'rew-bulk-editor' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
			break;
			
			case 'editor':
				wp_editor(
					$data,
					$option_name,
					array(
						'textarea_name' => $option_name,
					)
				);
			break;

		}

		switch ( $field['type'] ) {

			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				$html .= '<p class="description">' . $field['description'] . '</p>';
				break;

			default:
				if ( ! $post ) {
					$html .= '<label for="' . esc_attr( $field['id'] ) . '">' . "\n";
				}

				$html .= '<span class="description">' . $field['description'] . '</span>' . "\n";

				if ( ! $post ) {
					$html .= '</label>' . "\n";
				}
				break;
		}

		if ( ! $echo ) {
			
			return wp_kses_normalize_entities($html);
		}

		echo wp_kses_normalize_entities($html); //phpcs:ignore

	}

	/**
	 * Validate form field
	 *
	 * @param  string $data Submitted value.
	 * @param  string $type Type of field to validate.
	 * @return string       Validated value
	 */
	
	public function validate_output ( $data = '', $type = 'text' ) {

		switch( $type ) {
			
			case 'text'		: $data = esc_attr( $data ); break;
			case 'url'		: $data = esc_url( $data ); break;
			case 'email'	: $data = is_email( $data ); break;
		}

		return $data;
	}
	
	public function validate_input( $data = '', $type = 'text' ) {

		switch( $type ) {
			
			case 'text'		: $data = sanitize_text_field( $data ); break;
			case 'textarea'	: $data = sanitize_textarea_field( $data ); break;
			case 'url'		: $data = sanitize_url( $data ); break;
			case 'email'	: $data = sanitize_email( $data ); break;
		}

		return $data;
	}

	/**
	 * Add meta box to the dashboard.
	 *
	 * @param string $id            Unique ID for metabox.
	 * @param string $title         Display title of metabox.
	 * @param array  $post_types    Post types to which this metabox applies.
	 * @param string $context       Context in which to display this metabox ('advanced' or 'side').
	 * @param string $priority      Priority of this metabox ('default', 'low' or 'high').
	 * @param array  $callback_args Any axtra arguments that will be passed to the display function for this metabox.
	 * @return void
	 */
	public function add_meta_box( $id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null ) {

		// Get post type(s).
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		// Generate each metabox.
		foreach ( $post_types as $post_type ) {
			add_meta_box( $id, $title, array( $this, 'meta_box_content' ), $post_type, $context, $priority, $callback_args );
		}
	}

	/**
	 * Display metabox content
	 *
	 * @param  object $post Post object.
	 * @param  array  $args Arguments unique to this metabox.
	 * @return void
	 */
	public function meta_box_content( $post, $args ) {

		$fields = apply_filters( $post->post_type . '_custom_fields', array(), $post->post_type );

		if ( ! is_array( $fields ) || 0 === count( $fields ) ) {
			return;
		}

		echo '<div class="custom-field-panel">' . "\n";

		foreach ( $fields as $field ) {

			if ( ! isset( $field['metabox'] ) ) {
				continue;
			}

			if ( ! is_array( $field['metabox'] ) ) {
				$field['metabox'] = array( $field['metabox'] );
			}

			if ( in_array( $args['id'], $field['metabox'], true ) ) {
				$this->display_meta_box_field( $field, $post );
			}
		}

		echo '</div>' . "\n";

	}

	/**
	 * Dispay field in metabox
	 *
	 * @param  array  $field Field data.
	 * @param  object $post  Post object.
	 * @return void
	 */
	public function display_meta_box_field( $field = array(), $post = null ) {

		if ( ! is_array( $field ) || 0 === count( $field ) || empty($field['type'])  ) {
			return;
		}

		$field = '<p class="form-field"><label for="' . $field['id'] . '">' . $field['label'] . '</label>' . $this->display_field( $field, $post, false ) . '</p>' . "\n";

		echo $field; //phpcs:ignore
	}

	/**
	 * Save metabox fields.
	 *
	 * @param  integer $post_id Post ID.
	 * @return void
	 */
	public function save_meta_boxes( $post_id = 0 ) {

		if( !$post_id || isset($_POST['_inline_edit']) || isset($_GET['bulk_edit']) ){
			
			return;
		}
		
		$post_type = get_post_type( $post_id );

		$fields = apply_filters( $post_type . '_custom_fields', array(), $post_type );

		if ( ! is_array( $fields ) || 0 === count( $fields ) ) {
			
			return;
		}

		foreach ( $fields as $field ) {
			
			if( !empty($field['id']) ){
				
				if ( isset( $_REQUEST[ $field['id'] ] ) ) { //phpcs:ignore
					
					update_post_meta( $post_id, $field['id'], $this->validate_input( $_REQUEST[$field['id']], $field['type'] ) ); //phpcs:ignore
				} 
				else {
					
					update_post_meta( $post_id, $field['id'], '' );
				}
			}
		}
	}

}