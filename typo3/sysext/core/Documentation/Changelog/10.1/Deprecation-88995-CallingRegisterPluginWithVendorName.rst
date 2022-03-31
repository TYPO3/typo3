.. include:: /Includes.rst.txt

=============================================================
Deprecation: #88995 - Calling registerPlugin with vendor name
=============================================================

See :issue:`88995`

Description
===========

The first parameter :php:`$extensionName` of method :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin`
used to contain the vendor name in the past.

.. code-block:: php

   \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
       'TYPO3.CMS.Form',
       'Formframework',
       'Form',
       'content-form',
   );

As the vendor name does not have any effect at all, it's usage has been marked as deprecated.


Impact
======

Calling :php:`registerPlugin()` with first parameter containing dots (considered to be the full vendor) name will trigger a PHP :php:`E_USER_DEPRECATED` error.
As of TYPO3 v11 using the vendor name along with the extension name will lead to a wrong registration of plugins.


Affected Installations
======================

All installations that add the vendor name to the first parameter :php:`$extensionName`
of method :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin()` are affected.


Migration
=========

Just use the extension name like in this example.

.. code-block:: php

   \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
       'Form',
       'Formframework',
       'Form',
       'content-form',
   );

.. index:: PHP-API, NotScanned, ext:extbase
