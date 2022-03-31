
.. include:: /Includes.rst.txt

================================================================
Feature: #71251 - Add FlashMessage support in ModuleTemplate API
================================================================

See :issue:`71251`

Description
===========

Flash messages have different queues, depending on in which context they are
enqueued. The FlashMessageService defaults the queue to `core.template.flashMessages`,
Extbase defaults the queue to `extbase.flashmessages .$randomPluginQueue`.

Support for flash messages in ModuleTemplate has been added to enqueue flash messages automatically
in the correct queue identifier.


Impact
======

Flash messages can be enqueued by the following code:


.. code-block:: php

	$this->moduleTemplate->addFlashMessage('I am a message body', 'Title', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK, true);


.. index:: PHP-API, Backend
