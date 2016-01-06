<?php
/**
 * PHPSikuliBrowser is an extension to PHPSikuli.
 *
 * @category  PHP
 * @package   php-sikuli
 * @copyright 2013 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/php-sikuli/blob/master/licence.txt BSD Licence
 * @link      https://github.com/squizlabs/php-sikuli
 */

require_once 'PHPSikuli.php';

class PHPSikuliBrowser extends PHPSikuli
{

    /**
     * Browser window range object.
     *
     * @var string
     */
    private $_window = NULL;

    /**
     * Size of the browser window.
     *
     * @var array
     */
    private $_windowSize = NULL;

    /**
     * The browserid.
     *
     * @var string
     */
    private $_browserid = NULL;

    /**
     * The location of the top left corner of the page on the screen.
     *
     * @var array
     */
    private $_pageTopLeft = NULL;

    /**
     * Temporary directory used during script execution.
     *
     * @var string
     */
    private $_tmpDir = NULL;

    /**
     * Create files and directories with this group.
     *
     * @var string
     */
    private $_fileGroup = NULL;

    /**
     * List of supported browsers.
     *
     * @var array
     */
    private $_supportedBrowsers = array(
                                   'firefox'        => 'Firefox',
                                   'firefoxNightly' => 'FirefoxNightly',
                                   'chrome'         => 'Google Chrome',
                                   'chromium'       => 'Chromium',
                                   'chromeCanary'   => 'Google Chrome Canary',
                                   'safari'         => 'Safari',
                                   'ie8'            => 'Internet Explorer 8',
                                   'ie9'            => 'Internet Explorer 9',
                                   'ie10'           => 'Internet Explorer 10',
                                   'ie11'           => 'Internet Explorer 11',
                                   'edge'           => 'Edge',
                                  );

    /**
     * Default size of the browser window.
     *
     * @var array
     */
    private $_defaultWindowSize = NULL;

    /**
     * The delay after a mouse click in milliseconds.
     *
     * @var integer
     */
    private $_clickDelay = 0;

    /**
     * Default position of the browser window.
     *
     * @var array
     */
    private $_defaultWindowPosition = NULL;


    /**
     * Constructor.
     *
     * @param string $browser  The browser to use.
     * @param array  $settings Settings for PHPSikuliBrowser.
     */
    public function __construct($browser, array $settings=array())
    {
        $this->_tmpDir = dirname(__FILE__).'/tmp';
        if (file_exists($this->_tmpDir) === FALSE) {
            mkdir($this->_tmpDir, 0770, TRUE);
        } else {
            // Remove temp files.
            $files = glob($this->_tmpDir.'/*.*');
            foreach ($files as $file) {
                if (is_file($file) === TRUE) {
                    unlink($file);
                }
            }
        }

        $this->_handleSettings($settings);

        chmod($this->_tmpDir, 0770);
        if ($this->_fileGroup !== NULL) {
            chgrp($this->_tmpDir, $this->_fileGroup);
        }

        parent::__construct();

        $this->_setBrowser($browser);

    }//end __construct()


    /**
     * Resets the Sikuli connection.
     *
     * @return void
     */
    public function resetConnection()
    {
        $this->stopJSPolling();

        parent::resetConnection();
        $this->_windowSize = NULL;
        $this->_setBrowser($this->_browserid);

    }//end resetConnection()


    /**
     * Reloads the page.
     *
     * @return void
     */
    public function reloadPage()
    {
        $this->_pageTopLeft = NULL;
        $this->stopJSPolling();
        $this->keyDown('Key.CMD + r');

    }//end reloadPage()


    /**
     * Sets the browser URL to the specified URL.
     *
     * @param string $url The new URL.
     *
     * @return void
     */
    public function goToURL($url)
    {
        $this->stopJSPolling();
        $this->keyDown('Key.CMD+l');
        $this->type($url);
        $this->keyDown('Key.ENTER');
        sleep(1);

    }//end goToURL()


    /**
     * Type the text at the current focused input field or at a click point specified by PSMRL.
     *
     * @param string $text      The text to type.
     * @param string $modifiers Key modifiers.
     * @param string $psmrl     PSMRL variable.
     *
     * @return integer
     */
    public function type($text, $modifiers=NULL, $psmrl=NULL)
    {
        // We may need to increase the timeout when typing. It takes about 5s to
        // type 100 characters.
        $length  = strlen($text);
        $timeout = (int) (($length * 10) / 100);

        $currentTimeout = NULL;
        if ($timeout > 10) {
            $timeout       += 10;
            $currentTimeout = $this->setSikuliCMDTimeout($timeout);
        }

        $result = parent::type($text, $modifiers, $psmrl);

        if ($timeout > 10) {
            $this->setSikuliCMDTimeout($currentTimeout);
        }

        return $result;

    }//end type()


