..  include:: /Includes.rst.txt

..  _breaking-107944-1761867359:

===========================================================================================
Breaking: #107944 - Frontend CSS file processing no longer removes comments and whitespaces
===========================================================================================

See :issue:`107944`

Description
===========

When the TYPO3 frontend is configured to compress included
CSS assets, it also tried to minify CSS by removing comments
and some whitespaces.

This feature has been removed: The implementation is brittle
especially with modern CSS syntax capabilities and there are
typically no measurable performance gains neither when transferring
files nor when clients parse files.


Impact
======

CSS asset files included in frontend pages may become larger when
they include many comments. TYPO3 CSS parsing was disabled by default
and only active using a reconfigured system toggle
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']`
plus additional TypoScript.

This removed feature should usually have low impact and most systems
can probably ignore this change.


Affected installations
======================

Systems actively using the CSS parsing feature in TYPO3 frontend
asset management may be affected.


Migration
=========

Instances worried about CSS asset file sizes that relied on
the feature could either optimize their CSS files manually, or
ignore the fact clients will receive comments,  or - better - should
consider establishing a specialized frontend build chain to minify
CSS and probably JavaScript as well. Solutions often come with many
more suitable features on this level like linting and syntax checking
that TYPO3 core will never provide on its own.


..  index:: Frontend, NotScanned, ext:frontend
