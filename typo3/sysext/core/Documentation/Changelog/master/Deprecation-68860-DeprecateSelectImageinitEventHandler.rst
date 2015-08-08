============================================================
Deprecation: #68860 - Deprecate SelectImage.initEventHandler
============================================================

Description
===========

Removes the calls of SelectImage.initEventHandler method.
Was limited for UserAgent WebKit and the provided implementation
of require was wrong, so that the EventListener was not
registered at all. Nevertheless the functionality of
drag and drop is not broken without the initEventHandler.

Impact
======

Throws console log with deprecation message


Affected Installations
======================

All where SelectImage.initEventHandler method is called


Migration
=========

Remove call of SelectImage.initEventHandler method
