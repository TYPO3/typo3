..  include:: /Includes.rst.txt

..  _important-106649-1746479411:

============================================================
Important: #106649 - Default Language Binding in page module
============================================================

See :issue:`106649`

Description
===========

The Page module now always uses **default language binding**
(:typoscript:`mod.web_layout.defLangBinding`) when displaying
localized content in the language comparison mode.

Default language binding makes editing translations easier: Editors can see
what they’re translating next to the default language. It additionally prevents
confusion when localizations are incomplete. Editors directly see which content
is not translated yet. This improves UX in the backend for multilingual sites,
which was also a result of recent JTBD interviews.

Impact
======

Editors will now always see the content elements next to each other within
the page module, when in language comparison mode.

Migration
=========

Because **default language binding** is now always enabled, the previous Page
TSconfig setting :typoscript:`mod.web_layout.defLangBinding` is not evaluated
and can therefore be removed

..  index:: Backend, TSConfig, ext:backend
