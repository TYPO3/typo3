
.. include:: ../../Includes.txt

====================================================================
Important: #69846 - Have eIDs with PSR-7 without ControllerInterface
====================================================================

See :issue:`69846`

Description
===========

In order to allow the same logic as with the routing and the direct information
which method to call, implementing `ControllerInterface` is not mandatory anymore.

Remove the `implements ControllerInterface` instruction in the affected class. The former `processRequest`
method may (and should) be changed to:

.. code-block:: php

	public function anyMethodNameYouLike(ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) {
	}

Please note that the `$response` object is now passed into the method directly, thus you must not create a new object
by `$response = GeneralUtility::makeInstance(Response::class);` any more.

The eID_include registration in :file:`ext_localconf.php` must be changed in such case to

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['identifier'] = \Foo\Bar::class . '::anyMethodNameYouLike';


.. index:: PHP-API, Frontend
