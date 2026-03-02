..  include:: /Includes.rst.txt

..  _feature-107826-1766220191:

===================================================================
Feature: #107826 - Introduce Extbase action authorization attribute
===================================================================

See :issue:`107826`

Description
===========

A new authorization mechanism has been introduced for Extbase controller actions
using PHP attributes. Extension authors can now implement declarative access
control logic directly on action methods using the :php:`#[Authorize]` attribute.

The :php:`#[Authorize]` attribute supports multiple authorization strategies:

**Built-in checks**
- Require frontend user login via :php:`requireLogin`
- Require specific frontend user groups via :php:`requireGroups`

**Custom authorization logic**
- Dedicated authorization class (recommended for complex logic)
- Public controller method (for simple checks)

Multiple :php:`#[Authorize]` attributes can be stacked on a single action.
All authorization checks must pass for access to be granted. If any check fails,
a :php:`PropagateResponseException` is thrown with an HTTP 403 response, which
immediately stops the Extbase dispatching process.

Examples
========

Require frontend user login
----------------------------

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    namespace MyVendor\MyExtension\Controller;

    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Attribute\Authorize;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        #[Authorize(requireLogin: true)]
        public function listAction(): ResponseInterface
        {
            return $this->htmlResponse();
        }
    }

Require specific user groups
-----------------------------

The :php:`requireGroups` parameter accepts an array of frontend user group
identifiers. Groups can be specified either by their UID (recommended) or by
their title. If multiple groups are specified, the user must be a member of
**at least one** of the groups (OR logic).

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    namespace MyVendor\MyExtension\Controller;

    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Attribute\Authorize;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        // Recommended: Use group UIDs
        #[Authorize(requireGroups: [1, 2])]
        public function adminListAction(): ResponseInterface
        {
            // Only accessible to users in groups 1 or 2
            return $this->htmlResponse();
        }

        // Alternative: Use group titles (not recommended)
        #[Authorize(requireGroups: ['administrators', 'editors'])]
        public function editorListAction(): ResponseInterface
        {
            return $this->htmlResponse();
        }

        // Mixed: UIDs and titles can be combined (not recommended)
        #[Authorize(requireGroups: [1, 'editors'])]
        public function mixedListAction(): ResponseInterface
        {
            return $this->htmlResponse();
        }
    }

.. note::

   It is **strongly recommended to use group UIDs** instead of group titles.
   Group titles can be changed by editors, which would break the authorization
   logic. Group UIDs are stable and should be preferred.

Custom authorization class
--------------------------

For complex authorization logic, create a dedicated authorization class.
This class supports dependency injection and can be reused across controllers.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Authorization/MyObjectAuthorization.php

    namespace MyVendor\MyExtension\Authorization;

    use MyVendor\MyExtension\Domain\Model\MyObject;
    use TYPO3\CMS\Core\Context\Context;

    class MyObjectAuthorization
    {
        public function __construct(
            private readonly Context $context
        ) {}

        public function checkOwnership(MyObject $myObject): bool
        {
            $userAspect = $this->context->getAspect('frontend.user');
            return $myObject->getOwner()->getUid() === $userAspect->get('id');
        }
    }

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    namespace MyVendor\MyExtension\Controller;

    use MyVendor\MyExtension\Authorization\MyObjectAuthorization;
    use MyVendor\MyExtension\Domain\Model\MyObject;
    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Attribute\Authorize;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        #[Authorize(callback: [MyObjectAuthorization::class, 'checkOwnership'])]
        public function editAction(MyObject $myObject): ResponseInterface
        {
            $this->view->assign('myObject', $myObject);
            return $this->htmlResponse();
        }

        #[Authorize(callback: [MyObjectAuthorization::class, 'checkOwnership'])]
        public function deleteAction(MyObject $myObject): ResponseInterface
        {
            // Delete the object
            return $this->htmlResponse();
        }
    }

Public controller method
-------------------------