    /**
     * Returns the rectangle for a DOM element found using the specified selector.
     *
     * @param string  $selector          The jQuery selector to use for finding the element.
     * @param integer $index             The element index of the resulting array.
     * @param boolean $fallbackToVisible If set to true and the element at given index is not visible then the first
     *                                   visible element of the selector is returned.
     *
     * @return array
     */
    public function getBoundingRectangle($selector, $index=0, $fallbackToVisible=false)
    {
        if ($fallbackToVisible === true) {
            $fallbackToVisible = 'true';
        } else {
            $fallbackToVisible = 'false';
        }

        $selector = addcslashes($selector, '"');
        $rect     = $this->execJS('PHPSikuliBrowser.getBoundingRectangle("'.$selector.'", '.$index.', '.$fallbackToVisible.')', FALSE);
        return $rect;

    }//end getBoundingRectangle()


    /**
     * Clicks the specified location.
     *
     * @param string $psmrl     A Pattern, String, Match, Region or Location.
     * @param string $modifiers One or more key modifiers.
     *
     * @return void
     */
    public function click($psmrl, $modifiers=NULL)
    {
        parent::click($psmrl, $modifiers);

        if ($this->_clickDelay > 0) {
            usleep($this->_clickDelay * 1000);
        }

    }//end click()


    /**
     * Clicks an element in the content.
     *
     * @param string  $selector   The jQuery selector to use for finding the element.
     * @param integer $index      The element index of the resulting array.
     * @param boolean $rightClick If TRUE then right mouse button is used.
     *
     * @return void
     */
    public function clickElement($selector, $index=0, $rightClick=FALSE)
    {
        $region = $this->getElementRegion($selector, $index);

        // Click the element.
        if ($rightClick !== TRUE) {
            $this->click($region);
        } else {
            $this->rightClick($region);
        }

    }//end clickElement()


    /**
     * Clicks an element in the content.
     *
     * Only the first visible element will be clicked.
     *
     * @param string  $selector   The jQuery selector to use for finding the element.
     * @param boolean $rightClick If TRUE then right mouse button is used.
     *
     * @return void
     */
    public function clickVisibleElement($selector, $rightClick=FALSE)
    {
        $region = $this->getElementRegion($selector, 0, true);

        // Click the element.
        if ($rightClick !== TRUE) {
            $this->click($region);
        } else {
            $this->rightClick($region);
        }

    }//end clickVisibleElement()


    /**
     * Returns the region object of the element found using the selector.
     *
     * @param string  $selector          The jQuery selector to use for finding the element.
     * @param integer $index             The element index of the resulting array.
     * @param boolean $fallbackToVisible If set to true and the element at given index is not visible then the first
     *                                   visible element of the selector is returned.
     *
     * @return string
     */
    public function getElementRegion($selector, $index=0, $fallbackToVisible=false)
    {
        $elemRect = $this->getBoundingRectangle($selector, $index, $fallbackToVisible);
        if ($elemRect === NULL) {
            throw new Exception('Element: ['.$selector.']('.$index.') not found!');
        }

        $region = $this->getRegionOnPage($elemRect);
        return $region;

    }//end getElementRegion()


    /**
     * Returns the HTML contents of the found element.
     *
     * @param string  $selector The jQuery selector to use for finding the element.
     * @param integer $index    The element index of the resulting array.
     *
     * @return string
     */
    public function getHTML($selector, $index=0)
    {
        $html = $this->execJS('$.find("'.$selector.'")['.$index.'].innerHTML');
        return $html;

    }//end getHTML()


