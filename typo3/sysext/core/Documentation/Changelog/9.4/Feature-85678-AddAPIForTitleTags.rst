.. include:: ../../Includes.txt

===================================
Feature: #85678 - Add PageTitle API
===================================

See :issue:`85678`

Description
===========

In order to keep setting the titles in control, a new API to set the page title has been introduced.

The API uses :php:`PageTitleProviders` to define the page title based on page record and the content on the page.

Based on the priority of the providers, the :php:`PageTitleProviderManager` will check the providers if a title
is given by the provider. It will start with the highest priority PageTitleProviders and will end with the lowest
in priority.

By default, the core ships three providers. The provider with the (by default) highest priority will be the
:php:`AltPageTitleProvider`. This provider handles the (since TYPO3 v9 deprecated) property
:php:`$GLOBALS['TSFE']->altPageTitle`. If an extension has set a value to this property, this provider will return
that value.

If you have installed the system extension SEO, the second provider will be the :php:`SeoTitlePageTitleProvider`.
When an editor has set a value for the SEO title in the page properties of the page, this provider will provide
that title to the :php:`PageTitleProviderManager`. If you have not installed the SEO system extension, this fields
and provider are not available.

The fallback provider with the lowest priority is the :php:`RecordPageTitleProvider`. When no other title is set
by a provider, this provider will return the title of the page.

Besides the providers shipped by core, you can add own providers. An integrator can define the priority of the
providers for his project.

Create your own PageTitleProvider
=================================

Extension developers may want to have an own provider for page titles. For example if you have an extension with
records and a detail view, the title of the page record will not be the correct title. To make sure to display
the correct page title, you have to create your own :php:`PageTitleProvider`. It is quite easy to create one.

First of all create a PHP class in your extension that implements the :php:`PageTitleProviderInterface`. This will
force you to have at least the :php:`getTitle()` method in your class. Within this method you can create your
own logic to define the correct title.

Define priority of PageTitleProviders
=====================================

The priority of the providers are set by the TypoScript property :typoscript:`config.pageTitleProviders`. This
way an integrator is able to set the priorities for his project and can even have conditions in place.

By default, the core has the following setup:

.. code-block:: typoscript

   config.pageTitleProviders {
        altPageTitle {
            provider = TYPO3\CMS\Core\PageTitle\AltPageTitleProvider
            before = record
        }
        record {
            provider = TYPO3\CMS\Core\PageTitle\RecordPageTitleProvider
        }
   }

The ordering of the providers is based on the `before` and `after` parameters. If you want a provider to be handled
before a specific other provider, just set that provider in the `before`, do the same with `after`.

If you have installed the system extension SEO, you will also get a third provider. The configuration will be:

.. code-block:: typoscript

   config.pageTitleProviders {
      altPageTitle {
         provider = TYPO3\CMS\Core\PageTitle\AltPageTitleProvider
         before = record
      }
      record {
         provider = TYPO3\CMS\Core\PageTitle\RecordPageTitleProvider
      }
      seo {
         provider = TYPO3\CMS\Seo\PageTitle\SeoTitlePageTitleProvider
         before = record
         after = altPageTitle
      }
   }

First the :php:`AltPageTitleProvider` will be checked, then the :php:`SeoTitlePageTitleProvider` (because it will be
handled before record and after altPageTitle) and if both providers didn't provide a title, the
:php:`RecordPageTitleProvider` will be checked.

You can override these settings within your own installation. You can add as many providers as you want. Be aware
that if a provider returns a non-empty value, all provider with a lower priority won't be checked.

.. index:: Frontend, ext:core, ext:seo
