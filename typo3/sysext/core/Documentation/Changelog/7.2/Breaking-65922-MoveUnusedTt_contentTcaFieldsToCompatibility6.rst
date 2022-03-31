
.. include:: /Includes.rst.txt

======================================================================
Breaking: #65922 - Move unused tt_content TCA fields to compatibility6
======================================================================

See :issue:`65922`

Description
===========

There are some database fields defined in 'frontend' which are not used by `frontend` or `css_styled_content`. These
fields have been moved to `compatibility6`.

- altText
- imagecaption
- imagecaption_position
- image_link
- longdescURL
- titleText


Affected Installations
======================

All installations with extensions installed depending on these fields. For instance installations still
using `css_styled_content` static templates of the TYPO3 CMS 4.* versions.


Migration
=========

Add the moved TCA and SQL definitions to your own extension or install the compatibility extension `compatibility6`.
The latter is not recommended and should be considered a short-term solution.


.. index:: Database, Frontend
