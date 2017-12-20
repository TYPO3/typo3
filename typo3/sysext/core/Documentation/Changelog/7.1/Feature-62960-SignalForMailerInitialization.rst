
.. include:: ../../Includes.txt

==================================================
Feature: #62960 - Signal for mailer initialization
==================================================

See :issue:`62960`

Description
===========

This signal allows for additional processing upon initialization of a mailer object,
e.g. registering a Swift mailer plugin.

Registering the signal:

::

	$signalSlotDispatcher = \\TYPO3\\CMS\\Core\\Utility\\GeneralUtility::makeInstance(\\TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher::class);
	$signalSlotDispatcher->connect(
		\\TYPO3\\CMS\\Core\\Mail\\Mailer::class,
		'postInitializeMailer',
		\\Vendor\\Package\\Slots\\MailerSlot::class,
		'registerPlugin'
	);

..

The slot class:

::

	<?php
	namespace Vendor\\Package\\Slots;

	use TYPO3\\CMS\\Core\\Mail\\Mailer;

	class MailerSlot {
		/**
		 * @param Mailer $mailer
		 * @return void
		 */
		public function registerPlugin(Mailer $mailer) {
			// Processing here
		}
	}

..

Impact
======

Extensions may now perform arbitrary processing for every mail.


.. index:: PHP-API
