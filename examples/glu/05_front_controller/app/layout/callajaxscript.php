
YAHOO.namespace('glu.app.callajax');

YAHOO.glu.app.callajax = function(settings){

}

// PAGE INITIALIZATION
YAHOO.util.Event.onDOMReady(init);


function init() {
    YAHOO.util.Event.on('mylink', 'click', 
        function (e) { 
            YAHOO.util.Event.preventDefault(e); 
            
            var callback = { 
                success: function(o) {
                        document.getElementById('mydiv').innerHTML =  o.responseText;
                        }, 
                failure: function(o) {
                        alert("AJAX doesn't work"); //FAILURE
                        }
            } 
            
            var transaction = YAHOO.util.Connect.asyncRequest('GET', document.getElementById('mylink').href, callback, null);
            return false;
            
        } );
}