..  include:: /Includes.rst.txt

..  _feature-107436-1736639846:

============================================================
Feature: #107436 - Symfony Translation Component Integration
============================================================

See :issue:`107436`

Description
===========

TYPO3 now utilizes the Symfony Translation component for reading localization
label files - such as XLIFF and PO - instead of its custom localization parsers.

The migration brings several improvements:

* Standardized file parsing using Symfony's translation loaders
* Enhanced API for accessing translation catalogues
* Support for custom translation loaders following Symfony standards

The new system maintains backward compatibility while providing a modern
foundation for future improvements with translatable labels.

In addition, all label-related configuration options have been
streamlined under the :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']` namespace.

The following new configuration options have been introduced:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['loader']` - Configure custom translation loaders
* :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations']` - Moved from SYS.lang
* :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['format']` - Moved from SYS.lang
* :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['availableLocales']` - Moved from EXTCONF.lang
* :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` - Moved from SYS.locallangXMLOverride

Custom Translation Loaders
===========================

Extension developers can now implement custom translation loaders by
implementing Symfony's translation loader interfaces:

.. code-block:: php

    use Symfony\Component\Translation\Loader\LoaderInterface;
    use Symfony\Component\Translation\MessageCatalogue;

    class CustomLoader implements LoaderInterface
    {
        public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
        {
            // Custom loading logic
            $catalogue = new MessageCatalogue($locale);
            // ... populate catalogue
            return $catalogue;
        }
    }

Register custom loaders via configuration:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['LANG']['loader']['fileEnding'] = \MyExtension\Translation\CustomLoader::class;

Impact
======

All previous configuration options have been moved to the new
:php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']` namespace, these are automatically
migrated to the new location when accessing the install tool.

Please note: This functionality only affects internal handling of translation
files ("locallang" files). The public API of the localization system remains
unchanged.

..  index:: PHP-API, Backend, ext:core
