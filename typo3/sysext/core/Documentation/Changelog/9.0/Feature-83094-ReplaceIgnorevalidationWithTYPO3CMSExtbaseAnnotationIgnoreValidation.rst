.. include:: /Includes.rst.txt

===================================================================================================
Feature: #83094 - Replace @ignorevalidation with @TYPO3\\CMS\\Extbase\\Annotation\\IgnoreValidation
===================================================================================================

See :issue:`83094`

Description
===========

As a successor to the :php:`@ignorevalidation` annotation, the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\IgnoreValidation` has been introduced.

Example:

.. code-block:: php

	/**
	 * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("param")
	 */
	public function method($param)
	{
	}

Doctrine annotations are actual defined classes, therefore you can also use the annotation with a use statement.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;

.. code-block:: php

	/**
	 * @IgnoreValidation("param")
	 */
	public function method($param)
	{
	}

Used annotations can also be aliased which the core will most likely be using a lot in the future.

Example:

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation as Extbase;

.. code-block:: php

	/**
	 * @Extbase\IgnoreValidation("param")
	 */
	public function method($param)
	{
	}

.. tip::

	Please mind that `@TYPO3\CMS\Extbase\Annotation\IgnoreValidation` does no longer accept parameter names prepended with dollar signs `$`.
	Example: `@ignorevalidation $foo` becomes `@Extbase\IgnoreValidation("foo")`

Impact
======

In 9.x there is no actual impact. Both the simple :php:`@ignorevalidation` and
:php:`@TYPO3\CMS\Extbase\Annotation\IgnoreValidation` can be used side by side. However,
:php:`@ignorevalidation` is deprecated in 9.x and will be removed in version 10.

.. index:: PHP-API, ext:extbase, FullyScanned
