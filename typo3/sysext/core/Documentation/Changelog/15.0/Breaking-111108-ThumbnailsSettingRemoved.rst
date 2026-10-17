..  include:: /Includes.rst.txt

..  _breaking-111108-1792227470:

==================================================
Breaking: #111108 - GFX setting thumbnails removed
==================================================

See :issue:`111108`

Description
===========

The setting :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails']` has been
removed. It is removed from :file:`config/system/settings.php` on upgrade.

The setting turned off thumbnails of images in the page module, in the file
references of records, in the file list, the file browser and the link
browser. The rest of the backend, for example the clipboard, the metadata of
files, the live search and the workspaces module, showed thumbnails
regardless of it.

The backend now shows thumbnails of images everywhere. Whether the file list,
the file browser and the link browser show them is still chosen by every user.
In the file list, :typoscript:`options.file_list.enableDisplayThumbnails` in
user TSconfig still controls that choice.

Impact
======

Installations that had turned thumbnails off show thumbnails of images in the
page module, in the file references of records and, where users choose so, in
the file list, the file browser and the link browser.

Affected installations
======================

Installations whose :file:`config/system/settings.php` sets
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails']` to `false`, and
extensions that read the setting. The extension scanner reports them.

Migration
=========

None for installations, the upgrade removes the setting. Extensions that read
it drop that condition.

..  index:: Backend, LocalConfiguration, FullyScanned, ext:backend, ext:filelist
