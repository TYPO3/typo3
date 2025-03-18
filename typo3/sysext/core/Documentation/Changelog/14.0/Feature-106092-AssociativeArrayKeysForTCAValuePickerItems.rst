..  include:: /Includes.rst.txt

..  _feature-106092-1742366955:

===================================================================
Feature: #106092 - Associative array keys for TCA valuePicker items
===================================================================

See :issue:`106092`

Description
===========

It is now possible to define associative array keys for the :php:`items`
configuration of TCA configuration :php:`valuePicker`. The
new keys are called: :php:`label` and :php:`value`.

This follows the change done already to the :php:`items` configuratio of the TCA types :php:`select`, :php:`radio` and :php:`check`. See :issue:`99739`


Impact
======

It is now much easier and clearer to define the TCA :php:`items` configuration
with associative array keys. The struggle to remember which option is first,
label or value, is now over. In addition, optional keys like :php:`icon` and
:php:`group` can be omitted, for example, when one desires to set the
:php:`description` option.

..  index:: TCA, ext:backend
