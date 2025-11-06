..  include:: /Includes.rst.txt

..  _feature-107989-1776112956:

===================================================================
Feature: #107989 - Add H6 heading level to tt_content header_layout
===================================================================

See :issue:`107989`

Description
===========

The `header_layout` field of `tt_content` now includes a built-in option for the
`H6` heading level. The new option uses the value `6`, completing TYPO3's
default support for heading levels `H1` through `H6`.

Impact
======

Integrators and developers can now select `H6` in the `header_layout` field of
content elements without requiring additional TCA configuration.

An update is only necessary if the available `header_layout` values are
restricted through TCA adoptions or Page TSconfig, for example with
`TCEFORM.tt_content.header_layout.keepItems`, and the value `6` is not included
yet. In that case, the Page TSconfig configuration must be adjusted to make the
new option available.

..  index:: Frontend, TCA, ext:frontend