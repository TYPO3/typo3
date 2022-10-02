.. include:: /Includes.rst.txt

.. _deprecation-97244-2:

=============================================================================
Deprecation: #97244 - CompositeExpression methods 'add()' and 'addMultiple()'
=============================================================================

See :issue:`97244`

Description
===========

`doctrine/dbal` `deprecated`_ multiple :php:`CompositeExpression` methods
:php:`CompositeExpression->add()` and :php:`CompositeExpression->addMultiple()`.
Therefore, those methods have also been deprecated in the Core facade class
(:php:`\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression`),
to avoid shifting too far away.

.. _`deprecated`: https://github.com/doctrine/dbal/commit/7bcd6ebcc2d30ba96cf00d3dca2345d6ae779cf9

Impact
======

Using :php:`CompositeExpression->add()` and :php:`CompositeExpression->addMultiple()`
will trigger a PHP :php:`E_USER_DEPRECATED` error when called.

Affected Installations
======================

In general, instances with extensions that use the deprecated methods
:php:`CompositeExpression->add()` and :php:`CompositeExpression->addMultiple()`.
The extension scanner finds usages of :php:`CompositeExpression->addMultiple()`
with a weak match. The other method is not scanned, as its name is too common.

Migration
=========

The deprecated methods :php:`CompositeExpression->add()` and :php:`CompositeExpression->addMultiple()`
can be replaced by using the new method :php:`CompositeExpression->with()`.

..  note::

    The replacement method :php:`CompositeExpression->with()` has already been
    added in a forward-compatible way in TYPO3 v11. Thus giving extension developers
    the ability to adopt new methods and still being able to support multiple Core
    versions without workarounds.

For example, the following code:

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    $compositeExpression = CompositeExpression::or();

    $compositeExpression->add(
        $queryBuilder->expr()->eq(
            'field',
            $queryBuilder->createNamedParameter($singleValue)
        )
    );
    $compositeExpression->addMultiple(
        [
            $queryBuilder->expr()->eq(
                'field',
                $queryBuilder->createNamedParameter($value1)
            ),
            $queryBuilder->expr()->eq(
                'field',
                $queryBuilder->createNamedParameter($value2)
            ),
            //...
        ]
    );

should be replaced with:

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    $compositeExpression = CompositeExpression::or();

    // note, you have to assign the return of with() to the
    // variable, otherwise added elements are lost.
    $compositeExpression = $compositeExpression->with(
        $queryBuilder->expr()->eq(
            'field',
            $queryBuilder->createNamedParameter($singleValue)
        )
    );

    // Note the spread operator for the array
    $compositeExpression = $compositeExpression->with(
        ...[
            $queryBuilder->expr()->eq(
                'field',
                $queryBuilder->createNamedParameter($value1)
            ),
            $queryBuilder->expr()->eq(
                'field',
                $queryBuilder->createNamedParameter($value2)
            ),
            //...
        ]
    );

The multi expression example can now also be replaced like this:

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    $compositeExpression = CompositeExpression::or();

    // note, you have to assign the return of with() to the
    // variable, otherwise added elements are lost.
    $compositeExpression = $compositeExpression->with(
        $queryBuilder->expr()->eq(
          'field',
          $queryBuilder->createNamedParameter($value1)
        ),
        $queryBuilder->expr()->eq(
          'field',
          $queryBuilder->createNamedParameter($value2)
        ),
        //...
    );

Extension developers may have used to loop over some data to build
multiple expressions which should be connected with :php:`and` or :php:`or`,
either adding each element with the deprecated :php:`add(...)` method or
collecting it in an array and using deprecated :php:`addMultiple(...)` method.
Both use cases can be replaced with :php:`with(...)`.

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    $compositeExpression = CompositeExpression::or();

    foreach($array as $element) {
        // note, you have to assign the return of with() to the
        // variable, otherwise added elements are lost.
        $compositeExpression = $compositeExpression->with(
            $queryBuilder->expr()->eq(
                'field',
                $queryBuilder->createNamedParameter($element)
            )
        );
    }

or

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    $compositeExpression = CompositeExpression::or();

    $expressions = [];
    foreach($array as $element) {
        $expressions[] = $queryBuilder->expr()->eq(
            'field',
            $queryBuilder->createNamedParameter($element)
        );
    }

    // note, you have to assign the return of with() to the
    // variable, otherwise added elements are lost.
    $compositeExpression = $compositeExpression->with(...$expressions);

Instead of using :php:`with()` when collecting expressions in an array,
it can be used when instantiating the composite expression after the
expression collecting:

..  code-block:: php

    use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

    $expressions = [];
    foreach($array as $element) {
        $expressions[] = $queryBuilder->expr()->eq(
            'field',
            $queryBuilder->createNamedParameter($element)
        );
    }

    $compositeExpression = CompositeExpression::or(...$expressions);

.. index:: Database, PartiallyScanned, ext:core
