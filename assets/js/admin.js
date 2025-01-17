/**
 * Plugin Template admin js.
 *
 *  @package REW Bulk Editor/JS
 */

;(function($){
    
	$.fn.serializeObject = function(){

        var self = this,
            jsonData = {},
            push_counters = {},
            patterns = {
                "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
                "key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
                "push":     /^$/,
                "fixed":    /^\d+$/,
                "named":    /^[a-zA-Z0-9_]+$/
            };


        this.build = function(base, key, value){
            base[key] = value;
            return base;
        };

        this.push_counter = function(key){
            if(push_counters[key] === undefined){
                push_counters[key] = 0;
            }
            return push_counters[key]++;
        };

        $.each($(this).serializeArray(), function(){

            // Skip invalid keys
            if(!patterns.validate.test(this.name)){
                return;
            }

            var k,
                keys = this.name.match(patterns.key),
                merge = this.value,
                reverse_key = this.name;

            while((k = keys.pop()) !== undefined){

                // Adjust reverse_key
                reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

                // Push
                if(k.match(patterns.push)){
                    merge = self.build([], self.push_counter(reverse_key), merge);
                }

                // Fixed
                else if(k.match(patterns.fixed)){
                    merge = self.build([], k, merge);
                }

                // Named
                else if(k.match(patterns.named)){
                    merge = self.build({}, k, merge);
                }
            }

            jsonData = $.extend(true,jsonData,merge);
        });

        return jsonData;
    };
	
	$(document).ready(function(){

		// requests handler
		
		var ajaxQueue = $({});

		$.ajaxQueue = function( ajaxOpts ) {
			
			var jqXHR,
				dfd = $.Deferred(),
				promise = dfd.promise();

			// queue our ajax request
			ajaxQueue.queue( doRequest );

			// add the abort method
			promise.abort = function( statusText ) {

				// proxy abort to the jqXHR if it is active
				if ( jqXHR ) {
					return jqXHR.abort( statusText );
				}

				// if there wasnt already a jqXHR we need to remove from queue
				var queue = ajaxQueue.queue(),
					index = $.inArray( doRequest, queue );

				if ( index > -1 ) {
					queue.splice( index, 1 );
				}

				// and then reject the deferred
				dfd.rejectWith( ajaxOpts.context || ajaxOpts,
					[ promise, statusText, "" ] );

				return promise;
			};

			// run the actual query
			function doRequest( next ) {
				jqXHR = $.ajax( ajaxOpts )
					.done( dfd.resolve )
					.fail( dfd.reject )
					.then( next, next );
			}

			return promise;
		};
		
		// array input

		function set_array_field(id){
			
			$(id + " .add-input-group").on('click', function(e){
				
				e.preventDefault();
				
				var target 	= "#" + $(this).data("target");
				
				if( typeof $(this).data("html") != typeof undefined ){
					
					var html = $(this).data("html");
			
					var $block = $($.parseHTML(html));
				
					$(target + " .arr-input-group").append($block);				
				}
				else{
						
					var $clone 	= $(target + " .input-group-row").eq(0).clone().removeClass('ui-state-disabled');
					
					$clone.find('input,textarea,select,radio').val('');
					
					var $rands	= $clone.find('input[data-value="random"]');
					
					if( $rands.length > 0 ){
						
						$rands.val(Math.floor(Math.random()*1000000000));
					}
					
					if( $clone.find('a.remove-input-group').length < 1 ){
					
						$clone.append('<a class="remove-input-group" href="#">x</a>');
					}
					
					$(this).next(".arr-input-group").append($clone);

                    // Reset select elements to the marked selected option or the first option
                    
                    $clone.find('select').each(function () {
                        
                        var $select = $(this);
                        var $selectedOption = $select.find('option[selected]'); // Look for a selected option

                        if ($selectedOption.length > 0) {
                            
                            $select.val($selectedOption.val()); // Use the selected option's value
                        } 
                        else {
                            
                            $select.val($select.find('option:first').val()); // Fallback to the first option
                        }
                    });
				}
			});
			
			$(id + " .arr-input-group").on('click', ".remove-input-group", function(e){

				e.preventDefault();
				
				$(this).closest('.input-group-row').remove();
				
				load_task_items();
			});	
		}
		
		$(".arr-input").each(function(e){
			
			var id = "#" + $(this).attr('id');
			
			set_array_field(id);
		});
		
		// meta input
		
		function set_meta_field(id){
			
			$(id + " .add-input-group").on('click', function(e){
				
				e.preventDefault();
				
				var target 	= "#" + $(this).data("target");
				
				if( typeof $(this).data("html") != typeof undefined ){
					
					var html = $(this).data("html");
			
					var $block = $($.parseHTML(html));
				
					$(target + " .meta-input-group").append($block);				
				}
				else{
						
					var $clone 	= $(target + " .input-group-row").eq(0).clone().removeClass('ui-state-disabled');
					
					$clone.find('input,textarea,select,radio').val('');
					
					var $rands	= $clone.find('input[data-value="random"]');
					
					if( $rands.length > 0 ){
						
						$rands.val(Math.floor(Math.random()*1000000000));
					}
					
					if( $clone.find('a.remove-input-group').length < 1 ){
					
						$clone.append('<a class="remove-input-group" href="#">x</a>');
					}
					
					$(this).next(".meta-input-group").append($clone);
                    
                    // Reset select elements to the marked selected option or the first option
                    
                    $clone.find('select').each(function () {
                        
                        var $select = $(this);
                        var $selectedOption = $select.find('option[selected]'); // Look for a selected option

                        if ($selectedOption.length > 0) {
                            
                            $select.val($selectedOption.val()); // Use the selected option's value
                        } 
                        else {
                            
                            $select.val($select.find('option:first').val()); // Fallback to the first option
                        }
                    });
				}
			});
			
			$(id + " .meta-input-group").on('click', ".remove-input-group", function(e){

				e.preventDefault();
				
				$(this).closest('.input-group-row').remove();
				
				load_task_items();
			});	
		}
		
		$(".meta-input").each(function(e){
			
			var id = "#" + $(this).attr('id');
			
			set_meta_field(id);
		});
		
		// date input

		function set_date_field(id){
			
			$(id + " .add-date-group").on('click', function(e){
				
				e.preventDefault();
				
				var target 	= "#" + $(this).data("target");
				
				if( typeof $(this).data("html") != typeof undefined ){
					
					var html = $(this).data("html");
			
					var $block = $($.parseHTML(html));
				
					$(target + " .date-input-group").append($block);				
				}
			});
			
			$(id + " .date-input-group").on('click', ".remove-input-group", function(e){

				e.preventDefault();
				
				$(this).closest('.input-group-row').remove();
				
				load_task_items();
			});	
		}
		
		$(".date-input").each(function(e){
			
			var id = "#" + $(this).attr('id');
			
			set_date_field(id);
		});
		
		// taxonomy fields
		
		function set_taxonomy_field(id){
			
			// handle the click of close button on the tags

			$(document).on("click", id + " .data .item .close", function() {
				
				$(this).parent().remove();
				
				load_task_items();
			});

			// Handle the click of one suggestion

			$(document).on("click", id + " .autocomplete-items div", function() {
				
				let index=$(this).index()
				let data=_tag_input_suggestions_data[index];
				let data_holder = $(this).parents().eq(4).find(id + " .data")
				let name = $(id + " .data input:first").attr("name");

				$(data_holder).parents().eq(2).find(id + " .data").append(data.html);
				$(data_holder).val("");
				
				$(id + " .autocomplete-items").html("");
			});

			// detect enter on the input
			 
			$(id + " input").on( "keydown", function(e) {
				
				if(e.which == 13){
				
					e.preventDefault();
					
					return false;
				}
			});

			$(id + " input").on( "focusout", function(event) {
				
				$(this).val("")
				var that = this;
				setTimeout(function(){ $(that).parents().eq(2).find(".autocomplete .autocomplete-items").html(""); }, 500);
			
			});
			
			var typing;
			
			$(id + " input").on( "keyup", function(event) {
				
				clearTimeout(typing);

				var query = $(this).val()

				if(event.which == 8) {
					
					if(query==""){
						
						// clear suggestions
					
						$(id + " .autocomplete-items").html("");
						
						return;
					
					}
				
				}
				
				if( query.length < 3 ){
					
					return false;
				}
				
				$(id + " .autocomplete-items").html("");

				var element = $(this);
				
				let sug_area = element.parent().find(".autocomplete-items");
				
				let taxonomy = $(id).attr("data-taxonomy");
				
				let hierarchical = $(id).attr("data-hierarchical");
				
				let operator = $(id).attr("data-operator"); 
				
				let context = $(id).attr("data-context");
				
				typing = setTimeout(function() {

					// using ajax to populate suggestions
					
					element.addClass('loading');
					
					$.ajax({
						url : ajaxurl,
						type: "GET",
						dataType : "json",
						data : {
							
							action 		: "render_taxonomy_terms",
							taxonomy 	: taxonomy,
							h			: hierarchical,
							o			: operator,
							s 			: query,
							c			: context,
						},
					}).done(function( data ) {
						
						let val = element.val();
						
						if( query == val ){
							
							element.removeClass('loading');
							
							_tag_input_suggestions_data = data;
							
							$.each(data,function (key,value) {
								
								let template = $("<div>"+value.name+"</div>").hide()
								sug_area.append(template)
								template.show()

							});
						}
						else if( val.length < 3 ){
							
							element.removeClass('loading');
						}
					});

				},500);
				
			});
		}
		
		let _tag_input_suggestions_data = null;
		
		$(".tags-input").each(function(e){
			
			var id = "#" + $(this).attr('id');
			
			set_taxonomy_field(id);
		});
		
		// authors

		function set_author_field(id){
			
			let multi = $(id).attr("data-multi");
			
			if( $(id + " .item").length > 0 && multi == 'false' ){
				
				$(id + " .autocomplete").hide();
			}
			
			// handle the click of close button on the tags

			$(document).on("click", id + " .data .item .close", function() {

				if( multi == 'false' ){
					
					$(id + " .autocomplete").show();
				}
				
				$(this).parent().remove();
				
				load_task_items();
			});

			// Handle the click of one suggestion

			$(document).on("click", id + " .autocomplete-items div", function() {
				
				let index=$(this).index()
				let data=_authors_input_suggestions_data[index];
				let data_holder = $(this).parents().eq(4).find(id + " .data")
				let name = $(id + " .data input:first").attr("name");

				$(data_holder).parents().eq(2).find(id + " .data").append(data.html);
				$(data_holder).val("");
				
				$(id + " .autocomplete-items").html("");
				
				if( multi == 'false' ){
					
					$(id + " .autocomplete").hide();
				}
			});

			// detect enter on the input
			 
			$(id + " input").on( "keydown", function(e) {
				
				if(e.which == 13){
				
					e.preventDefault();
					
					return false;
				}
			});

			$(id + " input").on( "focusout", function(event) {
				
				$(this).val("")
				var that = this;
				setTimeout(function(){ $(that).parents().eq(2).find(".autocomplete .autocomplete-items").html(""); }, 500);
			
			});
			
			var typing;
			
			$(id + " input").on( "keyup", function(event) {
				
				clearTimeout(typing);

				var query = $(this).val();

				if(event.which == 8) {
					
					if(query==""){
						
						// clear suggestions
					
						$(id + " .autocomplete-items").html("");
						
						return;
					
					}
				}
				
				if( query.length < 3 ){
					
					return false;
				}
				
				$(id + " .autocomplete-items").html("");

				var element = $(this);
				
				let sug_area = element.parent().find(".autocomplete-items");
				
				typing = setTimeout(function() {

					// using ajax to populate suggestions
					
					element.addClass('loading');
					
					$.ajax({
						url : ajaxurl,
						type: "GET",
						dataType : "json",
						data : {
							
							action 	: "render_authors",
							id		: id,
							s 		: query,
						},
					}).done(function( data ) {
						
						let val = element.val();
						
						if( query == val ){
							
							element.removeClass('loading');
						
							_authors_input_suggestions_data = data;
							
							$.each(data,function (key,value) {
								
								let template = $("<div>"+value.name+"</div>").hide()
								sug_area.append(template)
								template.show()
							});
						}
						else if( val.length < 3 ){
							
							element.removeClass('loading');
						}
					});

				},500);
				
			});
		}
		
		let _authors_input_suggestions_data = null;
		
		$(".authors-input").each(function(e){
			
			var id = "#" + $(this).attr('id');
			
			set_author_field(id);
		});
		
        // update task
        
        var savingTask = false;
        
        function update_task(btn){
            
            if( savingTask === false ){
                
                savingTask = true;
                
                $(btn).hide();
                
                $('.update-loader').addClass('loading');
                
                $.ajax({
                    url : ajaxurl,
                    type: "POST",
                    dataType : "json",
                    data : {
                        action 	: "save_task",
                        task 	: $("#post").serializeObject(),
                    },
                }).done(function( data ) {
                    
                    savingTask = false;
                    
                    $(btn).show();
                
                    $('.update-loader').removeClass('loading');
                    
                    $('#rewbe_process_status').val(data.status);
                    
                    $('#rewbe_task_scheduled').text(data.scheduled + '%');
                    
                    $('#rewbe_task_processed').text(data.processed + '%');
                    
                    if( data.steps > 0 ){
                        
                        $('#rewbe_task_scheduled').attr('data-steps',data.steps);
                    }
                    
                    if( data.status != 'pause' ){
                    
                        if( data.scheduled < 100 ){
                            
                            load_task_schedule();
                        }
                        else if( data.processed < 100 ){
                            
                            load_task_progess();
                        }
                    }
                    
                    // Check if the current URL matches the pattern
                    
                    if ( window.location.href.includes('wp-admin/post-new.php') ) {
                        
                        var post_id = $("#post_ID").val();
                        
                        var newUrl = '/wp-admin/post.php?post=' + post_id + '&action=edit';

                        window.history.replaceState(null, '', newUrl);
                    }
                });
            }
        }
                
        $('#bulk-editor-update input[type="submit"]').on('click', function(e) {
			
            e.preventDefault();

			update_task(this);
		});
        
		// task process
		
		function load_task_items(){
			
			$("#rewbe_task_items").empty().addClass("loading");
				
			if( $('#rew_preview_items').length > 0 ){
			
				$('#rew_preview_items table').empty();
	
				$('#rew_preview_items').addClass('loading');
			}
			
			clearTimeout(processing);
			
			processing = setTimeout(function() {
				
				$.ajax({
					url : ajaxurl,
					type: "POST",
					dataType : "html",
					data : {
						action 	: "render_task_process",
						task 	: $("#post").serializeObject(),
					},
				}).done(function( data ) {
					
					$("#rewbe_task_items").empty().removeClass("loading").html(data);
					
					set_preview_button();
				});

			},100);
		}
		
		var processing;
		
		load_task_items();
		
		$('#bulk-editor-filters').on('change', 'select:not(:first), input, textarea', function() {
			
			load_task_items();
		});
		
		function load_task_schedule(){
			
			if( $('#rewbe_task_scheduled').length > 0 ){
				
				var steps 	= $('#rewbe_task_scheduled').attr('data-steps');
				
				if( steps > 0 ){
					
					$("#rewbe_task_scheduled").addClass("loading loading-right");
					
					var post_id = $("#post_ID").val();
					
					for(var step = 1; step <= steps; step++){
						
						$.ajaxQueue({
							
							url : ajaxurl,
							type: 'GET',
							data: {
								action 	: "render_task_schedule",
								pid 	: post_id,
								step	: step
							},
							success: function(prog){
								
								$('#rewbe_task_scheduled').empty().html( prog + '%' );
							
								if( prog == 100 ){
									
									$("#rewbe_task_scheduled").removeClass("loading loading-right");
									
									load_task_progess();
								}
							},
							error: function(xhr, status, error){
								
								console.error('Error loading step ' + step + ': ' + error);
							}
						});
					}
				}
			}
			else{
			
				load_task_progess();
			}
		}
		
		function load_task_progess(){
			
			if( $('#rewbe_task_processed').length > 0 ){
				
				$("#rewbe_task_processed").addClass("loading loading-right");
				
				var post_id = $("#post_ID").val();
				
				$.ajaxQueue({
					
					url : ajaxurl,
					type: 'GET',
					data: {
						action 	: "render_task_progress",
						pid 	: post_id,
					},
					success: function(prog){
						
                        if( !$('#rewbe_task_scheduled').hasClass('loading') ){
                            
                            var response = prog;
                            
                            if( !isNaN(prog) && !isNaN(parseFloat(prog)) ){
                                
                                $('#rewbe_task_processed').empty().html(prog+'%');
                                
                                if( prog < 100 ){
                                    
                                    load_task_progess();
                                }
                                else{
                                    
                                    $("#rewbe_task_processed").removeClass("loading loading-right");
                                }
                            }
                            else{
                               
                                $("#rewbe_task_processed").removeClass("loading loading-right");
                                
                                open_task_console(prog);
                            }
                        }
                        else{
                            
                            $("#rewbe_task_processed").removeClass("loading loading-right");
                        }
					},
					error: function(xhr, status, error){
						
						if( xhr.status === 500 || xhr.status === 504 ) {
							 
							console.log('Retrying after 10 seconds...');
							
							setTimeout(function(){
								
								$.ajaxQueue(this);
								
							}.bind(this),10000);
						}
						else{
							
							console.error('Error processing task: ' + error);
							
							$("#rewbe_task_processed").removeClass("loading loading-right");
						}
					}
				});
			}
		}		
		
        if( $('#rewbe_process_status').val() != 'pause' ){
        
            if( $('#rewbe_task_scheduled').text() == '100%' ){
            
                load_task_progess();
            }
            else{
                
                //load_task_schedule();
            }
        }
		
        // filter fields
        
        function load_task_filters(){

			clearTimeout(selectingFilters);
			
			var post_id = $("#post_ID").val();
    
			selectingFilters = setTimeout(function() {
    
				if( $("#rewbe_task_filters").length == 0 ){
					
					$('#bulk-editor-filters .form-field').not(':first').remove();

					$('#bulk-editor-filters .form-field:first').after('<div id="rewbe_task_filters"></div>')
                }
				
				$("#rewbe_task_filters").empty().addClass("loading");
				
                var type = $('#bulk-editor-filters .form-field:first select').val();

				$.ajax({
					url : ajaxurl,
					type: "GET",
					dataType : "html",
					data : {
						action 	: "render_task_filters",
						pid 	: post_id,
                        type    : type,
					},
                    beforeSend: function() {
                        
                        load_action_fields();
                    },
                    
				}).done(function( data ) {
					
                    $("#rewbe_task_filters").empty().removeClass("loading").html(data);
					
                    $("#rewbe_task_filters").find('.authors-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_author_field(id);
					});	
					
					$("#rewbe_task_filters").find('.arr-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_array_field(id);
					});
					
					$("#rewbe_task_filters").find('.meta-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_meta_field(id);
					});
					
					$("#rewbe_task_filters").find('.date-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_date_field(id);
					});
					
					$("#rewbe_task_filters").find('.tags-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_taxonomy_field(id);
					});
                    
                    load_task_items();
				});

			},100);
		}
        
        var selectingFilters;

		$("#bulk-editor-filters .form-field:first").on('change', function(e){
			
			load_task_filters();
		});
        
		// action fields
		
		function load_action_fields(){
			
			clearTimeout(selectingAction);
			
			var post_id = $("#post_ID").val();
            var type    = $('#bulk-editor-filters .form-field:first select').val();
			var action  = $("#rewbe_action").val();
			
			selectingAction = setTimeout(function() {

				if( $("#rewbe_action_fields").length == 0 ){
					
					$('#bulk-editor-task .form-field').not(':first').remove();
					
					$('#bulk-editor-task .form-field:first').after('<div id="rewbe_action_fields"></div>')
				}
				
				$("#rewbe_action_fields").empty().addClass("loading");
				
				$.ajax({
					url : ajaxurl,
					type: "GET",
					dataType : "html",
					data : {
						action 	: "render_task_action",
						pid 	: post_id,
                        type 	: type,
						ba 		: action,
					},
				}).done(function( data ) {
                    
                    var html = $('<div>').html(data);

                    // Extract options from the first field and replace in the original field
                    var firstField = html.find('.form-field:first');
                    var newOptions = firstField.find('select').html();
                    $('#bulk-editor-task .form-field:first select').html(newOptions);

                    // Append remaining fields to #rewbe_action_fields
                    html.find('.form-field').not(':first').appendTo("#rewbe_action_fields");
                    
                    $("#rewbe_action_fields").removeClass("loading");
        
					$("#rewbe_action_fields").find('.authors-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_author_field(id);
					});	
					
					$("#rewbe_action_fields").find('.arr-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_array_field(id);
					});
					
					$("#rewbe_action_fields").find('.meta-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_meta_field(id);
					});
					
					$("#rewbe_action_fields").find('.date-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_date_field(id);
					});
					
					$("#rewbe_action_fields").find('.tags-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_taxonomy_field(id);
					});
				});

			},100);
		}
		
		var selectingAction;

		$("#rewbe_action").on('change', function(e){
			
			load_action_fields();
		});
		
		// set action buttons
		
		$('.postbox').each(function() {
			
			var id = $(this).attr('id');
			
			var actionBtns = '';
			
			if( id == 'bulk-editor-task' ){
				
				actionBtns = $('<button/>', {
					html: '<span class="dashicons dashicons-update" aria-hidden="true"></span>',
					class: 'handle-order-higher',
					click: function(e) {
						
						e.preventDefault();
						e.stopPropagation();
						
						load_action_fields();
					}
				});
			}
			else if( id == 'bulk-editor-process' ){
				
				actionBtns = $('<button/>', {
					html: '<span class="dashicons dashicons-update" aria-hidden="true"></span>',
					class: 'handle-order-higher',
					click: function(e) {
						
						e.preventDefault();
						e.stopPropagation();
						
						load_task_items();
					}
				});
			}
			else if ( id == 'bulk-editor-progress' && $('#rewbe_task_processed').length > 0 ) {
                
                actionBtns = $('<button/>', {
					html: '<span class="dashicons dashicons-update" aria-hidden="true"></span>',
					class: 'handle-order-higher',
					click: function(e) {
						
						e.preventDefault();
						e.stopPropagation();
						
						load_task_progess();
					}
				});
			}
            else if ( id == 'bulk-editor-filters' ){
            
                actionBtns = $('<button/>', {
					html: '<span class="dashicons dashicons-update" aria-hidden="true"></span>',
					class: 'handle-order-higher',
					click: function(e) {
						
						e.preventDefault();
						e.stopPropagation();
						
						load_task_filters();
					}
				});
            }
            
			$(this).find('h2').append(actionBtns);
		});
		
        consoleBtn = $('<button/>', {
            html: '<span class="dashicons dashicons-bell" aria-hidden="true" ></span>',
            class: 'handle-order-higher',
            style: 'position:absolute;z-index:9999;right:5px;top:5px;cursor:pointer;background:#fff;border:none;padding:5px;',
            click: function(e) {
                
                e.preventDefault();
                e.stopPropagation();
                
                open_task_console();
            }
        });
        
        $('#titlewrap').append(consoleBtn);
        
		// set preview button
		
		function set_preview_button(){
			
			var page = 1;
						
			if( $("#rew_preview_dialog").length == 0 ){
				
				$('body').append('<div id="rew_preview_dialog" title="Matching Items"><div id="rew_preview_items" class="loading"><table></table></div></div>');
				
				$("#rew_preview_dialog").dialog({
				
					autoOpen	: false,
					
					width		: Math.round($(window).width() * 0.5),  // 50% of current window width
					height		: Math.round($(window).height() * 0.5), // 50% of current window height
					minWidth	: 250,
					minHeight	: 250,
					resizable	: true,
					position: {
						my: "center",
						at: "center",
						of: window
					},
					create : function (event) {
						
						$(event.target).parent().css({ 
						
							'position'	: 'fixed', 
							'left'		: 50, 
							'top'		: 150,
						});
						
						load_task_preview(page);
					},
					close : function (event) {
						
						// do something
					},
				});
			}
			else{
				
				load_task_preview(page);
			}
			
			$("#rew_preview_button").on('click', function(e){
				
				e.preventDefault();

				$("#rew_preview_dialog").dialog('open');
			});
		}
        
        function open_task_console(message){
            
            if( $("#rew_console_dialog").length == 0 ){
				
                $('body').append('<div id="rew_console_dialog" title="Task Console"><div id="rew_console_items" style="width:100%;"><table></table></div></div>');
                
                $("#rew_console_dialog").dialog({
                
                    autoOpen	: false,
                    
                    width		: Math.round($(window).width() * 0.5),  // 50% of current window width
                    height		: Math.round($(window).height() * 0.5), // 50% of current window height
                    minWidth	: 250,
                    minHeight	: 250,
                    resizable	: true,
                    position: {
                        my: "center",
                        at: "center",
                        of: window
                    },
                    create : function (event) {
                        
                        $(event.target).parent().css({ 
                        
                            'position'	: 'fixed', 
                            'left'		: 50, 
                            'top'		: 150,
                        });
                        
                    },
                    close : function (event) {
                        
                        // do something
                    },
                });
            }
                            
            if( message ){
                
                $('#rew_console_items table').append('<tr><td>' + message + '<hr/></td></tr>' );
            }
            
            $("#rew_console_dialog").dialog('open');
        }
        
		function load_task_preview(page){
			
			$.ajaxQueue({
				
				url : ajaxurl,
				type: 'POST',
				data: {
					action 	: "render_task_preview",
					task 	: $("#post").serializeObject(),
					page	: page,
				},
				success: function(data){
					
					if( page == 1 ){
						
						$('#rew_preview_items table').empty().html(data);
					}
					else{
						
						$('#rew_preview_items table').append(data);
					}
				
					$('#rew_preview_items').removeClass('loading');
				},
				error: function(xhr, status, error){
					
					console.error('Error loading preview ' + page + ': ' + error);
				}
			});
		}
        
        function set_sidebar(){
            
            var sideSortables = $("#side-sortables");
            var wpAdminBar = $("#wpadminbar");
            var offset = sideSortables.offset().top;
            var adminBarHeight = wpAdminBar.length ? wpAdminBar.outerHeight() + 20 : 0; // Get the height of the admin bar

            $(window).on("scroll resize", function () {
                
                // Only apply sticky behavior if viewport is wider than 850px
                
                if ($(window).width() > 850) {
                    
                    if ($(window).scrollTop() > offset) {
                       
                        sideSortables.css({
                            position: "fixed",
                            top: adminBarHeight + "px", // Adjust the top position to account for the admin bar
                            zIndex: "100",
                            width: sideSortables.parent().width(), // Maintain width
                        });
                    } 
                    else {
                        
                        sideSortables.css({
                            position: "static",
                            width: "",
                        });
                    }
                } 
                else {
                    
                    // Reset styles if viewport is smaller than 850px
                    sideSortables.css({
                        position: "static",
                        width: "",
                    });
                }
            });
        }
        
        set_sidebar();
	});
	
})(jQuery);