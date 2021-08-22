.. include:: ../../Includes.txt

=========================================================================
Deprecation: #94959 - ContentObjectRenderer constructor in StandaloneView
=========================================================================

See :issue:`94959`

Description
===========

The :php:`ContentObjectRenderer` argument to :php:`TYPO3\CMS\Fluid\View\StandaloneView`
has been deprecated. The TYPO3 core never used this optional argument and
it added a hard dependency to extbase classes from StandaloneView, which should
be avoided.

The ContentObjectRenderer instance within StandaloneView has been used to update
the extbase :php:`ConfigurationManager` singleton, even though extbase bootstrap
already sets the current ContentObjectRenderer to ConfigurationManager.


Impact
======

Extensions creating instances of :php:`StandaloneView` and handing over an
instance of :php:`ContentObjectRenderer` as constructor argument will get
a deprecation level log message logged.


Affected Installations
======================

Most instances are probably not affected by this change since handing over
the constructor argument is rather unusual.


Migration
=========

Do not hand over an instance of :php:`ContentObjectRenderer` when creating an
instance of :php:`StandaloneView`.

.. index:: Fluid, PHP-API, NotScanned, ext:fluid
