.. include:: /Includes.rst.txt

==============================================================
Feature: #90267 - Custom placeholder processing in site config
==============================================================

See :issue:`90267`

Description
===========

The Yaml import for site configuration was changed to allow custom placeholder processors.


Impact
======

It is now possible to register a new placeholder processor:

:file:`LocalConfiguration.php`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['yamlLoader']['placeholderProcessors'][\Vendor\MyExtension\PlaceholderProcessor\CustomPlaceholderProcessor::class] = [];

There are some options available to sort or disable placeholder processors if necessary.

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['yamlLoader']['placeholderProcessors'][\Vendor\MyExtension\PlaceholderProcessor\CustomPlaceholderProcessor::class] = [
      'before' => [
         \TYPO3\CMS\Core\Configuration\Processor\Placeholder\ValueFromReferenceArrayProcessor::class
      ],
      'after' => [
         \TYPO3\CMS\Core\Configuration\Processor\Placeholder\EnvVariableProcessor::class
      ],
      'disabled' => false
   ];

New placeholder processors must implement the :php:`\TYPO3\CMS\Core\Configuration\Processor\Placeholder\PlaceholderProcessorInterface`

Placeholders look mostly like functions.
So an implementation may look like the following:

.. code-block:: php

   class ExamplePlaceholderProcessor implements PlaceholderProcessorInterface
   {
      public function canProcess(string $placeholder, array $referenceArray): bool
      {
         return strpos($placeholder, '%example(') !== false;
      }

      public function process(string $value, array $referenceArray)
      {
         // do some processing
         $result = $this->getValue($value);

         // Throw this exception if the placeholder can't be substituted
         if (!$envVar) {
            throw new \UnexpectedValueException('Value not found', 1581596096);
         }
         return $result;
      }
   }


This may be used like the following in the site configuration:

.. code-block:: yaml

   someVariable: '%example(somevalue)%'
   anotherVariable: 'inline::%example(anotherValue)%::placeholder'

If a new processor returns a string or number, it may also be used inline as above.
If it returns an array, it cannot be used inline since the whole content will be replaced with the new value.


.. index:: Backend, PHP-API, ext:core
