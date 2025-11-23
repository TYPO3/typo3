..  include:: /Includes.rst.txt

..  _feature-106092-1742366955:

===================================================================
Feature: #106092 - Associative array keys for TCA valuePicker items
===================================================================

See :issue:`106092`

Description
===========

It is now possible to define associative array keys for the :php:`items`
configuration of the TCA type :php:`valuePicker`.
The new keys are called :php:`label` and :php:`value`.

This follows the change made previously to the :php:`items` configuration
of the TCA types :php:`select`, :php:`radio`, and :php:`check`.
See :issue:`99739`.

Impact
======

The TCA :php:`items` configuration can now be defined in a more consistent
and readable way using associative array keys. This eliminates ambiguity
about whether the label or value comes first.

Optional keys such as :php:`icon`, :php:`group`, or :php:`description` can be
used as needed.

..  index:: TCA, ext:backend
