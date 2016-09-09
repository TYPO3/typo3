
.. include:: ../../Includes.txt

================================================
Feature: #59646 - Add TSFE property $requestedId
================================================

See :issue:`59646`

Description
===========

A new property within the main TypoScriptFrontendController for the frontend called $requestedId stores
the information about the page ID which is set before the page ID processing and resolving.
It is accessible via `$TSFE->getRequestedId()`. Also see `$TSFE->fetch_the_id()` method.
