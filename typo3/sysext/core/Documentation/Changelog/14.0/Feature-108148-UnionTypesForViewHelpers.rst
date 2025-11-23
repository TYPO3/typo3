..  include:: /Includes.rst.txt

..  _feature-108148-1763297213:

==============================================
Feature: #108148 - Union types for ViewHelpers
==============================================

See :issue:`108148`

Description
===========

Fluid 5 brings support for union types in ViewHelper argument definitions.
Previously, it was necessary to specify an argument as :php:`mixed` if more
than one type should be possible. Now it is possible to specify multiple
types separated by a pipe character (`|`).

The following built-in PHP types can also be used:

*   `iterable`
*   `countable`
*   `callable`

Example:

..  code-block:: php

     use TYPO3\CMS\Core\Resource\File;
     use TYPO3\CMS\Core\Resource\FileReference;
     use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

     class MyViewHelper extends AbstractViewHelper
     {
         public function initializeArguments(): void
         {
             $this->registerArgument(
                 'file',
                 File::class . '|' . FileReference::class,
                 'a file object'
             );
             $this->registerArgument(
                 'items',
                 'iterable',
                 'a list of items'
             );
         }
     }

This feature also applies to the
`Argument ViewHelper <f:argument> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-argument>`_:

..  code-block:: html

    <f:argument name="file" type="TYPO3\CMS\Core\Resource\File|TYPO3\CMS\Core\Resource\FileReference" />

Note that union types disable automatic type conversion by Fluid, so it
might be necessary to specify more types to keep ViewHelpers flexible.
Example:

..  code-block:: php

     $this->registerArgument(
         'ids',
         'array|string|int',
         'a list of ids, either comma-separated or as array'
     );

Impact
======

Custom ViewHelper implementations have more options to specify an API with
strict type requirements and can avoid manual type checks.

..  index:: Fluid, ext:fluid
