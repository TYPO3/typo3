.. include:: /Includes.rst.txt

.. _deprecation-99811-1675447357:

============================================================
Deprecation: #99811 - Deprecate JavaScript bootstrap tooltip
============================================================

See :issue:`99811`

Description
===========

Bootstrap-related backend tooltips initiated with
:html:`data-bs-toggle="tooltip"` together with the core
JavaScript class :js:`typo3/backend/tooltip.js` have been marked
as deprecated and should not be used anymore.


Impact
======

Loading :js:`typo3/backend/tooltip.js` in a backend-related module
will trigger a :js:`console.warn()`. The module will vanish in TYPO3 v13.


Affected installations
======================

Instances with extensions that add backend modules using the bootstrap-related
tooltips plugin may be affected. A typical sign for this is
using the :html:`data-bs-toggle="tooltip"` attribute on elements, loading the
JavaScript module :js:`typo3/backend/tooltip.js` and calling :js:`Tooltip.initialize()`.


Migration
=========

Some parts of the Core will fall back to the :html:`title` attribute for now. However,
both the bootstrap tooltips as well as the title attribute raise accessibility
concerns. See `MDN <https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/title#accessibility_concerns>`_
for more information on this. The Core will continue to improve the situation.

.. index:: Backend, JavaScript, NotScanned, ext:backend
