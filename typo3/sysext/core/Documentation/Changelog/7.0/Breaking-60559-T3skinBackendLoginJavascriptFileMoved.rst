
.. include:: ../../Includes.txt

=============================================================
Breaking: #60559 - T3skin Backend Login Javascript File Moved
=============================================================

See :issue:`60559`

Description
===========

Javascript files of the backend login form moved from EXT:t3skin to EXT:backend.


Impact
======

Javascript or file not found errors.


Affected installations
======================

An installation is affected if a 3rd party extension includes EXT:t3skin/Resources/Public/JavaScript/login.js


Migration
=========

Include EXT:backend/Resources/Public/JavaScript/login.js instead or refactor the affected extension to free it
from the dependency to this core internal file.
