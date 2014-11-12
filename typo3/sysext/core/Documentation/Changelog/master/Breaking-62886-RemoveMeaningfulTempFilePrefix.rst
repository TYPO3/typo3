==================================================================
Breaking: #62886 - Removed setting config.meaningfulTempFilePrefix
==================================================================

Description
===========

The setting *config.meaningfulTempFilePrefix* which was still used for images built by GIFBUILDER and put in
typo3temp/GB/ was removed. With this option it was possible to configure a meaningful file prefix limiting
the filename prefix to a certain character length.

Impact
======

All files will always have their original filename fully prepended in the folder typo3temp/GB/.

Affected Installations
======================

All installations using GIFBUILDER to generate images. The files within typo3temp/GB/ will now be called
with the full name of each original file before the hash and the file extension.

Migration
=========

The setting can be removed from any TypoScript configuration because there is no impact anymore.