
.. include:: ../../Includes.txt

=========================================================
Feature: #7098 - Severity-filtering for FlashMessageQueue
=========================================================

Description
===========

So far only all messages of the FlashMessageQueue could be fetched and/or
removed. With this addition it's possible to do that for a given
severity only. The existing functions get an optional $severity-parameter.

Usage:

.. code-block:: php

	FlashMessageQueue::getAllMessages($severity);
	FlashMessageQueue::getAllMessagesAndFlush($severity);
	FlashMessageQueue::removeAllFlashMessagesFromSession($severity);
	FlashMessageQueue::clear($severity);
