.. include:: /Includes.rst.txt

========================================================
Breaking: #83161 - Remove TYPO3.LLL usages in TYPO3 core
========================================================

See :issue:`83161`

Description
===========

After moving to the :js:`TYPO3.lang` API for javascript, the :js:`TYPO3.LLL` is not needed anymore.


Impact
======

All extensions which are using :js:`TYPO3.LLL` for translation in javascript should be checked and updated accordingly.


Affected Installations
======================

Any installation using extensions, which are using :js:`TYPO3.LLL`.


Migration
=========

Use :js:`TYPO3.lang['label']` from javascript. To make custom language labels available in javascript,
add :php:`$this->pageRenderer->addInlineLanguageLabelFile('EXT:foo/Resources/Private/Language/locallang.xlf');`
in your backend controller.

The class typo3/sysext/feedit/Classes/FrontendEditAssetLoader.php was removed, so if you used it in your code you have to remove the dependency.

.. index:: Backend, JavaScript, PHP-API, NotScanned
