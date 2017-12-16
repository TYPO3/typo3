
.. include:: ../../Includes.txt

===========================================================
Breaking: #60559 - T3skin Backend Login Template File Moved
===========================================================

See :issue:`60559`

Description
===========

The HTML template file for the backend login screen was moved from EXT:t3skin to EXT:backend.


Impact
======

Broken login screen.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses file EXT:t3skin/Resources/Private/Templates/login.html


Migration
=========

Use file EXT:backend/Resources/Private/Templates/login.html instead or refactor the affected extension to free it
from the dependency to this core internal file.
