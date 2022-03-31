.. include:: /Includes.rst.txt

================================================
Deprecation: #79972 - Deprecated Fluid Overrides
================================================

See :issue:`79972`

Description
===========

following methods have been marked as deprecated:

* :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::injectObjectManager()`
* :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::getObjectManager()`
* :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::setLegacyMode()`

following methods have been removed:

* :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::getExpressionNodeTypes()`
* :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::getViewHelperVariableContainer()`

following classes have been removed:

* :php:`\TYPO3\CMS\Fluid\Core\Parser\PreProcessor\XmlnsNamespaceTemplatePreProcessor`
* :php:`\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\Expression\LegacyNamespaceExpressionNode`



Impact
======

Calling any of the methods marked as deprecated above will trigger a deprecation log entry.
Accessing any of the removed classes or methods will cause a PHP Fatal Error.


Affected Installations
======================

Any TYPO3 instances which uses the above described methods or classes.


Migration
=========

* Remove usage of classes / methods.


.. index:: Fluid
