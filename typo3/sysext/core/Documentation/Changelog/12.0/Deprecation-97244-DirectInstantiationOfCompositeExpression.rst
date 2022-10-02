.. include:: /Includes.rst.txt

.. _deprecation-97244-1:

=================================================================
Deprecation: #97244 - Direct instantiation of CompositeExpression
=================================================================

See :issue:`97244`

Description
===========

`doctrine/dbal` `deprecated`_ direct instantiation of :php:`CompositeExpression`
in favour of moving forward to an immutable class implementation. Therefore, this
has also been deprecated in the Core facade class (:php:`\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression`),
to avoid shifting too far away.

.. _`deprecated`: https://github.com/doctrine/dbal/commit/7bcd6ebcc2d30ba96cf00d3dca2345d6ae779cf9

Impact
======

Instantiating directly with :php:`new CompositeExpression(...)` will trigger a PHP :php:`E_USER_DEPRECATED` error.

The extension scanner cannot detect direct instantiation of this class.

Affected Installations
======================

In general, instances with extensions that directly instantiate a composite
expression with :php:`new CompositeExpression(...)`.

The extension scanner will not find and report direct instantiating.

Migration
=========

Instead of directly instantiating a composite expression with the type as
the first argument and an array of expressions as the second argument, the new
static methods :php:`and(...)` and :php:`or(...)` have to be used.

.. note::

    The static replacement methods :php:`CompositeExpression::and()`
    and :php:`CompositeExpression::or()` have already been added in
    a forward-compatible way in TYPO3 v11. Thus giving extension developers
    the ability to adopt new methods and still being able to support
    multiple Core versions without workarounds.

For example, following code:

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    $compositeExpressionAND = new CompositeExpression(
        CompositeExpression::TYPE_AND,
        [
            // expressions ...
        ]
    );

    $compositeExpressionOR = new CompositeExpression(
        CompositeExpression::TYPE_OR,
        [
            // expressions ...
        ]
    );

should be replaced with:

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    // Note the spread operator
    $compositeExpressionAND = CompositeExpression::and(
        ...[
            // expressions ...
        ]
    );

    $compositeExpressionOR = CompositeExpression::or(
        ...[
            // expressions ...
        ]
    );

.. index:: Database, NotScanned, ext:core
