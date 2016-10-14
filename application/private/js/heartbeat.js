(function($){

   var _events = [];

    Q['heartbeat'] = {
        bind: function(e, data, func) {
            _events.push({
                event: e,
                data: data,
                once: false,
                func: func
            });
        },
        one: function(e, data, func) {
            _events.push({
                event: e,
                data: data,
                once: true,
                func: func
            });
        }
 
    };


    $(function($) {
     
        setInterval(function(){

            var events = [];
            var ne = [];
            for( var i in _events) {
                events.push({
                    event: _events[i].event,
                    params: _events[i].func == undefined ? _events[i].data : _events[i].func(_events[i].data)
                });

                if (!_events[i].once) {
                    ne.push(_events[i]);
                }
            }

            var hasError = true;
            Q.trigger({
                object: 'heartbeat',
                event: 'check',
                global: false,
                data: {
                    events: events
                },
                success: function(data) {
                    hasError = false;
                    if (data.hasError == 'hasError') {
                        hasError = true;
                    }
                    _events = ne;
                },
                complete: function() {
                    if (hasError) {
                        window.location.href = window.location.href;
                    }
                }
            });

        }, 30000);
    });

})(jQuery);
