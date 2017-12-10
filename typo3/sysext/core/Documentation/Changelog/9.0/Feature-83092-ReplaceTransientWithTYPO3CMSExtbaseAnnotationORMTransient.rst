.. include:: ../../Includes.txt

=====================================================================================
Feature: #83092 - Replace @transient with @TYPO3\CMS\Extbase\Annotation\ORM\Transient
=====================================================================================

See :issue:`83092`

Description
===========

As a successor to the :php:`@transient` annotation, the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\ORM\Transient` has been introduced.

Example:

.. code-block:: php

	/**
	 * @TYPO3\CMS\Extbase\Annotation\ORM\Transient
	 */
	public $property;

Doctrine annotations are actual defined classes, therefore you can also use the annotation with a use statement.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation\ORM\Transient;

.. code-block:: php

	/**
	 * @Transient
	 */
	public $property;

Used annotations can also be aliased which the core will most likely be using a lot in the future.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation as Extbase;

.. code-block:: php

	/**
	 * @Extbase\ORM\Transient
	 */
	public $property;


Impact
======

In 9.x there is no actual impact. Both the simple :php:`@transient` and
:php:`@TYPO3\CMS\Extbase\Annotation\ORM\Transient` can be used side by side.
However, :php:`@transient` is deprecated in 9.x and will be removed in version 10.

.. index:: PHP-API, ext:extbase, FullyScanned
