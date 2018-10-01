.. include:: ../../Includes.txt

==================================================
Important: #85560 - Location of XLF labels changed
==================================================

See :issue:`85560`

Description
===========

Downloaded files for XLF language files are usually stored within :file:`typo3conf/l10n`. When the environment
variable `TYPO3_PATH_ROOT` is set, which is common for all composer-based installations, the XLF language files
are now found outside the document root, available under :file:`var/labels/`.

The Environment API :php:`Environment::getLabelsPath()` resolves the correct full location path prefix.

.. index:: PHP-API
