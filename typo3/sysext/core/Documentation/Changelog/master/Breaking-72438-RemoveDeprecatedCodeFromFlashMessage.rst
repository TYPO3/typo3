===========================================================
Breaking: #72438 - Remove deprecated code from FlashMessage
===========================================================

Description
===========

The deprecated :php:`render()` method has been removed.


Impact
======

Using the :php:`render()` method directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use calls to the :php:`render()` method.


Migration
=========

For FlashMessages that are displayed on top of a page you can replace the :php:`render()` method with code that enqueues the message to the FlashMessageService.

Replace
:php:`$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
  $message,
  $title,
  \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
$content .= $flashMessage->render();
`
with
:php:`$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
  $message,
  $title,
  \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
 $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
 $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
 $defaultFlashMessageQueue->enqueue($flashMessage);
 `

FlashMessges that are used as inline notification should be removed and replaced with custom HTML code.

For the core we have defined output and usage for messages:

1) FlashMessages
----------------

FlashMessages are designed to inform a user about success or failure of an action, which was **triggered** by the user.
Example: If the user deletes a record, a FlashMessage informs the user about success or failure.
This kind of information is not static, it is a temporary and volatile information and triggered by a user action.


2) Callouts (InfoBox-ViewHelper)
--------------------------------
Callouts are designed to display permanent information, a very good example is the usage in the Page-Module.
If a user opens a system folder with the page module, the callout explains: 'Hey, you try to use the page module on a sys folder, please switch to the list module'.
This ViewHelper can also be used to show some help or instruction how to use a backend module.


3) Any other information
------------------------
For any other information e.g. a list of files which has changed, must be handled in the action / view of the module or plugin. This is not a use case for a FlashMessage or Callout!
Example: Display a list of a hundred files within a FlashMessage or Callout is a bad idea, build custom markup in the view to handle this kind of message.
