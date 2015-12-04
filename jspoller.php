<?php
// TODO: Should load the allowed domains from a config file..
header('Access-Control-Allow-Origin: *');
if (isset($_POST['res']) === TRUE) {
    file_put_contents(dirname(__FILE__).'/tmp/jsres.tmp', preg_replace('/^result:/', '', $_POST['res']), LOCK_EX);
} else if (file_exists(dirname(__FILE__).'/tmp/jsexec.tmp') === TRUE) {
    $jsExecCont = file_get_contents(dirname(__FILE__).'/tmp/jsexec.tmp');
    unlink(dirname(__FILE__).'/tmp/jsexec.tmp');
    if ($jsExecCont) {
        echo $jsExecCont;
    }
}


?>
