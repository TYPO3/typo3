..  include:: /Includes.rst.txt

..  _important-106584-1787588711:

========================================================================
Important: #106584 - Asset and image ViewHelper arguments are registered
========================================================================

See :issue:`106584`

Description
===========

The ViewHelpers :html:`<f:asset.css>`, :html:`<f:asset.script>` and
:html:`<f:image>` read the arguments :html:`href`, :html:`src` and :html:`alt`
from the tag attributes instead of registering them. They were therefore
missing from the generated XSD schema and from the ViewHelper reference, which
made IDEs report them as unknown attributes.

All three are now registered as optional string arguments. Templates do not
need to be changed, and the arguments keep behaving as before.

One detail changes in the rendered markup of :html:`<f:image>`: an explicitly
given :html:`alt` attribute used to be written as the first attribute of the
:html:`<img>` tag, because unregistered arguments are added to the tag builder
before the ViewHelper renders. It is now written after :html:`src`,
:html:`width` and :html:`height`, which is the position the fallback to the
"alternative" metadata property has always used:

..  code-block:: html

    <!-- before -->
    <img alt="alternative text" src="image.jpg" width="400" height="300" />

    <!-- after -->
    <img src="image.jpg" width="400" height="300" alt="alternative text" />

The attribute order carries no meaning in HTML, so this only affects tests and
tooling that compare rendered markup as a string.

..  index:: Fluid, ext:fluid, NotScanned
