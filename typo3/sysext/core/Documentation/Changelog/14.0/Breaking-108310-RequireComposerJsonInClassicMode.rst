..  include:: /Includes.rst.txt

..  _breaking-108310-1732545123:

=========================================================
Breaking: #108310 - Require composer.json in classic mode
=========================================================

See :issue:`108310`

Description
===========

Extension detection in classic mode now requires a valid :file:`composer.json`
file instead of :file:`ext_emconf.php`. The :file:`composer.json` file must
include :json:`"type": "typo3-cms-*"` and the extension key in
:json:`extra.typo3/cms.extension-key`.

Impact
======

Extensions without a valid :file:`composer.json` are no longer detected
and loaded in classic mode installations.

Affected installations
======================

All classic mode installations must verify that every extension contains
a :file:`composer.json` with:

*   :json:`"type"` starting with :json:`"typo3-cms-"`
*   :json:`"extra.typo3/cms.extension-key"` containing the extension key

Composer-based installations are not affected.

Migration
=========

Extension authors must ensure their extensions include a valid
:file:`composer.json`. TER extensions have required this since 2021.

Example :file:`composer.json`:

..  code-block:: json

    {
        "name": "vendor/extension-name",
        "type": "typo3-cms-extension",
        "extra": {
            "typo3/cms": {
                "extension-key": "extension_name"
            }
        }
    }

..  index:: PHP-API, NotScanned, ext:core
