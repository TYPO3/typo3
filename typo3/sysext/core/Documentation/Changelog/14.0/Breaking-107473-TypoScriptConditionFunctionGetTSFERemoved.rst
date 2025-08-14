..  include:: /Includes.rst.txt

..  _breaking-107473-1758113238:

===================================================================
Breaking: #107473 - TypoScript condition function getTSFE() removed
===================================================================

See :issue:`107473`

Description
===========

The TypoScript condition function :typoscript:`getTSFE()` has been removed.

After various properties like :typoscript:`getTSFE().type` have already been removed
in TYPO3 v13, last remains of this functionality have been removed with TYPO3 v14.

The most common remaining usage was accessing the current page id using
:typoscript:`getTSFE().id`, which can be substituted by
:typoscript:`request.getPageArguments().getPageId()`


Impact
======

Using a condition like :typoscript:`getTSFE()` will never evaluate to true
and needs adaption.


Affected installations
======================

Instances with TypoScript conditions using function :typoscript:`getTSFE()` are affected.

Migration
=========

Adapt :typoscript:`getTSFE()` to an alternative. Example:

..  code-block:: typoscript

    # Before
    [getTSFE() && getTSFE().id == 42]

    # After
    [request?.getPageArguments()?.getPageId() == 42]


..  index:: TypoScript, NotScanned, ext:frontend
