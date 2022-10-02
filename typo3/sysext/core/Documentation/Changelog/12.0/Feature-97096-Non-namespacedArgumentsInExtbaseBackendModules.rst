.. include:: /Includes.rst.txt

.. _feature-97096:

=====================================================================
Feature: #97096 - Non-namespaced arguments in Extbase backend modules
=====================================================================

See :issue:`97096`

Description
===========

Extbase plugins and backend modules traditionally use the plugin / module
namespace to prefix their GET parameters and form data. In the frontend context,
this makes sense, as multiple plugins may reside on a page. In the backend,
however, an Extbase module is responsible for rendering a complete view.
Therefore, the namespacing of arguments has been disabled, making URLs easier
to read, more in line with non-Extbase modules and allowing Extbase modules
to directly access outside information like the `id` parameter handed over
by the page tree for example.

To allow Extbase modules to configure this behaviour, the Extbase feature
flag :typoscript:`enableNamespacedArgumentsForBackend` can be set in the module
configuration, turning the namespacing off or on.

Impact
======

Extbase will by default build and react to backend module links without paying
attention to the namespace of the parameters.

A link may look like this:

:samp:`https://example.org/typo3/module/web/BeuserTxBeuser?action=groups&controller=BackendUser`

If a module explicitly wants to keep using the namespaced version of the arguments,
the feature flag can be set:

..  code-block:: typoscript
    :caption: EXT:my_extension/ext_typoscript_setup.typoscript

    module.tx_myextension_somemodule {
        features {
            enableNamespacedArgumentsForBackend = 1
        }
    }

.. index:: Backend, PHP-API, ext:extbase
