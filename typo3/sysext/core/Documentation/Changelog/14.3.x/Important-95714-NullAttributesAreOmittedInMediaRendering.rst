..  include:: /Includes.rst.txt

..  _important-95714-1788459022:

==================================================================
Important: #95714 - Null attributes are omitted in media rendering
==================================================================

See :issue:`95714`

Description
===========

The media file renderers

*   :php:`\TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer`
*   :php:`\TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer`
*   :php:`\TYPO3\CMS\Core\Resource\Rendering\VimeoRenderer`
*   :php:`\TYPO3\CMS\Core\Resource\Rendering\YouTubeRenderer`

now treat the values of the :php:`additionalAttributes` option the same way
Fluid's :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder` does:

*   :php:`null` and :php:`false` omit the attribute entirely
*   :php:`true` renders a boolean attribute, the name without a value
*   An empty string renders :html:`name=""`
*   Any other value renders :html:`name="value"`

Up to now, :php:`null` and :php:`false` were both rendered as :html:`name=""`.
In the Vimeo and YouTube renderers, :php:`null` additionally triggered the
deprecation notice :php:`htmlspecialchars(): Passing null to parameter #1
($string) of type string is deprecated`.

This allows leaving out an attribute conditionally, which was not possible
before:

..  code-block:: html

    <f:media file="{file}" additionalAttributes="{poster: posterUrl}" />

If :php:`posterUrl` is not set, the :html:`poster` attribute is now dropped
instead of being rendered as :html:`poster=""`.

Attributes carrying an empty string are still rendered. In HTML, :html:`muted`
and :html:`muted=""` mean the same thing, so dropping them would change the
meaning of the generated markup.

..  index:: FAL, Fluid, Frontend, ext:core
