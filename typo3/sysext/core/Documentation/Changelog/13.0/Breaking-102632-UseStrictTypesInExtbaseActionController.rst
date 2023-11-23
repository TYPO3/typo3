.. include:: /Includes.rst.txt

.. _breaking-102632-1702043797:

================================================================
Breaking: #102632 - Use strict types in extbase ActionController
================================================================

See :issue:`102632`

Description
===========

All properties, except the :php:`$view` property, in
:php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController` are now strictly typed.
In addition, all function arguments and function return types are now strictly
typed.


Impact
======

Classes extending :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController` must
now ensure, that overwritten properties and methods are all are strictly typed.


Affected installations
======================

Custom classes extending :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController`


Migration
=========

Ensure classes that extend :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController`
use strict types for overwritten properties, function arguments and return types.

Extensions supporting multiple TYPO3 versions (e.g. v12 and v13) must not
overwrite properties of :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController`.
Instead, it is recommended to set values of overwritten properties in the
constructor of the extending class.

Before
------

.. code-block:: php

    <?php

    namespace Vendor\MyExtension\Controller;

    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        public string $errorMethodName = 'myAction';

After
-----

.. code-block:: php

    <?php

    namespace Vendor\MyExtension\Controller;

    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        public function __construct()
        {
            $this->errorMethodName = 'myAction';
        }


.. index:: Backend, Frontend, NotScanned, ext:extbase
