/**
 * Plugin Template admin js.
 *
 *  @package REW Bulk Editor/JS
 */

;(function($){
 
	$(document).ready(function(){
        
        $(".duplicate-button").on("click",function(){
                
            var id 		= $(this).attr("data-id");
            var type 	= $(this).attr("data-type");
            
            var form = "<form action=\"admin-post.php\" method=\"post\">";
                
                form += "<input type=\"hidden\" name=\"action\" value=\"duplicate\">";
                form += "<input type=\"hidden\" name=\"id\" value=\"" + id + "\">";
                form += "<input type=\"hidden\" name=\"type\" value=\"" + type + "\">";
                
                form += "<input type=\"text\" name=\"title\" value=\"\" placeholder=\"New Title\" class=\"required\" required>";
                
                form += "<button class=\"button\" type=\"submit\" id=\"duplicateBtn\">Duplicate</button>";
                
            form += "</form>";
            
            $("#duplicateForm").empty().append(form);
        });
        
	});
})(jQuery);