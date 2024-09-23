.. include:: /Includes.rst.txt

.. _feature-103019-1706856586:

======================================================================
Feature: #103019 - ModifyRedirectUrlValidationResultEvent PSR-14 event
======================================================================

See :issue:`103019`

Description
===========

This feature introduces the new PSR-14 event
:php:`ModifyRedirectUrlValidationResultEvent` in the felogin extension to
provide developers the possibility and flexibility to implement custom
validation for the redirect URL. This may be useful if TYPO3 frontend login
acts as an SSO system or if users should be redirected to an external URL after
login.

Example
-------

..  code-block:: php

    <?php

    namespace Vendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\FrontendLogin\Event\ModifyRedirectUrlValidationResultEvent;

    class ValidateRedirectUrl
    {
        #[AsEventListener('validate-custom-redirect-url')]
        public function __invoke(ModifyRedirectUrlValidationResultEvent $event): void
        {
            $parsedUrl = parse_url($event->getRedirectUrl());
            if ($parsedUrl['host'] === 'trusted-host-for-redirect.tld') {
                $event->setValidationResult(true);
            }
        }
    }


Impact
======

Developers now have the possibility to modify the validation results for the
redirect URL, allowing redirects to URLs not matching existing validation
constraints.

.. index:: Frontend, PHP-API, ext:felogin
