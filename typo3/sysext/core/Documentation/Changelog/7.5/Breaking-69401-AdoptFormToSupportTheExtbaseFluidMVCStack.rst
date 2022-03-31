
.. include:: /Includes.rst.txt

=========================================================================
Breaking: #69401 - Adopt ext:form to support the Extbase/ Fluid MVC stack
=========================================================================

See :issue:`69401`

Description
===========

The `postProcessor` interface and the mail postProcessor have changed.

Validators and filters have been moved to other folders and both class
names and algorithms have changed.


Impact
======

Own postProcessors, validators and filters will possibly fail with an error.


Affected Installations
======================

Installations with own postProcessors, validators and filters.


Migration
=========

Adopt own postProcessors, validators and filters to comply with the current implementation.


.. index:: PHP-API, Frontend, Backend, Fluid, ext:extbase, ext:form
