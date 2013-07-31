var PHPSikuliBrowser = new function()
{
    this.init = function()
    {
        // Script location.
        var scriptURL = '';
        var scripts   = document.getElementsByTagName('script');
        var path      = null;
        var c         = scripts.length;
        for (var i = 0; i < c; i++) {
            if (scripts[i].src) {
                if (scripts[i].src.match(/\/PHPSikuliBrowser\.js$/)) {
                    scriptURL = scripts[i].src.replace(/\/PHPSikuliBrowser\.js$/,'') + '/jspoller.php';
                    break;
                }
            }
        }

        if (scriptURL === '') {
            return;
        }

        var stopPolling = false;
        var seconds     = 0.5;
        var interval    = null;
        interval = setInterval(function() {
            if (stopPolling === true) {
                return;
            }

            stopPolling = true;
            dfx.post(scriptURL, {_t:(new Date().getTime())}, function(val) {
                if (!val) {
                    stopPolling = false;
                    return;
                }

                var async = false;
                if (val.indexOf('__asynchronous__') === 0) {
                    async = true;
                    val   = val.replace('__asynchronous__', '');
                }

                if (val === 'cw()' || val === 'cw();') {
                    stopPolling = true;
                    clearInterval(interval);
                    return;
                }

                if (async === false) {
                    var jsResult = null;
                    val = 'try {jsResult = dfx.jsonEncode(' + val + ');} catch (e) {}';

                    // Execute JS.
                    eval(val);

                    dfx.post(scriptURL, {res: jsResult, _t:(new Date().getTime())}, function() {
                        stopPolling = false;
                    });
                } else {
                    eval(val);
                }
            });
        }, (1000 * seconds));

    };

    this.init();

}