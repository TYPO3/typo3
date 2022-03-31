
.. include:: /Includes.rst.txt

===================================================
Breaking: #67946 - LowLevel Cleaner Scripts Removed
===================================================

See :issue:`67946`

Description
===========

The shell scripts for checking and cleaning a TYPO3 installation for its integrity have been removed from the Core.
They were previously located under :file:`typo3/cleaner_check.sh` and :file:`typo3/cleaner_fix.sh`. The contents have
been moved to the documentation for EXT:lowlevel in the respective :file:`README.rst`.


Impact
======

Any regular system jobs that execute these scripts will exit with the script not found anymore.


Affected Installations
======================

Any installation that uses the shell scripts above from the command line directly.


Migration
=========

If such a list is needed, create the files manually again in your system (outside your document root). The code can be
found inside :file:`EXT:lowlevel/README.rst`.


.. index:: ext:lowlevel, Backend
