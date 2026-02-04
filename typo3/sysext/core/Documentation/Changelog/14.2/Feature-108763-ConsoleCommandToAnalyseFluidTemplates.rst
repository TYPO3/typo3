..  include:: /Includes.rst.txt

..  _feature-108763-1769331943:

=============================================================
Feature: #108763 - Console command to analyse Fluid templates
=============================================================

See :issue:`108763`

Description
===========

The `fluid:analyse` console command is introduced, which analyses
Fluid templates in the current project for correct Fluid syntax and alerts
about deprecations that are emitted during template parsing.

Usage:

..  code-block:: bash

    vendor/bin/typo3 fluid:analyse

Example output:

..  code-block::

    [DEPRECATION] packages/myext/Resources/Private/Templates/Test.fluid.html: <my:obsolete> has been deprecated in X and will be removed in Y.
    [ERROR] packages/myext/Resources/Private/Templates/Test2.fluid.html: Variable identifiers cannot start with a "_": _temp

In its initial implementation, the command automatically finds all Fluid
templates within the current project based on the `*.fluid.*` file extension (See
:ref:`Feature: #108166 - Fluid file extension and template resolving <feature-108166-1763400992>`)
and analyses them. By default, TYPO3's system extensions are skipped, this can
be adjusted by specifying the `—-include-system-extensions` CLI option.

The following errors and deprecations are currently supported:

*   Fluid syntax errors (e. g. invalid nesting of ViewHelper tags)
*   Usage of invalid ViewHelpers or ViewHelper namespaces
*   Usage of variable names that start with `_`
    (see :ref:`Breaking: #108148 - Disallow Fluid variable names with underscore prefix <breaking-108148-1763288414>`)
*   Usage of deprecated ViewHelpers or ViewHelper arguments (if deprecation
    is triggered during parse time, see
    :ref:`Deprecating ViewHelpers <feature-108763-1769331943-deprecating-viewhelpers>` and
    :ref:`Deprecating ViewHelper arguments <feature-108763-1769331943-deprecating-viewhelper-arguments>`)

If exceptions are caught during the parsing process of at least one template,
the console command has a return status of 1 (error), otherwise it returns 0
(success). This means that deprecations are not interpreted as errors.

This should make it possible to use the command in CI workflows of most projects,
since deprecated functionality used by third-party templates won't make the
pipeline fail.

Verbose output allows to get feedback of analyzed templates
and the number of errors/deprecations (or success).

..  _feature-108763-1769331943-deprecating-viewhelpers:

Deprecating ViewHelpers
-----------------------

The `fluid:analyse` console command can catch deprecations of whole ViewHelpers
if the deprecation is emitted during parse time of a template. This is possible
by implementing the :php-short:`\TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface`:

..  code-block:: php
    :caption: ObsoleteViewHelper.php

    use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
    use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
    use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
    use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface;

    /**
     * @deprecated since X, will be removed in Y.
     */
    final class ObsoleteViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
    {
        // ...

        public static function nodeInitializedEvent(ViewHelperNode $node, array $arguments, ParsingState $parsingState): void
        {
            trigger_error(
                '<my:obsolete> has been deprecated in X and will be removed in Y.',
                E_USER_DEPRECATED,
            );
        }
    }

..  _feature-108763-1769331943-deprecating-viewhelper-arguments:

Deprecating ViewHelper arguments
--------------------------------

The :php-short:`\TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface`
can also be used to deprecate a ViewHelper's argument. The deprecation will only
be triggered if the argument is actually used in a template.

..  code-block:: php
    :caption: SomeViewHelper.php

    use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
    use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
    use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
    use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface;

    final class SomeViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
    {
        public function initializeArguments(): void
        {
            // @deprecated since X, will be removed in Y.
            $this->registerArgument('obsoleteArgument', 'string', 'Original description. Deprecated since X, will be removed in Y');
        }

        public static function nodeInitializedEvent(ViewHelperNode $node, array $arguments, ParsingState $parsingState): void
        {
            if (array_key_exists('obsoleteArgument', $arguments)) {
                trigger_error(
                    'ViewHelper argument "obsoleteArgument" in <my:some> is deprecated since X and will be removed in Y.',
                    E_USER_DEPRECATED,
                );
            }
        }
    }

Impact
======

The new `fluid:analyse` console command can be used to check basic validity of Fluid
templates in a project and can discover deprecated functionality used in template files.

..  index:: CLI, Fluid, ext:fluid
