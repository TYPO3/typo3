.. include:: /Includes.rst.txt

.. _feature-104789-1725194699:

========================================================================
Feature: #104789 - Support for contentArgumentName in AbstractViewHelper
========================================================================

See :issue:`104789`

Description
===========

ContentArgumentName has been a feature on Fluid ViewHelpers for some time now.
It allows ViewHelpers to link a ViewHelper argument to the children of the
ViewHelper name. As a result, an input value can either be specified as an
argument or as the ViewHelper's children, leading to the same result.

Example:

..  code-block:: html
    <!-- Tag syntax -->
    <f:format.json value="{data}" />
    <f:format.json>{data}</f:format.json>

    <!-- Inline syntax -->
    {f:format.json(value: data)}
    {data -> f:format.json()}

Previously, this feature was only available to ViewHelpers using the trait
:php-short:`\TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic`.
It is now available to all ViewHelpers since it has been integrated into  the
:php-short:`\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper`. The
trait is no longer necessary.

To use the new feature, all the ViewHelper implementation needs to do is to define
a method `getContentArgumentName()` which returns the name of the argument to be
linked to the ViewHelper's children:

Example:

..  code-block:: php
    public function getContentArgumentName(): string
    {
        return 'value';
    }


Impact
======

ViewHelpers using the trait
:php-short:`\TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic`
should be migrated to the new feature.

:php-short:`\TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic`
will continue to work in Fluid v4, but will log a deprecation level error message.
It will be removed in Fluid v5.

.. index:: Fluid, ext:fluid
