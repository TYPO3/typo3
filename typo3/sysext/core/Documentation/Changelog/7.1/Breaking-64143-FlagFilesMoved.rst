
.. include:: ../../Includes.txt

======================================================
Breaking: #64143 - Language / Country Flag files moved
======================================================

See :issue:`64143`

Description
===========

The static GIF file collection representing flags within :file:`typo3/gfx/flags/` has been removed. All PNG flag images
from EXT:t3skin are moved from :file:`typo3/sysext/t3skin/images/flags/` to
:file:`typo3/sysext/core/Resources/Public/Icons/flags/`. The according stylesheets for the that :file:`flags-*` icon
files have been moved to EXT:core as well.

Impact
======

Any hard-coded reference on any of the files within :file:`typo3/gfx/flags/` and
:file:`typo3/sysext/core/Resources/Public/Icons/flags/` will result in an error.

Changing the EXT:t3skin flags sprite now means changing the flags sprite of EXT:core.

Affected installations
======================

Any installation using third-party extensions that access :file:`typo3/gfx/flags/` or
:file:`typo3/sysext/t3skin/images/flags/` will fail.


.. index:: PHP-API, Backend
