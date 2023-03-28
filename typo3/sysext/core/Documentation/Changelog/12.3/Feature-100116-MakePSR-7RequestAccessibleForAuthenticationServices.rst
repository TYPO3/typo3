.. include:: /Includes.rst.txt

.. _feature-100116-1678299307:

============================================================================
Feature: #100116 - Make PSR-7 request accessible for authentication services
============================================================================

See :issue:`100116`

Description
===========

Authentication services can now access the PSR-7 request object via the
:php:`$authInfo` array. Previously, custom TYPO3 authentication services
did not have direct access to the object and therefore had to either
use PHP super globals or TYPO3's `GeneralUtility::getIndpEnv()` method.

The following example shows how to retrieve the PSR-7 request in the
`initAuth()` method of a custom authentication service:

..  code-block:: php

    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        /** @var ServerRequestInterface $request */
        $request = $authInfo['request'];

        /** @var NormalizedParams $normalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');
        $isHttps = $normalizedParams->isHttps();
    }


Impact
======

Custom TYPO3 authentication services can now directly access the PSR-7
request object from the authentication process. It is available via the
:php:`request` key of the :php:`$authInfo` array, which is handed over
to the :php:`initAuth()` method.

.. index:: ext:core
