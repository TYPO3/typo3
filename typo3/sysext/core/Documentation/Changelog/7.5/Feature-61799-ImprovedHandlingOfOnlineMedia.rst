
.. include:: /Includes.rst.txt

===================================================
Feature: #61799 - Improved handling of online media
===================================================

See :issue:`61799`

Description
===========

Editors can now use YouTube and Vimeo videos (online media) just like a any other file, organising them just like any
other file in the file list and selecting them in element browser to use in a CE or any other record.
Adding new online media files is done providing the URL to online media. The matching helper class will fetch the
needed metadata and supply an image that will be used as preview if available.


YouTube and Vimeo support
-------------------------

The core provides an `OnlineMediaHelper` and a `FileRenderer` class for YouTube and Vimeo.

Adding YouTube videos can be done by providing a URL in one of the following formats (with and without http(s)://):

- youtu.be/<code> # Share URL
- www.youtube.com/watch?v=<code> # Normal web link
- www.youtube.com/v/<code>
- www.youtube-nocookie.com/v/<code> # youtube-nocookie.com web link
- www.youtube.com/embed/<code> # URL form iframe embed code, can also get code from full iframe snippet

Adding Vimeo videos can be done by providing a URL in one of the following formats (with and without http(s)://):

- vimeo.com/<code> # Share URL
- player.vimeo.com/video/<code> # URL form iframe embed code, can also get code from full iframe snippet


Each renderer has some custom configuration options:


YouTubeRenderer
^^^^^^^^^^^^^^^

* `bool autoplay` default = FALSE; when set video starts immediately after loading of the page
* `int controls` default = 2; see `<https://developers.google.com/youtube/player_parameters#controls>`_
* `bool loop` default = FALSE; if set video starts over again from te beginning when finished
* `bool enablejsapi` default = TRUE; see `<https://developers.google.com/youtube/player_parameters#enablejsapi>`_
* `bool showinfo` default = FALSE; show video title and uploader before video starts playing
* `bool no-cookie` default = FALSE; use domain youtube-nocookie.com instead of youtube.com when embedding a video

Example of setting the YouTubeRenderer options with the MediaViewHelper:

.. code-block:: html

    <!-- enable js api and set no-cookie support for YouTube videos -->
    <f:media file="{file}" additionalConfig="{enablejsapi:1, 'no-cookie': true}" />


VimeoRenderer
^^^^^^^^^^^^^

* `bool autoplay` default = FALSE; when set video starts immediately after loading of the page
* `bool loop` default = FALSE; if set video starts over again from te beginning when finished
* `bool showinfo` default = FALSE; show video title and uploader before video starts playing

Example of setting the YouTubeRenderer options with the MediaViewHelper:

.. code-block:: html

    <!-- show title and uploader for YouTube and Vimeo before video starts playing -->
    <f:media file="{file}" additionalConfig="{showinfo:1}" />


Register your own online media service
--------------------------------------

For every service you need an `OnlineMediaHelper` class that implements `OnlineMediaHelperInterface` and a
`FileRenderer` class (see #61800) that implements `FileRendererInterface`. The online media helper is responsible
for translating the input given by the editor to a `onlineMediaId` that is known to the service. The renderer is
responsible for turning the `onlineMediaId` to the correct HTML output to show the media item.

The `onlineMediaId` is stored in a plain text file that only holds this ID. By giving this file a custom file extension
TYPO3 knows which `OnlineMediaHelper` and `FileRenderer` belong to it. To further tell TYPO3 what kind of
"file" (text, image, audio, video, application, other) this online media holds we also need to bind a custom mime-type to
this file extension.

With adding this custom file extension to `$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']` (see `#69543 <Feature-69543-IntroducedGLOBALSTYPO3_CONF_VARSSYSmediafile_ext.rst>`_) your custom
online media file can be used throughout the backend every where all media files are allowed.

**Example of registering your own online media file/service:**

.. code-block:: php

    // Register your own online video service (the used key is also the bind file extension name)
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']['myvideo'] = \MyCompany\Myextension\Helpers\MyVideoHelper::class;

    $rendererRegistry = \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance();
    $rendererRegistry->registerRendererClass(
        \MyCompany\Myextension\Rendering\MyVideoRenderer::class
    );

    // Register an custom mime-type for your videos
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['myvideo'] = 'video/myvideo';

    // Register your custom file extension as allowed media file
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',myvideo';


Override core Helper class with your own helper class
-----------------------------------------------------

The helper classed provided by the core use the `oEmbed` web service provided by YouTube and Vimeo to gather some basic
metadata for the provided video urls. The upside is that you do not need an API user/key to use their webservice as these
services are publicly available. But the downside is that the gathered info is kind of scarce. So if you have an API user/key
for these services, you could create an own helper class which provides more meta data.

.. code-block:: php

    // Register your own online custom youtube helper class
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']['youtube'] = \MyCompany\Myextension\Helpers\YouTubeHelper::class;


.. index:: FAL, Backend, Frontend, PHP-API, LocalConfiguration
