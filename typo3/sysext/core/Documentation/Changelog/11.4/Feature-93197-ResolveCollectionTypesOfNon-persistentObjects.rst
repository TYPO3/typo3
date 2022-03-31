.. include:: /Includes.rst.txt

====================================================================
Feature: #93197 - Resolve collection types of non-persistent objects
====================================================================

See :issue:`93197`

Description
===========

Collection types are used to define a specific class that should be within
Extbase's :php:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` class.

Example:

.. code-block:: php

    /**
     * @param ObjectStorage<Item> $items
     */
    public function setItems(ObjectStorage $items): void
    {
        $this->items = $items;
    }

These docblocks are analyzed so the :php:`PropertyMapper` knows how to map
incoming requests. This mapping already works for persistent objects
(domain models). As non-persistent objects are also used for property mapping,
namely DTOs (data transfer objects), this now works for them, too.

Impact
======

Developers can use collection types in docblock annotations for non-persistent
objects. The collection type is considered while property mapping requests.

.. index:: PHP-API, ext:extbase
