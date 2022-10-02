.. include:: /Includes.rst.txt

.. _deprecation-97435:

=======================================================================================
Deprecation: #97435 - Usage of SiteLanguageAwareTrait to denote site language awareness
=======================================================================================

See :issue:`97435`

Description
===========

The :php:`TYPO3\CMS\Core\Site\SiteLanguageAwareTrait` should not be used as
means to denote a class as aware of the site language anymore. Instead, the
:php:`TYPO3\CMS\Core\Site\SiteLanguageAwareInterface` should be implemented for
this purpose. The trait is an internal implementation and should not be used
in user land code.

Impact
======

If you are currently using the :php:`SiteLanguageAwareTrait` to denote a class
as aware of the site language, you should implement the
:php:`SiteLanguageAwareInterface` instead.

Affected Installations
======================

All installations where the :php:`SiteLanguageAwareTrait` is used to denote
a class as aware of the site language.

Migration
=========

Change classes that use the :php:`SiteLanguageAwareTrait` but not the
corresponding interface to implement the interface. Replace the usage of the
trait with an own trait, or implement the interface methods directly in the
class.

Example before the migration:

..  code-block:: php

    use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;

    class MyClass
    {
        use SiteLanguageAwareTrait;
    }

Example after the migration:

..  code-block:: php

    use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;
    use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

    class MyClass implements SiteLanguageAwareInterface
    {

        protected SiteLanguage $siteLanguage;

        public function setSiteLanguage(SiteLanguage $siteLanguage)
        {
            $this->siteLanguage = $siteLanguage;
        }

        public function getSiteLanguage(): SiteLanguage
        {
            return $this->siteLanguage;
        }
    }

.. index:: PHP-API, ext:core, NotScanned