For simple checks, a public controller method can be used as callback.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    namespace MyVendor\MyExtension\Controller;

    use MyVendor\MyExtension\Domain\Model\MyObject;
    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Core\Context\Context;
    use TYPO3\CMS\Extbase\Attribute\Authorize;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        public function __construct(
            private readonly Context $context
        ) {}

        #[Authorize(callback: 'checkOwnership')]
        public function editAction(MyObject $myObject): ResponseInterface
        {
            $this->view->assign('myObject', $myObject);
            return $this->htmlResponse();
        }

        public function checkOwnership(MyObject $myObject): bool
        {
            $userAspect = $this->context->getAspect('frontend.user');
            return $myObject->getOwner()->getUid() === $userAspect->get('id');
        }
    }

Combining multiple authorization checks
----------------------------------------

Multiple :php:`#[Authorize]` attributes can be stacked. All checks must pass.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    namespace MyVendor\MyExtension\Controller;

    use MyVendor\MyExtension\Authorization\MyObjectAuthorization;
    use MyVendor\MyExtension\Domain\Model\MyObject;
    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Attribute\Authorize;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class MyController extends ActionController
    {
        #[Authorize(requireLogin: true)]
        #[Authorize(requireGroups: [1, 2])]
        #[Authorize(callback: [MyObjectAuthorization::class, 'checkOwnership'])]
        public function editAction(MyObject $myObject): ResponseInterface
        {
            // Only accessible to logged-in users in groups 1 or 2 who own the object
            return $this->htmlResponse();
        }
    }

Authorization checks can also be combined within a single attribute:

..  code-block:: php

    #[Authorize(requireLogin: true, requireGroups: [1, 2])]
    public function adminAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

Customizing the authorization denied response
----------------------------------------------

By default, the authorization check will throw a
:php:`\TYPO3\CMS\Core\Http\PropagateResponseException` with a HTTP 403 response.
This response can be handled by the TYPO3 page error handler configured in
site settings.

The PSR-14 event :php:`BeforeActionAuthorizationDeniedEvent` can be used to
provide a custom PSR-7 response, which will be returned by Extbase.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/CustomAuthorizationResponseListener.php

    namespace MyVendor\MyExtension\EventListener;

    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use TYPO3\CMS\Extbase\Authorization\AuthorizationFailureReason;
    use TYPO3\CMS\Extbase\Event\Mvc\BeforeActionAuthorizationDeniedEvent;

    final class CustomAuthorizationResponseListener
    {
        public function __construct(
            private readonly ResponseFactoryInterface $responseFactory,
            private readonly StreamFactoryInterface $streamFactory
        ) {}

        public function __invoke(BeforeActionAuthorizationDeniedEvent $event): void
        {
            // Customize response based on failure reason
            $message = match ($event->getFailureReason()) {
                AuthorizationFailureReason::NOT_LOGGED_IN => 'Please log in to access this page',
                AuthorizationFailureReason::MISSING_GROUP => 'You do not have permission to access this page',
                AuthorizationFailureReason::CALLBACK_DENIED => 'Access to this resource is denied',
            };

            $response = $this->responseFactory->createResponse()
                ->withHeader('Content-Type', 'text/html; charset=utf-8')
                ->withStatus(403)
                ->withBody($this->streamFactory->createStream($message));

            $event->setResponse($response);
        }
    }

Security Considerations
=======================

.. warning::

   When using the :php:`BeforeActionAuthorizationDeniedEvent` event:

   - **Do not perform state changes** or modify domain objects in the event listener.
     The authorization check happens before the action is executed, and any state
     changes could lead to inconsistent data.

   - **Do not use Extbase persistence** (e.g., repository operations, persist calls)
     in the event listener, as this may result in unintended side effects.

   - **Custom PSR-7 responses should only be used for uncached Extbase actions**.
     For cached actions, the custom response may be cached and served to all users,
     regardless of their authorization status. Always ensure proper cache configuration
     when customizing authorization responses.

Impact
======

Extension authors can now implement secure, declarative authorization checks
for Extbase controller actions using the :php:`#[Authorize]` attribute.

..  index:: PHP-API, ext:extbase
