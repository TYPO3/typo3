.. include:: ../../Includes.txt

=========================================
Deprecation: #87277 - Fluid Class Aliases
=========================================

See :issue:`87277`

Description
===========

Since introduction of the standalone package typo3fluid/fluid, the TYPO3 core provides class aliases
for the moved classes to ease usage in extensions.
These class aliases will be dropped in TYPO3 v10.

The following class aliases have been marked as deprecated and should no longer be used:

* :php:`TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler`
* :php:`TYPO3\CMS\Fluid\Core\Exception`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode`
* :php:`TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode`
* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface`
* :php:`TYPO3\CMS\Fluid\Core\Variables\CmsVariableProvider`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Exception`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Facets\PostParseInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer`
* :php:`TYPO3\CMS\Fluid\View\Exception`
* :php:`TYPO3\CMS\Fluid\View\Exception\InvalidSectionException`
* :php:`TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException`

Impact
======

Extensions and third party packages using the :php:`TYPO3\CMS\Fluid` namespace might be affected
by the stand alone Fluid package change.
If aliased class names are used, there will be fatal PHP Errors after update to TYPO3 v10.

The extension scanner will find usage of these classes.

Affected Installations
======================

All installations that use the :php:`TYPO3\CMS\Fluid` namespace for class aliases.

Migration
=========

Migrate to the original classes in namespace :php:`TYPO3Fluid\Fluid`.

.. index:: PHP-API, FullyScanned, ext:fluid
