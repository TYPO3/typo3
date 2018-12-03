.. include:: ../../Includes.txt

========================================================================================
Deprecation: #86907 - Deprecate usage of dependency injection with non public properties
========================================================================================

See :issue:`86907`

Description
===========

The dependency injection via properties has been deprecated for all properties that are non public.

While there are several reasons not to use property injection at all there is one specific drawback with non public properties. To be able to inject dependencies into non public properties, said properties have to be made accessible during runtime. As that process is quite slow and expensive and non cachable, it should not be used at all.


Impact
======

Dependency injection will no longer work with non public properties


Affected Installations
======================

All installations that use dependency injection with non public properties.


Migration
=========

The easiest, yet ugliest migration is to make the property public. If possible, switch to constructor or setter injection instead.

.. index:: PHP-API, PartiallyScanned, ext:extbase
