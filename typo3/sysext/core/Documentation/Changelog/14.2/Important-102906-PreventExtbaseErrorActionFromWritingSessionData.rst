..  include:: /Includes.rst.txt

..  _important-102906-1761594424:

=========================================================================
Important: #102906 - Prevent Extbase errorAction from writing session data
=========================================================================

See :issue:`102906`

Description
===========

Previously, validation errors handled implicitly by the Extbase
:php:`ActionController::errorAction()` persisted the resulting
:php-short:`\TYPO3\CMS\Core\Messaging\FlashMessage` items to the user session.
If no session existed, a new session was generated and a session
cookie was sent to the client. This behavior could lead to automated crawlers
generating a large number of unnecessary sessions.

When :php:`errorAction()` is invoked (for example, due to validation
errors), flash messages are no longer persisted to the session but are
instead transferred with the corresponding
:php-short:`\TYPO3\CMS\Extbase\Http\ForwardResponse`.

The implementation introduces two new public methods in
:php:`\TYPO3\CMS\Extbase\Http\ForwardResponse`:

*   :php:`withFlashMessages(FlashMessage ...$flashMessages)` - Adds flash
    messages to the forward response
*   :php:`getFlashMessages()` - Retrieves flash messages from the forward
    response

Flash messages are transferred through
:php-short:`\TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters` when
forwarding requests and are restored from `ExtbaseRequestParameters` in
:php:`ActionController::initializeStateFromExtbaseRequestParameters()`.

..  hint::

    Custom code that overrides the internal methods
    :php:`ActionController::processRequest()` or
    :php:`ActionController::forwardToReferringRequest()` may need to be
    adjusted to benefit from this change. Ensure that your custom
    implementations properly handle flash messages via
    :php:`\TYPO3\CMS\Extbase\Http\ForwardResponse` when forwarding
    requests from the error action.

..  index:: Backend, Frontend, FullyScanned, ext:extbase
