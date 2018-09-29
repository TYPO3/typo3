.. include:: ../../Includes.txt

=================================================================================
Deprecation: #84196 - Backend controller actions do not receive prepared response
=================================================================================

See :issue:`84196`

Description
===========

The second argument to backend and eID controller actions has been marked as deprecated.
Controllers should create a response object implementing
:php:`Psr\Http\Message\ResponseInterface` on their own instead of relying
on a prepared response.

The signature of controller actions should look like

.. code-block:: php

    public function myAction(ServerRequestInterface $request): ResponseInterface

Impact
======

Controllers should typically instantiate one of the three core response classes
and return it:

.. code-block:: php

    public function myAction(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse('content');
        return new JsonResponse($jsonArray);
        return new RedirectResponse($url);
    }

Affected Installations
======================

Instances with extensions that register backend controllers (eg. modules) or eID
may be affected.

The dynamic scanning for not yet adapted controller actions relies on reflection and
costs some CPU cycles. If all affected extensions have been adapted, the feature toggle
:php:`simplifiedControllerActionDispatching` should be enabled. This can be managed in
the backend Settings -> Configure Installation-Wide Options module.


Migration
=========

See above code examples for typical controller actions return values and signature.

.. index:: Backend, PHP-API, NotScanned