    /**
     * Executes the specified JavaScript and returns its result.
     *
     * @param string  $js            The JavaScript to execute.
     * @param boolean $noReturnValue If TRUE then JS has no return value and NULL
     *                               will be returned to speed up execution.
     * @param boolean $raw           If TRUE content will not be modified.
     *
     * @return string
     * @throws Exception If there is a Selenium error.
     */
    public function execJS($js, $noReturnValue=FALSE, $raw=FALSE)
    {
        $this->debug('ExecJS: '.$js);

        clearstatcache();
        if (file_exists($this->_tmpDir.'/jsres.tmp') === TRUE) {
            unlink($this->_tmpDir.'/jsres.tmp');
        }

        $maxTry = 10;
        do {
            if (file_put_contents($this->_tmpDir.'/jsexec.tmp', $js, LOCK_EX) !== FALSE) {
                break;
            }

            usleep(50000);
        } while ($maxTry-- > 0);

        chmod($this->_tmpDir.'/jsexec.tmp', 0660);
        if ($this->_fileGroup !== NULL) {
            chgrp($this->_tmpDir.'/jsexec.tmp', $this->_fileGroup);
        }

        if ($noReturnValue === TRUE) {
            usleep(500000);
            return NULL;
        }

        $startTime = microtime(TRUE);
        $timeout   = 3;
        while (file_exists($this->_tmpDir.'/jsres.tmp') === FALSE) {
            if ((microtime(TRUE) - $startTime) > $timeout) {
                throw new Exception('Browser is not responding!');
            }

            usleep(50000);
        }

        $result = file_get_contents($this->_tmpDir.'/jsres.tmp');
        unlink($this->_tmpDir.'/jsres.tmp');

        if ($result === 'undefined' || trim($result) === '') {
            return NULL;
        }

        $result = json_decode($result, TRUE);

        if (is_string($result) === TRUE && $raw !== TRUE) {
            $result = str_replace("\r\n", '\n', $result);
            $result = str_replace("\n", '\n', $result);
        }

        return $result;

    }//end execJS()


    /**
     * Stops the JS polling for commands.
     *
     * @return void
     */
    public function stopJSPolling()
    {
        $this->execJS('PHPSikuliBrowser.stopPolling()', TRUE);

        if (file_exists($this->_tmpDir.'/jsres.tmp') === TRUE) {
            unlink($this->_tmpDir.'/jsres.tmp');
        }

        if (file_exists($this->_tmpDir.'/jsexec.tmp') === TRUE) {
            unlink($this->_tmpDir.'/jsexec.tmp');
        }

    }//end stopJSPolling()


    /**
     * Returns the JS errors.
     *
     * @return array
     */
    public function getJSErrors()
    {
        $res = $this->execJS('PHPSikuliBrowser.getJSErrors()', FALSE);
        return $res;

    }//end getJSErrors()


    /**
     * Returns a new Region object relative to the top left of the test page.
     *
     * @param array $rect The rectangle (x1, y1, x2, y2).
     *
     * @return string
     */
    public function getRegionOnPage(array $rect)
    {
        $pageLoc = $this->getPageTopLeft();

        $x = (int) ($pageLoc['x'] + $rect['x1']);
        $y = (int) ($pageLoc['y'] + $rect['y1']);
        $w = (int) ($rect['x2'] - $rect['x1']);
        $h = (int) ($rect['y2'] - $rect['y1']);

        $region = $this->createRegion($x, $y, $w, $h);
        return $region;

    }//end getRegionOnPage()


    /**
     * Returns the given page X location relative to the screen.
     *
     * @param integer $x The x location relative to the page.
     *
     * @return integer
     */
    public function getPageXRelativeToScreen($x)
    {
        $pageLoc = $this->getPageTopLeft();

        $x = ($pageLoc['x'] + $x);
        return $x;

    }//end getPageXRelativeToScreen()


    /**
     * Returns the given page X location relative to the screen.
     *
     * @param integer $y The x location relative to the page.
     *
     * @return integer
     */
    public function getPageYRelativeToScreen($y)
    {
        $pageLoc = $this->getPageTopLeft();

        $y = ($pageLoc['y'] + $y);
        return $y;

    }//end getPageYRelativeToScreen()


    /**
     * Returns the X position of given location relative to the page.
     *
     * @param string $loc The location variable.
     *
     * @return integer
     */
    public function getPageX($loc)
    {
        $pageLoc = $this->getPageTopLeft();
        $x       = ($this->getX($loc) - $pageLoc['x']);

        return $x;

    }//end getPageX()


    /**
     * Returns the Y position of given location relative to the page.
     *
     * @param string $loc The location variable.
     *
     * @return integer
     */
    public function getPageY($loc)
    {
        $pageLoc = $this->getPageTopLeft();
        $y       = ($this->getY($loc) - $pageLoc['y']);

        return $y;

    }//end getPageY()


