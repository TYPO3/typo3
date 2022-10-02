.. include:: /Includes.rst.txt

.. _breaking-98308-1662713106:

=============================================================================================
Breaking: #98308 - Legacy HTML attributes border and longdesc removed from frontend rendering
=============================================================================================

See :issue:`98308`

Description
===========

The :typoscript:`IMAGE` content object previously supported `longdesc` and `border` attributes
to be set to the :html:`<img>` tag which was composed. The appropriate settings
:typoscript:`longDesc` and :typoscript:`border` within :typoscript:`IMAGE` cObject
have been removed.

The TypoScript property :typoscript:`config.disableImgBorderAttr` has been removed
as well.

Also, the :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions` PHP class, which
generated default :html:`<img>` tags via the :php:`imgTag()` method, has been
adapted as the method is removed.

Impact
======

Using the TypoScript settings will have no effect anymore.

Calling the method :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->imgTag()`
will result in a fatal PHP error.

Affected installations
======================

TYPO3 installation using TypoScript :typoscript:`IMAGE` cObject explicitly
requiring the :typoscript:`border` and :typoscript:`longDesc` attributes.

Migration
=========

Instead of border attribute, styling via CSS should be used.

See https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/border.

Also use alternative markup for accessibility with the "title" attribute instead
of "longdesc".

See https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/longDesc
for examples on how to migrate.

For the removed :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->imgTag()`
method, it is recommended for PHP developers to build the HTML code themselves.

.. index:: TypoScript, PartiallyScanned, ext:frontend
