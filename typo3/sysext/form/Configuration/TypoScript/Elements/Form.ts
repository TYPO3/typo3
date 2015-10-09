plugin.tx_form {
		# elementPartials
		# Used by: frontend, wizard (not implemented right now)
		# Overwritable by user: FALSE
		#
		# Defines the template selection array for the form wizard.
		# Each defined item is shown as option within the wizard.
		#
		# If there is no partialPath property in the userdefined TypoScript
		# then elementPartials.ELEMENTNAME.10.partialPath is the default.
	view {
		elementPartials {
			FORM {
				10 {
					displayName = Default
					partialPath = ContainerElements/Form
				}
			}
		}
	}

	settings {
		registeredElements {
				# FORM
				# Used by: frontend, wizard (not implemented right now)
				# Overwritable by user: TRUE
				# Used ViewHelper: f:form
				#
				# @ToDo: add more details
			FORM {
					# compatibilityMode
					# Used by: frontend
					# Overwritable by user: TRUE
					# Only evaluated for FORM element
					#
					# If set to 1 tx_form acts almost like in TYPO3 6.2.
					# This setting can be overwritten in the FORM object.
					# @ToDo: add more details
					#
					# only for FORM
				compatibilityMode = 1

					# themeName
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: TRUE
					#
					# Sets the theme name used for templating.
					# Right now there are 2 themes:
					# 	* Default: This theme provides a solid and clean foundation and should be used.
					# 	* Compatibility: This theme imitates the form layout/ behavior of TYPO3 6.2.
					# If compatibilityMode = 1 and layout is used in the user definded TypoScript
					# the theme name switches automatically to "Compatibility".
					#
					# This setting can be overwritten in the FORM object.
					# @ToDo: add more details
				themeName = Default

					# htmlAttributes
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: FALSE
					#
					# Defines allowed HTML attributes for a specific element.
					# Based on selfhtml documentation version 8.1.2 (see http://wiki.selfhtml.org/wiki/Referenz:HTML/).
					# This is needed to detect and map these strings within the user configured element definition as HTML attributes.
					# As soon as prefix-* is defined every attribute is registered automatically as HTML attribute.
				htmlAttributes {
						# generic attributes
					10 = id
					20 = class
					30 = accesskey
					40 = contenteditable
					50 = contextmenu
					60 = dir
					70 = draggable
					80 = dropzone
					90 = hidden
					100 = lang
					110 = spellcheck
					120 = style
					130 = tabindex
					140 = title
					150 = data-*
					160 = translate
						# element specific attributes
					200 = action
					210 = accept
					220 = accept-charset
					230 = autocomplete
					240 = enctype
					250 = method
					260 = name
					270 = novalidate
					280 = target
				}

					# defaultHtmlAttributeValues
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: FALSE
					#
					# The following values are automatically set if there is no entry in the user configured element.
				defaultHtmlAttributeValues {
					enctype = multipart/form-data
					method = post
				}

					# fixedHtmlAttributeValues
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: FALSE
					#
					# The following values are automatically set as attributes.
				fixedHtmlAttributeValues =

					# htmlAttributesUsedByTheViewHelperDirectly
					# Used by: frontend
					# Overwritable by user: FALSE
					#
					# Each HTML attribute defined at ".htmlAttributes" is available as array within the model.
					# This array will be added to the resulting HTML tag.
					# For this purpose the Fluid argument "additionalAttributes" of the ViewHelper is used.
					#
					# Some HTML attributes have to be assigned directly as an argument to the ViewHelper.
					# The htmlAttributesUsedByTheViewHelperDirectly map is used to remove the specified
					# HTML attribute from the "htmlAttributes" array and sets it for the model's "additionalArguments" array.
					#
					# There are two attributes which special behavior:
					# 	* disabled
					#	* readonly
					# These attributes can be assigned to the most ViewHelpers but whenever a "disabled" attribute appears
					# the browser will disable this element no matter of the value.
					# See: https://forge.typo3.org/issues/42474
					# Therefore it is held in the htmlAttributes array and the code removes this attribute if its value is set to 0.
				htmlAttributesUsedByTheViewHelperDirectly {
						# generic attributes
					10 = class
					20 = dir
					30 = id
					40 = lang
					50 = style
					60 = title
					70 = accesskey
					80 = tabindex
						# FormViewHelper
					90 = enctype
					100 = method
					110 = name
				}

					# partialPath
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: TRUE
					#
					# The defined partial is used to render the element.
					# The partial paths to the element are build based on the following rule:
					# {$plugin.tx_form.view.partialRootPath}/{$themeName}/@actionName/{$partialPath}.
				partialPath =< plugin.tx_form.view.elementPartials.FORM.10.partialPath

					# viewHelperDefaultArguments
					# Used by: frontend
					# Overwritable by user: FALSE
					#
					# This helper array is used to cast some values needed by the ViewHelpers.
					# E.g the f:form ViewHelper needs an array for the
					# argument "additionalParams". If additionalParams is not set
					# in the userdefined TypoScript this results in a NULL value in the
					# templating variable "{model.additionalArguments.additionalParams}"
					# and this throws an error. Most of the ViewHelper arguments
					# are strings and/ or can handle such NULL values but there are some
					# ViewHelpers which need some type casting.
				viewHelperDefaultArguments {
					arguments {
					}

					additionalParams {
					}

					argumentsToBeExcludedFromQueryString {
					}
				}
			}
		}
	}
}