    /**
     * Returns the location of the page relative to the screen.
     *
     * @return array
     */
    public function getPageTopLeft()
    {
        if ($this->_pageTopLeft === NULL) {
            // Get the JS to display the window-target icon.
            $this->execJS('PHPSikuliBrowser.showTargetIcon()', FALSE);

            $targetIcon = $this->find(dirname(__FILE__).'/window-target.png');
            $topLeft    = $this->getTopLeft($targetIcon);
            $loc        = array(
                           'x' => $this->getX($topLeft),
                           'y' => $this->getY($topLeft),
                          );

            $this->_pageTopLeft = $loc;
            $this->execJS('PHPSikuliBrowser.hideTargetIcon()', TRUE);
        }

        return $this->_pageTopLeft;

    }//end getPageTopLeft()


    /**
     * Sets the browser to be used.
     *
     * @param string $browser A valid browser id (E.g. firefox).
     *
     * @return void
     * @throws Exception If the specified browser is not supported.
     */
    private function _setBrowser($browser)
    {
        if (isset($this->_supportedBrowsers[$browser]) === FALSE) {
            throw new Exception('Browser is not supported');
        }

        $appName = $this->_getAppName($browser);
        if ($this->getOS() === 'windows') {
            $this->closeBrowser($appName);
        } else {
            $this->restartBrowser($appName);
        }

        $this->_window = $this->_getBrowserWindow($browser);

        $this->_browserid = $browser;

        $this->addCacheVar($this->_window);

        // Position the browser.
        $this->position();

        // Resize the browser.
        $this->resize();

        // Reset zoom.
        $this->keyDown('Key.CMD + 0');

    }//end _setBrowser()


    /**
     * Returns the window object of the current browser.
     *
     * @param string $browser A valid browser id (E.g. firefox).
     *
     * @return Object
     */
    private function _getBrowserWindow($browser)
    {
        $appName = $this->_getAppName($browser);

        $win = null;
        $app = $this->switchApp($appName);
        if ($this->getOS() !== 'windows') {
            $windowNum = 0;
            switch ($appName) {
                case 'Google Chrome':
                case 'Chromium':
                case 'Google Chrome Canary':
                    $windowNum = 1;
                break;

                default:
                    $windowNum = 0;
                break;
            }

            $win = $this->callFunc(
                'window',
                array($windowNum),
                $app,
                TRUE
            );
        } else {
            $win = $app;
        }//end if

        return $win;

    }//end _getBrowserWindow()


    /**
     * Returns the browsers application name.
     *
     * @param string $browser A valid browser id (E.g. firefox).
     *
     * @return string
     */
    private function _getAppName($browser)
    {
        $appName = $browser;
        if ($this->getOS() === 'windows') {
            switch ($appName) {
                case 'chrome':
                case 'chromium':
                case 'chromeCanary':
                    $appName = 'chrome';
                break;

                case 'firefox':
                case 'firefoxNightly':
                    $appName = 'firefox';
                break;

                default:
                    if (strpos($appName, 'ie') === 0) {
                        $appName = 'iexplore';
                    }
                break;
            }
        } else {
            $appName = $this->getBrowserName($browser);
        }//end if

        return $appName;

    }//end _getAppName()


    /**
     * Closes the specified browser or the active browser.
     *
     * @param string $browserid Id of the browser to close.
     *
     * @return void
     */
    public function closeBrowser($browserid=NULL)
    {
        switch ($this->getOS()) {
            case 'osx':
                // Shutdown browser.
                exec('pkill -15 -i '.$browserid);
            break;

            case 'windows':
                $taskName = $browserid;
                if ($browserid === 'edge') {
                    $taskName = 'MicrosoftEdge';
                } else if (strpos($browserid, 'ie') === 0) {
                    $taskName = 'iexplore';
                }

                // Shutdown browser.
                exec('taskkill /F /IM '.$taskName.'.exe');
            break;

            default:
                // OS not supported.
            break;
        }//end switch

        sleep(1);

    }//end closeBrowser()


    /**
     * Restarts the browser.
     *
     * @param string $browserid Id of the browser to restart.
     *
     * @return void
     */
    public function restartBrowser($browserid=NULL)
    {
        if ($browserid === NULL) {
            $browserid = $this->getBrowserid();
        }

        $this->closeBrowser($browserid);

        sleep(3);
        $this->startBrowser($browserid);

    }//end restartBrowser()


