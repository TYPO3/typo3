.. include:: /Includes.rst.txt

.. _deprecation-104789-1725195584:

===========================================================
Deprecation: #104789 - renderStatic() for Fluid ViewHelpers
===========================================================

See :issue:`104789`

Description
===========

The usage of :php:`renderStatic()` for Fluid ViewHelpers has been deprecated.
Also, Fluid standalone traits
:php:`TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic`
and :php:`TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic`
have been marked as deprecated.


Impact
======

Using one of the mentioned traits or :php:`renderStatic()` in ViewHelpers
logs a deprecation level error message in Fluid standalone v4. :php:`renderStatic()`
will no longer be called in Fluid standalone v5. :php:`renderStatic()` and both
traits continue to work without deprecation level error message in
Fluid standalone v2.


Affected installations
======================

Instances with custom ViewHelpers using any variant of :php:`renderStatic()` are affected.


Migration
=========

ViewHelpers should always use :php:`render()` as their primary rendering method.

ViewHelpers using just :php:`renderStatic()` without any trait or with the trait
:php:`CompileWithRenderStatic` can be migrated by converting the static rendering
method to a non-static method:

Before:

..  code-block:: php
    class MyViewHelper extends AbstractViewHelper
    {
        use CompileWithRenderStatic;

        public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
        {
            return $renderChildrenClosure();
        }
    }

After:

..  code-block:: php
    class MyViewHelper extends AbstractViewHelper
    {
        public function render(): string
        {
            return $this->renderChildren();
        }
    }

ViewHelpers using :php:`CompileWithContentArgumentAndRenderStatic` can use the new
contentArgumentName feature added with Fluid v2.15:

Before:

..  code-block:: php
    class MyViewHelper extends AbstractViewHelper
    {
        use CompileWithContentArgumentAndRenderStatic;

        public function initializeArguments(): void
        {
            $this->registerArgument('value', 'string', 'a value');
        }

        public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
        {
            return $renderChildrenClosure();
        }

        public function resolveContentArgumentName(): string
        {
            return 'value';
        }
    }

After:

..  code-block:: php
    class MyViewHelper extends AbstractViewHelper
    {
        public function initializeArguments(): void
        {
            $this->registerArgument('value', 'string', 'a value');
        }

        public function render(): string
        {
            return $this->renderChildren();
        }

        public function getContentArgumentName(): string
        {
            return 'value';
        }
    }

Here is a basic recipe to perform this migration, preferably utilizing
statical code analysis/replacement tools on your :file:`*ViewHelper.php`
files:

*   Find definitions of :php:`renderStatic`

*   Rename method to :php:`render()`, remove the arguments, remove :php:`static` declaration

*   Within that method:

    *   Replace :php:`$arguments` with :php:`$this->arguments`
    *   Replace :php:`$renderingContext` with :php:`$this->renderingContext`
    *   Replace :php:`$renderChildrenClosure()` with :php:`$this->renderChildren()`
    *   Replace remaining :php:`$renderChildrenClosure` usages with proper closure handling, like :php:`$this->renderChildren(...)`.

*   Replace :php:`resolveContentArgumentName(` with :php:`getContentArgumentName(`

*   Remove the mentioned definitions:

    *   :php:`use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
    *   :php:`use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;`
    *   :php:`use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;`
    *   :php:`use CompileWithRenderStatic;` (class trait)
    *   :php:`use CompileWithContentArgumentAndRenderStatic;` (class trait)

*   (Optionally remove custom phpdoc annotations to the `renderStatic` parameters)

*   If you previously called ViewHelper's :php:`renderStatic` methods in other places,
    you may utilize something like:

    ..  code-block:: php
        $this->renderingContext->getViewHelperInvoker()->invoke(
            MyViewHelper::class,
            $arguments,
            $this->renderingContext,
            $this->renderChildren(...),
        );

.. index:: Fluid, PartiallyScanned, ext:fluid
