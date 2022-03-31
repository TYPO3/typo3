
.. include:: /Includes.rst.txt

============================================================
Deprecation: #68860 - Deprecate SelectImage.initEventHandler
============================================================

See :issue:`68860`

Description
===========

Removes the calls of `SelectImage.initEventHandler` method.
This was limited to WebKit UserAgents and the provided implementation of
`require` was wrong, so that the EventListener was not registered at all.
Nevertheless the functionality of drag and drop is not broken without the
initEventHandler.

Impact
======

Throws console log with deprecation message.


Affected Installations
======================

All installations calling `SelectImage.initEventHandler`.


Migration
=========

Remove the call of `SelectImage.initEventHandler`.


.. index:: JavaScript, Backend
