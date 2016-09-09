
.. include:: ../../Includes.txt

====================================================================================================
Breaking: #64719 - Multimedia and Media cObjects and Content Types are moved to new system extension
====================================================================================================

See :issue:`64719`

Description
===========

The Content Element Types "media" and "multimedia" have been extracted into one single place, which is an
extension called "mediace". This extension is not installed by default but is shipped with the core.

The following Content Objects are not available anymore by default:

	* MULTIMEDIA
	* MEDIA
	* SWFOBJECT
	* FLOWPLAYER
	* QTOBJECT

The Content Types "media" and "multimedia" are not available anymore by default.

The table column `tt_content.multimedia` is not available anymore by default.


Impact
======

Any TypoScript using any of the cObjects directly or Content Elements with the CType "media" or "multimedia"
will result in no output. Existing Content Elements of this type cannot be edited anymore.


Affected installations
======================

TYPO3 CMS 7 installations still using any of the cObjects or having Content Elements of CType "media" or "multimedia".


Migration
=========

Install the system extension "mediace" to regain all functionality as it was before.
