.. include:: /Includes.rst.txt

.. _deprecation-100033-1677433329:

============================================================
Deprecation: #100033 - TBE_STYLES stylesheet and stylesheet2
============================================================

See :issue:`100033`

Description
===========

The usage of :php:`$GLOBALS['TBE_STYLES']['stylesheet']` and
:php:`$GLOBALS['TBE_STYLES']['stylesheet2']` to add custom CSS files
to the TYPO3 backend has been marked as deprecated in TYPO3 v12 and will be
removed in TYPO3 v13.


Impact
======

Using any of the following configuration declarations

* :php:`$GLOBALS['TBE_STYLES']['stylesheet']`
* :php:`$GLOBALS['TBE_STYLES']['stylesheet2']`

will trigger a PHP deprecation notice and will throw a fatal PHP error in
TYPO3 v13.


Affected installations
======================

The extension scanner will find extensions using

* :php:`$GLOBALS['TBE_STYLES']['stylesheet']`
* :php:`$GLOBALS['TBE_STYLES']['stylesheet2']`

as "weak" matches.


Migration
=========

Extensions should use :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['my_extension']`
where :php:`'my_extension'` is the extension key.

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['my_extension'] = 'EXT:my_extension/Resources/Public/Css';

In the example above, all CSS files in the configured directory will be loaded
in TYPO3 backend.

.. index:: Backend, FullyScanned, ext:backend
