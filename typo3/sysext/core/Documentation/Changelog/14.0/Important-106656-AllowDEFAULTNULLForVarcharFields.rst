..  include:: /Includes.rst.txt

..  _important-106656-1746525351:

============================================================
Important: #106656 - Allow `DEFAULT NULL` for varchar fields
============================================================

See :issue:`106656`

Description
===========

In TCA, if an input field is configured to be nullable via
:php:`'nullable' => true`, the database migration now respects this and creates
new or updates existing fields with `DEFAULT NULL`.

In Extbase, this may cause issues if properties and their accessor methods are
not properly declared to be nullable, therefore this change is introduced to
TYPO3 v14 only.

Example:

..  code-block:: php
    :caption: Example properly implementing a nullable property

    <?php

    declare(strict_types=1);

    namespace Vendor\myExtension\Domain\Model;

    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class MyExtbaseEntity extends AbstractEntity
    {
        protected ?string $title;

        public function getTitle(): ?string
        {
            return $this->title;
        }

        public function setTitle(?string $title): void
        {
            $this->title = $title;
        }
    }

As stated above, this automatic detection is not provided in TYPO3 versions
older than 14.0. Using `DEFAULT NULL` can be enforced via an extension's
:file:`ext_tables.sql` instead:

..  code-block:: sql

    CREATE TABLE tx_myextension_table (
        title varchar(255) DEFAULT NULL
    );

..  index:: Database, ext:core
