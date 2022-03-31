.. include:: /Includes.rst.txt

===================================================================================================
Deprecation: #95009 - Passing TypoScript configuration as constructor argument to Exception handler
===================================================================================================

See :issue:`95009`

Description
===========

With :typoscript:`config.contentObjectExceptionHandler` it's possible to
adjust the exception handler behavior of the frontend. It's even possible
to use an own exception handler class. Previously, the TypoScript configuration
was therefore passed to the exception handler via a constructor argument. This
has now been deprecated to allow the use of DI.

The configuration will now be passed using the new :php:`setConfiguration()`
method. This method will be enforced by the :php:`ExceptionHandlerInterface`
in TYPO3 v12.

Impact
======

Using a custom exception handler, while not implementing the :php:`setConfiguration()`
method will trigger a deprecation log entry. The method will be enforced
in TYPO3 v12.

Affected Installations
======================

All installations defining a custom exception handler via the TypoScript
configuration :typoscript:`config.contentObjectExceptionHandler`, while
not implementing the :php:`setConfiguration()` method.

Migration
=========

Remove the :php:`$configuration` argument from the constructor of any
custom exception handler class and implement the :php:`setConfiguration()`
method instead.

.. index:: Frontend, PHP-API, NotScanned, ext:frontend
