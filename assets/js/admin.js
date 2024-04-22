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
		
		//input group add row

		$(".add-input-group").on('click', function(e){
			
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
		
		$(".input-group").on('click', ".remove-input-group", function(e){

			e.preventDefault();
			$(this).closest('.input-group-row').remove();
		});	
		
		// taxonomy fields

		function set_dynamic_field(id){
			
			// Handle click of the input area
			 
			$(id).click(function () {
				
				$(this).find("input").focus();
			
			});

			// handle the click of close button on the tags

			$(document).on("click", id + " .data .tag .close", function() {
				
				$(this).parent().remove();
			});

			// Handle the click of one suggestion

			$(document).on("click", id + " .autocomplete-items div", function() {
				
				let index=$(this).index()
				let data=_tag_input_suggestions_data[index];
				let data_holder = $(this).parents().eq(4).find(id + " .data")
				let name = $(id + " .data input:first").attr("name");
				let template="<span class=\"tag button button-default\"><span class=\"text\">"+data.name+"</span><span class=\"close\">&times;</span><input type=\"hidden\" value=\'"+data.id+"\' name=\'"+name+"\'/></span>\n";
				
				$(data_holder).parents().eq(2).find(id + " .data").append(template);
				$(data_holder).val("")
				
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
				
				typing = setTimeout(function() {

					// using ajax to populate suggestions
					
					element.addClass('loading');
					
					$.ajax({
						url : ajaxurl,
						type: "GET",
						dataType : "json",
						data : {
							
							action 		: "search_taxonomy_terms",
							taxonomy 	: taxonomy,
							s 			: query,
						},
					}).done(function( data ) {
						
						element.removeClass('loading');
						
						_tag_input_suggestions_data = data;
						
						$.each(data,function (key,value) {
							
							let template = $("<div>"+value.name+"</div>").hide()
							sug_area.append(template)
							template.show()

						})
						
					});

				},500);
				
			});
		}
		
		let _tag_input_suggestions_data = null;
		
		$(".tags-input").each(function(e){
			
			var id = "#" + $(this).attr('id');
			
			set_dynamic_field(id);
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
						action 	: "get_task_process",
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
						action 	: "get_bulk_action_form",
						pid 	: post_id,
						pt 		: post_type,
						ba 		: bulk_action,
					},
				}).done(function( data ) {
					
					$("#rewbe_action_fields").empty().removeClass("loading").html(data);
					
					$("#rewbe_action_fields").find('.tags-input').each(function(e){
						
						var id = "#" + $(this).attr('id');
						
						set_dynamic_field(id);
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