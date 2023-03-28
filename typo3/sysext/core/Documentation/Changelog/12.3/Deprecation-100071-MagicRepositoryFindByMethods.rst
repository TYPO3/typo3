.. include:: /Includes.rst.txt

.. _deprecation-100071-1677853787:

========================================================
Deprecation: #100071 - Magic repository findBy() methods
========================================================

See :issue:`100071`

Description
===========

Extbase repositories come with a magic :php:`__call()` method to allow calling
the following methods without implementing them:

- :php:`findBy[PropertyName]($propertyValue)`
- :php:`findOneBy[PropertyName]($propertyValue)`
- :php:`countBy[PropertyName]($propertyValue)`

These have now been marked as deprecated, as they are "magic", meaning
that proper IDE support is not possible, and other PHP-related tool
functionality such as PhpStorm.

In addition, it is not possible for Extbase repositories
to build their own magic method functionality as the logic is already
in use.

Impact
======

As these methods are widely used in almost all Extbase-based extensions,
they are marked as deprecated in TYPO3 v12, but will only trigger a deprecation
notice in TYPO3 v13, as they will be removed in TYPO3 v14.

This way, migration towards the new API methods can be made without
pressure.


Affected installations
======================

All installations with third-party extensions that use those magic methods.


Migration
=========

A new set of methods without all the downsides have been added:

- :php:`findBy(array $criteria, ...): QueryResultInterface`
- :php:`findOneBy(array $criteria, ...):object|null`
- :php:`count(array $criteria, ...): int`

The naming of the methods follows those of `doctrine/orm` and only
:php:`count()` differs from the formerly :php:`countBy()`. While all magic
methods only allow for a single comparison (`propertyName` = `propertyValue`),
those methods allow for multiple comparisons, called constraints.


`findBy[PropertyName]($propertyValue)` can be replaced with a call to `findBy`:

..  code-block:: php

    $this->blogRepository->findBy(['propertyName' => $propertyValue]);


`findOneBy[PropertyName]($propertyValue)` can be replaced with a call to `findOneBy`:

..  code-block:: php

    $this->blogRepository->findOneBy(['propertyName' => $propertyValue]);


`countBy[PropertyName]($propertyValue)` can be replaced with a call to `count`:

..  code-block:: php

    $this->blogRepository->count(['propertyName' => $propertyValue]);


.. index:: PHP-API, NotScanned, ext:extbase
