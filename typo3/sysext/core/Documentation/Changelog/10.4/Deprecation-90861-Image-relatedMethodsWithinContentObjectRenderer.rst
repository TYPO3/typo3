.. include:: /Includes.rst.txt

========================================================================
Deprecation: #90861 - Image-related methods within ContentObjectRenderer
========================================================================

See :issue:`90861`

Description
===========

The following methods within :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`,
all which are related to generating :html:`<img>` tags for TYPO3 Frontend output via TypoScript, have been marked as deprecated:

* :php:`cImage()`
* :php:`getBorderAttr()`
* :php:`getImageTagTemplate()`
* :php:`getImageSourceCollection()`
* :php:`linkWrap()`
* :php:`getAltParam()`

An additional method, :php:`imageLinkWrap()` has been marked as "internal" now in order to allow refactoring in future TYPO3 versions.

All methods have been moved to the :php:`ImageContentObject` class, als known as "IMAGE" cObject.

The methods purpose is only relevant for generating IMAGE, thus making the actual ContentObjectRenderer class smaller.


Impact
======

Any TypoScript configuration using code of this is not affected.

Only third-party extensions that use this code for frontend-related
image rendering might directly call these PHP methods. Calling these
methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom third-party extensions calling these
methods. TYPO3's Extension Scanner code can directly detect these calls.


Migration
=========

As all moved methods are protected, it is recommended to either
extend the ImageContentObject class, or copy the respective code
into the third-party extension requiring this code.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
