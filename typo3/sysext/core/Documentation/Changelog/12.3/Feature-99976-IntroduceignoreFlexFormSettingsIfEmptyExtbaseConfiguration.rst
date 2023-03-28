.. include:: /Includes.rst.txt

.. _feature-99976-1676660028:

===============================================================================
Feature: #99976 - Introduce ignoreFlexFormSettingsIfEmpty Extbase configuration
===============================================================================

See :issue:`99976`

Description
===========

It is now possible to exclude empty FlexForm settings from being merged into
Extbase extension settings. Extension authors and integrators can use the new
Extbase TypoScript configuration :typoscript:`ignoreFlexFormSettingsIfEmpty`
to define FlexForm settings, which will be ignored in the merge process of the
extension settings, if their value is considered empty (either an empty string or a
string containing `0`).

In the following example, :xml:`settings.showForgotPassword` and
:xml:`settings.showPermaLogin` from FlexForm will not be merged into extension
settings, if the individual value is empty:

..  code-block:: typoscript

    plugin.tx_felogin_login.ignoreFlexFormSettingsIfEmpty = showForgotPassword,showPermaLogin

If an extension already defined :typoscript:`ignoreFlexFormSettingsIfEmpty`,
integrators are advised to use :typoscript:`addToList` or
:typoscript:`removeFromList` to modify existing settings as shown in the
following example:

..  code-block:: typoscript

    plugin.tx_felogin_login.ignoreFlexFormSettingsIfEmpty := removeFromList(showForgotPassword)
    plugin.tx_felogin_login.ignoreFlexFormSettingsIfEmpty := addToList(domains)

It is possible to define the :typoscript:`ignoreFlexFormSettingsIfEmpty`
configuration globally for an extension using the
:typoscript:`plugin.tx_extension` TypoScript configuration or for an individual
plugin using the :typoscript:`plugin.tx_extension_plugin` TypoScript
configuration.

Extension authors can use the new PSR-14 event
:php:`\TYPO3\CMS\Extbase\Event\Configuration\BeforeFlexFormConfigurationOverrideEvent`
to implement a FlexForm override process in a custom extension based on the original
FlexForm configuration and the framework configuration.

Additionally, the new Extbase TypoScript configuration is used in EXT:felogin to
ensure that empty FlexForm settings are not merged into extension settings.

Event example
-------------

Register an event listener in your :file:`Services.yaml` file:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\FlexForm\EventListener\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/custom-absolute-path'


Implement the event listener:

..  code-block:: php
    :caption: EXT:my_extension/Classes/FlexForm/EventListener/MyEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\FlexForm\EventListener;

    use TYPO3\CMS\Extbase\Event\Configuration\BeforeFlexFormConfigurationOverrideEvent;

    final class MyEventListener
    {
        public function __invoke(BeforeFlexFormConfigurationOverrideEvent $event): void
        {
            // Configuration from TypoScript
            $frameworkConfiguration = $event->getFrameworkConfiguration();

            // Configuration from FlexForm
            $originalFlexFormConfiguration = $event->getOriginalFlexFormConfiguration();

            // Currently merged configuration
            $flexFormConfiguration = $event->getFlexFormConfiguration();

            // Implement custom logic
            $flexFormConfiguration['settings']['foo'] = 'set from event listener';
            $event->setFlexFormConfiguration($flexFormConfiguration);
        }
    }


Impact
======

Empty FlexForm extension settings can now conditionally be excluded from the
FlexForm configuration merge process.

Also, it is now possible again to use global TypoScript extension settings
in EXT:felogin, which previously might have been overridden by empty FlexForm
settings.

In addition, with the new :php:`BeforeFlexFormConfigurationOverrideEvent` it is
now possible to further manipulate the merged configuration after standard
override logic is applied.

.. index:: ext:extbase
