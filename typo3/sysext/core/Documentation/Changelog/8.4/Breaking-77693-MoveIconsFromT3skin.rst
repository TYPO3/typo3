.. include:: ../../Includes.txt

=========================================
Breaking: #77693 - Move icons from t3skin
=========================================

See :issue:`77693`

Description
===========

Icons from `EXT:t3skin/` have been removed or moved to different locations.


Impact
======

References of the following images of EXT:t3skin will throw a 404 not found:

- `typo3/sysext/t3skin/icons/gfx/error.png`
- `typo3/sysext/t3skin/icons/gfx/i/_icon_ftp.gif`
- `typo3/sysext/t3skin/icons/gfx/information.png`
- `typo3/sysext/t3skin/icons/gfx/notice.png`
- `typo3/sysext/t3skin/icons/gfx/warning.png`

References of the following images of EXT:t3skin/icons/gfx/i have been moved:

- `typo3/sysext/t3skin/icons/gfx/icon_fatalerror.gif` => `typo3/sysext/backend/Resources/Public/Icons/icon_fatalerror.gif`
- `typo3/sysext/t3skin/images/icons/status/status-edit-read-only.png` => `typo3/sysext/backend/Resources/Public/Icons/status-edit-read-only.png`
- `typo3/sysext/t3skin/images/icons/status/warning-in-use.png` => `typo3/sysext/backend/Resources/Public/Icons/warning-in-use.png`
- `typo3/sysext/t3skin/images/icons/status/warning-lock.png` => `typo3/sysext/backend/Resources/Public/Icons/warning-lock.png`
- `typo3/sysext/t3skin/images/icons/status/status-reference-hard.png` => `typo3/sysext/impexp/Resources/Public/Icons/status-reference-hard.png`
- `typo3/sysext/t3skin/images/icons/status/status-reference-soft.png` => `typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png`


Affected Installations
======================

Installations or extensions which have references to icons in EXT:t3skin/icons/.


Migration
=========

No migration

.. index:: Backend, ext:t3skin
