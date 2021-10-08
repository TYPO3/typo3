.. include:: ../../Includes.txt

==========================================================
Breaking: #92238 - Service injection in Extbase validators
==========================================================

See :issue:`92238`

Description
===========

With the deprecation and removal of objectManager usage in TYPO3, Extbase does not
use the objectManager to create validator instances any more. Since validators
have an internal state (prototypes) they must not have services injected.


Impact
======

Validators that use dependency injection will experience non injected services for affected properties.


Affected Installations
======================

All installations that use dependency injection in Extbase validators.


Migration
=========


Instead of injecting services to the validator, use :php:`GeneralUtility::makeInstance`
to create an instance of required services.

Given the following example for a service injection in a validator:

.. code-block:: php

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

Since the configurationManager is required globally in the class, :php:`GeneralUtility::makeInstance`
is used in the constructor of the validator to create an instance of the service.

.. code-block:: php

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    public function __construct(array $options = [])
    {
        $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        parent::__construct($options);
    }

In order to create instances of services that require dependency injection and which
are not already instantiated in the service container, it is required to declare those
services as :php:`public: true` in the :php:`Configuration/Services.yaml` of the given extension.

.. code-block:: yaml

    Vendor\MyExtension\Services\MyService:
      public: true


.. index:: PHP-API, NotScanned, ext:extbase
