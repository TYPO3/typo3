.. include:: /Includes.rst.txt

.. _breaking-97664-1688659987:

===========================================================
Breaking: #97664 - FormPersistenceManagerInterface modified
===========================================================

See :issue:`97664`

Description
===========

The PHP interface :php:`\TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface`
now requires PHP classes to implement an additional method :php:`hasForms()` in
order to fulfill the API.


Impact
======

TYPO3 projects with extensions using implementations of the
:php:`\TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface` will now
break with a fatal PHP error.


Affected installations
======================

Extensions using implementations of the
:php:`\TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface`.


Migration
=========

Add the new method to your extension's implementation of this interface,
which also makes it compatible with TYPO3 v12 and TYPO3 v13 at the same time.

.. index:: PHP-API, NotScanned, ext:form
