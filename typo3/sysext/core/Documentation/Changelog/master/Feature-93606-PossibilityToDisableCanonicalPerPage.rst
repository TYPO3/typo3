.. include:: ../../Includes.txt

===========================================================
Feature: #93606 - Possibility to disable canonical per page
===========================================================

See :issue:`93606`

Description
===========

Although it should not be needed to disable the canonical generation, people might
have a reason to disable the canonical generation. If for some reason core does not
render the proper canonical tag and also the PSR-14 event called `ModifyUrlForCanonicalTagEvent`
is not enough, you are now able to disable the generation of the canonical tag
via TypoScript. This can be done per page or part of your tree depending on where
you set the configuration.

To disable the canonical generation, you can add the following line to your
TypoScript setup.

.. code-block:: typoscript

   config.disableCanonical = 1


Impact
======

Only when you set this option, the canonical generation will be skipped.

.. index:: Frontend, TypoScript, ext:seo
