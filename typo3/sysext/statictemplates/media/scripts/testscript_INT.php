<?php
# TYPO3 SVN ID: $Id: testscript_INT.php 3437 2008-03-16 16:22:11Z flyguide $

if (!is_object($this)) die ('Error: No parent object present.');




$content.='
This is output from an internal script!
<br />
Works like ordinary include-scripts.
<br />
';

debug($this->data);

?>