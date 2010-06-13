<?php

// Use this file in your CSS in place of the .HTC file if it works offline but not online.
// It will send the correct MIME type so that IE will execute the script correctly.

header('Content-Type: text/x-component');
header('Expires: '.gmdate('D, d M Y H:i:s', time()+60*60*24*365).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime('iepngfix.htc')).' GMT');
include('iepngfix.htc');

?>
