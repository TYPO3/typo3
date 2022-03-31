.. include:: /Includes.rst.txt

=========================================================================
Deprecation: #94959 - ContentObjectRenderer constructor in StandaloneView
=========================================================================

See :issue:`94959`

Description
===========

The :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` constructor
argument of :php:`TYPO3\CMS\Fluid\View\StandaloneView` has been marked as
deprecated. The TYPO3 core never used this optional argument and
it added a hard dependency to Extbase classes from StandaloneView, which should
be avoided.

The :php:`ContentObjectRenderer` instance within :php:`StandaloneView` has been used to update
the Extbase :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager` singleton,
even though Extbase bootstrap already sets the current ContentObjectRenderer to
:php:`ConfigurationManager`.


Impact
======

Extensions creating instances of :php:`StandaloneView` and handing over an
instance of :php:`ContentObjectRenderer` as constructor argument will see a PHP :php:`E_USER_DEPRECATED` error raised.


Affected Installations
======================

Most instances are probably not affected by this change since handing over
the constructor argument is rather unusual.


Migration
=========

Do not hand over an instance of :php:`ContentObjectRenderer` when creating an
instance of :php:`StandaloneView`.

.. index:: Fluid, PHP-API, NotScanned, ext:fluid
