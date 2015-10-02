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
			HEADER {
				10 {
					displayName = Default
					partialPath = FlatElements/Header
				}
			}
		}
	}

	settings {
		registeredElements {
				# HEADER
				# Used by: frontend, wizard (not implemented right now)
				# Used ViewHelper: f:format.raw
				#
				# @ToDo: add more details
			HEADER {
					# partialPath
					# Used by: frontend, wizard (not implemented right now)
					# Overwritable by user: TRUE
					#
					# The defined partial is used to render the element.
					# The partial paths to the element are build based on the following rule:
					# {$plugin.tx_form.view.partialRootPath}/{$themeName}/@actionName/{$partialPath}.
				partialPath =< plugin.tx_form.view.elementPartials.HEADER.10.partialPath

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