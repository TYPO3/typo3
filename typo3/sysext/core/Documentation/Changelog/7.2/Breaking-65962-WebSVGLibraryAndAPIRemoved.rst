
.. include:: /Includes.rst.txt

======================================================================================
Breaking: #65962 - Third-party library "websvg" and the according API has been removed
======================================================================================

See :issue:`65962`

Description
===========

The third-party library "websvg" has been removed from the TYPO3 CMS Core. The according TypoScript options and
the public methods within PageRenderer have been removed without substitution.

The following PHP methods within PageRenderer have been removed:

.. code-block:: php

   $pageRenderer->setSvgPath()
   $pageRenderer->getSvgPath()
   $pageRenderer->loadSvg()
   $pageRenderer->enableSvgDebug()
   $pageRenderer->svgForceFlash()

The following TypoScript options are removed:

.. code-block:: typoscript

   page.javascriptLibs.SVG
   page.javascriptLibs.SVG.debug
   page.javascriptLibs.SVG.forceFlash


Impact
======

Any installation using one of the methods above in an extension will fail.

Any installation using `page.javascriptLibs.SVG = 1` will not include the websvg library anymore and might lead
to SVGs not being displayed anymore in certain browsers. Using the SVG Content Object will lead to the same result.


Affected installations
======================

TYPO3 CMS 7 installations using the TypoScript options, the SVG Content Object or the pageRenderer methods directly.


Migration
=========

Affected installations should include the "websvg" library directly from the library owner, and in their setups.


.. index:: PHP-API, TypoScript, Frontend, Backend
