
.. include:: /Includes.rst.txt

============================================
Feature: #66366 - Introduced MediaViewHelper
============================================

See :issue:`66366`

Description
===========

In order to comfortably render video, audio and all other file types with a registered Renderer class (`RenderingRegistry`
introduced with #61800) in FE, the `MediaViewHelper` has been added.

The `MediaViewHelper` first checks if there is a Renderer present for the given file. If not,  it will as fallback
render a image tag. This way it is a replacement for the `ImageViewHelper` in most cases when rendering video and
audio tags.

Examples:
---------

.. code-block:: html

    <code title="Image Object">
        <f:media file="{file}" width="400" height="375" />
    </code>
    <output>
        <img alt="alt set in image record" src="fileadmin/_processed_/323223424.png" width="396" height="375" />
    </output>

    <code title="MP4 Video Object">
        <f:media file="{file}" width="400" height="375" />
    </code>
    <output>
        <video width="400" height="375" controls><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
    </output>

    <code title="MP4 Video Object with loop and autoplay option set">
        <f:media file="{file}" width="400" height="375" additionalConfig="{loop: '1', autoplay: '1'}" />
    </code>
    <output>
        <video width="400" height="375" controls loop><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
    </output>


.. index:: Fluid, FAL, Frontend
