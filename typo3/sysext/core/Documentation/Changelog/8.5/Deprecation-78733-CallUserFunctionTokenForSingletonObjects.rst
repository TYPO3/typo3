.. include:: /Includes.rst.txt

======================================================================
Deprecation: #78733 - CallUserFunction "&" token for singleton objects
======================================================================

See :issue:`78733`

Description
===========

The method `GeneralUtility::callUserFunction()` allows to send the callee (the user-defined function)
to be prepended with a "&" before the method name to add the instantiated object to a "singleton" pool
during a single request. This functionality has been marked as deprecated as it can easily be solved by
implementing a class as singleton.

This way, the object is always a singleton, even when it is called via `GeneralUtility::makeInstance()`.


Impact
======

Calling `callUserFunction()` with a "&" symbol will trigger a deprecation log entry.


Affected Installations
======================

Any installation with a hook or user function which is registered with an ampersand "&" symbol.


Migration
=========

The class of the user function / method can implement the `SingletonInterface` to achieve the same behaviour.

.. index:: PHP-API
