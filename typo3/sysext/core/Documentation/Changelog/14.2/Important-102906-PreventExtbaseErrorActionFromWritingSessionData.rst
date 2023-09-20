..  include:: /Includes.rst.txt

..  _important-102906-1761594424:

=========================================================================
Important: #102906 - Prevent Extbase errorAction from writing session data
=========================================================================

See :issue:`102906`

Description
===========

Previously, any validation error handled implicitly by the Extbase
:php:`ActionController::errorAction()` would persist the resulting
:php:`FlashMessage` items to the user session. In cases where no session
existed, a new session would be generated and a session cookie sent to the
client. This behavior could lead to automated crawlers generating a large
number of unnecessary sessions.

When the :php:`errorAction()` is invoked (for example, due to validation
errors), flash messages are no longer persisted to the session but are
instead transferred with the corresponding :php:`ForwardResponse`.

The implementation introduces two new public methods in :php:`ForwardResponse`:

*   :php:`withFlashMessages(FlashMessage ...$flashMessages)` - Adds flash
    messages to the forward response
*   :php:`getFlashMessages()` - Retrieves flash messages from the forward
    response

Flash messages are transferred through :php:`ExtbaseRequestParameters` when
forwarding requests and are restored from :php:`ExtbaseRequestParameters` in
:php:`ActionController::initializeStateFromExtbaseRequestParameters()`.

..  hint::

    Custom code that has overridden the internal methods
    :php:`ActionController::processRequest()` or
    :php:`ActionController::forwardToReferringRequest()` may need to be
    adjusted to benefit from this change. Ensure that your custom
    implementations properly handle flash messages via :php:`ForwardResponse`
    when forwarding requests from the error action.

..  index:: Backend, Frontend, FullyScanned, ext:extbase
