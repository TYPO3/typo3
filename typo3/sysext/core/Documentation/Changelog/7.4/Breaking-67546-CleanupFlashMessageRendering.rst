
.. include:: ../../Includes.txt

=======================================================================
Breaking: #67546 - Cleanup Flash message rendering in FlashMessageQueue
=======================================================================

See :issue:`67546`

Description
===========

The rendering of flash messages has changed when using the view helper.
Now the rendering output of  `\TYPO3\CMS\Core\Messaging\FlashMessageQueue::renderFlashMessages()`
is adapted to being exactly the same.


Impact
======

Extensions using the rendered output of `\TYPO3\CMS\Core\Messaging\FlashMessageQueue::renderFlashMessages()`
and in addition using HTML tags in flash messages for styling purposes will get their HTML flash message output
properly HTML encoded. Thus the HTML tags will be visible in the rendered flash message output.

Since `\TYPO3\CMS\Backend\Template\DocumentTemplate` also uses this rendering type, modules using this class
will also be affected.


Affected Installations
======================

All extensions that use modules with `\TYPO3\CMS\Backend\Template\DocumentTemplate` or are using
`\TYPO3\CMS\Core\Messaging\FlashMessageQueue::renderFlashMessages()` directly.


Migration
=========

Remove all HTML from flash messages.
