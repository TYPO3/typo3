.. include:: /Includes.rst.txt

.. _feature-97747-1654691279:

===========================================
Feature: #97747 - Introduce MailerInterface
===========================================

See :issue:`97747`

Description
===========

To be able to use your own custom mailer implementation in the TYPO3 Core, an
interface :php:`\TYPO3\CMS\Core\Mail\MailerInterface` is introduced, which extends
:php:`\Symfony\Component\Mailer\MailerInterface`

By default, :php:`\TYPO3\CMS\Core\Mail\Mailer` is registered as implementation in
:file:`Configuration/Services.yaml`.

Example
-------

..  code-block:: php

    use TYPO3\CMS\Core\Mail\MailerInterface;

    class MyClass
    {
        public function __construct(
            private readonly MailerInterface $mailer
        ) {
        }
    }

Or where constructor injection is not possible:

..  code-block:: php

    $mailer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailerInterface::class);

Impact
======

This change makes it possible to create your own :php:`\My\Custom\Mailer` that implements
:php:`\TYPO3\CMS\Core\Mail\MailerInterface` which is used by TYPO3 core. Therefore, it is recommended
to use the interface :php:`\TYPO3\CMS\Core\Mail\MailerInterface`, to let dependency injection inject the
desired implementation for every :php:`\TYPO3\CMS\Core\Mail\MailerInterface`.

Add the following line in :file:`Configuration/Services.yaml`, to ensure that your custom
implementation can be injected.

..  code-block:: yaml

    TYPO3\CMS\Core\Mail\MailerInterface:
      alias: My\Custom\Mailer

.. index:: PHP-API, ext:core
