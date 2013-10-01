<?php
// Legacy file. The old install tool entry point was here, it is kept as well known
// resource for backward compatibility and just redirects to the new entry point.
header('HTTP/1.1 303 See Other');
header('Location: ../sysext/install/Start/Install.php');
die;
