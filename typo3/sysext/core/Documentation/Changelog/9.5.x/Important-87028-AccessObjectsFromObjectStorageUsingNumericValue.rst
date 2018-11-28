.. include:: ../../Includes.txt

===========================================================================
Important: #87028 - Access objects from `ObjectStorage` using numeric value
===========================================================================

See :issue:`87028`

Description
===========

It is now possible to access the objects of an instance of `ObjectStorage` using a numeric value.

The following code now works:


.. code-block:: php

	$objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	$objectStorage->attach(new \stdClass());
	$myObject = $objectStorage->offsetGet(0);

And more importantly, the following Fluid code works as well:

.. code-block:: html

	<f:image image="{myObject.resources.0}" alt="My image!" />


Impact
======

The old way of getting information of an object in the storage still works as before.

.. index:: Fluid, PHP-API, ext:extbase, NotScanned
