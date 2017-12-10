.. include:: ../../Includes.txt

===========================================
Important: #81023 - Drop EXT:typo3db_legacy
===========================================

See :issue:`81023`

Description
===========

The legacy extension `typo3db_legacy` that contains the old non doctrine based
database API known as :php:`$GLOBALS['TYPO3_DB']` has been dropped from core.

The extension is available in the TER and an install tool upgrade wizard is in place
to download and load the extension as compatibility layer for extension that still
rely on :php:`$GLOBALS['TYPO3_DB']` in TYPO3 v9.

If an extension should be compatible with both TYPO3 v8 and TYPO3 v9 and relies in v9 on `typo3db_legacy`, it should
list `typo3db_legacy` as `suggests` dependency in it's :file:`ext_emconf.php` file. This way, the dependency is optional
and needs to be manually loaded by an administrator in the TYPO3 v9 backend, but the core still ensures
`typo3db_legacy` is loaded before the affected extension:

.. code-block:: php

    'constraints' => [
        ...
        'suggests' => [
            'typo3db_legacy' => '1.0.0-1.0.99',
        ],
    ],

Extensions that dropped support for TYPO3 v8 (or keeps separate branches) and did not migrate to doctrine in
its v9 version, should list `typo3db_legacy` in the `depends` section of :file:`ext_emconf.php`:

.. code-block:: php

    'constraints' => [
        ...
        'depends' => [
            'typo3db_legacy' => '1.0.0-1.0.99',
        ],
    ],

.. index:: Database, PHP-API