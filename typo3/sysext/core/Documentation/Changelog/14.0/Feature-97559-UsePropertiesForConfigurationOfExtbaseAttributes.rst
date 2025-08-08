..  include:: /Includes.rst.txt

..  _feature-97559-1760451913:

=============================================================================
Feature: #97559 - Support property-based configuration for Extbase attributes
=============================================================================

See :issue:`97559`

Description
===========

PHP attributes in Extbase context can now be configured using properties
instead of an array of configuration values. This resolves a limitation which
was present since the introduction of Extbase annotations back in TYPO3 v9,
where annotation configuration was quite limited – all available options
needed to be defined in a single array. Since annotations were dropped
with :issue:`107229` in favor of PHP attributes, the definition of configuration
options is now possible in a more flexible and typesafe way.

Example usage
-------------

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


Impact
======

This patch serves as follow-up to :issue:`107229` and thrives to improve the
attribute configuration option mechanism by using constructor property
promotion in combination with strictly typed properties. In order to maintain
backwards compatibility, the first property of each attribute still accepts an
array with configuration options to be passed. However, this is considered
deprecated and will be dropped with TYPO3 v15.0 (see :ref:`deprecation notice
<deprecation-97559-1760453281>`). Developers are advised to migrate towards
single properties when using PHP attributes in Extbase.


..  index:: PHP-API, ext:extbase
