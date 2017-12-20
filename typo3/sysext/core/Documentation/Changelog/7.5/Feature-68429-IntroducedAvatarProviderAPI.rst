
.. include:: ../../Includes.txt

===============================================
Feature: #68429 - Introduced AvatarProvider API
===============================================

See :issue:`68429`

Description
===========

To make providing an avatar image for BE users more flexible an API has been
introduced so you can register AvatarProviders.
The core provides the `DefaultAvatarProvider` by default to handle the image
defined in the user settings.

When an avatar is rendered in the BE the available `AvatarProviders` are asked
if they can provide an `TYPO3\CMS\Backend\Backend\Avatar\Image` for the given
`be_users` record in the requested size. The first `TYPO3\CMS\Backend\Backend\Avatar\Image`
that gets returned is used.

Registering an avatar provider
------------------------------

An avatar provider can be registered within your `ext_localconf.php` file like this:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']['myCustomProvider'] = [
		'provider' => \MyVendor\MyExtension\AvatarProvider\CompanyAvatarProvider::class,
		'before' => ['defaultAvatarProvider']
	];

The settings are defined as:

* `provider`: The avatar provider class name, which must implement `TYPO3\CMS\Backend\Backend\Avatar\AvatarProviderInterface`.
* `before`/`after`: You can define the ordering how providers are executed. This is used to get the order in which the providers are executed.


For a new avatar provider you have to register a **new key** in `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']`.
If your avatar provider extends another one, you may only overwrite necessary settings. An example would be to
extend an existing provider and replace its registered 'provider' class with your new class name.

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']['defaultAvatarProvider']['provider'] = \MyVendor\MyExtension\AvatarProvider\CustomAvatarProvider::class;


AvatarProviderInterface
-----------------------

The AvatarProviderInterface contains only one method:

`public function getImage(array $backendUser, $size);`

The parameters are defined as:

* `$backendUser`: The record from `be_users` database table.
* `$size`: The requested size of the avatar image.

The return value of the method is expected to be an instance of `TYPO3\CMS\Backend\Backend\Avatar\Image` or NULL
when the provider can not provide an image.

An `TYPO3\CMS\Backend\Backend\Image` object has 3 properties:

* `$url`: Url of avatar image. Needs to be relative to the website root or an absolute URL.
* `$width`: The width of the image.
* `$height`: The height of the image.


.. index:: PHP-API, Backend
