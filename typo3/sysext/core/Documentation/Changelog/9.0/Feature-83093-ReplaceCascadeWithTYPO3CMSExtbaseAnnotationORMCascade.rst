.. include:: ../../Includes.txt

======================================================================================
Feature: #83093 - Replace @cascade with @TYPO3\\CMS\\Extbase\\Annotation\\ORM\\Cascade
======================================================================================

See :issue:`83093`

Description
===========

As a successor to the :php:`@cascade` annotation, the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\ORM\Cascade` has been introduced.

Example:

.. code-block:: php

	/**
	 * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
	 */
	public $property;

Doctrine annotations are actual defined classes, therefore you can also use the annotation with a use statement.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;

.. code-block:: php

	/**
	 * @Cascade("remove")
	 */
	public $property;

Used annotations can also be aliased which the core will most likely be using a lot in the future.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation as Extbase;

.. code-block:: php

	/**
	 * @Extbase\ORM\Cascade("remove")
	 */
	public $property;


Impact
======

In 9.x there is no actual impact. Both the simple :php:`@cascade` and
:php:`@TYPO3\CMS\Extbase\Annotation\ORM\Cascade` can be used side by side.
However, :php:`@cascade` is deprecated in 9.x and will be removed in version 10.

.. index:: PHP-API, ext:extbase, FullyScanned
