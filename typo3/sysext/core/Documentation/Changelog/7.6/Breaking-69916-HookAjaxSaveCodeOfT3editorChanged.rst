
.. include:: ../../Includes.txt

========================================================
Breaking: #69916 - Hook ajaxSaveCode of t3editor changed
========================================================

See :issue:`69916`

Description
===========

The `$ajaxObj` parameter has been replaced by PSR-7-compliant `$request` and `$response` objects.


Impact
======

Using the `$ajaxObj` parameter will result in a fatal error.


Affected Installations
======================

All 3rd party extensions using the `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode']`
hook are affected.


Migration
=========

Make use of ServerRequestInterface and ResponseInterface, see :file:`typo3/sysext/t3editor/Classes/Hook/FileEditHook.php` for reference.


.. index:: PHP-API, ext:t3editor
