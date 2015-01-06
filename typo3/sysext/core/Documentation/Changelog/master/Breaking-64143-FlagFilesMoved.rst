======================================================
Breaking: #64143 - Language / Country Flag files moved
======================================================

Description
===========

The static GIF file collection representing flags within typo3/gfx/flags/ are removed. All PNG flag images from
EXT:t3skin are moved from typo3/sysext/t3skin/images/flags/ to typo3/sysext/core/Resources/Public/Icons/flags/. The
according stylesheets for the that flags-* icon files are moved to EXT:core as well.

Impact
======

Any hard-coded reference on any of the files within typo3/gfx/flags/ and typo3/sysext/core/Resources/Public/Icons/flags/
will result in an error.

Changing of the EXT:t3skin flags sprite now means changing the flags sprite of EXT:core.

Affected installations
======================

Any installation using third-party extensions that access typo3/gfx/flags/ or
typo3/sysext/t3skin/images/flags/ will fail.
