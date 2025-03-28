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
		
		add_action($this->parent->_base.'allowed_admin_html', array( $this, 'add_allowed_default_html' ), 10, 1 );
		add_action($this->parent->_base.'allowed_admin_html', array( $this, 'add_allowed_form_html' ), 10, 1 );
		add_action($this->parent->_base.'allowed_admin_html', array( $this, 'add_allowed_table_html' ), 10, 1 );
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
		
		$option_name = ( isset( $data['prefix'] ) ? $data['prefix'] : '' ) . ( !empty($field['name']) ? $field['name'] : ( isset($field['id']) ? $field['id'] : '' ) );

		// Get default
			
		$default = isset($field['default']) ? $field['default'] : null;
			
		// Get saved data
		
		$data = '';
			
		if ( isset( $field['data'] ) ) {
			
			$data = $field['data'];
		}
		elseif ( $post ) {

			// Get saved field data.
			
			$data = get_post_meta( $post->ID, $option_name, true );
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
		
		$style = ' style="'.( !empty($field['style']) ? $field['style'] : 'width:60%;' . ( empty($field['description']) ? 'margin-bottom:5px;' : '' )).'"';
		
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
			case 'date':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="date" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '"' . $style . $required . $disabled . '/>' . "\n";
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
				
				$step = '';
				
				if ( isset( $field['step'] ) ) {
					
					$step = ' step="' . esc_attr( $field['step'] ) . '"';
				}
				
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . $placeholder . '" value="' . esc_attr( $data ) . '"' . $min . $max . $step . $style . $required . $disabled . '/>' . "\n";
			
			break;

			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . $placeholder . '" value=""' . $style . $required . $disabled . '/>' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . $placeholder . '"' . $style . $required . $disabled . '>' . $data . '</textarea><br/>' . "\n";
			break;

			case 'checkbox':
				
				$html .='<div style="width:fit-content;">';
					
					$checked = '';
					
					if ( $data && 'on' === $data ) {
						
						$checked = 'checked="checked"';
					}
					
					if( !empty($field['description']) ){
					
						$html .= '<label for="' . esc_attr( $field['id'] ) . '" class="checkbox"'.$style.'><input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/> ' . esc_html( $field['description'] ) . '</label>';
					}
					else{
						
						$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>';
					}
					
				$html .='</div>';
				
			break;

			case 'checkbox_multi':
               
				$html .='<div style="width:fit-content;">';
					
					foreach ( $field['options'] as $k => $v ) {
						
						$checked = false;
						
						if ( in_array( $k, (array) $data, true ) ) {
							
							$checked = true;
						}
						
						$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"'.$style.'><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
					}
					
				$html .='</div>';
				
			break;
            
			case 'radio':
			
				$html .='<div style="width:fit-content;">';
					
					foreach ( $field['options'] as $k => $v ) {
						
						$checked = false;
						
						if ( $k === $data ) {
							
							$checked = true;
						}
						
						$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" style="margin-right:5px;float:left;"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
					}
				
				$html .='</div>';
				
			break;
            
            case 'process_status' :

                $html .= $this->display_field(array(
                    
                    'id'        => $field['id'],
                    'name'      => $option_name,
                    'type'      => 'select',
                    'options'   => $field['options'],
                    'style'     => 'height:32px;width:68%;',
                
                ),$post,$echo);
                
                $html .= $this->display_field(array(
                    
                    'type' => 'update_button',
                    
                ),$post,$echo);
                
            break;
            
            case 'update_button':
                
                $html .= '<div id="bulk-editor-update" style="float:right;">';
                
                    $html .= '<input type="submit" name="publish" class="button button-primary button-large" value="Update">';
                
                    $html .= '<div class="update-loader"></div>';
                
                $html .= '</div>';
                
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
            case 'data_fields':
                
                if( !empty($field['options']) ){
                    
                    if( !empty($data['key']) && !empty($data['value']) ){
                        
                        $data = array_combine($data['key'],$data['value']);
                    }
                    else{
                        
                        $data = array(
                        
                            'key'       => array(),
                            'value'   => array(),
                        );
                    }
                    
                    foreach( $field['options'] as $option ) {
                        
                        $html .= '<div style="clear:both;">';
                        
                            $html .= $this->display_field(array(
						
                                'id' 	=> $field['id'] . '_key',
                                'name' 	=> $option_name.'[key][]',
                                'type'	=> 'hidden',
                                'data'  => $option,
                            
                            ),null,false);
                            
                            $html .= $this->display_field(array(
						
                                'id' 	    => $field['id'] . '_key',
                                'name' 	    => $option_name.'[key][]',
                                'type'	    => 'text',
                                'disabled'  => true,
                                'data'      => $option,
                                'style'     => 'float:left;width:40%;',
                            
                            ),null,false);
                            
                            $html .= '<div style="float:left;padding:6px;margin-bottom:5px;"> to </div>';
                            
                            $value = '';
                           
                            if( !empty($data[$option]) ){
                                
                                $value = $data[$option];
                            }
                            elseif( isset($field['attrs'][$option]) ){
                                
                                $value = $option;
                            }
                            elseif( strpos($option,'tax:') === 0 ){
                                
                                $value = 'term_slug';
                            }
                            elseif( strpos($option,'meta:') === 0 ){
                                
                                $value = 'meta';
                            }
                            
                            $html .= $this->display_field(array(
						
                                'id' 			=> $field['id'] . '_value',
                                'name' 			=> $option_name.'[value][]',
                                'type'			=> 'select',
                                'style'         => 'float:left;width:40%;',
                                'data'          => $value,
                                'options'		=> array(
                                
                                    '' => 'Select a field',
                                    
                                ) + $field['attrs'],
                                
                            ),null,false);
                            
                        $html .= '</div>';
                    }   
                }
                
            break;
			case 'array':
				
				if( 	!isset($data['key']) 
					|| 	!is_array($data['key']) 
					|| 	!isset($data['value']) 
					|| 	!is_array($data['value']) 
				){

					$data = [
					
						'key' 	=> [ 0 => '' ], 
						'value' => [ 0 => '' ],
					];
				}
				
				$set_keys = !empty($field['keys']) ? true : false;
				
				$input = !empty($field['input']) ? sanitize_title($field['input']) : 'text';
				
				$options = !empty($field['options']) ? $field['options'] : array();
				
				$html .= '<div id="'.$field['id'].'" class="arr-input">';
					
					$html .= ' <a href="#" class="add-input-group" data-target="'.$field['id'].'" style="line-height:40px;">Add Field</a>';
				
					$html .= '<ul class="arr-input-group">';
						
						if( !empty($data['key']) ){
							
							foreach( $data['key'] as $e => $key) {

								$class='input-group-row';

								$value = str_replace('\\\'','\'',$data['value'][$e]);
								
								$html .= '<li class="'.$class.'" style="display:inline-block;width:100%;">';
									
									if( $set_keys === true ){
									
										$html .= '<input placeholder="name" type="text" name="'.$option_name.'[key][]" style="height:30px;width:20%;float:left;" value="'.$data['key'][$e].'">';
									}
									else{
										
										$html .= '<input type="hidden" name="'.$option_name.'[key][]" value="">';
									}
									
									$html .= $this->display_field(array(
						
										'id' 			=> $field['id'] . '_value',
										'name' 			=> $option_name.'[value][]',
										'placeholder' 	=> $placeholder,
										'type'			=> $input,
										'options'		=> $options,
										'data'			=> $value,
									
									),null,false);
									
									if( $e > 0 ){
										
										$html .= '<a class="remove-input-group" href="#">x</a> ';
									}

								$html .= '</li>';						
							}
						}
					
					$html .= '</ul>';					
					
				$html .= '</div>';

			break;
			case 'meta':
				
				if( 	!isset($data['key']) 
					|| 	!is_array($data['key']) 
					|| 	!isset($data['value']) 
					|| 	!is_array($data['value']) 
					|| 	!isset($data['type']) 
					|| 	!is_array($data['type']) 
					|| 	!isset($data['compare']) 
					|| 	!is_array($data['compare']) 
				){

					$data = [
					
						'key' 		=> [ 0 => '' ], 
						'value' 	=> [ 0 => '' ],
						'type' 		=> [ 0 => '' ],
						'compare' 	=> [ 0 => '' ],						
					];
				}
				
				$type_options = $this->get_data_type_options();
				
				$compare_options = $this->get_compare_options();
				
				$html .= '<div id="'.$field['id'].'" class="meta-input">';
					
					$html .= '<a href="#" class="add-input-group" data-target="'.$field['id'].'" style="line-height:40px;">Add Field</a>';
					
					$html .= '<ul class="meta-input-group" style="display:grid;">';
						
						if( !empty($data['key']) ){
							
							foreach( $data['key'] as $e => $key) {

								$class='input-group-row';

								$value = str_replace('\\\'','\'',$data['value'][$e]);
								
								$type = str_replace('\\\'','\'',$data['type'][$e]);
								
								$compare = str_replace('\\\'','\'',$data['compare'][$e]);

								$html .= '<li class="'.$class.'" style="display:inline-block;width:100%;">';
									
									$html .= '<input placeholder="key" type="text" name="'.$option_name.'[key][]" style="height:30px;width:20%;float:left;" value="'.$data['key'][$e].'">';
									
									$html .= '<input placeholder="value" type="text" name="'.$option_name.'[value][]" style="height:30px;width:20%;float:left;" value="'.$value.'">';
									
									$html .= '<select name="'.$option_name.'[type][]" style="width:80px;float:left;">';
										
										foreach ( $type_options as $k => $v ) {
											
											$selected = false;
											
											if( is_numeric($type) && floatval($k) === floatval($type) ) {
												
												$selected = true;
											}
											elseif( $k === $type ){
												
												$selected = true;
											}
											elseif( empty($type) && $k == 'char' ){
												
												$selected = true;
											}
											
											$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
										}
									
									$html .= '</select> ';
									
									$html .= '<select name="'.$option_name.'[compare][]" style="width:70px;float:left;">';
										
										foreach ( $compare_options as $k => $v ) {
											
											$selected = false;
											
											if( is_numeric($compare) && floatval($k) === floatval($compare) ) {
												
												$selected = true;
											}
											elseif( $k === $compare ){
												
												$selected = true;
											}
											elseif( empty($type) && $k == 'equal' ){
												
												$selected = true;
											}
											
											$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
										}
									
									$html .= '</select> ';

									if( $e > 0 ){
										
										$html .= '<a class="remove-input-group" href="#">x</a> ';
									}

								$html .= '</li>';						
							}
						}
					
					$html .= '</ul>';					
					
				$html .= '</div>';

			break;
            case 'search':
				
				if( 	!isset($data['s']) 
					|| 	!is_array($data['s']) 
                    || 	!isset($data['in']) 
					|| 	!is_array($data['in']) 
					|| 	!isset($data['op']) 
					|| 	!is_array($data['op']) 
				){

					$data = [
					
						's' 	=> [ 0 => is_string($data) ? $data : '' ], 
                        'in' 	=> [ 0 => 'post_content' ],		
						'op' 	=> [ 0 => 'like' ],						
					];
				}
				
				$compare_options = array(
                
                    'like'      => 'LIKE',
                    'not-like'  => 'NOT LIKE',
                );
				
				$html .= '<div id="'.$field['id'].'" class="search-input">';
					
					$html .= '<a href="#" class="add-input-group" data-target="'.$field['id'].'" style="line-height:40px;">Add Field</a>';
					
					$html .= '<ul class="search-input-group" style="display:grid;">';
						
						if( !empty($data['s']) ){
							
							foreach( $data['s'] as $e => $s ) {

								$class='input-group-row';

								$column = str_replace('\\\'','\'',$data['in'][$e]);
                                
                                $compare = str_replace('\\\'','\'',$data['op'][$e]);

								$html .= '<li class="'.$class.'" style="display:inline-block;width:100%;">';
									
									$html .= '<input placeholder="Search keyword" type="text" name="'.$option_name.'[s][]" style="height:30px;width:30%;float:left;" value="'.$s.'">';
									
									$html .= '<select name="'.$option_name.'[in][]" style="width:100px;float:left;">';
										
										foreach ( $field['options'] as $k => $v ) {
											
											$selected = $k === $column ? true : false;
											
											$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
										}
									
									$html .= '</select> ';
                                    
									$html .= '<select name="'.$option_name.'[op][]" style="width:100px;float:left;">';
										
										foreach ( $compare_options as $k => $v ) {
											
											$selected = $k === $compare ? true : false;
											
											$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
										}
									
									$html .= '</select> ';

									if( $e > 0 ){
										
										$html .= '<a class="remove-input-group" href="#">x</a> ';
									}

								$html .= '</li>';						
							}
						}
					
					$html .= '</ul>';					
					
				$html .= '</div>';

			break;            
			case 'dates':
				
				if( 	!isset($data['type']) 
					|| 	!is_array($data['type']) 
					|| 	!isset($data['column'])
					|| 	!is_array($data['column']) 
					|| 	!isset($data['position'])
					|| 	!is_array($data['position']) 						
					|| 	!isset($data['value']) 
					|| 	!is_array($data['value']) 	
					|| 	!isset($data['period']) 
					|| 	!is_array($data['period']) 	
					|| 	!isset($data['from']) 
					|| 	!is_array($data['from']) 	
					|| 	!isset($data['limit']) 
					|| 	!is_array($data['limit']) 	
				){
					
					$data = array (
				
						'type' 		=> [ 0 => '' ],
						'column' 	=> [ 0 => '' ],
						'value' 	=> [ 0 => '' ],
						'position' 	=> [ 0 => 'before' ],
						'period' 	=> [ 0 => 'days' ],
						'from' 	=> [ 0 => 'ago' ],
						'limit' 	=> [ 0 => 'in' ],
						
					);
				}
				
				$html .= '<div id="'.$field['id'].'" class="date-input">';
					
					$html .= '<a href="#" class="add-date-group" data-html="' . esc_html($this->display_field(array(
						
						'id' 		=> $field['id'] . '_input_date',
						'name' 		=> $option_name,
						'type'		=> 'date_group',
						'columns'	=> $field['columns'],
						'data'		=> array (
							
							'column' 	=> '',
							'value' 	=> '',
							'position' 	=> 'before',
							'limit' 	=> 'in',
						),
					
					),null,false)) . '" data-target="'.$field['id'].'" style="line-height:40px;">Add Date</a>';
					
					$html .= ' | ';
					
					$html .= '<a href="#" class="add-date-group" data-html="' . esc_html($this->display_field(array(
						
						'id' 		=> $field['id'] . '_input_time',
						'name' 		=> $option_name,
						'type'		=> 'time_group',
						'columns'	=> $field['columns'],
						'data'		=> array (
							
							'column' 	=> '',
							'value' 	=> 1,
							'position' 	=> 'before',
							'period' 	=> 'days',
							'from' 	=> 'ago',
							'limit' 	=> 'in',
						),
					
					),null,false)) . '" data-target="'.$field['id'].'" style="line-height:40px;">Add Time</a>';
				
					$html .= '<ul class="date-input-group">';
						
						if( !empty($data['type']) ){
							
							foreach( $data['type'] as $e => $type ) {
								
								if( !empty($type) ){
									
									$html .= $this->display_field(array(
										
										'id' 		=> $field['id'] . '_input_' . $type,
										'name' 		=> $option_name,
										'type'		=> $type . '_group',
										'columns'	=> $field['columns'],
										'data'		=> array (
											
											'column' 	=> isset($data['column'][$e]) 	? $data['column'][$e] 	: '',
											'value' 	=> isset($data['value'][$e]) 	? $data['value'][$e] 	: '',
											'position' 	=> isset($data['position'][$e]) ? $data['position'][$e] : '',
											'period' 	=> isset($data['period'][$e]) 	? $data['period'][$e] 	: '',
											'from' 		=> isset($data['from'][$e]) 	? $data['from'][$e] 	: '',
											'limit' 	=> isset($data['limit'][$e]) 	? $data['limit'][$e] 	: '',
										),
									
									),null,false);
								}						
							}
						}
					
					$html .= '</ul>';					
					
				$html .= '</div>';

			break;
			case 'date_group':

				$html .= '<li class="input-group-row" style="display:inline-block;width:100%;">';
					
					$html .= $this->display_field(array(
						
						'id' 	=> $field['id'] . '_type',
						'name' 	=> $option_name.'[type][]',
						'type'	=> 'hidden',
						'data'	=> 'date',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_column',
						'name' 		=> $option_name.'[column][]',
						'type'		=> 'select',
						'options'	=> $field['columns'],
						'data'		=> $data['column'],
						'style'		=> 'width:15%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_position',
						'name' 		=> $option_name.'[position][]',
						'type'		=> 'select',
						'options'	=> $this->get_date_position_options(),
						'data'		=> $data['position'],
						'style'		=> 'width:15%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id'	=> $field['id'] . '_from',
						'name'	=> $option_name.'[from][]',
						'type'	=> 'hidden',
						'data'	=> '',
						'style'	=> 'width:15%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 	=> $field['id'] . '_value',
						'name' 	=> $option_name.'[value][]',
						'type'	=> 'date',
						'data'	=> $data['value'],
						'style'	=> 'width:31%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 	=> $field['id'] . '_period',
						'name' 	=> $option_name.'[period][]',
						'type'	=> 'hidden',
						'data'	=> '',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_limit',
						'name' 		=> $option_name.'[limit][]',
						'type'		=> 'select',
						'options'	=> $this->get_boundary_options(),
						'data' 		=> $data['limit'],
						'style'		=> 'width:15%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= ' <a class="remove-input-group" href="#">x</a>';
					
				$html .= '</li>';
			break;
			case 'time_group':
				
				$html .= '<li class="input-group-row" style="display:inline-block;width:100%;">';
					
					$html .= $this->display_field(array(
						
						'id' 	=> $field['id'] . '_type',
						'name' 	=> $option_name.'[type][]',
						'type'	=> 'hidden',
						'data'	=> 'time',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_column',
						'name' 		=> $option_name.'[column][]',
						'type'		=> 'select',
						'options'	=> $field['columns'],
						'data'		=> $data['column'],
						'style'		=> 'width:15%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_position',
						'name' 		=> $option_name.'[position][]',
						'type'		=> 'select',
						'options'	=> $this->get_date_position_options(),
						'data'		=> $data['position'],
						'style'		=> 'width:15%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 	=> $field['id'] . '_value',
						'name' 	=> $option_name.'[value][]',
						'type'	=> 'number',
						'min'	=> 1,
						'step'	=> 1,
						'data'	=> $data['value'],
						'style'	=> 'width:10%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_period',
						'name' 		=> $option_name.'[period][]',
						'type'		=> 'select',
						'options'	=> array(
							
							'minutes' 	=> 'minute(s)',
							'hours' 	=> 'hour(s)',
							'days' 		=> 'day(s)',
							'weeks' 	=> 'weeks(s)',
							'months' 	=> 'month(s)',
							'years' 	=> 'year(s)',
						),
						'data'	=> $data['period'],
						'style'	=> 'width:10%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_from',
						'name' 		=> $option_name.'[from][]',
						'type'		=> 'select',
						'options'	=> array(
							
							'ago' 		=> 'ago',
							'from_now' 	=> 'from now',
						),
						'data'	=> $data['from'],
						'style'	=> 'width:10%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= $this->display_field(array(
						
						'id' 		=> $field['id'] . '_limit',
						'name' 		=> $option_name.'[limit][]',
						'type'		=> 'select',
						'options'	=> $this->get_boundary_options(),
						'data' 		=> $data['limit'],
						'style'		=> 'width:15%;margin-bottom:5px;',
					
					),null,false);
					
					$html .= ' <a class="remove-input-group" href="#">x</a>';
					
				$html .= '</li>';
			break;
			case 'terms':
				
				$hierarchical = !empty($field['hierarchical']) ? filter_var($field['hierarchical'], FILTER_VALIDATE_BOOLEAN) : false;
				
				$operator = !empty($field['operator']) ? filter_var($field['operator'], FILTER_VALIDATE_BOOLEAN) : false;
				
				$context = !empty($field['context']) ? $field['context'] : 'filter';
				
                $html .= '<div class="auto-input tags-input" id="'.$field['id'].'" data-taxonomy="'.$field['taxonomy'].'" data-hierarchical="'.$hierarchical.'" data-operator="'.$operator.'" data-context="'.$field['context'].'">';

					$html .= '<div class="autocomplete">';
						
						$html .= '<input style="width:60%;margin-bottom:5px;" type="text" placeholder="Search term...">';
						
						$html .= '<div class="autocomplete-items"></div>';
					
					$html .= '</div>';
					
					$html .= '<div class="data">';
						
						$html .= '<input type="hidden" value="-1" name="'.$option_name.'[term][]"/>';
						
						if( !empty($operator) ){
							
							$html .= '<input type="hidden" value="in" name="'.$option_name.'[operator][]"/>';
						}
						
						if( !empty($hierarchical) ){
						
							$html .= '<input type="hidden" value="in" name="'.$option_name.'[children][]"/>';
						}
						
						if( !empty($data['term'])  && is_array($data['term']) ){
							
							foreach( $data['term'] as $i => $id ){
								
								$id = intval($id);
								
								if( $id > 0 ){
									
									$term = get_term($id);
									
									if( !empty($term->name) ){
										
										$html .= $this->display_field(array(
									
											'id'    => $field['id'],
											'name'  => $option_name,
											'type' 	=> 'term',
											'data' 	=> array(
											
												'term' 		=> $term,
												'operator' 	=> !empty($data['operator'][$i]) ? str_replace('\\\'','\'',$data['operator'][$i]) : 'in',
												'children' 	=> !empty($data['children'][$i]) ? str_replace('\\\'','\'',$data['children'][$i]) : 'in',
											),
											'operator' 		=> $operator,
											'hierarchical' 	=> $hierarchical,
											
										),null,false);
									}
								}
							}
						}
						
					$html .= '</div>';
					
				$html .= '</div>';
				
			break;
			case 'term':
			
				$html .= '<div class="item">';
					
					$html .= '<input style="width:60%;float:left;" type="text" value="' . $data['term']->name . '" disabled="disabled"/>';

					$html .= '<input type="hidden" value="' . $data['term']->term_id . '" name="'.$option_name.'[term][]"/>';
					
					if( !empty($field['operator']) ){
						
						$operators 	= $this->get_operator_options();
						$operator = $data['operator'];
						
						$html .= '<select name="'.$option_name.'[operator][]" style="width:80px;float:left;">';

							foreach ( $operators as $k => $v ) {
								
								$selected = false;
								
								if( $k === $operator ){
									
									$selected = true;
								}
								elseif( empty($operator) && $k == 'in' ){
									
									$selected = true;
								}
								
								$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
							}
							
						$html .= '</select> ';
					}
					
					if( !empty($field['hierarchical']) ){
					
						$children = $this->get_children_options();
					
						$html .= '<select name="'.$option_name.'[children][]" style="width:157px;float:left;">';

							foreach ( $children as $k => $v ) {
								
								$selected = false;
								
								if( $k === $data['children'] ){
									
									$selected = true;
								}
								elseif( empty($data['children']) && $k == 'in' ){
									
									$selected = true;
								}
								
								$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
							}
							
						$html .= '</select> ';
					}
					
					$html .= '<span class="close m-0 p-0 border-0 bg-transparent">x</span>';
					
				$html .= '</div>';
					
			break;
            case 'tasks':
				
				$html .= '<div class="auto-input tasks-input" id="'.$field['id'].'" data-type="'.$field['task_type'].'">';
					
					$html .= '<div class="autocomplete">';
						
						$html .= '<input style="width:60%;margin-bottom:5px;" type="text" placeholder="Search task...">';
						
						$html .= '<div class="autocomplete-items"></div>';
					
					$html .= '</div>';
					
					$html .= '<div class="data">';
						
						$html .= '<input type="hidden" value="-1" name="'.$option_name.'[]"/>';
						
						if( !empty($data) && is_array($data) ){
							
                            $i = 0;
                            
							foreach( $data as $id ){
								
								$id = intval($id);
								
								if( $id > 0 ){
									
									if( $task = get_post($id) ){
										
                                        ++$i;
                                        
										$html .= $this->display_field(array(
									
											'id'        => $field['id'],
											'name'      => $option_name,
											'type' 	    => 'task',
                                            'number' 	=> $i,
											'data'	    => array(
											
												'id' 	    => $task->ID,
												'name' 	    => $task->post_title . ' - #' . $task->ID,
                                            )
											
										),null,false);
									}
								}
							}
						}
						
					$html .= '</div>';
					
				$html .= '</div>';
				
			break;
            case 'task':
			
				$html .= '<div class="item">';
					
                    $html .= '<div style="padding:7px;width:54%;float:left;"><b>Task '.$field['number'].'</b> - <a href="'.admin_url('post.php?post='.$data['id'].'&action=edit').'" target="_blank">' . $data['name'] . '</a></div>';

					$html .= '<input type="hidden" value="' . $data['id'] . '" name="'.$option_name.'[]"/>';
					
					$html .= '<span class="close m-0 p-0 border-0 bg-transparent">x</span>';
					
				$html .= '</div>';
					
			break;
			case 'authors':
				
				$html .= '<div class="auto-input authors-input loading" id="'.$field['id'].'" data-multi="'.( !empty($field['multi']) ? 'true' : 'false' ).'" data-callback="'.( !empty($field['callback']) ? $field['callback'] : 'render_author_suggestions' ).'">';
					
					$html .= '<div class="autocomplete">';
						
						$html .= '<input style="width:60%;margin-bottom:5px;" type="text" placeholder="'.$placeholder.'">';
						
						$html .= '<div class="autocomplete-items"></div>';
					
					$html .= '</div>';
					
					$html .= '<div class="data">';
						
						$html .= '<input type="hidden" value="-1" name="'.$option_name.'[]"/>';
						
						if( !empty($data) && is_array($data) ){
							
							foreach( $data as $i => $id ){
								
								$id = intval($id);
								
								if( $id > 0 ){
									
                                    if( $field['callback'] == 'render_author_suggestions' ){
                                        
                                        if( $user = get_user_by('id',$id) ){
                                            
                                            $html .= $this->display_field(array(
                                        
                                                'id'    => $field['id'],
                                                'name'  => $option_name,
                                                'type' 	=> 'user',
                                                'data'	=> array(
                                                
                                                    'id' 	=> $user->ID,
                                                    'name' 	=> $user->display_name . ' (' . $user->user_email . ')',
                                                )
                                                
                                            ),null,false);
                                        }
                                    }
                                    elseif( $post = get_post($id) ){
                                        
                                        $html .= $this->display_field(array(
                                    
                                            'id'    => $field['id'],
                                            'name'  => $option_name,
                                            'type' 	=> 'user',
                                            'data'	=> array(
                                            
                                                'id' 	=> $post->ID,
                                                'name' 	=> $post->post_title . ' - #' . $post->ID,
                                            )
                                            
                                        ),null,false);
                                    }
								}
							}
						}
						
					$html .= '</div>';
					
				$html .= '</div>';
				
			break;
			case 'author':
			
				$html .= '<div class="item">';
					
					$html .= '<input style="width:60%;float:left;" type="text" value="' . $data['name'] . '" disabled="disabled"/>';

					$html .= '<input type="hidden" value="' . $data['id'] . '" name="'.$option_name.'[]"/>';
					
					$html .= '<span class="close m-0 p-0 border-0 bg-transparent">x</span>';
					
				$html .= '</div>';
					
			break;
			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}
				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image', 'bulk-task-editor' ) . '" data-uploader_button_text="' . __( 'Use image', 'bulk-task-editor' ) . '" class="image_upload_button button" value="' . __( 'Upload new image', 'bulk-task-editor' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="' . __( 'Remove image', 'bulk-task-editor' ) . '" />' . "\n";
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
			
			case 'checkbox':
			break;
			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				if( !empty($field['description']) ){
				
					$html .= '<p class="description" style="clear:both;margin-bottom:7px;font-style:italic;">' . $field['description'] . '</p>';
				}
			break;
			default:
				
				if( !empty($field['description']) ){
                      
                    if ( !$post && isset($field['id']) ){
                        
                        $html .= '<label for="' . esc_attr( $field['id'] ) . '">' . PHP_EOL;
                    }
                    
					$html .= '<div class="description" style="display:block;margin-bottom:7px;font-style:italic;">' . $field['description'] . '</div>' . PHP_EOL;
				
                    if ( !$post && isset($field['id']) ){
                        
                        $html .= '</label>' . PHP_EOL;
                    }
                }
				
				break;
		}
		
		$allowed_html = apply_filters('rewbe_allowed_admin_html',array());
		
		if ( !$echo ){
			
			return wp_kses($html,$allowed_html);
		}

		echo wp_kses($html,$allowed_html);

	}
	
	public function add_allowed_default_html($html){
		
		$default_html = array_merge($html,array(
		
			'h1' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'h2' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'h3' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array()
			),
			'img' => array(
			
				'src' 	=> array(),
				'alt' 	=> array(),
				'title' => array(),
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'div' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
				
				'data-taxonomy'		=> array(),
				'data-hierarchical'	=> array(),
				'data-operator'		=> array(),
				'data-context'		=> array(),
				'data-multi'		=> array(),
                'data-callback'     => array(),
				'data-type'		    => array(),
				'data-steps'	    => array(),
				'data-uploader_title'		=> array(),
				'data-uploader_button_text'	=> array(),
			),
			'p' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'a' => array(
			
				'href' => array(),
				'style' => array(),
				'class' => array(),
				'id'    => array(),
				
				'data-target'	=> array(),
				'data-html'		=> array(),
			),
			'i' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'b' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'br' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'span' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
				
			),
			'ul' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'ol' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'li' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'code' => array(
			
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
		));
        
        foreach( $default_html as $tag => $args ){
            
            $html[$tag] = isset($html[$tag]) ? array_merge($args,$html[$tag]) : $args;
        }
        
        return $html;
	}
	
	public function add_allowed_form_html($html) {
		
		return array_merge($html, array(
		
			'label' => array(
			
				'for'   => array(),
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'form' => array(
			
				'action' => array(),
				'method' => array(),
				'style'  => array(),
				'class'  => array(),
				'id'     => array(),
				//'enctype' => array(),
			),
			'input' => array(
			
				'type'        => array(),
				'name'        => array(),
				'value'       => array(),
				'style'       => array(),
				'class'       => array(),
				'id'          => array(),
				'checked'     => array(),
				'required'    => array(),
				'disabled'    => array(),
				'placeholder' => array(),
				'min'         => array(),
				'max'         => array(),
				'maxlength'   => array(),
				'minlength'   => array(),
				'pattern'     => array(),
				'step'        => array(),
				'data-value'  => array(),
			),
			'textarea' => array(
			
				'name'        => array(),
				'rows'        => array(),
				'cols'        => array(),
				'style'       => array(),
				'class'       => array(),
				'id'          => array(),
				'placeholder' => array(),
				'required'    => array(),
				'disabled'    => array(),
			),
			'select' => array(
			
				'name'        => array(),
				'style'       => array(),
				'class'       => array(),
				'id'          => array(),
				'required'    => array(),
				'disabled'    => array(),
			),
			'option' => array(
			
				'value'     => array(),
				'selected'  => array(),
				'style'     => array(),
				'class'     => array(),
				'id'        => array(),
				'disabled'  => array(),
			),
			'button' => array(
			
				'type' 		=> array(),
				'style' 	=> array(),
				'class' 	=> array(),
				'id'    	=> array(),
				'disabled' 	=> array(),
				'name'   	=> array(),
				'value'  	=> array(),
			),
		));
	}

	public function add_allowed_table_html($html){
			
		return array_merge($html, array(
		
			'table' => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
				'border' => array(),
				'cellpadding' => array(),
				'cellspacing' => array(),
			),
			'thead' => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'tbody' => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'tfoot' => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'tr' => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'th' => array(
				'style'    => array(),
				'class'    => array(),
				'id'       => array(),
				'colspan'  => array(),
				'rowspan'  => array(),
				'width'    => array(),
				'height'   => array(),
				'scope'    => array(),
				'align'    => array(),
				'valign'   => array(),
			),
			'td' => array(
				'style'    => array(),
				'class'    => array(),
				'id'       => array(),
				'colspan'  => array(),
				'rowspan'  => array(),
				'width'    => array(),
				'height'   => array(),
				'align'    => array(),
				'valign'   => array(),
			),
			'caption' => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'col' => array(
				'style'   => array(),
				'class'   => array(),
				'span'    => array(),
				'width'   => array(),
			),
			'colgroup' => array(
				'style'   => array(),
				'class'   => array(),
				'span'    => array(),
			),
		));
	}
	
	public function get_operator_options(){
		
		return array(
			
			'in' 		=> 'IN',
			'not-in'	=> 'NOT IN',
			//'and' 	=> 'AND',
			//'exists' 	=> 'EXISTS',
			//'not-exists'=> 'NOT EXISTS',
		);
	}
	
	public function get_relation_options(){
		
		return array(

			'and' 	=> 'AND',
			'or'	=> 'OR',
		);
	}
	
	public function get_children_options(){
		
		return array(
			
			'in' 	=> 'Including Children',
			'ex'	=> 'Excluding Children',
		);
	}
	
	public function get_date_position_options(){
		
		return array(
			
			'before' 	=> 'before',
			'after' 	=> 'after',
		);
	}
	
	public function get_date_column_options($type='post'){
		
		if( $type == 'user' ){
			
			return array(
			
				'user_registered' 	=> 'Registered',
			);
		}
		else{
			
			return array(
			
				'post_date_gmt' 	=> 'Posted',
				'post_modified_gmt' => 'Modified',
			);
		}
	}
	
	public function get_boundary_options(){
		
		return array(
						
			'in' 	=> 'inclusive',
			'ex'	=> 'exclusive',
		);
	}
		
	public function get_data_type_options(){
		
		return array(
			
			'char' 		=> 'CHAR',
			'numeric' 	=> 'NUMERIC',
			'binary' 	=> 'BINARY',
			'date' 		=> 'DATE',
			'datetime' 	=> 'DATETIME',
			'decimal' 	=> 'DECIMAL',
			'signed' 	=> 'SIGNED',
			'time' 		=> 'TIME',
			'unsigned' 	=> 'UNSIGNED',
		);
	}
	
	public function get_compare_options(){
		
		return array(
			
			'equal' 		=> '=',
			'not-equal' 	=> '!=',
			'greater' 		=> '>',
			'greater-equal' => '>=',
			'less' 			=> '<',
			'less-equal' 	=> '<=',
			'like' 			=> 'LIKE',
			'not-like' 		=> 'NOT LIKE',
			'in' 			=> 'IN',
			'not-in' 		=> 'NOT IN',
			'between' 		=> 'BETWEEN',
			'not-between' 	=> 'NOT BETWEEN',
			'exists' 		=> 'EXISTS',
			'not-exists' 	=> 'NOT EXISTS',
			'regexp' 		=> 'REGEXP',
			'not-regexp' 	=> 'NOT REGEXP',
			'rlike' 		=> 'RLIKE',
			'not-rlike' 	=> 'NOT RLIKE',
		);
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

			case 'url'		: $data = esc_url( $data ); break;
			case 'email'	: $data = is_email( $data ); break;
			case 'textarea'	: esc_textarea( $data ); break;
			default			: $data = esc_attr( $data ); break;
		}

		return $data;
	}
	
	public function validate_input( $data = '', $type = 'text' ) {

		switch( $type ) {
			
			case 'textarea'	: $data = sanitize_textarea_field( $data ); break;
			case 'url'		: $data = sanitize_url( $data ); break;
			case 'email'	: $data = sanitize_email( $data ); break;
			default			: 
			
				if( is_array($data) ){ 
					
					foreach( $data as $k => $v ){
						
						$data[$k] = $this->validate_input($v,$type);
					}
				}
				else{
					
					$data = sanitize_text_field( $data ); 
				}
				
			break;
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

		$fields = apply_filters( 'rewbe_' . $post->post_type . '_custom_fields', array() );
        
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
	public function display_meta_box_field( $field = array(), $post = null, $echo = true ) {

		if ( ! is_array( $field ) || 0 === count( $field ) || empty($field['type'])  ) {
			return;
		}

        if( $field['id'] == $this->parent->_base . 'process_status' ){
            
            $html = '<div id="major-publishing-actions" style="margin:0 -12px -12px;">';
                
                $html .= $this->display_field( $field, $post, false );
            
            $html .= '</div>' . PHP_EOL;
        }
        else{
            
            $html = '<div class="form-field">';
                
                if( !empty($field['label']) ){
                    
                    $html .= '<label for="' . $field['id'] . '">';
                        
                        $html .= '<b>' . $field['label'] . '</b>';
                    
                    $html .= '</label>';
                }
                
                $html .= $this->display_field( $field, $post, false );
            
            $html .= '</div>' . PHP_EOL;
        }
		
		$allowed_html = apply_filters('rewbe_allowed_admin_html',array());
		
		if($echo){
			
			echo wp_kses($html,$allowed_html);
			
		}
		else{
			
			return wp_kses($html,$allowed_html);
		}
	}
}
