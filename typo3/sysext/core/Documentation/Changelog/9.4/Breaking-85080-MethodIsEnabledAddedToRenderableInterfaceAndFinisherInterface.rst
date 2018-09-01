.. include:: ../../Includes.txt

==========================================================================================
Breaking: #85080 - Method "isEnabled()" added to RenderableInterface and FinisherInterface
==========================================================================================

See :issue:`85080`

Description
===========

A new method :php:`isEnabled()` has been added to the :php:`RenderableInterface` as well as the :php:`FinisherInterface`.


Impact
======

Third party code implementing these interfaces and not extending :php:`AbstractRenderable` or :php:`AbstractFinisher` will
cause a fatal error if used in a form.


Affected Installations
======================

Instances with third party code implementing these interfaces and not extending :php:`AbstractRenderable` or :php:`AbstractFinisher`.


Migration
=========

Third party code implementing these interfaces must be updated to implement the :php:`isEnabled()` method, preferably
by extending :php:`AbstractRenderable` (or one of its subclasses) or :php:`AbstractFinisher`.

.. index:: NotScanned, ext:form
