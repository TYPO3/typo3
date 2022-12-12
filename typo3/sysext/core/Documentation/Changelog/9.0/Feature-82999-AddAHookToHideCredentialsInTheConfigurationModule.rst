.. include:: /Includes.rst.txt

.. _feature-82999:

============================================================================
Feature: #82999 - Add a hook to hide credentials in the Configuration module
============================================================================

See :issue:`82999`

Description
===========

To blind additional configuration options in the Configuration module a hook has been added:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Lowlevel\Controller\ConfigurationController::class]['modifyBlindedConfigurationOptions']`

This can be implemented e.g. by adding a class :php:`\MyVendor\MyExtension\Hook\BlindedConfigurationOptionsHook`:

.. code-block:: php

    class BlindedConfigurationOptionsHook
    {
        /**
         * Blind something in ConfigurationOptions
         *
         * @param array $blindedConfigurationOptions
         * @return array
         */
        public function modifyBlindedConfigurationOptions(array $blindedConfigurationOptions): array
        {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['example']['password'])) {
                $blindedConfigurationOptions['TYPO3_CONF_VARS']['EXTENSIONS']['example']['password'] = '******';
            }

            return $blindedConfigurationOptions;
        }
    }

and adding the following line to ext_localconf.php:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Lowlevel\Controller\ConfigurationController::class]['modifyBlindedConfigurationOptions'][] = \MyVendor\MyExtension\Hook\BlindedConfigurationOptionsHook::class;

Impact
======

Extension developers can use this hook to e.g. hide custom credentials in the Configuration module.

.. index:: PHP-API
