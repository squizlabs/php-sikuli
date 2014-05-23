<?php
require_once 'Exceptions.inc';


/**
 * PHPSikuli is a PHP wrapper for Sikuli.
 *
 * @category  PHP
 * @package   php-sikuli
 * @copyright 2013 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/php-sikuli/blob/master/licence.txt BSD Licence
 * @link      https://github.com/squizlabs/php-sikuli
 */

class PHPSikuli
{

    /**
     * Interactive Sikuli Jython session resouce handle.
     *
     * @var resource
     */
    private $_sikuliHandle = NULL;

    /**
     * Sikuli input resource handle.
     *
     * @var resource
     */
    private $_sikuliInput = NULL;

    /**
     * Sikuli output resource handle.
     *
     * @var resource
     */
    private $_sikuliOutput = NULL;

    /**
     * Sikuli error resource handle.
     *
     * @var resource
     */
    private $_sikuliError = NULL;

    /**
     * Sikuli command timeout in seconds.
     *
     * @var integer
     */
    private $_sikuliCMDTimeout = 15;

    /**
     * Number of variables created.
     *
     * @var resource
     */
    private $_varCount = 0;

    /**
     * Set to TRUE when interactive Sikuli Jython session  is created.
     *
     * @var boolean
     */
    private $_connected = FALSE;

    /**
     * Name of the Operating System the PHP is running on.
     *
     * @var string
     */
    private $_os = NULL;

    /**
     * Default region to use for find commands.
     *
     * @var string
     */
    private $_defaultRegion = NULL;

