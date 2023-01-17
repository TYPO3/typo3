.. include:: /Includes.rst.txt

.. _deprecation-99586-1673990657:

==================================================================
Deprecation: #99586 - Registration of upgrade wizards via $GLOBALS
==================================================================

See :issue:`99586`

Description
===========

Registration of upgrade wizards via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`,
usually placed in an extensions :file:`ext_localconf.php` has been deprecated
in favour of the :ref:`new service tag <feature-99586-1673989775>`.

Additionally, the :php:`UpgradeWizardInterface`, which all upgrade wizards must
implement, does no longer require the :php:`getIdentifier()` method. TYPO3 does
not use this method anymore since an upgrade wizards identifier is now
defined using the new service tag.


Impact
======

Upgrade wizards, registered via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`
will no longer be recognized with TYPO3 v13.

Definition of the :php:`getIdentifier()` method does no longer have any effect.


Affected installations
======================

All installations registering custom upgrade wizards using
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`.

All installations implementing the :php:`getIdentifier()` method in their
upgrade wizards.


Migration
=========

Use the new service tag to register custom upgrade wizards and remove the
registration via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`.

Before
~~~~~~

..  code-block:: php

    // ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['myUpgradeWizard'] = \Vendor\Extension\Updates\MyUpgradeWizard::class;

After
~~~~~

..  code-block:: php

    // Classes/Updates/MyUpgradeWizard.php

    use TYPO3\CMS\Install\Attribute\UpgradeWizard;
    use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

    #[UpgradeWizard('myUpgradeWizard')]
    class MyUpgradeWizard implements UpgradeWizardInterface
    {

    }

Drop any :php:`getIdentifier()` method in custom upgrade wizards.

.. index:: Backend, PHP-API, FullyScanned, ext:install
