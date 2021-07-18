.. include:: ../../Includes.txt

=====================================================
Breaking: #83256 - Removed lockFilePath functionality
=====================================================

See :issue:`83256`

Description
===========

The TypoScript option :typoscript:`config.lockFilePath` has been removed, which was possible to allow TypoScript
:typoscript:`stdWrap.filelist` to use a different base directory than fileadmin/ (which was the default).

However, :typoscript:`stdWrap.filelist` now checks for valid local FAL storages (File Abstraction Layer), which can
now be used if multiple storages are in use.

Thus, the following PHP property has been removed:

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->lockFilePath`

The following PHP method has been removed:

* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->clean_directory()`


Impact
======

Setting :typoscript:`config.lockFilePath` has no effect anymore.

Accessing or setting :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->lockFilePath` will trigger
a PHP notice.

Calling :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->clean_directory()` will trigger a PHP fatal error.


Affected Installations
======================

Any installation using the PHP method/property or having config.lockFilePath set to a specific non-FAL folder,
and using :typoscript:`stdWrap.filelist` functionality.


Migration
=========

If the TypoScript option was set to a different folder than a FAL storage, ensure to set a local FAL storage
to this folder.

.. index:: Frontend, TypoScript, PartiallyScanned
