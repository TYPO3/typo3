..  include:: /Includes.rst.txt

..  _feature-108904-1771065699:

===========================================================================================
Feature: #108904 - Add generic error action for custom HTTP status codes in ErrorController
===========================================================================================

See :issue:`108904`

Description
===========

The :php:`TYPO3\CMS\Frontend\Controller\ErrorController` has been enhanced with
a new method :php:`customErrorAction()` which allows custom error handling
for HTTP status codes.

The new method can be used with TYPO3 site error handling, allowing site
administrators to configure dedicated error handling (for example, rendering a
Fluid template) for a given status code.

Example of usage in an Extbase action:

..  code-block:: php

    use TYPO3\CMS\Core\Http\PropagateResponseException;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Frontend\Controller\ErrorController;

    $response = GeneralUtility::makeInstance(ErrorController::class)->customErrorAction(
        $this->request,
        429,
        'Rate limit exceeded.',
        'You have exceeded the rate limit.'
    );
    throw new PropagateResponseException($response, 1771065101);

Impact
======

It is now possible to trigger custom error pages with specific HTTP status
codes and messages from within TYPO3 or extensions, while still respecting
the error handling in the main site configuration.

..  index:: Frontend, ext:core
