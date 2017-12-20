
.. include:: ../../Includes.txt

=========================================================================
Breaking: #66707 - issueCommand() now adds quotes when used in JS context
=========================================================================

See :issue:`66707`

Description
===========

Using `\TYPO3\CMS\Backend\Template\DocumentTemplate::issueCommand()` in JavaScript context (second parameter = -1),
now ensures that the URL is properly escaped and quoted for being used in JavaScript code.


Impact
======

Having additional quotes around the result of the call to `issueCommand()` will lead to JavaScript errors.


Affected Installations
======================

Any installation using third party extensions, which use `issueCommand()` with second parameter set to -1.


Migration
=========

Make sure that you do **not** specify any additional quotes around the result of the call to `issueCommand()`.


.. index:: PHP-API, Backend
