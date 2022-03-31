.. include:: /Includes.rst.txt

=================================================================================
Breaking: #87957 - Validators are not registered automatically in Extbase anymore
=================================================================================

See :issue:`87957`

Description
===========

There were several validators that Extbase applies automatically. One example are domain validators that are registered
if created in a specific directory. Another one is the type validator which is created if a validator with a specific
name exists.

The method :php:`TYPO3\CMS\Extbase\Utility\ClassNamingUtility::translateModelNameToValidatorName` has
been removed without substitution. This leads to no automatically registered validators anymore.

Domain Validators
=================

Given that there is a model :php:`\TYPO3\CMS\Extbase\Domain\Model\BackendUser`, extbase searched for a validator named
:php:`\TYPO3\CMS\Extbase\Domain\Validator\BackendUserValidator`. The `Model` part of the namespace had been replaced
with `Validator` and another `Validator` string had been added to the actual class name. In this example, `BackendUser`
has been replaced with `BackendUserValidator`.

If such a validator class existed it had been magically applied and used during the validation of the model.

Example::

   <?php
   namespace ExtbaseTeam\BlogExample\Domain\Validator;

   use TYPO3\CMS\Extbase\Validation\Validator;

   class BlogValidator implements ValidatorInterface
   {
      public function validate($value);
      {
         // ...
      }
   }

::

   <?php
   namespace ExtbaseTeam\BlogExample\Controller;

   use ExtbaseTeam\BlogExample\Domain\Model\Blog;
   use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

   class BlogController extends ActionController
   {
      public function showAction(Blog $blog)
      {
         // ...
      }
   }

In this example there is a model validator :php:`ExtbaseTeam\BlogExample\Domain\Validator\BlogValidator` defined for
model :php:`ExtbaseTeam\BlogExample\Domain\Model\Blog`, which had been automatically registered before calling action
:php:`ExtbaseTeam\BlogExample\Controller\BlogController::showAction`.

From now on the validator needs to be registered manually.

::

   <?php
   namespace ExtbaseTeam\BlogExample\Controller;

   use ExtbaseTeam\BlogExample\Domain\Model\Blog;
   use TYPO3\CMS\Extbase\Annotation as Extbase;
   use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

   class BlogController extends ActionController
   {
      /**
       * @Extbase\Validate(param="blog", validator="ExtbaseTeam\BlogExample\Domain\Validator\BlogValidator")
       */
      public function showAction(Blog $blog)
      {
         // ...
      }
   }


Type Validators
===============

Given that there is any kind of simple type param or property that is to be validated, e.g. a property of a model or an
action method param, extbase tried to apply a validator for that param/property derived from its type. If there was an
action param of type string, extbase searched for a `StringValidator` in the namespace
`TYPO3\CMS\Extbase\Validation\Validator`. The :php:`TYPO3\CMS\Extbase\Validation\Validator\StringValidator` does
actually exist, as well as :php:`TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator` and others.

If a validator for a specific type existed it had been magically applied and used during the validation of models and
action arguments.

Example:

::

   <?php
   namespace ExtbaseTeam\BlogExample\Controller;

   use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

   class BlogController extends ActionController
   {
      public function showAction(int $blogUid)
      {
         // ...
      }
   }

In this example there is a simple type param, extbase automatically registered a type validator for. First, `int` had
been normalized to `integer`, then :php:`ucfirst($type)` had been called, resulting in `Integer` and then extbase looked
for a :php:`TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator`. As this Validator exists, it had been
automatically registered.

If this behaviour is desired, the validator needs to be registered manually from now on.

::

   <?php
   namespace ExtbaseTeam\BlogExample\Controller;

   use TYPO3\CMS\Extbase\Annotation as Extbase;
   use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

   class BlogController extends ActionController
   {
      /**
       * @Extbase\Validate(param="blogUid", validator="TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator")
       */
      public function showAction(int $blogUid)
      {
         // ...
      }
   }


Impact
======

With these mentioned validators no longer being applied automatically, developers actively need to apply those
validators if needed. Most developers might want to register existing domain validators manually while leaving the type
validators unregistered. This however will vary from project to project.


Affected Installations
======================

All installations that use the extbase validation framework.


Migration
=========

There is no automatic migration. Validators need to be re-applied manually if needed.

.. index:: PHP-API, PartiallyScanned, ext:extbase
