.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _editing-language-marks:

Editing language marks
^^^^^^^^^^^^^^^^^^^^^^


.. _case-1-the-author-highlights-a-part-of-a-text-node-and-selects-a-language:

Case 1: The author highlights a part of a text node and selects a language
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Highlightedarea:

<p>Semantisches Markupmindert Barrieren.</p>

Result in markup with cursor position:

<p>Semantisches <span lang="en">Markup</span>\|mindert Barrieren.</p>


.. _case-2-the-author-highlights-the-whole-text-node-inside-an-inline-element-and-selects-a-language:

Case 2: The author highlights the whole text node inside an inline element and selects a language
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""


.. _a-toadd-the-lang-attribute:

a) toadd the lang attribute
"""""""""""""""""""""""""""

Highlighted area:

<cite>New York Times</cite>

Result in markup with cursor position:

<cite lang="en">New York Times</cite>\|


.. _b-tochange-the-value-of-the-lang-attribute:

b) tochange the value of the lang attribute
"""""""""""""""""""""""""""""""""""""""""""

Highlighted area:

<cite lang="en">Le Monde</cite>

Result in markup with cursor position:

<cite lang="fr">Le Monde</cite>\|


.. _c-todelete-the-lang-attribute-no-language:

c) todelete the lang attribute (No language)
""""""""""""""""""""""""""""""""""""""""""""

Highlighted area:

<cite lang="en">Die Zeit</cite>

Result in markup with cursor position:

<cite>Le Monde</cite>\|

In case the inline element in question isa span thatdoes not have any
other attributes, the span element will be removed.


.. _case-3-the-author-highlights-a-complete-element-node-via-the-status-bar-and-selects-a-language:

Case 3: The author highlights a complete element node via the status bar and selects a language
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Highlighted area:

<cite>New York Times</cite>

Result in markup with cursor position:

<cite lang="en">New York Times</cite>\|


.. _case-4-nothing-is-highlighted-and-the-author-selects-a-language:

Case 4: Nothing is highlighted and the author selects a language
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""


.. _case-4-a-to-add-the-lang-attribute:

a) to add the lang attribute
""""""""""""""""""""""""""""

Cursor position:

<p>Die <cite>New \|York Times</cite> titelte ….</p>

The lang attribute is set in the direct parent element node.Result in
markup with cursor position:

<p>Die <cite lang="en">New\|York Times</cite> titelte ….</p>


.. _case-4-b-tochange-the-attribute-value:

b) tochange the attribute value
"""""""""""""""""""""""""""""""

Cursor position:

<p><cite lang="fr">New \|York Times</cite> titelte ….</p>

Result in markup with cursor position:The value of the lang attribute
of the direct parent element node is changed.

<p><cite lang="en">New \|York Times</cite> titelte ….</p>


.. _case-4-c-toremove-the-lang-attribute:

c) toremove the lang attribute
""""""""""""""""""""""""""""""

Cursor position:

<p><cite lang="fr">Die \|Zeit</cite> titelte ….</p>

Result in markup with cursor position:The lang attribute gets removed.

<p><cite>Die \|Zeit</cite> titelte ….</p>

In case of a span element that has no other attributes the span
element will be removed.


.. _case-5-nothing-is-highlighted-the-cursor-is-directly-inside-a-block-element-and-the-author-selects-a-language:

Case 5: Nothing is highlighted, the cursor is directly inside a block element and the author selects a language
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

<p>Beware \|the dog!</p>

The lang attribute is set for the parent element:

<p lang="en">Beware\|the dog!</p>


.. _case-6-the-author-selects-a-block-element-via-the-status-bar:

Case 6: The author selects a block element via the status bar
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""


.. _case-6-a-to-add-the-lang-attribute:

a) to add the lang attribute
""""""""""""""""""""""""""""

Highlightedblock:

<blockquote><p>Did you ever experience a <span lang="fr">dejà
vu</span> effect?</p></blockquote>

Resulting markup with cursor position:

<blockquote lang="en"><p>Have you ever had a <span lang="fr">dejà
vu</span> effect.</p></blockquote>\|


.. _case-6-b-to-change-the-value-of-the-lang-attribute:

b) to change the value of the lang attribute
""""""""""""""""""""""""""""""""""""""""""""

Highlighted block:

<blockquote lang="en"><p>Hattest du jemals einen <span lang="fr">dejà
vu</span>-Effekt?</p></blockquote>

Resulting markup with cursor position:

<blockquote lang="de"><p>Hattest du je einen <span lang="fr">dejà
vu</span>-Effekt.</p></blockquote>\|


.. _case-6-c-to-remove-the-lang-attribute:

c) to remove the lang attribute
"""""""""""""""""""""""""""""""

Highlighted block:

<blockquote lang="de"><p>Hattest du jemals einen <span lang="fr">dejà
vu</span>-Effekt?</p></blockquote>

Resulting markup with cursor position:

<blockquote><p>Hattest du je einen <span lang="fr">dejà
vu</span>-Effekt.</p></blockquote>\|


.. _case-7-the-author-highlights-multiple-block-elements-and-selects-a-language:

Case 7: The author highlights multiple block elements and selects a language
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

<p>Rats!</p>

<p>They fought the dogs and killed the cats,</p>

<p>And bit the babies in the cradles,</p>

<p>And ate the cheeses out of the vats,</p>

<p>And licked the soup from the cooks' ownladles,</p>

Resulting markup with cursor position:

<plang="en">Rats!</p>

<plang="en">They fought the dogs and killed the cats,</p>

<plang="en">And bit the babies in the cradles,</p>

<plang="en">And ate the cheeses out of the vats,</p>

<plang="en">And licked the soup from the cooks' own\|ladles,</p>

