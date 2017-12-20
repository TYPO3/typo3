
.. include:: ../../Includes.txt

====================================================================
Breaking: #70578 - JumpURL functionality removed from the TYPO3 Core
====================================================================

See :issue:`70578`

Description
===========

The handling and generation of so-called Jump URLs has been moved into its own extension called "jumpurl"
and is now available in the TYPO3 Extension Repository (TER), and available via composer as the package name
"friendsoftypo3/jumpurl".


Impact
======

If the functionality was used in an installation before (e.g. with Direct Mail or via TypoScript), this functionality is
not working anymore.


Affected Installations
======================

All installations where Jump URLs were used.


Migration
=========

Download and install the extension "jumpurl" from the TER.


.. index:: PHP-API, Frontend, ext:jumpurl
