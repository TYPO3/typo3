..  include:: /Includes.rst.txt

..  _important-106649-1746479411:

==============================================================
Important: #106649 - Default language binding in layout module
==============================================================

See :issue:`106649`

Description
===========

The :guilabel:`Content > Layout` module now always uses **default language binding**
(:typoscript:`mod.web_layout.defLangBinding`) when displaying
localized content in language comparison mode.

Default language binding makes editing translations easier: Editors can see
what they are translating next to the default language. It also prevents
confusion when localizations are incomplete. Editors can directly see which
content is not yet translated. This improves the user experience in the backend
for multilingual sites, which was also a result of recent "Jobs To Be Done"
(JTBD) interviews.

..  note::
    The "Content > Layout" module was called "Web > Page" before TYPO3 v14, see also
    `Feature: #107628 - Improved backend module naming and structure <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

Impact
======

Editors will now always see the content elements next to each other within
the :guilabel:`Content > Layout` module when in language comparison mode.

Migration
=========

Because **default language binding** is now always enabled, the previous Page
TSconfig setting :typoscript:`mod.web_layout.defLangBinding` is not evaluated
and can therefore be removed.

..  index:: Backend, TSConfig, ext:backend
