plugin.tx_felogin_pi1 {
		#storagePid - where are the user records? use single value or a commaseperated list
	storagePid = {$styles.content.loginform.pid}
	recursive =

		#Template File
	templateFile = {$styles.content.loginform.templateFile}

		#baseURL for the link generation
	feloginBaseURL =

		#wrapContentInBaseClass
	wrapContentInBaseClass = 1


		#typolink-configuration for links / urls
		#parameter and additionalParams are set by extension
	linkConfig {
		target =
		ATagParams = rel="nofollow"
	}

		#preserve GET vars - define "all" or commaseperated list of GET-vars that should be included by link generation
	preserveGETvars = all


		#additional fields
	showForgotPasswordLink =
	showPermaLogin =

		# time in hours how long the link for forget password is valid
	forgotLinkHashValidTime = 12

	newPasswordMinLength = 6

	welcomeHeader_stdWrap {
		wrap = <h3>|</h3>
	}
	welcomeMessage_stdWrap {
		wrap = <div>|</div>
	}

	successHeader_stdWrap {
		wrap = <h3>|</h3>
	}
	successMessage_stdWrap {
		wrap = <div>|</div>
	}

	logoutHeader_stdWrap {
		wrap = <h3>|</h3>
	}
	logoutMessage_stdWrap {
		wrap = <div>|</div>
	}

	errorHeader_stdWrap {
		wrap = <h3>|</h3>
	}
	errorMessage_stdWrap {
		wrap = <div>|</div>
	}

	forgotHeader_stdWrap {
		wrap = <h3>|</h3>
	}
	forgotMessage_stdWrap {
		wrap = <div>|</div>
	}
	forgotErrorMessage_stdWrap {
		wrap = <div>|</div>
	}
	forgotResetMessageEmailSentMessage_stdWrap {
		wrap = <div>|</div>
	}
	changePasswordNotValidMessage_stdWrap {
		wrap = <div>|</div>
	}
	changePasswordTooShortMessage_stdWrap {
		wrap = <div>|</div>
	}
	changePasswordNotEqualMessage_stdWrap {
		wrap = <div>|</div>
	}

	changePasswordHeader_stdWrap {
		wrap = <h3>|</h3>
	}
	changePasswordMessage_stdWrap {
		wrap = <div>|</div>
	}
	changePasswordDoneMessage_stdWrap {
		wrap = <div>|</div>
    }

	cookieWarning_stdWrap {
		wrap = <p style="color:red; font-weight:bold;">|</p>
	}

	# stdWrap for fe_users fields used in Messages
	userfields {
		username {
			htmlSpecialChars = 1
			wrap = <strong>|</strong>
		}
	}

		#redirect
	redirectMode =
	redirectFirstMethod =
	redirectPageLogin =
	redirectPageLoginError =
	redirectPageLogout =

	#disable redirect with one switch
	redirectDisable =

	email_from =
	email_fromName =
	replyTo =


	# Allowed Referrer-Redirect-Domains:
	domains =

	# Show logout form direct after login
	showLogoutFormAfterLogin =

	dateFormat = Y-m-d H:i

	# Expose the information on whether or not the account for which a new password was requested exists. By default, that information is not disclosed for privacy reasons.
	exposeNonexistentUserInForgotPasswordDialog = 0
}

plugin.tx_felogin_pi1._CSS_DEFAULT_STYLE (
	.tx-felogin-pi1 label {
		display: block;
	}
)

plugin.tx_felogin_pi1._LOCAL_LANG.default {
}

plugin.tx_felogin_pi1._DEFAULT_PI_VARS {
}