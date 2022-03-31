.. include:: /Includes.rst.txt

==========================================================================
Breaking: #96044 - Harden method signature of logicalAnd() and logicalOr()
==========================================================================

See :issue:`96044`

Description
===========

The method signature of :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface::logicalAnd()`
and :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface::logicalOr()` changed.
As a consequence the method signature of :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Query::logicalAnd()`
and :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Query::logicalOr()` changed as well.

Both methods do no longer accept an array as first parameter. Furthermore both
methods have two mandatory :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface`
parameters to form a logical conjunction on.

Both methods do indeed accept an infinite number of further constraints.

The :php:`logicalAnd()` method does now reliably return an instance of
:php:`\TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface` instance
while the :php:`logicalOr()` method returns a :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface`
instance.


Impact
======

This change impacts all usages of said methods with

- either just one array parameter containing all constraints
- or passing just a single constraint

An array is no longer accepted as first parameter because it does not
guarantee the minimum number (2) of constraints is given.

Just one constraint is no longer accepted because in that case, the
incoming constraint would have simply been returned which is not compatible
with returning either a :php:`AndInterface` or a :php:`OrInterface` but just
a :php:`ConstraintInterface`.


Affected Installations
======================

All installations that passed all constraints as array or that passed just
one constraint.


Migration
=========

The migration is the same for :php:`logicalAnd()` and :php:`logicalOr()`
since their param signature is the same. The upcoming example will show a
migration for a :php:`logicalAnd()` call.

**Example**:

.. code-block:: php

   $query = $this->createQuery();
   $query->matching($query->logicalAnd([
       $query->equals('propertyName1', 'value1'),
       $query->equals('propertyName2', 'value2'),
       $query->equals('propertyName3', 'value3'),
   ]));

In this case an array is used as one and only method argument. The migration is
easy and quickly done. Simply don't use an array:

.. code-block:: php

   $query = $this->createQuery();
   $query->matching($query->logicalAnd(
       $query->equals('propertyName1', 'value1'),
       $query->equals('propertyName2', 'value2'),
       $query->equals('propertyName3', 'value3'),
   ));

Things become a little more tricky as soon as the number of constraints is below
2 or unknown before runtime.

**Example**:

.. code-block:: php

   $constraints = [];

   if (...) {
      $constraints[] = $query->equals('propertyName1', 'value1');
   }

   if (...) {
      $constraints[] = $query->equals('propertyName2', 'value2');
   }

   $query = $this->createQuery();
   $query->matching($query->logicalAnd($constraints));

In this case there needs to be a distinction of number of constraints in the code base:

.. code-block:: php

   $constraints = [];

   if (...) {
      $constraints[] = $query->equals('propertyName1', 'value1');
   }

   if (...) {
      $constraints[] = $query->equals('propertyName2', 'value2');
   }

   $query = $this->createQuery();

   $numberOfConstraints = count($constraints);
   if ($numberOfConstraints === 1) {
       $query->matching(reset($constraints));
   } elseif ($numberOfConstraints >= 2) {
       $query->matching($query->logicalAnd(...$constraints));
   }

.. index:: PHP-API, FullyScanned, ext:extbase
