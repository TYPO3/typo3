.. include:: /Includes.rst.txt

.. _breaking-101820:

=======================================================================
Breaking: #101820 - Remove bootstrap jQuery interface and window.jQuery
=======================================================================

See :issue:`101820`

Description
===========

The bootstrap jQuery interfaces required a global :js:`window.jQuery` variable
to be set. The jquery drop-in is dropped in order to remove this non-optional
jQuery dependency.

As a side effect the :js:`window.jQuery` global is removed as well.
Note that global jQuery usage has already been deprecated in :issue:`86438` and
removed in :issue:`97243` with the suggestion to use JavaScript modules instead.
:js:`window.jQuery` was basically left in place for bootstrap to operate and
therefore only :js:`window.$` was removed back then.

Impact
======

Loading the ES6 'bootstrap' module no longer has side effects, as the global
scope :js:`window` is no longer polluted by writing to the property :js:`jQuery`.
This also means jQuery will no longer be loaded when it is not actually needed.

Affected Installations
======================

All installations that use bootstrap's jQuery interface or applications that
use `window.jQuery` to invoke jQuery.

Following method calls are affected:

- :js:`$(…).alert()`
- :js:`$(…).button()`
- :js:`$(…).carousel()`
- :js:`$(…).collapse()`
- :js:`$(…).dropdown()`
- :js:`$(…).tab()`
- :js:`$(…).modal()`
- :js:`$(…).offcanvas()`
- :js:`$(…).popover()`
- :js:`$(…).scrollspy()`
- :js:`$(…).toast()`
- :js:`$(…).tooltip()`


Migration
=========

Use bootstrap's ES6 exports :js:`import { Carousel } from 'bootstrap';` instead.

.. index:: Backend, JavaScript, NotScanned, ext:core
