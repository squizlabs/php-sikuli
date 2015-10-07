var PHPSikuliBrowser = new function()
{
    var _pollingInterval = null;
    var _scriptURL       = '';
    var _jsErrors        = [];

    this.pageLoaded = false;

    var _jQuery = window.$ || window.jQuery || window.dfxjQuery;

    var errHandler = window.onerror;
    window.onerror = function (errorMsg, url, lineNumber, column, errorObj)
    {
        _jsErrors.push({
            errorMsg: errorMsg,
            url: url,
            lineNumber: lineNumber,
            column: column,
            stackTrace: errorObj.stack
        });

        if (errHandler) {
            errHandler.apply(this, arguments);
        }

    };

    this.init = function()
    {
        // Script location.
        var scriptURL = '';
        var scripts   = document.getElementsByTagName('script');
        var path      = null;
        var c         = scripts.length;
        for (var i = 0; i < c; i++) {
            if (scripts[i].src) {
                if (scripts[i].src.match(/\/PHPSikuliBrowser\.js/)) {
                    scriptURL  = scripts[i].src.replace(/\/PHPSikuliBrowser\.js.*$/,'');
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

    this.getScriptURL = function()
    {
        return _scriptURL;

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
            _jQuery.post(scriptURL, {_t:(new Date().getTime())}, function(val) {
                if (!val || val === 'noop') {
                    pausePolling = false;
                    return;
                }

                var jsResult = null;
                val = 'try {jsResult = JSON.stringify(' + val + ');} catch (e) {}';

                // Execute JS.
                eval(val);

                _jQuery.post(scriptURL, {res: 'result:' + jsResult, _t:(new Date().getTime())}, function() {
                    pausePolling = false;
                });
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

    this.getJSErrors = function()
    {
        return _jsErrors;

    };

    this.getBoundingRectangle = function(selector, index, fallbackToVisible)
    {
        var elem   = _jQuery(selector)[index];
        var offset = _jQuery(elem).offset();
        var rect   = {
            x1: parseInt(offset.left),
            x2: parseInt(offset.left + elem.offsetWidth),
            y1: parseInt(offset.top),
            y2: parseInt(offset.top + elem.offsetHeight)
        };

        if (fallbackToVisible === true
            && (rect.x1 === rect.x2
            || rect.y1 === rect.y2)
        ) {
            rect       = null;
            var elems  = _jQuery(selector);
            for (var i = 0; i < elems.length; i++) {
                var offset  = _jQuery(elems[i]).offset();
                var tmpRect = {
                    x1: parseInt(offset.left),
                    x2: parseInt(offset.left + elems[i].offsetWidth),
                    y1: parseInt(offset.top),
                    y2: parseInt(offset.top + elems[i].offsetHeight)
                };

                if (tmpRect.x1 !== tmpRect.x2 && tmpRect.y1 !== tmpRect.y2) {
                    rect = tmpRect;
                    break;
                }
            }


        }

        return rect;

    };

    this.showTargetIcon = function()
    {
        var img = document.createElement('img');
        img.id  = 'PHPSikuliBrowser-window-target';
        img.src = _scriptURL + '/window-target.png';

        img.style.position = 'fixed';
        img.style.left     = 0;
        img.style.top      = 0;
        img.style.zIndex   = 99999;

        document.body.appendChild(img);

    };

    this.hideTargetIcon = function()
    {
        document.body.removeChild(document.getElementById('PHPSikuliBrowser-window-target'));

    };

    this.init();

}
