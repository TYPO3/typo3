
.. include:: ../../Includes.txt

======================================================
Deprecation: #70052 - TCA Display condition EXT LOADED
======================================================

See :issue:`70052`

Description
===========

In `TCA` the `EXT:anExtension:LOADED` display condition has been marked as deprecated.


Affected Installations
======================

Extensions that use `LOADED` display conditions. Those can be located by
searching for `LOADED` in the backend module `Configuration` `TCA` section,
example match from rtehtmlarea:

.. code-block:: php

    'static_lang_isocode' => array(
        'displayCond' => 'EXT:static_info_tables:LOADED:true',
        'config' => ...
        ...
    ),


Migration
=========

Do not use any longer. `TCA` works additive, so the extension that is referenced in
`EXT:LOADED:extensionName` should instead add columns definition instead of the
referring extension defining the `TCA` conditional. In the example above, the
column definition of `static_lang_isocode` was removed from extension `rtehtmlarea`
and moved to extension `static_info_tables`, adding the field in an
`Configuration/TCA/Overrides` file to the affected table. To ensure the load order
of extensions is correct, `static_info_tables` could set a `suggest` dependency
`rtehtmlarea`.
