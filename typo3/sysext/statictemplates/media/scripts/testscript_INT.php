<?php

if (!is_object($this)) die ('Error: No parent object present.');




$content.='
This is output from an internal script!
<br />
Works like ordinary include-scripts.
<br />
';

debug($this->data);

?>