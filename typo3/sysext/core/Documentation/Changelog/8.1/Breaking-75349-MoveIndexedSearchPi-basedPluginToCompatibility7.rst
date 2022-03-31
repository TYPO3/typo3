
.. include:: /Includes.rst.txt

========================================================================
Breaking: #75349 - Move Indexed Search pi-based plugin to compatibility7
========================================================================

See :issue:`75349`

Description
===========

Indexed Search pi1 plugin (based on AbstractPlugin) has been moved to EXT:compatibility7 and will not be developed further. EXT:compatibility7 will be moved to TER before the release of 8 LTS.
The Extbase plugin (pi2) stays in Indexed Search as before.


Impact
======

Installation of EXT:compatibility7 is required to continue using the pi1 plugin. In the longer run migrating to the Extbase plugin is required.


Affected Installations
======================

All installations using the pi-based indexed search plugin.


Migration
=========

Installations using pi1 should migrate to the Extbase plugin or install EXT:compatibility7.

.. index:: PHP-API, ext:indexed_search
