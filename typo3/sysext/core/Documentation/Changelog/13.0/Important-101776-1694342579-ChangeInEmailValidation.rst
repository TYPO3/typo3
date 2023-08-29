.. include:: /Includes.rst.txt

.. _important-101776-1694342579:

===================================================================================================
Important: #101776 - Email validation in GeneralUtility::validEmail() now rejects spaces before "@"
===================================================================================================

See :issue:`101776`

Description
===========

The :php:`GeneralUtility::validEmail()` method uses the package egulias/email-validator
for validating emails.
This library treats an email address like "email @example.com" with a space before the "@"
character as valid, but issues a warning, which has previously not been caught by TYPO3. Warnings
like these are defined as "deviations from the RFC that in a broader interpretation are accepted."

In the context of TYPO3, such non-RFC mail address shall be rejected.
Thus, this specific warning (:php:CFWSNearAt) will now be caught, and the warning is turned into an
invalidation of the given mail address.

This will have the effect, that if integrators previously accepted email addresses formatted like
these, validation will now fail (as the RFC implies).

.. index:: Backend, ext:core
