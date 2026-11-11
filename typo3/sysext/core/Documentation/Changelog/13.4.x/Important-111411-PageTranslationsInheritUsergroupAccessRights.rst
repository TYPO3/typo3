..  include:: /Includes.rst.txt

..  _important-111411-1794480000:

======================================================================
Important: #111411 - Page translations inherit usergroup access rights
======================================================================

See :issue:`111411`

Description
===========

The field "Usergroup Access Rights" (:sql:`pages.fe_group`) of a page
translation is synchronized from the page in the default language again, as it
was up to TYPO3 v12. The field is no longer displayed when editing a page
translation.

Since TYPO3 v13.3, the field was generated from the
:php:`['ctrl']['enablecolumns']['fe_group']` setting of the :php:`pages` table
and lost its :php:`'l10n_mode' => 'exclude'` setting. Editors could set a
different value on a page translation. Access to a page is evaluated on the
page in the default language only, but the value of the translation replaced
the value of the default language page in language overlays, for example in
the "USR" state of menus.

Language overlays of pages now keep the "Usergroup Access Rights" of the page
in the default language. A deviating value stored in a page translation is
ignored in the frontend. It is replaced by the value of the page in the default
language when that page is saved the next time.

Affected installations
======================

Installations with page translations that have a "Usergroup Access Rights"
value different from the page in the default language are affected.

Migration
=========

Translated pages that are meant to be restricted to other frontend user
groups than their default language page must be restructured into separate
pages.

..  index:: Database, TCA, Frontend, ext:core
