.. include:: /Includes.rst.txt

===========================================================================
Feature: #87457 - Use symfony/property-info to gather doc block information
===========================================================================

See :issue:`87457`

Description
===========

The use of `symfony/property-info` enables us to resolve non fully qualified class names.

This is now possible:

.. code-block:: php

   use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
   use ExtbaseTeam\BlogExample\Domain\Model\Comment;

   class Post
   {
       /*
        * @var ObjectStorage<Comment>
        */
       public $comments;
   }

Important:
This only works in extbase models as the reflection
costs are high and the information is only needed
in this case.

The non fully qualified class name is now also
supported for injection properties, although it is
still recommended to avoid injection properties in
favor of injection methods or constructor injection.

Example:

.. code-block:: php

   use TYPO3\CMS\Extbase\Annotation as Extbase;
   use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

   class Service
   {
       /*
        * @Extbase\Inject
        * @var ConfigurationManager
        */
       public $configurationManager;
   }


.. index:: PHP-API, ext:extbase
