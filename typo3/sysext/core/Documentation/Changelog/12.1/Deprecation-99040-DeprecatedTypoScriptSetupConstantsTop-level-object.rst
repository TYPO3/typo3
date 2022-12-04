.. include:: /Includes.rst.txt

.. _deprecation-99040-1668076207:

==============================================================================
Deprecation: #99040 - Deprecated TypoScript setup "constants" top-level-object
==============================================================================

See :issue:`99040`

Description
===========

The frontend TypoScript setup (!) top-level-object :typoscript:`constants` can be
used to define constants for replacement inside a :typoscript:`parseFunc`.
If :typoscript:`parseFunc` somewhere is configured with :typoscript:`.constants = 1`,
then all occurrences of the constant in the text will be substituted with the
actual value.

This construct has been marked as deprecated in TYPO3 v12 and will be removed with v13.


Impact
======

Using the :typoscript:`constants` top-level-object in combination with the
:typoscript:`constants = 1` in :typoscript:`parseFunc` to substitute strings
like :typoscript:`###MY_CONSTANT###` triggers a deprecation level log error
in TYPO3 v12 and will stop working in v13.


Affected installations
======================

This is a relatively rarely used feature, not well-known by many integrators.
TYPO3 integrators should watch out for :typoscript:`###` markers within
TypoScript, the :guilabel:`Template` backend module search functionality should
help here.

The :guilabel:`Template Analyzer` will also show usages of the setup top-level-object
:typoscript:`constants`.


Migration
=========

One possible solution is to switch to TypoScript constants / settings instead
for simple cases.

A simple example usage before:

..  code-block:: typoscript

    TypoScript setup:

    constants.EMAIL = mail@example.com
    page = PAGE
    page.10 = TEXT
    page.10.value = Write an email to ###EMAIL###
    page.10.parseFunc.constants = 1

Switching to a TypoScript constant / setting:

..  code-block:: typoscript

    TypoScript constants / settings:

    myEmail = mail@example.com

    TypoScript setup:

    page = PAGE
    page.10 = TEXT
    page.10.value = Write an email to {$myEmail}

The main usage of this feature has been a "magic" substitution within :typoscript:`lib.parseFunc_RTE`:
When :sql:`tt_content` rich text content elements contain such substitution strings, they are
replaced by :typoscript:`parseFunc` accordingly. For instance, a tt_content RTE element with the
content `Send an email to ###EMAIL###` would substitute to `Send an email to email@example.com` *if*
the top-level setup :typoscript:`constants` object has been set up. This substitution
relies on the fact that editors actively know about and use this construct: If only one content
element did not prepare for this - since an editor forgot or hasn't been trained about it, changing
such a constant on TypoScript level would still lead to faulty frontend output, rendering the
entire substitution approach useless.

In case instances still rely on this magic substitution principle, and made sure all editors
always know and follow this approach, instances can use the :typoscript:`userFunc`
property of :typoscript:`parseFunc` to re-implement the functionality: basically by
copying the deprecated code to an own class and registering the :typoscript:`userFunc`
in :typoscript:`lib.parseFunc_RTE`.


.. index:: TypoScript, NotScanned, ext:frontend
