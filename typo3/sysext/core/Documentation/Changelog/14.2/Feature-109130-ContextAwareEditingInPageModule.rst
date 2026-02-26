..  include:: /Includes.rst.txt

..  _feature-109130-1772489836:

===========================================================
Feature: #109130 - Context-aware editing in the page module
===========================================================

See :issue:`109130`

Description
===========

The page module now features a **context panel** for editing page properties
and content elements. Clicking an edit button opens a slide-in panel next to
the page layout. The editing form is displayed inside the panel while the
page layout remains visible in the background.

The panel supports all FormEngine fields in an improved UI. The panel header
displays the record title along with a **Save** and **Close** button. An
**expand** button allows switching to the full record editing form in the
content area at any time. After saving, the panel stays open for further
edits.

User settings
=============

The context panel is **enabled by default**. It can be disabled per user in
:guilabel:`User Settings` via the
:guilabel:`Use quick editing for records in the page module` option. When
disabled, edit buttons navigate directly to the full record editing form as
before. The setting takes effect immediately.

Impact
======

Editors can now edit records in the page module without leaving the page layout
context. The full editing form remains accessible for more complex editing tasks.

..  index:: Backend, ext:backend