    /**
     * If TRUE then debugging output will be printed.
     *
     * @var boolean
     */
    private $_debugging = FALSE;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->connect();

    }//end __construct()


    /**
     * Find a particular GUI element.
     *
     * @param string $ps         A Pattern object or Path to an image file or text.
     * @param string $region     Region or a Match object.
     * @param float  $similarity The similarity setting.
     *
     * @return string
     */
    public function find($ps, $region=NULL, $similarity=NULL)
    {
        if ($region === NULL) {
            $region = $this->_defaultRegion;
        } else if ($region < 0) {
            $region = NULL;
        }

        if ($similarity !== NULL && file_exists($ps) === TRUE) {
            // If ps is not a file then ignore this setting.
            $pattern = $this->createPattern($ps);
            $pattern = $this->similar($pattern, $similarity);
            $ps      = $pattern;
        }

        $var = $this->callFunc('find', array($ps), $region, TRUE);
        return $var;

    }//end find()


    /**
     * Sets the auto timeout.
     *
     * @param float  $timeout The timeout in seconds.
     * @param string $region  Region object.
     *
     * @return void
     */
    public function setAutoWaitTimeout($timeout, $region=NULL)
    {
        if ($region === NULL) {
            $region = $this->_defaultRegion;
        }

        $this->callFunc('setAutoWaitTimeout', array($timeout, '_noQuotes' => TRUE), $region);

    }//end setAutoWaitTimeout()


    /*
        Mouse & Keyboard
    */


    /**
     * Returns the location of the mouse pointer.
     *
     * @return string
     */
    public function getMouseLocation()
    {
        return $this->callFunc('getMouseLocation', array(), 'Env', TRUE);

    }//end getMouseLocation()


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
        $this->callFunc('click', array($psmrl, $modifiers), NULL, FALSE);

    }//end click()


    /**
     * Double clicks the specified location.
     *
     * @param string $psmrl     A Pattern, String, Match, Region or Location.
     * @param string $modifiers One or more key modifiers.
     *
     * @return void
     */
    public function doubleClick($psmrl, $modifiers=NULL)
    {
        $this->callFunc('doubleClick', array($psmrl, $modifiers), NULL, FALSE);

    }//end doubleClick()


    /**
     * Right clicks the specified location.
     *
     * @param string $psmrl     A Pattern, String, Match, Region or Location.
     * @param string $modifiers One or more key modifiers.
     *
     * @return void
     */
    public function rightClick($psmrl, $modifiers=NULL)
    {
        $this->callFunc('rightClick', array($psmrl, $modifiers), NULL, FALSE);

    }//end rightClick()


    /**
     * Perform a drag & drop from a start to end point.
     *
     * @param string $start The start PSMRL.
     * @param string $end   The end PSMRL.
     *
     * @return void
     */
    public function dragDrop($start, $end)
    {
        $this->callFunc('dragDrop', array($start, $end));

    }//end dragDrop()


    /**
     * Start a drag operation.
     *
     * @param string $start The start PSMRL.
     *
     * @return void
     */
    public function drag($start)
    {
        $this->callFunc('drag', array($start));

    }//end drag()


    /**
     * Complete a drag and drop operation by dropping at the given point.
     *
     * @param string $end The end PSMRL.
     *
     * @return void
     */
    public function dropAt($end)
    {
        $this->callFunc('dropAt', array($end));

    }//end dropAt()


    /**
     * Move the mouse pointer to a location indicated by PSRML.
     *
     * @param string $psmrl A Pattern, String, Match, Region or Location.
     *
     * @return void
     */
    public function mouseMove($psmrl)
    {
        $this->callFunc('mouseMove', array($psmrl));

    }//end mouseMove()


    /**
     * Move the mouse pointer by given X and Y offsets.
     *
     * @param integer $offsetX The X offset.
     * @param integer $offsetY The Y offset.
     *
     * @return string
     */
    public function mouseMoveOffset($offsetX, $offsetY)
    {
        $mouseLocation = $this->getMouseLocation();
        $this->setLocation(
            $mouseLocation,
            ($this->getX($mouseLocation) + $offsetX),
            ($this->getY($mouseLocation) + $offsetY)
        );
        $this->mouseMove($mouseLocation);

        return $mouseLocation;

    }//end mouseMoveOffset()


    /**
     * Turns the mouse wheel.
     *
     * @param integer $steps Number of steps. A positive value will scroll down
     *                       and a negative value will scroll up.
     * @param string  $psmrl A Pattern, String, Match, Region or Location.
     *
     * @return void
     */
    public function wheel($steps, $psmrl=NULL)
    {
        $dir = NULL;
        if ($steps > 0) {
            $dir = 'WHEEL_DOWN';
        } else {
            $dir = 'WHEEL_UP';
        }

        $psmrl = $this->_defaultRegion;

        $args = array(
                 '_noQuotes' => TRUE,
                 $psmrl,
                 $dir,
                 abs($steps),
                );
        $this->callFunc('wheel', $args);

    }//end wheel()


    /**
     * Returns a valid Sikuli key combination string.
     *
     * @param string $keysStr Keys combination.
     *
     * @return string
     */
    private function _extractKeys($keysStr)
    {
        if (empty($keysStr) === TRUE) {
            return NULL;
        }

        $str  = array();
        $keys = explode('+', $keysStr);
        foreach ($keys as $key) {
            $key = trim($key);
            if (strpos($key, 'Key.') === 0) {
                if ($key === 'Key.CMD' && $this->getOS() === 'windows') {
                    $key = 'Key.CTRL';
                }

                // Special key.
                $str[] = $key;
            } else {
                $str[] = '"'.$key.'"';
            }
        }

        $str = implode('+', $str);

        return $str;

    }//end _extractKeys()


    /**
     * Executes keyDown event.
     *
     * @param string  $keysStr  Keys to press.
     * @param boolean $holdDown If TRUE then keyUp() event will not be executed right
     *                          after keyDown.
     *
     * @return void
     */
    public function keyDown($keysStr, $holdDown=FALSE)
    {
        $keys = $this->_extractKeys($keysStr);
        $this->callFunc('keyDown', array($keys, '_noQuotes' => TRUE));

        if ($holdDown === FALSE) {
            $this->callFunc('keyUp');
        }

    }//end keyDown()


    /**
     * Executes keyUp event.
     *
     * @param string $keysStr Keys to release, if none specified all keys are released.
     *
     * @return void
     */
    public function keyUp($keysStr=NULL)
    {
        $keys = $this->_extractKeys($keysStr);
        $this->callFunc('keyUp', array($keys, '_noQuotes' => TRUE));

    }//end keyUp()


    /**
     * Pastes the given content.
     *
     * @param string $text The text to paste.
     *
     * @return void
     */
    public function paste($text)
    {
        $this->callFunc('paste', array($text));

    }//end paste()


    /**
     * Returns the contents of the clipboard.
     *
     * @return string
     */
    public function getClipboard()
    {
        return $this->callFunc('getClipboard', array(), 'Env');

    }//end getClipboard()


    /**
     * Extract the text contained in the region using OCR.
     *
     * @param string $region The region variable to use.
     *
     * @return string
     */
    public function text($region=NULL)
    {
        return $this->callFunc('text', array(), $region);

    }//end text()


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
        $retval    = NULL;
        $modifiers = $this->_extractKeys($modifiers);
        if (is_numeric($text) === TRUE) {
            $text   = "'".$text."'";
            $retval = $this->callFunc('type', array($psmrl, $text, $modifiers, '_noQuotes' => TRUE));
        } else {
            $retval = $this->callFunc('type', array($psmrl, $text, $modifiers));
        }

        return $retval;

    }//end type()


    /*
        Pattern, Region, Location.
    */


    /**
     * Creates a Pattern object using the given image.
     *
     * @param string $image Path to the image.
     *
     * @return string
     * @throws Exception If the image does not exist.
     */
    public function createPattern($image)
    {
        if (file_exists($image) === FALSE) {
            throw new PHPSikuliException('Image file does not exist: '.$image);
        }

        $var = $this->callFunc('Pattern', array($image), NULL, TRUE);
        return $var;

    }//end createPattern()


    /**
     * Creates a new Region object.
     *
     * @param integer $x The X position of the region.
     * @param integer $y The Y position of the region.
     * @param integer $w The width of the region.
     * @param integer $h The height of the region.
     *
     * @return string
     */
    public function createRegion($x, $y, $w, $h)
    {
        $var = $this->callFunc('Region', array($x, $y, $w, $h), NULL, TRUE);
        return $var;

    }//end createRegion()


    /**
     * Creates a new Location object.
     *
     * @param integer $x The X position of the new location.
     * @param integer $y The Y position of the new location.
     *
     * @return string
     */
    public function createLocation($x, $y)
    {
        $var = $this->callFunc('Location', array($x, $y), NULL, TRUE);
        return $var;

    }//end createLocation()


    /**
     * Sets the default region to use for find commands if not specified.
     *
     * @param string $region The region variable.
     *
     * @return void
     */
    public function setDefaultRegion($region)
    {
        $this->_defaultRegion = $region;

    }//end setDefaultRegion()


    /**
     * Creates a new Pattern object with the specified minimum similarity set.
     *
     * @param string $patternObj The pattern variable.
     * @param float  $similarity The similarity value between 0 and 1.
     *
     * @return string
     */
    public function similar($patternObj, $similarity=0.7)
    {
        if ($similarity === NULL) {
            $similarity = 0.7;
        }

        $var = $this->callFunc('similar', array($similarity), $patternObj, TRUE);
        return $var;

    }//end similar()


    /**
     * Sets the region's X attribute.
     *
     * @param string  $obj The region object.
     * @param integer $val The new value.
     *
     * @return void
     */
    public function setX($obj, $val)
    {
        $ret = NULL;
        $this->callFunc('setX', array($val), $obj);

    }//end setX()


    /**
     * Sets the region's Y attribute.
     *
     * @param string  $obj The region object.
     * @param integer $val The new value.
     *
     * @return void
     */
    public function setY($obj, $val)
    {
        $this->callFunc('setY', array($val), $obj);

    }//end setY()


    /**
     * Sets the region's W attribute.
     *
     * @param string  $obj The region object.
     * @param integer $val The new value.
     *
     * @return void
     */
    public function setW($obj, $val)
    {
        $this->callFunc('setW', array($val), $obj);

    }//end setW()


    /**
     * Sets the region's H attribute.
     *
     * @param string  $obj The region object.
     * @param integer $val The new value.
     *
     * @return void
     */
    public function setH($obj, $val)
    {
        $this->callFunc('setH', array($val), $obj);

    }//end setH()


    /**
     * Returns the region's X attribute.
     *
     * @param string $obj The region object.
     *
     * @return integer
     */
    public function getX($obj)
    {
        $ret = (int) $this->callFunc('getX', array(), $obj);
        return $ret;

    }//end getX()


    /**
     * Returns the region's Y attribute.
     *
     * @param string $obj The region object.
     *
     * @return integer
     */
    public function getY($obj)
    {
        $ret = (int) $this->callFunc('getY', array(), $obj);
        return $ret;

    }//end getY()


    /**
     * Returns the region's W attribute.
     *
     * @param string $obj The region object.
     *
     * @return integer
     */
    public function getW($obj)
    {
        $ret = (int) $this->callFunc('getW', array(), $obj);
        return $ret;

    }//end getW()


    /**
     * Returns the region's H attribute.
     *
     * @param string $obj The region object.
     *
     * @return integer
     */
    public function getH($obj)
    {
        $ret = (int) $this->callFunc('getH', array(), $obj);
        return $ret;

    }//end getH()


    /**
     * Returns the top left location.
     *
     * @param string $obj The region object.
     *
     * @return Location
     */
    public function getTopLeft($obj)
    {
        $loc = $this->callFunc('getTopLeft', array(), $obj, TRUE);
        return $loc;

    }//end getTopLeft()


    /**
     * Returns the top right location.
     *
     * @param string $obj The region object.
     *
     * @return Location
     */
    public function getTopRight($obj)
    {
        $loc = $this->callFunc('getTopRight', array(), $obj, TRUE);
        return $loc;

    }//end getTopRight()


    /**
     * Returns the bottom left location.
     *
     * @param string $obj The region object.
     *
     * @return Location
     */
    public function getBottomLeft($obj)
    {
        $loc = $this->callFunc('getBottomLeft', array(), $obj, TRUE);
        return $loc;

    }//end getBottomLeft()


    /**
     * Returns the bottom right location.
     *
     * @param string $obj The region object.
     *
     * @return Location
     */
    public function getBottomRight($obj)
    {
        $loc = $this->callFunc('getBottomRight', array(), $obj, TRUE);
        return $loc;

    }//end getBottomRight()


    /**
     * Returns the center location.
     *
     * @param string $obj The region object.
     *
     * @return Location
     */
    public function getCenter($obj)
    {
        $loc = $this->callFunc('getCenter', array(), $obj, TRUE);
        return $loc;

    }//end getCenter()


    /**
     * Extends the given region to the right.
     *
     * @param string  $obj   The region object.
     * @param integer $range Number of pixels to extend by.
     *
     * @return Region
     */
    public function extendRight($obj, $range=NULL)
    {
        return $this->callFunc('right', array($range), $obj, TRUE);

    }//end extendRight()


    /**
     * Sets the location of a Location object.
     *
     * @param string  $locObj The Location object var name.
     * @param integer $x      The new X position.
     * @param integer $y      The new Y position.
     *
     * @return string
     */
    public function setLocation($locObj, $x, $y)
    {
        $loc = $this->callFunc('setLocation', array($x, $y), $locObj);
        return $loc;

    }//end setLocation()


    /**
     * Captures the given region and returns the created image path.
     *
     * @param mixed  $psmrl A Pattern, String, Match, Region or Location..
     * @param string $obj   The object var name.
     *
     * @return string
     */
    public function capture($psmrl=array(), $obj=NULL)
    {
        if (is_array($psmrl) === FALSE) {
            $psmrl = array($psmrl);
        } else if (empty($psmrl) === TRUE) {
            $psmrl = array(
                      'SCREEN.getBounds()',
                      '_noQuotes' => TRUE,
                     );
        }

        $imagePath = $this->callFunc('capture', $psmrl, $obj);
        $matches   = array();
        preg_match('#u\'(.+)\'#', $imagePath, $matches);

        $imagePath = $matches[1];

        return $imagePath;

    }//end capture()


    /**
     * Sets a Sikuli setting.
     *
     * @param string $setting Name of the setting.
     * @param string $value   Value of the setting.
     *
     * @return void
     */
    public function setSetting($setting, $value)
    {
        $this->sendCmd('Settings.'.$setting.' = '.$value);
        $this->_getStreamOutput();

    }//end setSetting()


    /*
        Tests.
    */


    /**
     * Checks that given pattern exists on the screen or specified region.
     *
     * @param string  $ps      The pattern or text.
     * @param string  $obj     The region object.
     * @param integer $seconds The number of seconds to wait.
     *
     * @return boolean
     */
    public function exists($ps, $obj=NULL, $seconds=NULL)
    {
        if ($obj === NULL) {
            $obj = $this->_defaultRegion;
        }

        $ret = $this->callFunc('exists', array($ps, $seconds), $obj);
        if (strpos($ret, 'Match[') === 0) {
            return TRUE;
        }

        return FALSE;

    }//end exists()


    /**
     * Switches to the specifed application.
     *
     * @param string $name The name of the application to switch to.
     *
     * @return string
     */
    public function switchApp($name)
    {
        if ($this->getOS() === 'windows') {
            $app = $this->callFunc('switchApp', array($name), NULL, TRUE);
            sleep(2);
            return $this->callFunc('App.focusedWindow', array(), NULL, TRUE);
        } else {
            $app = $this->callFunc('App', array($name), NULL, TRUE);
            return $this->callFunc('focus', array(), $app, TRUE);
        }

    }//end switchApp()


    /**
     * Print the given string.
     *
     * @param string $str The string to print.
     *
     * @return void
     */
    public function printVar($str)
    {
        echo $this->sendCmd('print '.$str);

    }//end printVar()


    /**
     * Returns the free memory remaining in Java.
     *
     * @return integer
     */
    public function getMemoryAvailable()
    {
        $this->sendCmd('from java.lang import Runtime;print Runtime.getRuntime().freeMemory()');
        $memory = (int) $this->_getStreamOutput();
        return $memory;

    }//end getMemoryAvailable()


    /**
     * Highlights the specified region for given seconds.
     *
     * @param string  $region  The region variable.
     * @param integer $seconds The number of seconds to highlight.
     *
     * @return void
     */
    public function highlight($region=NULL, $seconds=1)
    {
        if ($region === NULL) {
            $region = $this->_defaultRegion;
        }

        $this->callFunc('highlight', array($seconds), $region);

    }//end highlight()


    /**
     * Exit Sikuli.
     *
     * @return void
     */
    public function close()
    {
        fwrite($this->_sikuliInput, 'exit()'."\n");

    }//end close()


    /**
     * Sets the seconds before the Sikuli command timeout.
     *
     * @param integer $seconds The number of seconds before timeout.
     *
     * @return integer
     */
    public function setSikuliCMDTimeout($seconds)
    {
        $current = $this->_sikuliCMDTimeout;
        $this->_sikuliCMDTimeout = $seconds;
        return $current;

    }//end setSikuliCMDTimeout()


    /**
     * Add a var to be cached.
     *
     * Cached vars are not removed when clearVars() is called.
     *
     * @param string $varName The name of the variable to add to cache.
     *
     * @return void
     */
    public function addCacheVar($varName)
    {
        $matches = array();
        preg_match('#PHPSikuliVars\["(.+)"\]#i', $varName, $matches);
        $varName = $matches[1];

        $this->_cachedVars[$varName] = $varName;

    }//end addCacheVar()


    /**
     * Remove a cached var.
     *
     * @param string $varName The name of the cached variable.
     *
     * @return void
     */
    public function removeCacheVar($varName)
    {
        $matches = array();
        preg_match('#PHPSikuliVars\["(.+)"\]#i', $varName, $matches);
        $varName = $matches[1];

        if (isset($this->_cachedVars[$varName]) === TRUE) {
            unset($this->_cachedVars[$varName]);
        }

    }//end removeCacheVar()


    /**
     * Clears the variables created in Sikuli.
     *
     * Thid method should be called to clear vars that are created by sikuli
     * when they are no longer needed. If specific variables should be kept
     * then use the addCacheVar method to prevent them being removed.
     *
     * @return void
     */
    public function clearVars()
    {
        $varToCache = '';
        $cacheToVar = '';
        foreach ($this->_cachedVars as $varName) {
            $varToCache .= 'PHPSikuliVarsCached[\''.$varName.'\'] = PHPSikuliVars[\''.$varName.'\'];';
            $cacheToVar .= 'PHPSikuliVars[\''.$varName.'\'] = PHPSikuliVarsCached[\''.$varName.'\'];';
        }

        $cmd = 'PHPSikuliVarsCached = {};'.$varToCache.'PHPSikuliVars = {};'.$cacheToVar;
        $this->sendCmd(trim($cmd, ';'));
        $this->_getStreamOutput();

    }//end clearVars()


    /**
     * Calls the specified function.
     *
     * If $assignToVar is set to TRUE then the return value will be the name of the
     * new variable, if FALSE then return value will be the output of the function.
     *
     * @param string  $name        The name of the function to call.
     * @param array   $args        The array of arguments to pass to the function.
     * @param string  $obj         The object to use when calling the function.
     * @param boolean $assignToVar If TRUE then the return value of the function is
     *                             assigned to a new variable.
     *
     * @return string
     */
    public function callFunc($name, array $args=array(), $obj=NULL, $assignToVar=FALSE)
    {
        $command = '';
        $var     = NULL;
        if ($assignToVar === TRUE) {
            $var     = 'PHPSikuliVars["var_'.(++$this->_varCount).'"]';
            $command = $var.' = ';
        }

        if ($obj !== NULL) {
            $command .= $obj.'.';
        }

        $command .= $name.'(';

        $addQuotes = TRUE;
        if (isset($args['_noQuotes']) === TRUE) {
            unset($args['_noQuotes']);
            $addQuotes = FALSE;
        }

        $cmdArgs = array();
        foreach ($args as $arg) {
            if ($arg === NULL) {
                continue;
            }

            if ($addQuotes === FALSE
                || is_numeric($arg) === TRUE
                || strpos($arg, 'var_') === 0
                || strpos($arg, 'PHPSikuliVars') === 0
                || strpos($arg, 'Key.') === 0
            ) {
                $cmdArgs[] = $arg;
            } else {
                $cmdArgs[] = "'".$arg."'";
            }
        }

        $command .= implode(', ', $cmdArgs);

        $command .= ')';

        $output = $this->sendCmd($command);

        if ($this->getOS() !== 'windows') {
            $output = $this->_getStreamOutput();
        }

        if ($assignToVar === FALSE) {
            return $output;
        }

        return $var;

    }//end callFunc()


    /**
     * Executes the given command.
     *
     * @param string $command The command to execute.
     *
     * @return string
     */
    public function sendCmd($command)
    {
        $this->debug('CMD>>> '.$command);

        if ($this->getOS() === 'windows') {
            $filePath = dirname(__FILE__).'/sikuli.out';
            file_put_contents($filePath, '');
        }

        // This will allow _getStreamOutput method to stop waiting for more data.
        $command .= ";print '>>>';\n";
        fwrite($this->_sikuliInput, $command);

        if ($this->getOS() === 'windows') {
            return $this->_getStreamOutput();
        }

    }//end sendCmd()


    /**
     * Creates an interactive Sikuli Jython session from command line.
     *
     * @return void
     * @throws Exception If cannot connect to Sikuli.
     */
    public function connect()
    {
        if ($this->_connected === TRUE) {
            return;
        }

        $this->_varCount = 0;

        $sikuliScriptPath = dirname(__FILE__).'/SikuliX/sikuli-script.jar';

        if ($this->getOS() === 'windows') {
            $cmd     = 'start "PHPSikuli" /B java -Dsikuli.Debug=-2 -jar "'.$sikuliScriptPath.'" -i';
            $process = popen($cmd, 'w');
            if (is_resource($process) === FALSE) {
                throw new PHPSikuliException('Failed to connect to Sikuli');
            }

            $sikuliOutputFile = dirname(__FILE__).'/sikuli.out';
            file_put_contents($sikuliOutputFile, '');
            $sikuliOut = fopen($sikuliOutputFile, 'r');

            $this->_sikuliHandle = $process;
            $this->_sikuliInput  = $process;
            $this->_sikuliOutput = $sikuliOut;

            // Redirect Sikuli output to a file.
            $this->sendCmd('sys.stdout = sys.stderr = open("'.$sikuliOutputFile.'", "w", 1000)');
        } else {
            $cmd = 'java -jar '.$sikuliScriptPath.' -i';
            $descriptorspec = array(
                               0 => array(
                                     'pipe',
                                     'r',
                                    ),
                               1 => array(
                                     'pipe',
                                     'w',
                                    ),
                               2 => array(
                                     'pipe',
                                     'w',
                                    ),
                              );

            $pipes   = array();
            $process = proc_open($cmd, $descriptorspec, $pipes);

            if (is_resource($process) === FALSE) {
                throw new PHPSikuliException('Failed to connect to Sikuli');
            }

            $this->_sikuliHandle = $process;
            $this->_sikuliInput  = $pipes[0];
            $this->_sikuliOutput = $pipes[1];
            $this->_sikuliError  = $pipes[2];

            // Dont block reads.
            stream_set_blocking($this->_sikuliOutput, 0);
            stream_set_blocking($this->_sikuliError, 0);

            $this->_getStreamOutput();
        }//end if

        $this->setSetting('OcrTextSearch', 'True');

        $this->sendCmd('PHPSikuliVars = {}');
        $this->_getStreamOutput();

        $this->_connected = TRUE;

    }//end connect()


    /**
     * Exists the interactive Sikuli Jython session.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->close();

        fclose($this->_sikuliOutput);
        fclose($this->_sikuliInput);

        if ($this->getOS() === 'windows') {
            $this->_sikuliHandle = NULL;
        } else {
            fclose($this->_sikuliError);
            proc_close($this->_sikuliHandle);
        }

        $this->_connected = FALSE;

    }//end disconnect()


    /**
     * Resets the Sikuli connection.
     *
     * @return void
     */
    public function resetConnection()
    {
        $this->disconnect();
        $this->connect();

    }//end resetConnection()


    /**
     * Returns the name of the Operating System the PHP is running on.
     *
     * @return string
     */
    public function getOS()
    {
        if ($this->_os === NULL) {
            $os = strtolower(php_uname('s'));
            switch ($os) {
                case 'darwin':
                    $this->_os = 'osx';
                break;

                case 'linux':
                    $this->_os = 'linux';
                break;

                case 'windows nt':
                    $this->_os = 'windows';
                break;

                default:
                    $this->_os = $os;
                break;
            }//end switch
        }

        return $this->_os;

    }//end getOS()


    /**
     * Returns the output from interactive Sikuli Jython session (Windows only).
     *
     * This method is used to get the output from Sikuli due to a PHP bug with
     * streams on Windows OS.
     *
     * @return string
     * @throws Exception If Sikuli server does not respond in time.
     */
    private function _getStreamOutputWindows()
    {
        $startTime = microtime(TRUE);
        $timeout   = 15;
        $filePath  = dirname(__FILE__).'/sikuli.out';

        while (TRUE) {
            $contents = trim(file_get_contents($filePath));
            if ($contents !== '') {
                $startTime = microtime(TRUE);

                if (strpos($contents, 'File "<stdin>"') !== FALSE) {
                    $contents = str_replace("print '>>>';", '', $contents);
                    $this->_errorToException($contents);
                }

                $contents = trim(str_replace('>>>', '', $contents));
                return $contents;
            }

            if ((microtime(TRUE) - $startTime) > $timeout) {
                throw new PHPSikuliException('Sikuli server did not respond');
            }

            usleep(50000);
        }//end while

    }//end _getStreamOutputWindows()


    /**
     * Returns the output from interactive Sikuli Jython session.
     *
     * @return string
     * @throws Exception If Sikuli server does not respond in time.
     */
    private function _getStreamOutput()
    {
        if ($this->getOS() === 'windows') {
            return $this->_getStreamOutputWindows();
        }

        $isError = FALSE;
        $timeout = $this->_sikuliCMDTimeout;
        $content = array();
        $start   = microtime(TRUE);

        while (TRUE) {
            $read    = array(
                        $this->_sikuliOutput,
                        $this->_sikuliError,
                       );
            $write   = array();
            $except  = NULL;
            $changed = stream_select($read, $write, $except, 0, 100000);
            if ($changed !== FALSE && $changed > 0) {
                $idx = 0;
                if (isset($read[$idx]) === FALSE) {
                    if (isset($read[1]) === TRUE) {
                        $idx = 1;
                    } else {
                        throw new PHPSikuliException('Failed to read from stream');
                    }
                }

                $lines = stream_get_contents($read[$idx]);
                $lines = explode("\n", $lines);
                if ($isError === FALSE && $read[$idx] === $this->_sikuliError) {
                    $content = array();
                    $isError = TRUE;
                }

                foreach ($lines as $line) {
                    if (strlen($line) > 0) {
                        if ($line === '>>>' || $line === '[info] VDictProxy loaded.' || $line === '... use ctrl-d to end the session') {
                            break(2);
                        }

                        if (strpos($line, 'Using substitute bounding box at') === 0) {
                            $isError = FALSE;
                            continue;
                        }

                        $start     = microtime(TRUE);
                        $content[] = $line;

                        if ($isError === TRUE
                            && (preg_match('/  Line \d+, in file <stdin>/i', $line) === 1
                            || preg_match('/File "<stdin>", line \d+/i', $line) === 1)
                        ) {
                            $timeout = 1;
                        }
                    }//end if
                }//end foreach

                if ($isError === TRUE && empty($content) === TRUE) {
                    $isError = FALSE;
                }
            }//end if

            if (ob_get_level() > 0) {
                ob_flush();
            }

            if ((microtime(TRUE) - $start) > $timeout) {
                if ($isError === TRUE) {
                    break;
                } else {
                    $this->debug('PHPSikuliException: Sikuli did not respond');
                    throw new PHPSikuliException('Sikuli did not respond');
                }
            }
        }//end while

        $content = implode("\n", $content);

        if ($isError === TRUE) {
            if (strpos('java.lang.OutOfMemoryError', $content) !== FALSE) {
                $this->resetConnection();
            }

            $this->_errorToException($content);
        }

        $this->debug($content);

        return $content;

    }//end _getStreamOutput()


    /**
     * Converts Sikuli error to a PHP exception.
     *
     * @param string $error The Sikuli Error.
     *
     * @return void
     */
    private function _errorToException($error)
    {
        if (strpos($error, 'org.sikuli.script.FindFailed:') !== FALSE) {
            throw new FindFailedException($error);
        }

        $this->debug("Sikuli ERROR: \n".$error);
        throw new SikuliException("Sikuli ERROR: \n".$error);

    }//end errorToException()


    /**
     * Prints debug output if debugging is enabled.
     *
     * @param string $content The content to print.
     *
     * @return void
     */
    public function debug($content)
    {
        if ($this->_debugging === TRUE && trim($content) !== '') {
            echo trim($content)."\n";
            @ob_flush();
        }

    }//end debug()


}//end class
