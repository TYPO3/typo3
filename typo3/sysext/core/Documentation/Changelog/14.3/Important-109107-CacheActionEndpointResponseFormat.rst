..  include:: /Includes.rst.txt

..  _important-109107-1772108218:

=======================================================================
Important: #109107 - Cache action endpoints should return JSON response
=======================================================================

See :issue:`109107`

Description
===========

AJAX endpoints registered as custom cache actions via
:php:`\TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent` should
return a JSON response containing :php:`success`, :php:`title`, and
:php:`message` fields.

The clear-cache toolbar now treats a missing or non-:php:`false` :php:`success`
value as a successful operation and falls back to generic notification labels
when :php:`title` or :php:`message` are absent. While this keeps older
endpoints working without changes, providing explicit values gives users
meaningful, context-specific feedback and ensures error conditions are surfaced
correctly.

..  hint::

    Update any custom cache action endpoint to return a structured JSON
    response:

    .. code-block:: php

       use TYPO3\CMS\Core\Http\JsonResponse;

       // Success
       return new JsonResponse([
           'success' => true,
           'title'   => $languageService->sL('myext.locallang:notification.success.title'),
           'message' => $languageService->sL('myext.locallang:notification.success.message'),
       ]);

       // Failure
       return new JsonResponse([
           'success' => false,
           'title'   => $languageService->sL('myext.locallang:notification.error.title'),
           'message' => $languageService->sL('myext.locallang:notification.error.message'),
       ]);

..  index:: Backend, PHP-API, ext:backend, NotScanned
