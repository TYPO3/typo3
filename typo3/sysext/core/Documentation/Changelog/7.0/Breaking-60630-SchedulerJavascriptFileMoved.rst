
.. include:: ../../Includes.txt

=============================================================
Breaking: #60630 - Scheduler Javascript File Moved
=============================================================

See :issue:`60630`

Description
===========

Javascript files of the scheduler module moved from EXT:scheduler/res/tx_scheduler_be.js to
EXT:scheduler/Resources/Public/JavaScript/Scheduler.js


Impact
======

Javascript or file not found errors.


Affected installations
======================

An installation is affected if a 3rd party extension includes EXT:scheduler/res/tx_scheduler_be.js


Migration
=========

Include EXT:scheduler/Resources/Public/JavaScript/Scheduler.js instead or refactor the affected extension to free it
from the dependency to this scheduler internal file.
