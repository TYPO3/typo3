.. include:: /Includes.rst.txt

========================================================================
Deprecation: #80511 - AbstractFunctionModule->incLocalLang and $thisPath
========================================================================

See :issue:`80511`

Description
===========

The method :php:`AbstractFunctionModule->incLocalLang()` and the public property
:php:`AbstractFunctionModule->thisPath` have been marked as deprecated.


Impact
======

Calling method above will trigger a deprecation log entry.


Affected Installations
======================

Any extension extending the AbstractFunctionModule and calling the mentioned method.


Migration
=========

The functionality of loading a locallang file is now taken care of by :php:`LanguageService::includeLLFile()`
and takes care of everything automatically.

If any specific calls to the method is made, it should be replaced by the LanguageService equivalent.

The property :php:`$thisPath` contains the path to the class, which can be accessed via Reflection,
as it is done currently as well, should be implemented in the custom extension itself that needs this
information.

.. index:: Backend, PHP-API
