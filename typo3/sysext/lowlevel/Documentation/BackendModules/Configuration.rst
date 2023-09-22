.. include:: /Includes.rst.txt

.. _module-configuration:

==============================
Configuration
==============================

The Configuration module allows integrators to view and validate the global
configuration of TYPO3. The module displays all relevant global variables such
as TYPO3_CONF_VARS, TCA and many more, in a tree format which is easy to browse
through. Over time this module got extended to also display the configuration of
newly introduced features like the middleware stack or the event listeners.

.. include:: /Images/AutomaticScreenshots/Modules/Configuration.rst.txt

.. rst-class:: bignums-attention

1.  Access this module in the TYPO3 backend under
    :guilabel:`System > Configuration`.

2.  Select the desired configuration entry in the upper menu bar.

3.  To find a configuration setting quickly enter a phrase in the search box.

    Is is also possible to use a regular expression for the search phrase.
    Click on the dropdown box and enable the :guilabel:`Use regular expression`
    checkbox.

4.  The configuration tree of the selected entry is displayed.

    Expand and collapse the settings with clicking on the triangle.

The Configuration module displays various configuration settings:

- :ref:`Global configuration <t3coreapi:typo3ConfVars>` (:php:`$GLOBALS['TYPO3_CONF_VARS']`)
- :doc:`Table configuration array <t3tca:Index>` (:php:`$GLOBALS['TCA']`)
- :ref:`Registered services <t3coreapi:services>` (:php:`$GLOBALS['T3_SERVICES']`)
- :doc:`Skinning styles <t3skinning:Index>` (:php:`$GLOBALS['TBE_STYLES']`)
- :ref:`User settings configuration <t3coreapi:user-settings>` (:php:`$GLOBALS['TYPO3_USER_SETTINGS']`)
- :ref:`Table permissions by page type <t3coreapi:page-types-intro>`
- :ref:`User settings <t3coreapi:be-user-configuration>` (:php:`$GLOBALS['BE_USER']->uc`)
- :ref:`User TSconfig <t3tsconfig:usertsconfig>` (:php:`$GLOBALS['BE_USER']->getTSConfig()`)
- :ref:`Backend Routes <t3coreapi:backend-routing>`
- :ref:`Backend Modules <t3coreapi:backend-modules>`
- :ref:`HTTP Middlewares (PSR-15) <t3coreapi:request-handling>`
- :ref:`Sites: TCA configuration <t3coreapi:sitehandling>`
- :ref:`Sites: YAML configuration <t3coreapi:sitehandling>`
- :ref:`Event listeners (PSR-14) <t3coreapi:EventDispatcher>`
- :ref:`MFA providers <t3coreapi:multi-factor-authentication>`
- :ref:`Soft Reference Parsers <t3coreapi:soft-references>`
- :ref:`Form: YAML Configuration <ext_form:concepts-configuration>` (with installed :doc:`Form system extension <ext_form:Index>`)
- `Backend Toolbar Items`
- :ref:`Symfony Expression Language Providers <t3coreapi:sel-ts-registering-new-provider-within-extension>`
- :ref:`Reactions <ext_reactions:reactions-overview>` (with installed :doc:`Reactions system extension <ext_reactions:Index>`)
- :ref:`Content Security Policy Mutations <t3coreapi:content-security-policy>`


Extending the Configuration module
==================================

The Configuration module can be extended by third-party extensions. Have a look
into the :ref:`t3coreapi:config-module` chapter in TYPO3 Explained.
