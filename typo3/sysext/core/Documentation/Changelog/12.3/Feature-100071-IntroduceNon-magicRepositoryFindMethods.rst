.. include:: /Includes.rst.txt

.. _feature-100071-1677853567:

==============================================================
Feature: #100071 - Introduce non-magic repository find methods
==============================================================

See :issue:`100071`

Description
===========

Extbase repositories come with a magic :php:`__call()` method to allow calling
the following methods without implementing:

- :php:`findBy[PropertyName]($propertyValue)`
- :php:`findOneBy[PropertyName]($propertyValue)`
- :php:`countBy[PropertyName]($propertyValue)`

Magic methods are quite handy but they have a huge disadvantage. There is no
proper IDE support i.e. most IDEs show an error or at least a warning,
saying method :php:`findByAuthor()` does not exist. Also, type declarations are
impossible to use because with :php:`__call()` everything is :php:`mixed`. And
last but not least, static code analysis - like PHPStan - cannot properly
analyze those and give meaningful errors.

Therefore, there is a new set of methods without all those downsides:

- :php:`findBy(array $criteria, ...): QueryResultInterface`
- :php:`findOneBy(array $criteria, ...): object|null`
- :php:`count(array $criteria, ...): int`

The naming of those methods follows those of `doctrine/orm` and only
:php:`count()` differs from the formerly :php:`countBy()`. While all magic
methods only allow for a single comparison (`propertyName` = `propertyValue`),
those methods allow for multiple comparisons, called constraints.

Example:

..  code-block:: php

    $this->blogRepository->findBy(['author' => 1, 'published' => true]);

Impact
======

The new methods support a broader feature set, support IDEs, static code
analyzers and type declarations.

.. index:: PHP-API, NotScanned, ext:extbase
