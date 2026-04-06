..  include:: /Includes.rst.txt

..  _important-109493-1788970444:

========================================================================
Important: #109493 - Messages in the About module respect their severity
========================================================================

See :issue:`109493`

Description
===========

Messages added to the "About" backend module through the PSR-14 event
:php:`\TYPO3\CMS\Backend\Controller\Event\ModifyGenericBackendMessagesEvent`
were always rendered as an error infobox, no matter which severity the
:php:`FlashMessage` carried. The module now renders each message with its own
severity.

Extensions that add messages to that event are affected: The severity of
:php:`FlashMessage` defaults to :php:`ContextualFeedbackSeverity::OK`, so a
message created without an explicit severity - as in the example that came
with the event in TYPO3 v12, see :ref:`feature-96899` - is now rendered as a
green "OK" infobox instead of a red "error" one.

Event listeners should therefore state the intended severity explicitly:

..  code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\ModifyGenericBackendMessagesEvent;
    use TYPO3\CMS\Core\Messaging\FlashMessage;
    use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

    final class MyEventListener
    {
        public function __invoke(ModifyGenericBackendMessagesEvent $event): void
        {
            $event->addMessage(new FlashMessage(
                'Something needs attention',
                '',
                ContextualFeedbackSeverity::WARNING
            ));
        }
    }

..  index:: Backend, PHP-API, ext:backend, NotScanned
