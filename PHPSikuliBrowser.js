var PHPSikuliBrowser = new function()
{
    var _pollingInterval = null;
    var _scriptURL       = '';

    this.pageLoaded = false;

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
                    scriptURL  = scripts[i].src.replace(/\/PHPSikuliBrowser\.js$/,'');
                    break;
                }
            }
        }

        if (scriptURL === '') {
            return;
        }

        this.setScriptURL(scriptURL);
        this.startPolling();

    };

    this.setScriptURL = function(url)
    {
        _scriptURL = url;
        this.stopPolling();

    };

    this.stopPolling = function()
    {
        clearInterval(_pollingInterval);

    };

    this.startPolling = function()
    {
        var scriptURL    = _scriptURL + '/jspoller.php';
        var pausePolling = false;
        var seconds      = 0.5;
        _pollingInterval = setInterval(function() {
            if (pausePolling === true) {
                return;
            }

            pausePolling = true;
            dfx.post(scriptURL, {_t:(new Date().getTime())}, function(val) {
                if (!val || val === 'noop') {
                    pausePolling = false;
                    return;
                }

                var async = false;
                if (val.indexOf('__asynchronous__') === 0) {
                    async = true;
                    val   = val.replace('__asynchronous__', '');
                }

                if (async === false) {
                    var jsResult = null;
                    val = 'try {jsResult = dfx.jsonEncode(' + val + ');} catch (e) {}';

                    // Execute JS.
                    eval(val);

                    dfx.post(scriptURL, {res: jsResult, _t:(new Date().getTime())}, function() {
                        pausePolling = false;
                    });
                } else {
                    eval(val);
                }
            });
        }, (1000 * seconds));
    };

    this.isPageLoaded = function(reset)
    {
        var loaded = this.pageLoaded;
        if (reset === true) {
            this.pageLoaded = false;
        }

        return loaded;

    };

    this.getBoundingRectangle = function(selector, index)
    {
        var rect = dfx.getBoundingRectangle(dfxjQuery(selector)[index]);
        rect.x1 = parseInt(rect.x1);
        rect.x2 = parseInt(rect.x2);
        rect.y1 = parseInt(rect.y1);
        rect.y2 = parseInt(rect.y2);
        return rect;

    };

    this.showTargetIcon = function()
    {
        var img = document.createElement('img');
        img.id  = 'PHPSikuliBrowser-window-target';
        img.src = _scriptURL + '/window-target.png';

        img.style.position = 'absolute';
        img.style.left     = 0;
        img.style.top      = 0;
        img.style.zIndex   = 9999;

        document.body.appendChild(img);

    };

    this.hideTargetIcon = function()
    {
        document.body.removeChild(document.getElementById('PHPSikuliBrowser-window-target'));

    };

    this.init();

}