    /**
     * Starts the browser.
     *
     * @param string $browserid Id of the browser to start.
     *
     * @return void
     */
    public function startBrowser($browserid=NULL)
    {
        if ($browserid === NULL) {
            $browserid = $this->getBrowserid();
        }

        switch ($this->getOS()) {
            case 'osx':
                // Start browser.
                $this->keyDown('Key.CMD + Key.SPACE');
                $this->type($browserid);
                $this->keyDown('Key.ENTER');
            break;

            case 'windows':
                // Start browser.
                if ($browserid === 'edge') {
                    $this->keyDown('Key.WIN + r');
                    $this->type('microsoft-edge://');
                } else {
                    // Start browser.
                    $this->keyDown('Key.WIN + r');
                    $this->keyDown('Key.DELETE');

                    if (strpos($browserid, 'ie') === 0) {
                      $browserid = 'iexplore';
                    }

                    $this->type($browserid.' about:blank');
                }

                $this->keyDown('Key.ENTER');
            break;

            default:
                // OS not supported.
            break;
        }//end switch

        // Wait a few seconds for browser to start.
        sleep(2);

        $this->keyDown('Key.ENTER');
        sleep(2);

    }//end startBrowser()


    /**
     * Returns the id of the browser.
     *
     * @return string
     */
    public function getBrowserid()
    {
        return $this->_browserid;

    }//end getBrowserid()


    /**
     * Returns the name of the current browser.
     *
     * @param string $browserid Id of the browser.
     *
     * @return string
     */
    public function getBrowserName($browserid=NULL)
    {
        if ($browserid === NULL) {
            $browserid = $this->getBrowserid();
        }

        return $this->_supportedBrowsers[$browserid];

    }//end getBrowserName()


    /**
     * Switches to the specifed application.
     *
     * @param string $name The name of the application to switch to.
     *
     * @return string
     */
    public function switchApp($name)
    {
        if ($this->getOS() !== 'windows') {
            return parent::switchApp($name);
        } else if ($name === 'edge') {
            $this->keyDown('Key.WIN + r');
            $this->type('microsoft-edge://');
        } else {
            $this->keyDown('Key.WIN + r');
            $this->keyDown('Key.DELETE');
            $this->type($name.' about:blank');
        }

        $this->keyDown('Key.ENTER');
        sleep(2);
        return $this->callFunc('App.focusedWindow', array(), NULL, TRUE);

    }//end switchApp()


    /**
     * Returns the default browser window size.
     *
     * @return array
     */
    public function getDefaultWindowSize()
    {
        return $this->_defaultWindowSize;

    }//end getDefaultWindowSize()


    /**
     * Set the default browser window size.
     *
     * @param integer $w The width of the window.
     * @param integer $h The height of the window.
     *
     * @return void
     */
    public function setDefaultWindowSize($w, $h)
    {
        $this->_defaultWindowSize = array(
                                     'w' => $w,
                                     'h' => $h,
                                    );

    }//end setDefaultWindowSize()


    /**
     * Set the default browser window size.
     *
     * @param integer $x The X position.
     * @param integer $y The Y position.
     *
     * @return void
     */
    public function setPosition($x, $y)
    {
        $this->_defaultWindowPosition = array(
                                         'x' => $x,
                                         'y' => $y,
                                        );

    }//end setPosition()


    /**
     * Position the browser window.
     *
     * @param integer $x The X position.
     * @param integer $y The Y position.
     *
     * @return void
     */
    public function position($x=NULL, $y=NULL)
    {
        if ($x === NULL || $y === NULL) {
            $pos = $this->_defaultWindowPosition;
            if ($pos === NULL) {
                return;
            }

            if ($x === NULL) {
                $x = $pos['x'];
            }

            if ($y === NULL) {
                $y = $pos['y'];
            }
        }//end if

        $window   = $this->getBrowserWindow();
        $topLeft  = $this->getTopLeft($window);
        $topRight = $this->getTopRight($window);

        $yOffset = 5;
        $xOffset = ($this->getX($topRight) - $this->getX($topLeft) - 250);

        $start = $this->createLocation(
            $this->getX($topLeft) + $xOffset,
            $this->getY($topLeft) + $yOffset
        );

        $endX = $x + $xOffset;
        $endY = $y + $yOffset;
        $end = $this->createLocation(
            $endX,
            $endY
        );

        $this->dragDrop($start, $end);

        $size = $this->getWindowSize();

        $this->removeCacheVar($this->_window);
        $this->_window = $this->_getBrowserWindow($this->_browserid);
        $this->addCacheVar($this->_window);

        // Set the default region of the find operations.
        $this->setDefaultRegion($this->_window);

    }//end position()


