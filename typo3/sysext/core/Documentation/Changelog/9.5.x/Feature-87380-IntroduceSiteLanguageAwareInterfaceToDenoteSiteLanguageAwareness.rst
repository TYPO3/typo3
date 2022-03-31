.. include:: /Includes.rst.txt

========================================================================================
Feature: #87380 - Introduce SiteLanguageAwareInterface to denote site language awareness
========================================================================================

See :issue:`87380`

Description
===========

A `SiteLanguageAwareInterface` with the methods `setSiteLanguage(Entity\SiteLanguage $siteLanguage)`
and `getSiteLanguage()` has been introduced. The interface can be used to denote a class as aware of
the site language.


Impact
======

Routing aspects respecting the site language are now using the `SiteLanguageAwareInterface` in addition
to the `SiteLanguageAwareTrait`. The `AspectFactory` check has been adjusted to check for the interface
_or_ the trait. If you are currently using the trait, you should implement the interface as well.

.. index:: PHP-API, ext:core
