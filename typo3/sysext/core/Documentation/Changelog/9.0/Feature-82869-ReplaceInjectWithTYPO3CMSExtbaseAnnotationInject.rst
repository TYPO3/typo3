.. include:: /Includes.rst.txt

===============================================================================
Feature: #82869 - Replace @inject with @TYPO3\\CMS\\Extbase\\Annotation\\Inject
===============================================================================

See :issue:`82869`

Description
===========

As a successor to the :php:`@inject` annotation, the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\Inject` has been introduced.

Example:

.. code-block:: php

	/**
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 * @var Foo
	 */
	public $property;

Doctrine annotations are actual defined classes, therefore you can also use the annotation with a use statement.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation\Inject;

.. code-block:: php

	/**
	 * @Inject
	 * @var Foo
	 */
	public $property;

Used annotations can also be aliased which the core will most likely be using a lot in the future.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation as Extbase;

.. code-block:: php

	/**
	 * @Extbase\Inject
	 * @var Foo
	 */
	public $property;


Impact
======

In 9.x there is no actual impact. Both the simple :php:`@inject` and
:php:`@TYPO3\CMS\Extbase\Annotation\Inject` can be used side by side.
However, :php:`@inject` is deprecated in 9.x and will be removed in version 10.

.. index:: PHP-API, ext:extbase, FullyScanned
