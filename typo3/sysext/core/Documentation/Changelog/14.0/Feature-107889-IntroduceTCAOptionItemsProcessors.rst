..  include:: /Includes.rst.txt

..  _feature-107889-1761661319:

=========================================================
Feature: #107889 - Introduce TCA option "itemsProcessors"
=========================================================

See :issue:`107889`

Description
===========

As part of centralizing and improving the processing of items for `select`,
`check`, and `radio` type fields, a new TCA option
:php:`itemsProcessors` has been introduced as a replacement for
:php:`itemsProcFunc`. This option is an array, allowing any number of
processors to be called instead of just one. Processors are ordered by their
array key (numerical) and executed in that order. Processors can receive
arbitrary data through the :php:`$context->processorParameters` property.

All processors must implement the
:php-short:`\TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface` interface.

Processor methods receive two parameters:
a :php-short:`\TYPO3\CMS\Core\Schema\Struct\SelectItemCollection` instance
containing the items, and an
:php-short:`\TYPO3\CMS\Core\DataHandling\ItemsProcessorContext` instance
providing access to table, field, row data, and configuration. The processor
must return a :php:`SelectItemCollection`. This means that added items can no
longer be untyped arrays, making the entire process cleaner and safer.

A new Page TSconfig option is also available, mirroring the existing one for
:php:`itemsProcFunc`. See the example below for syntax.


Example
=======

The TCA registration might look like this:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/my_table.php

     use MyVendor\MyExtension\Processors\SpecialRelationsProcessor;

    'relation' => [
        'label' => 'Relational field',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'value' => 0,
                    'label' => '',
                ],
            ],
            'foreign_table' => 'some_foreign_table',
            'itemsProcessors' => [
                100 => [
                    'class' => SpecialRelationsProcessor::class,
                    'parameters' => [
                        'foo' => 'bar',
                    ],
                ],
                50 => [
                    'class' => SpecialRelationsProcessor2::class,
                ],
            ],
        ],
    ],

In this example, :php:`SpecialRelationsProcessor2` will be called before
:php:`SpecialRelationsProcessor`.

Here is an example processor:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Processors/SpecialRelationsProcessor.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Processors;

    use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
    use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
    use TYPO3\CMS\Core\Schema\Struct\SelectItem;
    use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;

    final class SpecialRelationsProcessor implements ItemsProcessorInterface
    {
        public function processItems(
            SelectItemCollection $items,
            ItemsProcessorContext $context,
        ): SelectItemCollection {
            $items->add(
                new SelectItem(
                    type: 'select',
                    label: 'Extra item',
                    value: 42,
                )
            );

            return $items;
        }
    }

The :php:`$context->processorParameters` property contains any parameters
defined in the TCA declaration.

Using Page TSconfig to pass custom parameters to the processor would look like
this:

..  code-block:: typoscript

    TCEFORM.example_table.content.itemsProcessors.100.foo = bar

Note that the numerical key of the processor must be reused.
With this setup, the class :php:`SpecialRelationsProcessor` receives the PHP
array :php:`['foo' => 'bar']` in the :php:`$context->fieldTSconfig` property.
The class :php:`SpecialRelationsProcessor2` receives an empty array
:php:`[]` (since it is registered with the key :php:`50`).

Registration of processors is also possible within FlexForms:

..  code-block:: xml
    :caption: EXT:my_package/Configuration/FlexForms/SomeForm.xml

    <some_selector>
        <label>Choice</label>
        <config>
            <type>select</type>
            <renderType>selectSingle</renderType>
            <itemsProcessors>
                <numIndex index="100">
                    <class>MyVendor\MyPackage\Processors\SpecialRelationsProcessor</class>
                </numIndex>
            </itemsProcessors>
        </config>
    </some_selector>


Impact
======

It is still possible to use :php:`itemsProcFunc`, but switching to
:php:`itemsProcessors` is recommended because it offers two main advantages:

*   Being an array, it allows extensions to add processors on top of existing
    ones and to define their execution order.
*   The processing chain is strictly typed, ensuring safer and more reliable
    code.

If both :php:`itemsProcFunc` and :php:`itemsProcessors` are defined,
both are executed, with :php:`itemsProcFunc` executed first.

..  tip::

    The naming **itemsProcessors**, using a double plural form, was chosen for
    two reasons. First, it complements the former plural form
    **itemsUserFunc**. Second, multiple processors can be defined that operate
    on the collection of all "items", not just on a single item.

..  index:: TCA, ext:core
