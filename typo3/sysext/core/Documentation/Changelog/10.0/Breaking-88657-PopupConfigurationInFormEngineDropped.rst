.. include:: /Includes.rst.txt

============================================================
Breaking: #88657 - Popup configuration in FormEngine dropped
============================================================

See :issue:`88657`

Description
===========

The options :typoscript:`options.popupWindowSize` and :typoscript:`options.rte.popupWindowSize` used to configure popup sizes have been
removed.


Impact
======

These options are not evaluated anymore.


Affected Installations
======================

All installations using 3rd party extensions relying on the options are affected.


Migration
=========

In most cases it's fine to remove the configuration.

In the unlikely case one is negatively affected by this change, fetch the configuration from backend user's TSConfig and
use it where it is required.

.. index:: Backend, RTE, TSConfig, NotScanned, ext:backend
