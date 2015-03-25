=======================================
Breaking: #66034 - Drop Content Adapter
=======================================

Description
===========

The TYPO3 configuration option [FE][activateContentAdapter] and the associated code is now dropped from Core.
This option was used to transform FAL fields back to old file fields.

Impact
======

Any installation using TypoScript referring to old file columns as  present *before* TYPO3 CMS 6.x will stop working.

Migration
=========

Change your TypoScript to use the new content object FILES to retrieve files.
