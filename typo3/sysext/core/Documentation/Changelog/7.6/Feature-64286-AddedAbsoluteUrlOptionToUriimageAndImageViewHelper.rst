
.. include:: ../../Includes.txt

=============================================================================
Feature: #64286 - Added absolute url option to uri.image and image viewHelper
=============================================================================

See :issue:`64286`

Description
===========

The ImageViewhelper and Uri/ImageViewHelper got a new option `absolute`. With this option you are able to force
the ViewHelpers to output an absolute url.

Examples:
---------

.. code-block:: html

    <code title="ImageViewHelper">
        <f:image image="{file}" width="400" height="375" absolute="1" />
    </code>
    <output>
        <img alt="alt set in image record" src="http://www.mydomain.com/fileadmin/_processed_/323223424.png" width="400" height="375" />
    </output>

    <code title="Uri/ImageViewHelper">
        <f:uri.image image="{file}" width="400" height="375" absolute="1" />
    </code>
    <output>
        http://www.mydomain.com/fileadmin/_processed_/323223424.png
    </output>


.. index:: Fluid
