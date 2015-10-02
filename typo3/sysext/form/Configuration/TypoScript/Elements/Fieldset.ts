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
			FIELDSET {
				10 {
					displayName = Default
					partialPath = ContainerElements/Fieldset
				}
			}
		}
	}

	settings {
		registeredElements {
				# FIELDSET
				# Used by: frontend, wizard (not implemented right now)
				# Used ViewHelper: none
				#
				# This defines a container element.
				# @ToDo: add more details
			FIELDSET {
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
						# element specific attributes
					200 = disabled
					210 = name
				}

					# partialPath
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: TRUE
					#
					# The defined partial is used to render the element.
					# The partial paths to the element are build based on the following rule:
					# {$plugin.tx_form.view.partialRootPath}/{$themeName}/@actionName/{$partialPath}.
				partialPath =< plugin.tx_form.view.elementPartials.FIELDSET.10.partialPath

					# visibleInShowAction
					# Used by: frontend
					# Overwritable by user: TRUE
					#
					# If set to 1 this element is displayed in the form.
					# @ToDo: add more details
				visibleInShowAction = 1

					# visibleInConfirmationAction
					# Used by: frontend
					# Overwritable by user: TRUE
					#
					# If set to 1 this element is displayed in the confirmation page.
					# @ToDo: add more details
				visibleInConfirmationAction = 0

					# visibleInProcessAction
					# Used by: frontend
					# Overwritable by user: TRUE
					#
					# If set to 1 this element is displayed in the mail.
					# @ToDo: add more details
				visibleInMail = 0
			}
		}
	}
}