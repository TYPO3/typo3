..  include:: /Includes.rst.txt

..  _feature-101404-1795708000:

===============================================================
Feature: #101404 - Extbase maps JSON fields to array properties
===============================================================

See :issue:`101404`

Description
===========

Extbase now maps database columns configured as TCA type
:php:`json` to domain model properties typed as :php:`array` or
:php:`?array`. The JSON stored in the database is decoded into a PHP array
when the entity is reconstituted and an array is encoded as JSON when the
entity is persisted.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Domain/Model/Product.php

    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class Product extends AbstractEntity
    {
        protected array $attributes = [];
        protected ?array $metadata = null;

        // getters and setters
    }

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_myextension_domain_model_product.php

    return [
        'columns' => [
            'attributes' => [
                'config' => [
                    'type' => 'json',
                    'default' => '[]',
                ],
            ],
            'metadata' => [
                'config' => [
                    'type' => 'json',
                ],
            ],
        ],
    ];

An empty database value and JSON that does not decode to an array leave a
non-nullable property at its default value and set a nullable property to
:php:`null`. A nullable property set to :php:`null` is stored as
:sql:`NULL`.

JSON objects are decoded into associative arrays, so an empty object
:json:`{}` is written back as :php:`[]` on the next persist.

Impact
======

Array properties of Extbase domain models can be used with TCA type
:php:`json` columns without a custom :php:`TypeInterface` value object.
Mapping such properties from request arguments (property mapping) and query
constraints on JSON columns are not part of this feature.

Array properties that map to other column types are still not populated
from the database.

..  index:: PHP-API, ext:extbase
