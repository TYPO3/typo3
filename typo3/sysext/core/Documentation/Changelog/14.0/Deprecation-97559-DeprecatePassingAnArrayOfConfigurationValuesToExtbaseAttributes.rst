..  include:: /Includes.rst.txt

..  _deprecation-97559-1760453281:

==============================================================================================
Deprecation: #97559 - Deprecate passing an array of configuration values to Extbase attributes
==============================================================================================

See :issue:`97559`

Description
===========

Passing an array of configuration values to Extbase attributes has been
deprecated. All configuration values should now be passed as single properties
using constructor property promotion. When an array of configuration values is
passed for the first available property in an attribute, a deprecation notice
will be triggered. The possibility to pass such an array will be removed with
TYPO3 v15.

Impact
======

The usage of constructor property promotion as an alternative to an array of
configuration values enables type safety and value hardening and moves Extbase
attributes toward a modern configuration element for models,
:abbr:`Data Transfer Objects (DTOs)`, and controller actions.

Affected installations
======================

All installations that make use of Extbase attribute configuration are
affected, since this was previously only possible by passing an array of
configuration values.

Migration
=========

Use the available attribute properties instead of an array.

Before:
-------

..  code-block:: php

     use TYPO3\CMS\Extbase\Attribute\FileUpload;
     use TYPO3\CMS\Extbase\Attribute\Validate;
     use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
     use TYPO3\CMS\Extbase\Domain\Model\FileReference;

     class MyModel extends AbstractEntity
     {
         #[Validate(['validator' => 'NotEmpty'])]
         protected string $foo = '';

         #[FileUpload([
             'validation' => [
                 'required' => true,
                 'maxFiles' => 1,
                 'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
                 'allowedMimeTypes' => ['image/jpeg', 'image/png'],
             ],
             'uploadFolder' => '1:/user_upload/files/',
         ])]
         protected ?FileReference $bar = null;
     }

After:
------

..  code-block:: php

     use TYPO3\CMS\Extbase\Attribute\FileUpload;
     use TYPO3\CMS\Extbase\Attribute\Validate;
     use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
     use TYPO3\CMS\Extbase\Domain\Model\FileReference;

     class MyModel extends AbstractEntity
     {
         #[Validate(validator: 'NotEmpty')]
         protected string $foo = '';

         #[FileUpload(
             validation: [
                 'required' => true,
                 'maxFiles' => 1,
                 'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
                 'allowedMimeTypes' => ['image/jpeg', 'image/png'],
             ],
             uploadFolder: '1:/user_upload/files/',
         )]
         protected ?FileReference $bar = null;
     }

Combined diff:
--------------

..  code-block:: diff

      class MyModel extends AbstractEntity
      {
     -    #[Validate(['validator' => 'NotEmpty'])]
     +    #[Validate(validator: 'NotEmpty')]
          protected string $foo = '';

     -    #[FileUpload([
     -        'validation' => [
     +    #[FileUpload(
     +        validation: [
                  'required' => true,
                  'maxFiles' => 1,
                  'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
                  'allowedMimeTypes' => ['image/jpeg', 'image/png'],
              ],
     -        'uploadFolder' => '1:/user_upload/files/',
     -    ])]
     +        uploadFolder: '1:/user_upload/files/',
     +    )]
          protected ?FileReference $bar = null;
      }

..  index:: PHP-API, NotScanned, ext:extbase
