.. include:: ../../Includes.txt

================================================
Deprecation: #79972 - Deprecated Fluid Overrides
================================================

See :issue:`79972`

Description
===========

* ``XmlnsNamespaceTemplatePreProcessor`` is removed without substitute (no longer required)
* ``LegacyNamespaceExpressionNode`` is removed without substitute (no longer required)
* ``setLegacyMode`` and `$legacyMode` on RenderingContext is deprecated (no-op, triggers deprecation log message)
* ``$objectManager`` plus injection method on RenderingContext is deprecated (no usages)
* ``getObjectManager`` on RenderingContext is removed (no usages)

Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instances which uses the above described methods or classes.


Migration
=========

* Remove usage of classes / properties / methods.


.. index:: Fluid
