..  include:: /Includes.rst.txt

..  _feature-108982-1771078311:

==============================================================
Feature: #108982 - Introduce rate limiting for Extbase actions
==============================================================

See :issue:`108982`

Description
===========

Extbase now supports rate limiting for controller actions using the new PHP
attribute :php:`\TYPO3\CMS\Extbase\Attribute\RateLimit`. This feature allows
developers to restrict the number of requests a user can make to a specific
action within a given time frame.

..  note::

    Rate limiting only works for uncached Extbase actions. For cached actions,
    the TYPO3 frontend cache might return the response before the Extbase
    controller is invoked, thus bypassing the rate limiting logic.

Rate limiting is based on the client's IP address and uses Symfony's
RateLimiter component with caching framework storage.

The :php:`#[RateLimit]` attribute supports the following properties:

*   :php:`limit`: The maximum number of requests allowed (default: 5).
*   :php:`interval`: The time window for the limit (for example,
    `15 minutes`, `1 hour`) (default: `15 minutes`).
*   :php:`policy`: The rate limiting policy to use (for example,
    `sliding_window`, `fixed_window`) (default: `sliding_window`).
*   :php:`message`: An optional, localizable translation key for the error
    message shown when the limit is reached, for example
    `messages.rate_limit_message` (the translation domain, such as
    `my_extension`, is added automatically and must not be part of the key),
    or
    `LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:rate_limit_message`.

When a rate limit is exceeded, Extbase returns a response with HTTP status code
429 by default (:abbr:`Too Many Requests (Too Many Requests)`).

Usage
-----

Apply a rate limit to an Extbase action by adding a :php:`#[RateLimit]` attribute
to the action method:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Attribute\RateLimit;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        #[RateLimit(limit: 3, interval: '1 minute', message: 'message.ratelimitexceeded')]
        public function createAction(): ResponseInterface
        {
            // Business logic for creating an entity
            return $this->redirect('index');
        }
    }

PSR-14 event: BeforeActionRateLimitResponseEvent
------------------------------------------------

The new PSR-14 event
:php:`\TYPO3\CMS\Extbase\Event\BeforeActionRateLimitResponseEvent`
is dispatched when a rate limit is triggered but before the response is
returned. This allows extension developers to modify the response or perform
additional actions, such as logging, throwing a custom exception, and enqueuing
a flash message.

The following example implementation shows how to throw a custom error if a rate
limit is reached. It is handled by a configured site error handler.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ModifyRateLimitResponse.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Http\PropagateResponseException;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Extbase\Event\BeforeActionRateLimitResponseEvent;
    use TYPO3\CMS\Frontend\Controller\ErrorController;

    final readonly class MyEventListener
    {
        #[AsEventListener('my_extension/modify-rate-limit-response')]
        public function __invoke(BeforeActionRateLimitResponseEvent $event): void
        {
            $response = GeneralUtility::makeInstance(ErrorController::class)
                ->accessDeniedAction(
                    $event->getRequest(),
                    $event->getRateLimit()->message,
                );
            throw new PropagateResponseException($response, 1771077885);
        }
    }

Impact
======

Developers can now protect sensitive Extbase actions (for example, form
submissions, login attempts, and heavy API endpoints) from abuse, spam, or
brute-force attacks with minimal effort.

..  index:: Frontend, ext:extbase
