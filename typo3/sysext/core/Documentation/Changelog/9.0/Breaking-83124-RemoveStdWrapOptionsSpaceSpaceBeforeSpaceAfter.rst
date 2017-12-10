.. include:: ../../Includes.txt

========================================================================
Breaking: #83124 - Remove stdWrap options space, spaceBefore, spaceAfter
========================================================================

See :issue:`83124`

Description
===========

The stdWrap options :ts:`space`, :ts:`spaceBefore`, :ts:`spaceAfter` are rarely used and should be better done completely by CSS.


Impact
======

The stdWrap options :ts:`space`, :ts:`spaceBefore`, :ts:`spaceAfter` do not work anymore. The following calls to :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` will trigger an error:

- :php:`stdWrap_spaceBefore`
- :php:`stdWrap_spaceAfter`
- :php:`stdWrap_space`


Affected Installations
======================

Any instance using the stdWrap option :ts:`space`, :ts:`spaceBefore`, :ts:`spaceAfter` or calls to :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` :

- :php:`stdWrap_spaceBefore`
- :php:`stdWrap_spaceAfter`
- :php:`stdWrap_space`


Migration
=========

Use CSS or a wrap option of stdWrap.

.. index:: Frontend, TypoScript, PartiallyScanned
