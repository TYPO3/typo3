..  include:: /Includes.rst.txt

..  _feature-102239-1794220811:

=================================================
Feature: #102239 - TCAdefaults for FlexForm fields
=================================================

See :issue:`102239`

Description
===========

The :typoscript:`TCAdefaults` configuration now supports fields of FlexForm
data structures. Previously, the default value of a FlexForm field could only be
set by the :xml:`<default>` element in the data structure itself.

The path follows the syntax known from :typoscript:`TCEFORM` for FlexForm fields:
table, FlexForm field, data structure identifier, sheet and FlexForm field name.
Dots in FlexForm field names have to be escaped:

..  code-block:: typoscript
    :caption: Page TSconfig

    TCAdefaults.tt_content.pi_flexform.news_pi1.sDEF.settings\.orderBy = title
    TCAdefaults.tt_content.pi_flexform.news_pi1.sDEF.settings\.limit = 5

The same syntax is supported in User TSconfig. As for regular fields, Page
TSconfig overrides User TSconfig.

Impact
======

When a new record is created in the backend, FlexForm fields are pre-filled
with the values configured in :typoscript:`TCAdefaults`, taking precedence over
the :xml:`<default>` of the data structure. Existing records are not changed,
also not if a FlexForm field has no value yet.

Fields within FlexForm section containers are not supported, as for
:typoscript:`TCEFORM`. Like the :xml:`<default>` of the data structure, the
values are applied by the backend forms only, not when records are created
via the :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` API.

..  index:: Backend, FlexForm, TSConfig, ext:backend
