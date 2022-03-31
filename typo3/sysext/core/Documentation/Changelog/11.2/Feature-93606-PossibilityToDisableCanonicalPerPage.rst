.. include:: /Includes.rst.txt

===========================================================
Feature: #93606 - Possibility to disable canonical per page
===========================================================

See :issue:`93606`

Description
===========

Although it should not be needed to disable the generation of :html:`canonical`, people might
have a reason to disable it. If for some reason Core does not
render the proper canonical tag and also the :php:`ModifyUrlForCanonicalTagEvent` PSR-14 event
is not enough, you are now able to disable the generation of the canonical tag
via TypoScript. This can be done per page or part of your tree depending on where
you set the configuration.

To disable the canonical generation, you can add the following line to your
TypoScript setup.

.. code-block:: typoscript

   config.disableCanonical = 1


Impact
======

If the option is set to :typoscript:`1`, the canonical generation will be skipped.

.. index:: Frontend, TypoScript, ext:seo
