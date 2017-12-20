.. include:: ../../Includes.txt

============================================================================================
Important: #82763 - Fluid config for ExpressionNodeType and TemplatePreProcessor made global
============================================================================================

See :issue:`82763`

Description
===========

Before, Fluid's arrays of class names for ``ExpressionNodeType`` and ``TemplatePreProcessor`` were hardcoded into the
``RenderingContext`` constructor and were not possible to modify except when having access to the ``RenderingContext``.

Now, these two arrays of class names are possible to configure in ``TYPO3_CONF_VARS`` which allows extensions or site
administrators to add and remove such Fluid components on a global level.

Example:

.. code-block:: php

    // Add one new ExpressionNodeType and one new TemplatePreProcessor to be used in every RenderingContext
    // For example from an ext_localconf.php file in an extension.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['expressionNodeTypes'][] = \MyVendor\MyExtension\MyFluidExpressionNodeType::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['preProcessors'][] = \MyVendor\MyExtension\MyFluidTemplatePreProcessor::class;

These classes will then be *added to the list of existing implementations* and will be automatically used as defaults
when new ``RenderingContext`` instances are created.

See for reference how to create such implementations:

* ``\TYPO3Fluid\Fluid\Core\Parser\TemplateProcessorInterface``
* ``\TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface``

Both interfaces contain the documentation for how they must be implemented, when/why functions get called and what the
expected return types are. The interfaces must of course be implemented by classes you add to ``TYPO3_CONF_VARS`` in
these configuration sections. Due to their global nature you should be very careful to implement the classes and in
particular observe the return types.

.. index:: Fluid, LocalConfiguration