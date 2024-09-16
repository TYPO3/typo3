.. include:: /Includes.rst.txt

.. _feature-104896-1726046146:

================================================
Feature: #104896 - Raise Fluid Standalone to 4.0
================================================

See :issue:`104896`

Description
===========

TYPO3 13 now uses Fluid 4 as the new base version. Old TYPO3 versions
will keep using Fluid 2, which will still receive bugfixes if necessary.
For detailed information about this release, please refer to the
`dedicated release notes on GitHub <https://github.com/TYPO3/Fluid/releases/tag/4.0.0>`_.

With the update to Fluid 4, tag-based ViewHelpers now have proper
support for boolean attributes. Before this change, it was very
cumbersome to generate these with Fluid, now it is implemented similar
to popular JavaScript frameworks by using the newly introduced
boolean literals:

..  code-block:: html

    <my:viewhelper async="{true}" />
    Result: <tag async="async" />

    <my:viewhelper async="{false}" />
    Result: <tag />


Of course, any variable containing a boolean can be supplied as well:

..  code-block:: html

    <my:viewhelper async="{isAsync}" />


This can also be used in combination with variable casting:

..  code-block:: html

    <my:viewhelper async="{myString as boolean}" />


For compatibility reasons empty strings still lead to the attribute
being omitted from the tag.


Impact
======

For existing installations, negative consequences of this update should be
minimal as deprecated features will still work. Users are however advised
to look into the already announced deprecations and to update their code
accordingly. This update helps with this by now writing log messages to the
deprecation log (if activated) if any deprecated feature is used in the
TYPO3 instance.

.. index:: Fluid, ext:fluid
