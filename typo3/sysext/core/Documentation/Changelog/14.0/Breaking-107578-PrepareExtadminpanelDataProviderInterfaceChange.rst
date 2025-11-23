..  include:: /Includes.rst.txt

..  _breaking-107578-1759326054:

=======================================================================
Breaking: #107578 - Prepare EXT:adminpanel DataProviderInterface change
=======================================================================

See :issue:`107578`

Description
===========

The :composer:`typo3/cms-adminpanel` system extension provides the interface
:php:`\TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface`. It can be used by
extensions that extend the Admin Panel with custom modules and allows storing
additional request-related data in the Admin Panel–specific data store.

The signature of the interface method :php:`getDataToStore()` has changed.

Impact
======

Extension authors may benefit from the additional argument passed with TYPO3
v14, but implementations must be adjusted accordingly.

Affected installations
======================

Most installations are not affected, as few extensions extend the Admin Panel.
Instances with classes implementing
:php-short:`\TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface` are affected.

Migration
=========

**Interface until TYPO3 v13:**

..  code-block:: php

    namespace TYPO3\CMS\Adminpanel\ModuleApi;

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;

    interface DataProviderInterface
    {
        public function getDataToStore(
            ServerRequestInterface $request
        ): ModuleData;
    }

The :php:`getDataToStore()` method is called by the Admin Panel after the
:php:`Response` has been created by the TYPO3 Core.
Starting with TYPO3 v14, the method receives the :php:`ResponseInterface`
as an additional argument:

..  code-block:: php

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;

    public function getDataToStore(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ModuleData;

**Compatibility example for TYPO3 v13 and v14:**

..  code-block:: php

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;

    public function getDataToStore(
        ServerRequestInterface $request,
        ?ResponseInterface $response = null
    ): ModuleData {
        // TYPO3 v13: $response is null
        // TYPO3 v14: $response is an instance of ResponseInterface
    }

TYPO3 v13 does not pass the second argument, so it must be nullable, and
extensions should not expect to receive an instance of
:php-short:`\Psr\Http\Message\ResponseInterface`.

TYPO3 v14, however, provides the response instance automatically.

..  index:: PHP-API, NotScanned, ext:adminpanel
