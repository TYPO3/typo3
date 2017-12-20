.. include:: ../../Includes.txt

===================================================
Breaking: #79227 - Removed ExtDirect State Provider
===================================================

See :issue:`79227`

Description
===========

The ExtDirect based State Provider for ExtJS applications (endpoint `TYPO3.ExtDirectStateProvider.ExtDirect`) has been removed.

The ExtDirect endpoint `TYPO3.ExtDirectStateProvider.ExtDirect` is no longer available.

The following PHP classes have been removed:
* `\TYPO3\CMS\Backend\InterfaceState\ExtDirect\DataProvider`
* `\TYPO3\CMS\Backend\Tree\AbstractTreeStateProvider`
* `\TYPO3\CMS\Backend\Tree\AbstractExtJsTree`

The relevant JavaScript file `ExtDirect.StateProvider.js` has been removed.

The PHP method php:`DocumentTemplate->setExtDirectStateProvider()` to load the JavaScript file has been removed.

Instead the jQuery-based AMD module `TYPO3\CMS\Backend\Storage` is incorporated to load the data the same way via an anonymous
State Provider which is handed to ExtJS as long as ExtJS is still available in the TYPO3 Core.


Impact
======

Accessing the ExtDirect endpoint will result in a JavaScript error. Loading the JavaScript file will result in a HTTP 404 error.

Instantiating the PHP class will result in a fatal PHP error.


Affected Installations
======================

Any installation using custom implementations with ExtDirect and the State Provider shipped with the TYPO3 Core.


Migration
=========

Include the `TYPO3\CMS\Backend\Storage`, and use the UserSettingsController class directly on the PHP side to
access the user settings.

See the implementation of the JavaScript Storage object for a more detailed usage.

.. index:: JavaScript, Backend
