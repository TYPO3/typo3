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
			BUTTON {
				10 {
					displayName = Default
					partialPath = FlatElements/Button
				}
			}
		}
	}

	settings {
		registeredElements {
				# BUTTON
				# Used by: frontend, wizard (not implemented right now)
				# Used ViewHelper: f:form.button
				#
				# A historical element which generates a <input type="button" /> tag.
				# To be compatible it is a copy of the new element INPUTTYPEBUTTON
				# If you want to use a <button> tag you have to use
				# BUTTON =< .BUTTONTAG or use the BUTTONTAG directly
			BUTTON =< .INPUTTYPEBUTTON
			BUTTON {
				partialPath =< plugin.tx_form.view.elementPartials.INPUTTYPEBUTTON.10.partialPath
			}
		}
	}
}