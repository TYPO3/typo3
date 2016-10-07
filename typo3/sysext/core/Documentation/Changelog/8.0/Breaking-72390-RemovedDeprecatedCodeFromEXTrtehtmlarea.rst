
.. include:: ../../Includes.txt

===============================================================
Breaking: #72390 - Removed deprecated code from EXT:rtehtmlarea
===============================================================

See :issue:`72390`

Description
===========

The following methods have been removed:

* `UserElementsController::main()`
* `UserElementsController::printContent()`
* `ParseHtmlController::main()`
* `ParseHtmlController::printContent()`

Furthermore a JavaScript function has been removed:

* `initEventListeners()`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to UserElementsController, ParseHtmlController via the methods above, or rely on the JavaScript function mentioned above being executed.


Migration
=========

`initEventListener()` no replacement for this
`UserElementsController::main()` call `UserElementsController::main_user()` instead
`UserElementsController::printContent()` call `UserElementsController::mainAction()` instead
`ParseHtmlController::main()` call `ParseHtmlController::main_parse_html()` instead
`ParseHtmlController::printContent()` call `ParseHtmlController::mainAction()` instead

.. index:: PHP-API, RTE
