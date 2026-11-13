..  include:: /Includes.rst.txt

..  _important-111440-1794584649:

=================================================================
Important: #111440 - HTML transformation uses native HTML5 parser
=================================================================

See :issue:`111440`

Description
===========

The internal class :php:`\TYPO3\CMS\Frontend\Html\HtmlWorker`, which backs the
Fluid ViewHelper :html:`<f:transform.html>`, no longer parses markup with the
third-party library `masterminds/html5`. It now uses the native, spec-compliant
HTML5 parser of PHP, :php:`\Dom\HTMLDocument`, available since PHP 8.4.

Along with this, the internal class
:php:`\TYPO3\CMS\Core\DependencyInjection\CommonFactory` and the public
service :php:`Masterminds\HTML5` have been removed from the dependency
injection container, and `masterminds/html5` is no longer a direct requirement
of `typo3/cms-core`. The library is still installed as a dependency of
`typo3/html-sanitizer`, so HTML sanitizing, :typoscript:`parseFunc` and RTE
processing are not affected. Extensions that injected or fetched the service
:php:`Masterminds\HTML5` must either require `masterminds/html5` themselves
and instantiate the parser, or switch to :php:`\Dom\HTMLDocument`.

The native parser is implemented in C. In a local measurement on PHP 8.5 with
an HTML document of about 510 KB, parsing and serializing took about 8 ms
instead of about 130 ms with `masterminds/html5`. The actual gain depends on
the markup, but is in the order of magnitude of 12 to 16 times faster.

The native parser follows the WHATWG HTML parsing and serialization
algorithms, the markup is parsed as a fragment in the context of a
:html:`<div>` element. This results in the following differences:

..  list-table::
    :header-rows: 1

    *   -   Input
        -   Output (before)
        -   Output (now)
    *   -   :html:`<a href>visit</a>`
        -   :html:`<a href>visit</a>`
        -   :html:`<a href="">visit</a>`
    *   -   :html:`<custom-el/>after`
        -   :html:`<custom-el></custom-el>after`
        -   :html:`<custom-el>after</custom-el>`
    *   -   :html:`<td>cell</td>`
        -   :html:`<td>cell</td>`
        -   :html:`cell`
    *   -   :html:`<table>text<tr><td>cell</td></tr></table>`
        -   unchanged
        -   :html:`text<table><tbody><tr><td>cell</td></tr></tbody></table>`
    *   -   :html:`<svg><path d="M0 0"/></svg>`
        -   :html:`<svg><path d="M0 0" /></svg>`
        -   :html:`<svg><path d="M0 0"></path></svg>`

*   Empty attributes are serialized with an empty value, for example
    :html:`<img alt>` becomes :html:`<img alt="">` and :html:`disabled`
    becomes :html:`disabled=""`.
*   The self-closing syntax is not honoured for non-void HTML elements.
*   Table cells and rows outside of a table lose their tag, an implied
    :html:`<tbody>` is added, and misplaced content is moved in front of the
    table.
*   Node and attribute names of the ViewHelper argument `selector` are matched
    case-insensitively, :html:`A.HREF` now matches :html:`<a href="...">`.

Entities, void elements such as :html:`<br>`, the raw content of
:html:`<script>` and :html:`<style>`, comments and the `onFailure` behaviours
are unchanged.

The changes only apply where :html:`<f:transform.html>` is used. In TYPO3 Core,
these are the labels and texts of EXT:form rendered in the frontend (checkbox
labels, static text elements, the summary page, the confirmation finisher and
the default email finisher template), as well as the news on the backend login
screen and the module descriptions of the backend submodule overview. Custom
templates and extensions using the ViewHelper are affected as well and should
check their rendered output if they pass unusual or invalid markup.

..  index:: Fluid, Frontend, PHP-API, NotScanned, ext:frontend
