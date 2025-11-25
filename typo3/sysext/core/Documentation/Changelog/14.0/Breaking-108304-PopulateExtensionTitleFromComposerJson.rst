..  include:: /Includes.rst.txt

..  _breaking-108304-1764058005:

===============================================================
Breaking: #108304 - Populate extension title from composer.json
===============================================================

See :issue:`108304`

Description
===========

To avoid reading the legacy `ext_emconf.php` file even in
Composer mode, the extension title is now optionally pulled
from the composer.json description. If the character sequence
` - ` (space, dash, space) is present in description field
in composer.json, then everything before this sequence
is used as title of the extension and the second part
is used as extension description. Note that only the first
occurrence of this sequence is evaluated, so it remains
possible to have this inside the extension description if required.

Impact
======

Extensions not having their title incorporated in the
composer.json description field, will be shown with the
full description in extension manager and from command
line with `typo3 extension:list` command.

Affected installations
======================

Installations having custom extensions, where the title
is not part of the description in composer.json

Migration
=========

Put the desired extension title into the composer.json description field
and separate it from the description with ` - ` (space, dash, space),
or use the extension manager "Composer Support of Extensions" to get
a suggestion for updating composer.json files accordingly.

All TYPO3 core extensions have set their description in composer.json
accordingly already.

Example of description with title in composer.json:

..  code-block:: php
    :caption: ext_emconf.php

    <?php

    $EM_CONF[$_EXTKEY] = [
        'title' => 'TYPO3 CMS Backend User',
        'description' => 'TYPO3 backend module System>Backend Users for managing backend users and groups.',
        // ...
    ];

..  code-block:: json
    :caption: composer.json

    {
        "name": "typo3/cms-beuser",
        "type": "typo3-cms-framework",
        "description": "TYPO3 CMS Backend User - TYPO3 backend module System>Backend Users for managing backend users and groups.",
    }

..  index:: PHP-API, NotScanned, ext:core
