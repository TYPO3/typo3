..  include:: /Includes.rst.txt

..  _breaking-107578-1759326054:

=======================================================================
Breaking: #107578 - Prepare ext:adminpanel DataProviderInterface change
=======================================================================

See :issue:`107578`

Description
===========

The `adminpanel` extension provides the interface :php:`DataProviderInterface`. It can
be used by extensions that extend the admin panel with further modules and allows storing
additional in the admin panel specific request related store.

The signature of the main interface method has been changed.

Impact
======

Extension authors may benefit from the additional argument that is hand over
with TYPO3 v14.


Affected installations
======================

Most instances are not affected by this change since there aren't many known extensions
that add features to the admin panel extension. Instances with classes implementing
interface :php:`TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface` are affected.


Migration
=========

The interface until TYPO3 v13:

.. code-block:: php

    namespace TYPO3\CMS\Adminpanel\ModuleApi;

    interface DataProviderInterface
    {
        public function getDataToStore(ServerRequestInterface $request): ModuleData;
    }

Method :php:`getDataToStore()` is called by the admin panel after the :php:`Response` has been
created by the TYPO3 core. The interface has been adapted with TYPO3 v14 to receive
the response from the calling admin panel code:

.. code-block:: php

    public function getDataToStore(ServerRequestInterface $request, ResponseInterface $response): ModuleData;

Extension authors aiming for compatibility with TYPO3 v13 and v14 in the same extension version
can modify their consumers like this:

.. code-block:: php

    public function getDataToStore(ServerRequestInterface $request, ?ResponseInterface $response = null): ModuleData

TYPO3 v13 does not hand over the second argument, so it must be nullable and extensions should not expect to
receive an instance of :php:`ResponseInterface`. TYPO3 v14 however does hand over the instance.

..  index:: PHP-API, NotScanned, ext:adminpanel
