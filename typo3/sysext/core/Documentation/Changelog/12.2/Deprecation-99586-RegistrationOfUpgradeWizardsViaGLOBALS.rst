.. include:: /Includes.rst.txt

.. _deprecation-99586-1673990657:

==================================================================
Deprecation: #99586 - Registration of upgrade wizards via $GLOBALS
==================================================================

See :issue:`99586`

Description
===========

Registration of upgrade wizards via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`,
usually placed in an extension's :file:`ext_localconf.php` has been deprecated
in favor of the :ref:`new service tag <feature-99586-1673989775>`.

Additionally, the :php:`\TYPO3\CMS\Install\Updates\UpgradeWizardInterface`, which all upgrade wizards must
implement, does no longer require the :php:`getIdentifier()` method. TYPO3 does
not use this method anymore since an upgrade wizard's identifier is now
defined using the new service tag.


Impact
======

Upgrade wizards, registered via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`
will no longer be recognized in TYPO3 v13.

The definition of the :php:`getIdentifier()` method has no effect anymore.


Affected installations
======================

All installations registering custom upgrade wizards using
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`.

All installations implementing the :php:`getIdentifier()` method in their
upgrade wizards.


Migration
=========

Use the new service tag to register custom upgrade wizards and remove the
registration via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`.

Before
~~~~~~

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['myUpgradeWizard']
        = \MyVendor\MyExtension\Updates\MyUpgradeWizard::class;

After
~~~~~

..  code-block:: php
    :caption: EXT:my_extension/Classes/Updates/MyUpgradeWizard.php

    namespace MyVendor\MyExtension\Updates;

    use TYPO3\CMS\Install\Attribute\UpgradeWizard;
    use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

    #[UpgradeWizard('myUpgradeWizard')]
    class MyUpgradeWizard implements UpgradeWizardInterface
    {

    }

Drop any :php:`getIdentifier()` method in custom upgrade wizards.

.. index:: Backend, PHP-API, FullyScanned, ext:install
