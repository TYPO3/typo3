.. include:: ../../Includes.txt

===================================================================================
Feature: #83167 - Replace @validate with @TYPO3\\CMS\\Extbase\\Annotation\\Validate
===================================================================================

See :issue:`83167`

Description
===========

As a successor to the :php:`@validate` annotation, the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\Validate` has been introduced.


Example:
--------

.. code-block:: php

	/**
	 * @TYPO3\CMS\Extbase\Annotation\Validate
	 * @var Foo
	 */
	public $property;

Doctrine annotations are actual defined classes, therefore you can also use the annotation with a use statement.


Example:
--------

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation\Validate;

.. code-block:: php

	/**
	 * @Validate
	 * @var Foo
	 */
	public $property;

Used annotations can also be aliased which the core will most likely be using a lot in the future.


Example:
--------

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation as Extbase;

.. code-block:: php

	/**
	 * @Extbase\Validate
	 * @var Foo
	 */
	public $property;


Impact
======

In v9 there is no actual impact. Both the simple :php:`@validate` and
:php:`@TYPO3\CMS\Extbase\Annotation\Validate` can be used side by side.
However, :php:`@validate` is deprecated in v9 and will be removed in v10.

.. index:: PHP-API, ext:extbase, FullyScanned
