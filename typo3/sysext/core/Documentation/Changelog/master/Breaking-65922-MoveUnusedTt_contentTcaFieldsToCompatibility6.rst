======================================================================
Breaking - #65922: Move unused tt_content TCA fields to compatibility6
======================================================================

Description
===========

There are also some fields defined in 'frontend' which are not used by `frontend` or `css_styled_content`. These fields are moved to `compatibility6`.

- altText
- imagecaption
- imagecaption_position
- image_link
- longdescURL
- titleText


Affected Installations
======================

All installations with extensions installed depending on these fields. For instance installations still using `css_styled_content` static templates of the TYPO3 CMS 4.* versions.


Migration
=========

Add the moved TCA and sql definitions to you own extension or install the compatibility extension `compatibility6`.