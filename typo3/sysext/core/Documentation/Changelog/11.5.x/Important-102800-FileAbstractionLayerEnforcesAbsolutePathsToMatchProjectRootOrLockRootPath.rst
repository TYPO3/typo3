.. include:: /Includes.rst.txt

.. _important-102800-1707409544:

=========================================================================================================
Important: #102800 - File Abstraction Layer enforces absolute paths to match project root or lockRootPath
=========================================================================================================

See :issue:`102800`

Description
===========


The File Abstraction Layer Local Driver has been adapted to verify whether a
given absolute file path is allowed in order to prevent access to files outside
the project root or to the additional root path restrictions defined in
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']`.

The option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']` has been
extended to support an array of root path prefixes to allow for multiple storages
to be listed. Beware that trailing slashes are enforced automatically.

It is suggested to use the new array-based syntax, which will be applied automatically
once this setting is updated via Install Tool Configuration Wizard:

..  code-block:: php

    // Before
    $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] = '/var/extra-storage';

    // After
    $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] =  [
        '/var/extra-storage1/',
        '/var/extra-storage2/',
    ];


.. index:: FAL, LocalConfiguration, ext:core
