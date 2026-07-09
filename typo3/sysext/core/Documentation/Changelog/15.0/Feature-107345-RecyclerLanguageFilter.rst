..  include:: /Includes.rst.txt

..  _feature-107345-1783604196:

==========================================================
Feature: #107345 - Language filter for the recycler module
==========================================================

See :issue:`107345`

Description
===========

The recycler backend module now provides a language switcher in the module
doc header, comparable to the one in the record history module.

Selecting a language restricts the list of deleted records to that specific
language. The relevant language field is resolved per table through the TCA
schema :php:`Language` capability, so tables that use a custom language field
name are handled correctly.

Tables that are not language aware are omitted from the result while a language
is selected. Choosing the *[All]* entry shows all deleted records,
independent of their language or language awareness.

The selected language is persisted as module data for the backend user, so the
chosen language context is remembered across requests, just like the depth and
table selection. The language dropdown is only shown when there are
at least two languages present, otherwise it defaults to showing
all records.

Additionally, the language icon and name is now shown inside the recycler
record listing for each record that is language aware and has a language
set (also `-1` is evaluated as `all languages`).

Impact
======

Editors can now narrow down deleted records to a single language in the
recycler module, making it easier to locate and restore translated content.

..  index:: Backend, ext:recycler
