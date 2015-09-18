=====================================================================
Breaking: #69401 - Adopt form to support the Extbase/ Fluid MVC stack
=====================================================================

Description
===========

The postProcessor interface and the mail postProcessor have changed.

Validators and filters have been moved to other folders and both class
names and algorithms have changed.


Impact
======

Own postProcessors, validators and filters will possibly fail with
error.


Affected Installations
======================

Installations with own postProcessors, validators and filters are
affected.


Migration
=========

Adopt own postProcessors, validators and filters to current
implementation.