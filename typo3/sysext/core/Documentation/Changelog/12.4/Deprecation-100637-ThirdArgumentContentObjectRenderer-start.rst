.. include:: /Includes.rst.txt

.. _deprecation-100637-1681737971:

====================================================================
Deprecation: #100637 - Third argument ContentObjectRenderer->start()
====================================================================

See :issue:`100637`

Description
===========

When creating instances of the
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`, the
third argument :php:`$request` when calling :php:`start()` should not be
handed over anymore. Instead, :php:`setRequest()` should be used
after creating the object.


Impact
======

Handing over the third argument to :php:`start()` has been marked as deprecated
in TYPO3 v12, it will be ignored with TYPO3 v13.


Affected installations
======================

Instances with casual extensions are probably not affected by this: Instances
of :php:`ContentObjectRenderer` are usually set-up framework internally.

Using the third argument on :php:`start()` triggers a deprecation level log
message. The extension scanner will *not* find usages, since the method
name :php:`start()` is used in different context as well and would lead to
too many false positives.


Migration
=========

Ensure the request is an instance of :php:`Psr\Http\Message\ServerRequestInterface`,
and call :php:`setRequest()` after instantiation instead of calling
:php:`start()` with three arguments.


.. index:: Frontend, PHP-API, NotScanned, ext:frontend
