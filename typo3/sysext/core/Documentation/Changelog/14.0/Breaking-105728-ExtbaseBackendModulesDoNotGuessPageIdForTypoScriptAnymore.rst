..  include:: /Includes.rst.txt

..  _breaking-105728-1732882067:

==============================================================================================
Breaking: #105728 - Extbase backend modules not in page context rely on global TypoScript only
==============================================================================================

See :issue:`105728`

Description
===========

Configuration of Extbase-based backend modules can be done using frontend
TypoScript.

The standard prefix in TypoScript to do this is
:typoscript:`module.tx_myextension`. Extbase backend module controllers can
typically retrieve their configuration using a call like:
:php:`$configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'myextension');`

TypoScript itself is always bound to a page: The frontend must have
either some rootline page with a `sys_template` record or a page that has a site

set, otherwise frontend rendering will terminate with an error message.

Extbase-based backend modules are sometimes bound to pages as well: They can
have a rendered page tree configured in their module configuration and then
receive the selected page UID within the request as GET parameter :php:`id`.

Other Extbase-based backend modules, however, are not inside a page scope and do
not render the page tree. Examples of such modules within the TYPO3 Core are the
backend modules delivered by the `form` and `beuser` extensions.

Such Extbase-based backend modules without a page tree had a hard time
calculating their relevant frontend TypoScript-based configuration: Since
TypoScript is bound to pages, they looked for "the first" valid page in the page
tree, and the first valid `sys_template` record to calculate their TypoScript
configuration. This dependency on guesswork made final configuration of Extbase
backend module configuration not in page context brittle, opaque, and clumsy.

TYPO3 v14 puts an end to this: Extbase backend modules without page context
compile their TypoScript configuration from *global* TypoScript only and no longer
calculating TypoScript by guessing "the first valid" page.

The key call to register such "global" TypoScript is the method
:php:`ExtensionManagementUtility::addTypoScriptSetup()` in
:file:`ext_localconf.php` files.

Impact
======

Configuration of Extbase-based backend modules may change if their configuration
is defined by the first valid page in the page tree. Configuration of such
backend modules can no longer be changed by including TypoScript on the "first
valid" page.

Affected installations
======================

Instances with Extbase-based backend modules without a page tree may be affected.

Migration
=========

Configuration of Extbase-based backend modules without a page tree must be
supplied programmatically and made "global" by extending
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup']` using
:php:`ExtensionManagementUtility::addTypoScriptSetup()` within extensions’
:file:`ext_localconf.php` files. The backend module of the `form` extension is a
good example. Additional locations of extensions that deliver form YAML
definitions are defined like this:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_form {
            settings {
                yamlConfigurations {
                    1732884807 = EXT:my_extension/Configuration/Yaml/FormSetup.yaml
                }
            }
        }
    ');

Note it is also possible to use the method
:php:`ExtensionManagementUtility::addTypoScriptConstants()` to declare "global"
TypoScript constants and to use them in the TypoScript shown above.

..  index:: Backend, PHP-API, TypoScript, NotScanned, ext:extbase
