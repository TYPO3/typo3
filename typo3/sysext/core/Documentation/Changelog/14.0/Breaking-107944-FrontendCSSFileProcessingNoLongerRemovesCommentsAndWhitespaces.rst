..  include:: /Includes.rst.txt

..  _breaking-107944-1761867359:

===========================================================================================
Breaking: #107944 - Frontend CSS file processing no longer removes comments and whitespaces
===========================================================================================

See :issue:`107944`

Description
===========

When the TYPO3 frontend was configured to compress included CSS assets, it also
attempted to minify CSS by removing comments and certain whitespace characters.

This behavior has now been removed. The previous implementation was brittle,
especially with modern CSS syntax, and provided no measurable performance
benefit in either file transfer or client-side parsing.

Impact
======

CSS asset files included in frontend pages may become slightly larger if they
contain many comments. TYPO3’s internal CSS parsing was disabled by default and
only active when explicitly enabled using
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']`
along with additional TypoScript configuration.

In most cases, this change has minimal or no practical impact.

Affected installations
======================

Instances that actively used TYPO3's built-in CSS parsing feature for frontend
asset management are affected.

Migration
=========

If minimizing CSS file size is important, consider one of the following options:

-   Optimize or minify CSS files manually during deployment.
-   Accept that comments and whitespace are retained (usually negligible impact).
-   Preferably, integrate a dedicated frontend build chain to handle CSS and
    JavaScript minification.

Modern frontend build tools provide many additional advantages, such as
linting, syntax validation, and advanced optimizations, which are beyond the
scope of the TYPO3 Core.

..  index:: Frontend, NotScanned, ext:frontend
