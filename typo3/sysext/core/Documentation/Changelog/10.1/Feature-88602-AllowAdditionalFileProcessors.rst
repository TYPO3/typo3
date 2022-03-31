.. include:: /Includes.rst.txt

==============================================================
Feature: #88602 - Allow registering additional file processors
==============================================================

See :issue:`88602`

Description
===========

Registering additional file processors has been introduced.
New processors need to implement the interface :php:`\TYPO3\CMS\Core\Resource\Processing\ProcessorInterface`.

To register a new processor, add the following code to :file:`ext_localconf.php`

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['MyNewImageProcessor'] = [
       'className' => \Vendor\ExtensionName\Resource\Processing\MyNewImageProcessor::class,
       'before' => ['LocalImageProcessor']
   ];

To order the processors, use `before` and `after` statements. TYPO3 will process the file
with the first processor that is able to process a given task.

Impact
======

Developers are now able to provide their own file processing. By providing priorities, the processor ending up handling
the file can be determined on a fine granular level including a fallback.

Examples for custom implementations might be:

* add a watermark to each image of type png
* compress uploaded pdf files into zip archives
* store images that should be cropped at a separate position in the target storage

.. index:: Backend, ext:core, FAL
