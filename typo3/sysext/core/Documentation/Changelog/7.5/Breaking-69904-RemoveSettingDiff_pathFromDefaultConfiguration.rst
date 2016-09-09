
.. include:: ../../Includes.txt

=====================================================================
Breaking: #69904 - Remove Setting diff_path from DefaultConfiguration
=====================================================================

See :issue:`69904`

Description
===========

Creating a diff view of text has been replaced with a PHP library instead of using `diff` on the command line.
Thus we no longer need to be able to configure the path.


Impact
======

The setting `[BE][diff_path]` will no longer have any effect.


Affected Installations
======================

Any Installation that had to define a path different than `diff`


Migration
=========

Delete the line from LocalConfiguration.php if the UpgradeWizard should fail to do so.
