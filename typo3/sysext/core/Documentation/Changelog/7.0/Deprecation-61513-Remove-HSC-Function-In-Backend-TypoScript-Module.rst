
.. include:: ../../Includes.txt

============================================================================
Deprecation: #61513 - Use native htmlspecialchars in ExtendedTemplateService
============================================================================

See :issue:`61513`

Description
===========

In previous versions the ExtendedTemplateService used a conditional wrapper method to apply htmlspecialchars()
for rendering TypoScript search labels, keys and comments in the backend. This option was never used, so
htmlspecialchars() was always activated rendering the option and method redundant and not necessary.
Calls to the method are removed. The ExtendedTemplateServer method and property are now marked as deprecated
for removal with CMS 8.

Impact
======

Custom extensions using the flag ExtendedTemplateService->ext_noSpecialCharsOnLabels or a custom implementation
of ExtendedTemplateService might get different results when using this switch within the class.

Affected installations
======================

Installations using ExtendedTemplateService in their own extensions.


.. index:: PHP-API, Backend
