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
			CHECKBOXGROUP {
				10 {
					displayName = Default
					partialPath = ContainerElements/Checkboxgroup
				}
			}
		}
	}

	settings {
		registeredElements {
				# CHECKBOXGROUP
				# Used by: frontend, wizard (not implemented right now)
				# Used ViewHelper: none
				#
				# This defines a container element.
				# @ToDo: add more details
			CHECKBOXGROUP =< .FIELDSET
			CHECKBOXGROUP {
					# partialPath
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: TRUE
					#
					# The defined partial is used to render the element.
					# The partial paths to the element are build based on the following rule:
					# {$plugin.tx_form.view.partialRootPath}/{$themeName}/@actionName/{$partialPath}.
				partialPath =< plugin.tx_form.view.elementPartials.CHECKBOXGROUP.10.partialPath

					# childrenInheritName
					# Used by: frontend
					# Overwritable by user: FALSE
					#
					# If set to 1 all child elements inherit the name of the parent element.
					# @ToDo: add more details
				childrenInheritName = 1

					# visibleInConfirmationAction
					# Used by: frontend
					# Overwritable by user: TRUE
					#
					# If set to 1 this element is displayed in the confirmation page.
					# @ToDo: add more details
				visibleInConfirmationAction = 1

					# visibleInProcessAction
					# Used by: frontend
					# Overwritable by user: TRUE
					#
					# If set to 1 this element is displayed in the mail.
					# @ToDo: add more details
				visibleInMail = 1
			}
		}
	}
}