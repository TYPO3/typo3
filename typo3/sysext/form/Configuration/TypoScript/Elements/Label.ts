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
				# special definitions for internal usage
			LABEL {
				10 {
					displayName = Default
					partialPath = AdditionalElements/Label
				}
			}
		}
	}
}