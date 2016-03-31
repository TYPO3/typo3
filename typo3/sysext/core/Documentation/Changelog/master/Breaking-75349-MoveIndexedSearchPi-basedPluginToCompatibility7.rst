========================================================================
Breaking: #75349 - Move Indexed Search pi-based plugin to compatibility7
========================================================================

Description
===========

Indexed Search pi1 plugin (based on AbstractPlugin) has been moved to compatibility7 extension and will not be developed further. The compatibility7 extension will be moved to TER before the release of 8 LTS.
Extbase plugin (pi2) stays in Indexed Search as before.


Impact
======

Installation of the compatibility7 extension is required to continue using pi1 plugin. In the longer run migration to Extbase plugin is required.


Affected Installations
======================

All installations using pi-based indexed search plugin.


Migration
=========

Installations using pi1 should migrate to Extbase plugin or install compatibility7 extension.