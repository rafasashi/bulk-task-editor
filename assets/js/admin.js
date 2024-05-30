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
		
		//input group add row
		
		function set_meta_field(id){
			
			$(id + " .add-input-group").on('click', function(e){
				
				e.preventDefault();
				
				var target 	= "#" + $(this).data("target");
				
				if( typeof $(this).data("html") != typeof undefined ){
					
					var html = $(this).data("html");
			
					var $block = $($.parseHTML(html));
				
					$(target + " .input-group").append($block);				
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
					
					$(this).next(".input-group").append($clone);
				}
			});
			
			$(id + " .input-group").on('click', ".remove-input-group", function(e){

				e.preventDefault();
				
				$(this).closest('.input-group-row').remove();
				
				load_task_process();
			});	
		}
		
		$(".meta-input").each(function(e){
			
			var id = "#" + $(this).attr('id');
			
			set_meta_field(id);
		});
		
		// taxonomy fields
		
		function set_taxonomy_field(id){
			
			// handle the click of close button on the tags

			$(document).on("click", id + " .data .item .close", function() {
				
				$(this).parent().remove();
				
				load_task_process();
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
				
				load_task_process();
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
							
							action 		: "render_authors",
							s 			: query,
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
		
		// task process
		
		function load_task_process(){
			
			$("#rewbe_task_process").empty().addClass("loading");
				
			clearTimeout(processing);
			
			processing = setTimeout(function() {
				
				$.ajax({
					url : ajaxurl,
					type: "GET",
					dataType : "html",
					data : {
						action 	: "render_post_type_process",
						task 	: $("#post").serializeObject(),
					},
				}).done(function( data ) {
					
					$("#rewbe_task_process").empty().removeClass("loading").html(data);
				});

			},100);
		}
		
		var processing;
		
		load_task_process();
		
		$('#bulk-editor-filters').on('change', 'input, select, textarea', function() {
			
			load_task_process();
		});
		
		function load_task_schedule(){
			
			if( $('#rewbe_task_scheduled').length > 0 ){
				
				$("#rewbe_task_scheduled").addClass("loading loading-right");
				
				var steps 	= $('#rewbe_task_scheduled').data('steps');
				
				var post_id = $("#post_ID").val();
				
				for(var step = 1; step <= steps; step++){
					
					$.ajaxQueue({
						
						url : ajaxurl,
						type: 'GET',
						data: {
							action 	: "render_post_type_schedule",
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
						action 		: "render_post_type_progress",
						pid 		: post_id,
						bulk_edit 	: "" // prevent meta update
					},
					success: function(prog){
						
						$('#rewbe_task_processed').empty().html( prog + '%' );
					
						if( prog < 100 ){
							
							load_task_progess();
						}
						else{
							
							$("#rewbe_task_processed").removeClass("loading loading-right");
						}
					},
					error: function(xhr, status, error){
						
						console.error('Error processing task: ' + error);
					}
				});
			}
		}		
		
		load_task_schedule();
		
		// action fields
		
		function load_action_fields(){
			
			$("#rewbe_action_fields").empty().addClass("loading");
				
			clearTimeout(selecting);
			
			var post_id 	= $("#post_ID").val();
			var post_type 	= $("#rewbe_post_type").val();
			var bulk_action = $("#rewbe_action").val();
			
			selecting = setTimeout(function() {

				$.ajax({
					url : ajaxurl,
					type: "GET",
					dataType : "html",
					data : {
						action 	: "render_post_type_action",
						pid 	: post_id,
						pt 		: post_type,
						ba 		: bulk_action,
					},
				}).done(function( data ) {
					
					$("#rewbe_action_fields").empty().removeClass("loading").html(data);
					
					$("#rewbe_action_fields").find('.authors-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_author_field(id);
					});	
					
					$("#rewbe_action_fields").find('.meta-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_meta_field(id);
					});
					
					$("#rewbe_action_fields").find('.tags-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_taxonomy_field(id);
					});
				});

			},100);
		}
		
		var selecting;
		
		load_action_fields();
		
		$("#rewbe_action").on('change', function(e){
			
			load_action_fields();
		});
	});
	
})(jQuery);