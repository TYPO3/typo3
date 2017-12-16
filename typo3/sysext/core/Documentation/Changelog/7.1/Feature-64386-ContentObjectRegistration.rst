
.. include:: ../../Includes.txt

====================================================
Feature: #64386 - Public Content Object Registration
====================================================

See :issue:`64386`

Description
===========

A new global option is now available to register and/or extend/overwrite content objects like TEXT.
A list of all available Content Objects that was previously registered within the main ContentObjectRenderer class
is now moved to the global array `$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']` which allows for modifications
via third-party extensions.

Example: Register a new Content Object EXAMPLE in a third-party extension

.. code-block:: php

  $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['EXAMPLE'] = Acme\MyExtension\ContentObject\ExampleContentObject::class

The registered class must be a subclass of `TYPO3\CMS\Frontend\ContentObject\AbstractContentObject`.

For future autoloading mechanisms, it is encouraged to place the custom ContentObject class inside
`EXT:myextension/Classes/ContentObject/`.
