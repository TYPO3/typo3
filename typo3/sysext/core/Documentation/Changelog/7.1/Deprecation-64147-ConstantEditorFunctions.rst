
.. include:: ../../Includes.txt

======================================================
Deprecation: #64147 - TemplateService->ext_getKeyImage
======================================================

See :issue:`64147`

Description
===========

`ExtendedTemplateService::ext_getKeyImage()` has been marked as deprecated.
`ConfigurationForm::ext_getKeyImage()` has been marked as deprecated.


Impact
======

Using the two methods will throw a deprecation message.


Affected installations
======================

TYPO3 installations with extensions that call the methods above directly.


Migration
=========

As in the methods directly, plain HTML based on Twitter bootstrap can be used.
Example: `<span class="label label-danger">3</span>`.
