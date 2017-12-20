
.. include:: ../../Includes.txt

======================================================================
Breaking: #69161 - Removed includeCsh setting from ContainerViewHelper
======================================================================

See :issue:`69161`

Description
===========

Include CSH setting in `<f:be.container>` is not needed anymore and has therefore been removed.
The JavaScript will be loaded automatically when ext:cshmanual is enabled.


Impact
======

Using `<f:be.container>` view helpers in a custom Backend module, setting the includeCsh property, will result in a fatal error.


Affected Installations
======================

Extensions that use `<f:be.container>` which set the setting `includeCsh`

Migration
=========

Remove the property from the template. When ext:cshmanual is enabled the JavaScript is loaded automatically.


.. index:: Fluid, Backend
