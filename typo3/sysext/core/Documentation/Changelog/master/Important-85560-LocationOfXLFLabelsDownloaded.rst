.. include:: ../../Includes.txt

=====================================================
Important: #85560 - Location of XLF labels downloaded
=====================================================

See :issue:`85560`

Description
===========

Downloaded files for XLF language files are usually stored within `typo3conf/l10n`, however, if the environment
variable `TYPO3_PATH_ROOT` is set, which is common for all composer-based installations, the XLF language files
are now found outside the document root, available under `var/labels/`.

The Environment API :php:`Environment::getLabelsPath()` resolves the correct full location path prefix.

.. index:: PHP-API