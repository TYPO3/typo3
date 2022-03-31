
.. include:: /Includes.rst.txt

=======================================================
Breaking: #60630 - Scheduler Module Template File Moved
=======================================================

See :issue:`60630`

Description
===========

The HTML template file for the scheduler module was moved from EXT:scheduler/mod1/mod_template.html
to EXT:scheduler/Resources/Private/Templates/Module.html.


Impact
======

Broken module screen.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses file EXT:scheduler/mod1/mod_template.html


Migration
=========

Use file EXT:scheduler/Resources/Private/Templates/Module.html instead or refactor the affected extension to free it
from the dependency to this scheduler internal file.


.. index:: ext:scheduler, Backend