    /**
     * Resizes the browser window.
     *
     * @param integer $w The width of the window.
     * @param integer $h The height of the window.
     *
     * @return void
     */
    public function resize($w=NULL, $h=NULL)
    {
        if ($w === NULL || $h === NULL) {
            $size = $this->getDefaultWindowSize();
            if ($size === NULL) {
                return;
            }

            if ($w === NULL) {
                $w = $size['w'];
            }

            if ($h === NULL) {
                $h = $size['h'];
            }
        }

        $window = $this->getBrowserWindow();
        if (is_array($this->_windowSize) === TRUE) {
            if ($this->_windowSize['w'] === $w && $this->_windowSize['h'] === $h) {
                $this->setDefaultRegion($this->createRegion($this->getX($window), $this->getY($window), $w, $h));
                return;
            }
        }

        if ($this->getW($window) === $w && $this->getH($window) === $h) {
            $this->setDefaultRegion($this->createRegion($this->getX($window), $this->getY($window), $w, $h));
            return;
        }

        $bottomRight = $this->getBottomRight($window);

        if ($this->getOS() === 'windows') {
            $bottomRight = $this->createLocation(
                ($this->getX($bottomRight) - 3),
                ($this->getY($bottomRight) - 3)
            );
        }

        $browserX = $this->getX($window);
        $browserY = $this->getY($window);
        $locX     = ($browserX + $w);
        $locY     = ($browserY + $h);

        $screenW = $this->getW('SCREEN');
        $screenH = $this->getH('SCREEN');

        if ($locX > $screenW) {
            $locX = ($screenW - 5);
        }

        if ($locY > $screenH) {
            $locY = ($screenH - 5);
        }

        $newLocation = $this->createLocation($locX, $locY);

        $this->dragDrop($bottomRight, $newLocation);

        // Update the window object.
        $this->removeCacheVar($this->_window);
        $this->_window = $this->createRegion($browserX, $browserY, $w, $h);
        $this->addCacheVar($this->_window);

        $this->_windowSize = array(
                              'w' => $w,
                              'h' => $h,
                             );

        // Set the default region of the find operations.
        $this->setDefaultRegion($this->_window);

    }//end resize()


    /**
     * Returns the region of the browser window.
     *
     * @return string
     */
    public function getBrowserWindow()
    {
        return $this->_window;

    }//end getBrowserWindow()


    /**
     * Returns the size of the browser window.
     *
     * @return array
     */
    public function getWindowSize()
    {
        $w = $this->getW($this->getBrowserWindow());
        $h = $this->getH($this->getBrowserWindow());

        $size = array(
                 'w' => $w,
                 'h' => $h,
                );

        return $size;

    }//end getWindowSize()


    /**
     * Sets the delay in milliseconds after a click.
     *
     * @param integer $milSec Delay time in milliseconds.
     *
     * @return void
     */
    public function setClickDelay($milSec)
    {
        $this->_clickDelay = $milSec;

    }//end setClickDelay()


    /**
     * Sets the file group setting.
     *
     * @param string $group The group name.
     *
     * @return void
     */
    public function setFileGroup($group)
    {
        $this->_fileGroup = $group;

    }//end setFileGroup()


    /**
     * Print specified message in browser's console.
     *
     * @param string $msg  The message to print.
     * @param string $type The type of the log message.
     *
     * @return void
     */
    public function log($msg, $type='info')
    {
        $msg = str_replace("\n", '\n', addcslashes($msg, '"'));
        $this->execJS('console.'.$type.'("'.$msg.'")', TRUE);

    }//end log()


    /**
     * Sets the given settings.
     *
     * @param array $settings The settings for PHPSikuliBrowser.
     *
     * @return void
     */
    private function _handleSettings(array $settings=array())
    {
        foreach ($settings as $setting => $value) {
            switch ($setting) {
                case 'fileGroupOwner':
                    $this->setFileGroup($value);
                break;

                case 'position':
                    $this->setPosition($value['x'], $value['y']);
                break;

                case 'size':
                    if (is_array($value) === TRUE
                        && isset($value['width']) === TRUE
                        && isset($value['height']) === TRUE
                    ) {
                        $this->setDefaultWindowSize($value['width'], $value['height']);
                    }
                break;
            }
        }

    }//end _handleSettings()


}//end class
