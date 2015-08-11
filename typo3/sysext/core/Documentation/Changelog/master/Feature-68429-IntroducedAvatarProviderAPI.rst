===============================================
Feature: #68429 - Introduced AvatarProvider API
===============================================

Description
===========

To make providing a avatar image for BE users more flexible a API it introduced so you can register AvatarProviders.
The core provides the ``ImageProvider`` by default.

When an avatar is rendered in the BE the available AvatarProviders are asked if they can provide an
``TYPO3\CMS\Backend\Backend\Image`` for given ``be_user`` and requested size. The first ``TYPO3\CMS\Backend\Backend\Image``
that gets returned is used.

Registering a avatar provider
-----------------------------

A avatar provider can be registered within your ``ext_localconf.php`` file like this:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']['myCustomProvider'] = [
		'provider' => \MyVendor\MyExtension\AvatarProvider\CompanyAvatarProvider::class,
		'before' => ['imageProvider']
	];

The settings are defined as:

* ``provider``: The avatar provider class name, which must implement ``TYPO3\CMS\Backend\Backend\AvatarProviderInterface``.
* ``before``/``after``: You can define the ordering how providers are executed. This is order is used as fallback of the avatar providers. Each property must be an array of provider names.


For a new avatar provider you have to register a **new key** in ``$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']``.
If your avatar provider extends another one, you may only overwrite necessary settings. An example would be to
extend an existing provider and replace its registered 'provider' class with your new class name.

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']['imageProvider']['provider'] = \MyVendor\MyExtension\AvatarProvider\CustomImageProvider::class


AvatarProviderInterface
-----------------------

The AvatarProviderInterface contains only one method:

``public function getImage(array $backendUser, $size);``

The parameters are defined as:

* ``$backendUser``: The record of from ``be_user`` database table.
* ``$size``: The requested size of the avatar image.

The return value of the method is expected to be and ``TYPO3\CMS\Backend\Backend\Image`` instance or NULL when the
provider can not provide an image.

An ``TYPO3\CMS\Backend\Backend\Image`` object holds 3 properties:

* ``$url``: Url of avatar image. Needs to be relative to the /typo3/ folder or an absolute URL.
* ``$width``: The width of the image.
* ``$height``: The height of the image.
