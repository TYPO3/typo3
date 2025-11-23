..  include:: /Includes.rst.txt

..  _breaking-107473-1758113238:

===================================================================
Breaking: #107473 - TypoScript condition function getTSFE() removed
===================================================================

See :issue:`107473`

Description
===========

The TypoScript condition function :typoscript:`getTSFE()` has been removed.

After various properties like :typoscript:`getTSFE().type` were already removed
in TYPO3 v13, the remaining parts of this functionality have now been removed
in TYPO3 v14.

The most common remaining use was accessing the current page ID via
:typoscript:`getTSFE().id`, which can be replaced with
:typoscript:`request.getPageArguments().getPageId()`.

Impact
======

Conditions using :typoscript:`getTSFE()` will no longer evaluate to true and
must be updated.

Affected installations
======================

Instances with TypoScript conditions that use the function
:typoscript:`getTSFE()` are affected.

Migration
=========

Replace :typoscript:`getTSFE()` with an equivalent condition. For example:

**Before:**

..  code-block:: typoscript

    [getTSFE() && getTSFE().id == 42]

**After:**

..  code-block:: typoscript

    [request?.getPageArguments()?.getPageId() == 42]

..  index:: TypoScript, NotScanned, ext:frontend
