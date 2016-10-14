jQuery(function($){
    $('input.enter_to_tab').bind('keypress', function(e){
        var ekey = e.keyCode || e.which;
        if(ekey==13){
            return false;            
        }
    });
});
