
.. include:: /Includes.rst.txt

===================================
Feature: #66669 - BE login form API
===================================

See :issue:`66669`

Description
===========

The backend login has been completely refactored and a new API has been introduced.
The OpenID form has been extracted and is now using the new API as well and is completely independent of the central
Core classes for the first time.


Registering a login provider
----------------------------

The concept of the new backend login is based on "login providers".
A login provider can be registered within your `ext_localconf.php` file like this:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416020] = [
		'provider' => \TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider::class,
		'sorting' => 50,
		'icon-class' => 'fa-key',
		'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:login.link'
	];

The settings are defined as:

* `provider`: The login provider class name, which must implement `TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface`.
* `sorting`: The sorting is important for the ordering of the links to the possible login providers on the login screen.
* `icon-class`: The font-awesome icon name for the link on the login screen.
* `label`: The label for the login provider link on the login screen.

For a new login provider you have to register a **new key** - a unix timestamp - in `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders']`.
If your login provider extends another one, you may only overwrite necessary settings. An example would be to
extend an existing provider and replace its registered 'provider' class with your new class name.

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416020]['provider'] = CustomProviderExtendingUsernamePasswordLoginProvider::class

LoginProviderInterface
----------------------

The LoginProviderInterface contains only one method:

`public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController);`

The parameters are defined as:

* `$view`: The Fluid StandaloneView which renders the login screen. You have to set the template file and you may add variables to the view according to your needs.
* `$pageRenderer`: The PageRenderer instance provides possibility to add necessary JavaScript resources.
* `$loginController`: The LoginController instance.


The View
--------

As mentioned above, the `render` method gets the Fluid StandaloneView as first parameter.
You have to set the template path and filename using the methods of this object.
The template file must only contain the form fields, not the form-tag.
Later on, the view renders the complete login screen.

View requirements:

* The template must use the `Login`-layout provided by the Core `<f:layout name="Login">`.
* Form fields must be provided within the section `<f:section name="loginFormFields">`.


.. code-block:: html

	<f:layout name="Login" />
	<f:section name="loginFormFields">
		<div class="form-group t3js-login-openid-section" id="t3-login-openid_url-section">
			<div class="input-group">
				<input type="text" id="openid_url" name="openid_url" value="{presetOpenId}" autofocus="autofocus" placeholder="{f:translate(key: 'openId', extensionName: 'openid')}" class="form-control input-login t3js-clearable t3js-login-openid-field" />
				<div class="input-group-addon">
					<span class="fa fa-openid"></span>
				</div>
			</div>
		</div>
	</f:section>


Examples
--------

Within the Core you can find two best practice implementations:

1. EXT:backend, which implements the `UsernamePasswordLoginProvider` (the default)
2. EXT:openid, which implements the `OpenIdLoginProvider` and adds a second login option


Impact
======

All extensions which add additional fields to the login form must be updated and make use of the new BE login form API.


.. index:: PHP-API, Backend, ext:openid
