.. include:: /Includes.rst.txt

================================================================================
Feature: #83078 - Replace @lazy with @TYPO3\\CMS\\Extbase\\Annotation\\ORM\\Lazy
================================================================================

See :issue:`83078`

Description
===========

As a successor to the :php:`@lazy` annotation, the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\ORM\Lazy` has been introduced.

Example:

.. code-block:: php

	/**
	 * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
	 * @var Foo
	 */
	public $property;

Doctrine annotations are actual defined classes, therefore you can also use the annotation with a use statement.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;

.. code-block:: php

	/**
	 * @Lazy
	 * @var Foo
	 */
	public $property;

Used annotations can also be aliased which the core will most likely be using a lot in the future.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation as Extbase;

.. code-block:: php

	/**
	 * @Extbase\ORM\Lazy
	 * @var Foo
	 */
	public $property;


Impact
======

In 9.x there is no actual impact. Both the simple :php:`@lazy` and
:php:`@TYPO3\CMS\Extbase\Annotation\ORM\Lazy` can be used side by side.
However, :php:`@lazy` is deprecated in 9.x and will be removed in version 10.

.. index:: PHP-API, ext:extbase, FullyScanned
