..  include:: /Includes.rst.txt

..  _important-109660-1792081075:

========================================================
Important: #109660 - GIFBUILDER rotate accepts any angle
========================================================

See :issue:`109660`

Description
===========

The :typoscript:`rotate` effect of the :typoscript:`EFFECT` GIFBUILDER object
takes any angle now, including negative and fractional ones. Rotation is
cyclic, so the angle is normalized to the same rotation within one turn:
:typoscript:`rotate = -90` rotates by 270 degrees clockwise, and
:typoscript:`rotate = 450` by 90 degrees.

Before, the angle was cut to an integer and clamped to `0` to `360`. A negative
angle did not rotate the image at all, an angle above `360` rotated it by a
full turn, and :typoscript:`rotate = 45.5` rotated by 45 degrees.

Integer angles from `0` to `360` render as before. Integrators who relied on a
negative angle leaving the image unrotated have to remove the effect instead.

In the same change, :typoscript:`blur` and :typoscript:`sharpen` stop at their
documented maximum of `99`.

..  index:: Frontend, TypoScript, ext:frontend
