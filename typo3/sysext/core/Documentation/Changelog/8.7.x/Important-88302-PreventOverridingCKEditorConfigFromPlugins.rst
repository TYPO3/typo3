.. include:: /Includes.rst.txt

===================================================================
Important: #88302 - Prevent overriding CKEditor config from plugins
===================================================================

See :issue:`88302`

Description
===========

Configuration from external plugins is now only set if the plugin actually
provided some. By default, the plugin name is used for any configuration
provided by an external plugin. Since the name of the internal configuration
setting can be chosen by the author of a plugin we now provide a new option
"configName" to adjust the name that should be used.

Input

.. code-block:: yaml

   editor:
     externalPlugins:
       myplugin:
         resource: "EXT:my_extension/Resources/Public/JavaScript/Contrib/plugins/myplugin/"
         route: "myroute"
         configName: "super_config"
         option1: "value1"
         option2: "value2"
         option3: "value3"

Output

.. code-block:: js

   CKEDITOR.plugins.addExternal(
      'myplugin',
      'typo3conf/ext/my_extension/Resources/Public/JavaScript/Contrib/plugins/myplugin/'
   );

.. code-block:: yaml

   editor:
     config:
       super_config:
         route: "myroute"
         routeUrl: "/typo3/index.php?route=myroute"
         option1: "value1"
         option2: "value2"
         option3: "value3"
         option3: "value3"

.. index:: JavaScript, RTE, ext:rte_ckeditor
