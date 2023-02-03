.. include:: /Includes.rst.txt

.. _feature-99586-1673989775:

=================================================================
Feature: #99586 - Registration of upgrade wizards via service tag
=================================================================

See :issue:`99586`

Description
===========

Upgrade wizards are used to execute one time migrations when
updating a TYPO3 installation. The registration was previously done
in an extension's :php:`ext_localconf.php` file. This has now been
improved by introducing the custom PHP attribute
:php:`\TYPO3\CMS\Install\Attribute\UpgradeWizard`. All upgrade wizards,
defining the new attribute, are automatically tagged and registered
in the service container. The registration via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`
has been deprecated.

The registration of an upgrade wizard is therefore now be done
directly in the class by adding the new attribute with the upgrade
wizard's unique identifier as constructor argument:

..  code-block:: php

    use TYPO3\CMS\Install\Attribute\UpgradeWizard;
    use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

    #[UpgradeWizard('myUpgradeWizard')]
    class MyUpgradeWizard implements UpgradeWizardInterface
    {

    }

..  note::

    All upgrade wizards have to implement the
    :php:`\TYPO3\CMS\Install\Updates\UpgradeWizardInterface`.

Impact
======

It is now possible to tag upgrade wizards with the PHP attribute
:php:`\TYPO3\CMS\Install\Attribute\UpgradeWizard` to have them
auto-configured and auto-registered.

.. index:: Backend, PHP-API, ext:install
