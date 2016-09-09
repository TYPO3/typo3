
.. include:: ../../Includes.txt

=========================================
Breaking: #60063 - Felogin Plugin Removed
=========================================

See :issue:`60063`

Description
===========

File EXT:felogin/pi1/class.tx_felogin_pi1.php was removed.


Impact
======

- A require in PHP of this file throws a fatal error.

- An :code:`includeLibs` TypoScript setting to this file raises a warning.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension requires EXT:felogin/pi1/class.tx_felogin_pi1.php or if an includeLibs TypoScript setting to this file is set.


Migration
=========

Remove the require line in PHP and includeLibs line in TypoScript, they are obsolete.
