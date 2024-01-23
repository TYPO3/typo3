.. include:: /Includes.rst.txt

.. _breaking-102632-1702043797:

===============================================
Breaking: #102632 - Use strict types in Extbase
===============================================

See :issue:`102632`, :issue:`102878`, :issue:`102879`, :issue:`102885`,
:issue:`102954`, :issue:`102956`, :issue:`102966`, :issue:`102969`

Description
===========

All properties, except the :php:`$view` property, in
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController` are now strictly typed.
In addition, all function arguments and function return types are now strictly
typed.

Also, the properties in the :php:`\TYPO3\CMS\Extbase\Annotation\Annotation`
namespace now have native PHP types for their properties.

In summary, the following classes have received strict types:

- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Annotation\IgnoreValidation`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Annotation\ORM\Cascade`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Annotation\Required\Validate`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Domain\Model\AbstractFileFolder`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Domain\Model\Category`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Domain\Model\FileReference`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Domain\Model\File`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Persistence\ObjectStorage`
- :php:`\TYPO3\CMS\Extbase\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface`


Impact
======

Classes extending the changed classes must now ensure that overwritten
properties and methods are all are strictly typed.


Affected installations
======================

Custom classes extending the changed classes.


Migration
=========

Ensure classes that extend the changed classes use strict types for overwritten
properties, function arguments and return types.

Extensions supporting multiple TYPO3 versions (for example, v12 and v13) must not
overwrite properties of the changed classes.
Instead, it is recommended to set values of overwritten properties in the
constructor of the extending class.

Before
------

..  code-block:: php

    <?php

    namespace MyVendor\MyExtension\Controller;

    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        public string $errorMethodName = 'myAction';
    }

After
-----

..  code-block:: php

    <?php

    namespace MyVendor\MyExtension\Controller;

    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        public function __construct()
        {
            $this->errorMethodName = 'myAction';
        }
    }


.. index:: Backend, Frontend, NotScanned, ext:extbase
