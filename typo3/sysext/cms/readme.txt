A few notes on the "cms" extension:

Apart from being default in probably most/all Typo3 installations it is also hardwired to sysext/ dir because of one thing: The "Web>Layout" module. 
This module has the path set hardcoded in typo3/db_layout.php and if this module is moved to another place, that path must be changed accordingly.