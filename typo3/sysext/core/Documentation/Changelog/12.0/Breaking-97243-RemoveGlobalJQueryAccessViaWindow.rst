.. include:: /Includes.rst.txt

.. _breaking-97243:

===========================================================
Breaking: #97243 - Remove global jQuery access via window.$
===========================================================

See :issue:`97243`

Description
===========

The global :js:`window.$` accessor to the jQuery instance is now no longer
provided.

Global jQuery usage has been deprecated in :issue:`86438` with the suggestion to
use JavaScript modules instead. With the integration of browser native ES6
modules jQuery should now be loaded as a regular module.

Impact
======

Loading the ES6 'jquery' module no longer has side effects, as the global
scope :js:`window` is no longer polluted by writing to the property :js:`$`.
This renders any :js:`jQuery.noConflict()` workarounds unneeded.

Affected Installations
======================

All installations that use `$` to invoke jQuery in inline JavaScripts or
custom JavaScript modules that miss to define their jQuery import, and
implicitly used the global before.

Migration
=========

Migrate to ES6 JavaScript modules and use :js:`import $ from 'jquery';` instead.

.. index:: Backend, JavaScript, NotScanned, ext:core
