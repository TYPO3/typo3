.. include:: /Includes.rst.txt

==========================================================
Feature: #93188 - Possibility to disable hreflang per page
==========================================================

See :issue:`93188`

Description
===========

Although it should not be needed to disable the hreflang generation, people might
have a reason to disable it. If for some reason Core does not
render the proper hreflang tags and also the :php:`ModifyHrefLangTagsEvent` PSR-14 event
is not enough, you are now able to disable the generation of the hreflang tags
via TypoScript. This can be done per page or part of your tree depending on where
you set the configuration.

To disable the hreflang generation, you can add the following line to your
TypoScript setup.

.. code-block:: typoscript

   config.disableHrefLang = 1


Impact
======

If the option is set to :typoscript:`1`, hreflang generation will be skipped.

.. index:: Frontend, TypoScript, ext:seo
