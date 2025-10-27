.. include:: /Includes.rst.txt

.. _feature-107889-1761661319:

=========================================================
Feature: #107889 - Introduce TCA option "itemsProcessors"
=========================================================

See :issue:`107889`

Description
===========

As part of centralizing and improving the processing of items for select,
check and radio type fields, a new TCA option :php:`itemsProcessors` is introduced
as a replacement for :php:`itemsProcFunc`. This option is an array, so that any
number of processors can be called instead of just one. Processors are ordered
by their array key (numerical) and executed accordingly. Processors can receive
arbitrary data via the :php:`$context->processorParameters` property.

All processors must implement the :php:`\TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface`
interface.

Processor methods receive two parameters: a
:php:`\TYPO3\CMS\Core\Schema\Struct\SelectItemCollection` instance containing the items,
and an :php:`\TYPO3\CMS\Core\DataHandling\ItemsProcessorContext` instance providing
access to table, field, row data, and configuration. The processor must return
a :php:`SelectItemCollection`. This means that added items cannot be untyped arrays
anymore, making the whole processing much cleaner and safer.

A new Page TSconfig option is also available, mirroring the existing option for
:php:`itemsProcFunc`. See example below for the syntax.

Example
=======

The TCA registration might look like this:

.. code-block:: php
   :caption: EXT:my_package/Configuration/TCA/my_table.php

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
                    'class' => \MyVendor\MyPackage\Processors\SpecialRelationsProcessor::class,
                    'parameters' => [
                        'foo' => 'bar',
                    ],
                ],
                50 => [
                    'class' => \MyVendor\MyPackage\Processors\SpecialRelationsProcessor2::class,
                ],
            ],
        ],
    ],

:php:`SpecialRelationsProcessor2` will be called before :php`SpecialRelationsProcessor`.

And here is an example processor:

.. code-block:: php
   :caption: EXT:my_package/Classes/Processors/SpecialRelationsProcessor.php

    <?php

    namespace MyVendor\MyPackage\Processors;

    use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
    use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
    use TYPO3\CMS\Core\Schema\Struct\SelectItem;
    use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;

    class SpecialRelationsProcessor implements ItemsProcessorInterface
    {
        public function processItems(
            SelectItemCollection $items,
            ItemsProcessorContext $context,
        ): SelectItemCollection {
            $items->add(
                new SelectItem(
                    type: 'select',
                    label: 'Extra item',
                    value: 42
                )
            );
            return $items;
        }
    }

The :php:`$context->processorParameters` property contains whatever "parameters" were
defined in the TCA declaration.

Using Page TSconfig to pass custom parameters to the processor would look like:

.. code-block:: typoscript

    TCEFORM.example_table.content.itemsProcessors.100.foo = bar

Note how the numerical key of the processor must be reused. With this setup,
the class :php:`SpecialRelationsProcessor` would receive the PHP array
:php:`['foo' => 'bar']` in the :php:`$context->fieldTSconfig` property.
Class :php:`SpecialRelationsProcessor2` would receive an empty array :php:`[]`
(since it is registered with key :php:`50`).

Registration of processors is also possible within FlexForms:

.. code-block:: xml
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

It is still possible to use :php:`itemsProcFunc`, but it is recommended to
switch to :php:`itemsProcessors`, which has two main advantages:

- being an array, it allows for extensions adding processors on top of
  existing ones (and defining the execution order)
- the processing chain is strictly typed, ensuring safer code.

If both :php:`itemsProcFunc` and :php:`itemsProcessors` are defined, both are
executed, with :php:`itemsProcFunc` coming first.

..  tip::

    The naming **itemsProcessors** with a double plural form has been chosen for two
    reasons: First, it complements the former plural-form **itemsUserFunc**.
    Second, it's because multiple processors can be defined that operate on the
    whole of all "items", and not just a single item.

.. index:: TCA, ext:core
