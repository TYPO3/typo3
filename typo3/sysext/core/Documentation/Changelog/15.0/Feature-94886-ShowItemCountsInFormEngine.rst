..  include:: /Includes.rst.txt

..  _feature-94886-1787128828:

=========================================================
Feature: #94886 - Show selected item counts in FormEngine
=========================================================

See :issue:`94886`

Description
===========

The backend FormEngine now displays a compact badge for fields configured via
TCA :php:`types` and :php:`renderTypes` when they use item count validation
rules such as :php:`minItems` and :php:`maxItems`.

The badge reflects the currently selected number of items and the configured
minimum or maximum values, and it updates automatically when entries are
added or removed. This improves the visibility of selection limits for
supported field types including select, category, group, folder and inline
elements.

When the maximum allowed number of items is reached, the max-items notice
continues to be shown as before. If a field requires a minimum number of
selected items, the badge also indicates that the current selection has not
yet reached the required value.

Impact
======

Editors get immediate visual feedback about how many items are currently
selected in relation to the configured :php:`minItems` and :php:`maxItems`
limits. The badge is injected on the client side for any field exposing item
count validation rules, so it also applies to custom render types without
requiring additional PHP code.

..  index:: Backend, JavaScript, ext:backend, NotScanned