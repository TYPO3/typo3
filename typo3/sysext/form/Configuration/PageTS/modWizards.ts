mod.wizards {
	newContentElement.wizardItems {
		forms {
			show :=addToList(mailform)
			elements {
				mailform {
					iconIdentifier = content-elements-mailform
					title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_mail_title
					description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_mail_description
					tt_content_defValues {
						CType = mailform
						bodytext (
enctype = multipart/form-data
method = post
prefix = tx_form
						)
					}
				}
			}
		}
	}

	form {
		defaults {
			showTabs = elements, options, form
			tabs {
				elements {
					showAccordions = basic, predefined, content
					accordions {
						basic {
							showButtons = textline, textarea, checkbox, radio, select, fileupload, hidden, password, fieldset, submit, reset, button
						}
						predefined {
							showButtons = name, email, checkboxgroup, radiogroup
						}
						content {
							showButtons = header, textblock
						}
					}
				}

				options {
					showAccordions = legend, label, attributes, options, validation, filters, various
					accordions {
						label {
							showProperties = label
						}
						attributes {
							showProperties = accept, accept-charset, accesskey, action, alt, autocomplete, autofocus, checked, class, cols, contenteditable, contextmenu, dir, draggable, dropzone, disabled, enctype, hidden, height, id, inputmode, label, lang, list, max, maxlength, method, min, minlength, multiple, name, novalidate, pattern, placeholder, readonly, required, rows, selected, selectionDirection, selectionEnd, selectionStart, size, spellcheck, src, step, style, tabindex, text, title, translate, type, value, width, wrap
						}
						validation {
							showRules = alphabetic, alphanumeric, between, date, digit, email, equals, fileallowedtypes, filemaximumsize, fileminimumsize, float, greaterthan, inarray, integer, ip, length, lessthan, regexp, required, uri

							rules {
								alphabetic {
									showProperties = message, error, showMessage, allowWhiteSpace
								}

								alphanumeric {
									showProperties = message, error, showMessage, allowWhiteSpace
								}

								between {
									showProperties = message, error, showMessage, minimum, maximum, inclusive
								}

								date {
									showProperties = message, error, showMessage, format
								}

								digit {
									showProperties = message, error, showMessage
								}

								email {
									showProperties = message, error, showMessage
								}

								equals {
									showProperties = message, error, showMessage, field
								}

								fileallowedtypes {
									showProperties = message, error, showMessage, types
								}

								filemaximumsize {
									showProperties = message, error, showMessage, maximum
								}

								fileminimumsize {
									showProperties = message, error, showMessage, minimum
								}

								float {
									showProperties = message, error, showMessage
								}

								greaterthan {
									showProperties = message, error, showMessage, minimum
								}

								inarray {
									showProperties = message, error, showMessage, array, strict
								}

								integer {
									showProperties = message, error, showMessage
								}

								ip {
									showProperties = message, error, showMessage
								}

								length {
									showProperties = message, error, showMessage, minimum, maximum
								}

								lessthan {
									showProperties = message, error, showMessage, maximum
								}

								regexp {
									showProperties = message, error, showMessage, expression
								}

								required {
									showProperties = message, error, showMessage
								}

								uri {
									showProperties = message, error, showMessage
								}
							}
						}
						filtering {
							showFilters = alphabetic, alphanumeric, currency, digit, integer, lowercase, regexp, removexss, stripnewlines, titlecase, trim, uppercase

							filters {
								alphabetic {
									showProperties = allowWhiteSpace
								}

								alphanumeric {
									showProperties = allowWhiteSpace
								}

								currency {
									showProperties = decimalPoint, thousandSeparator
								}

								digit {
									showProperties =
								}

								integer {
									showProperties =
								}

								lowercase {
									showProperties =
								}

								regexp {
									showProperties = expression
								}

								removexss {
									showProperties =
								}

								stripnewlines {
									showProperties =
								}

								titlecase {
									showProperties =
								}

								trim {
									showProperties = characterList
								}

								uppercase {
									showProperties =
								}
							}
						}
					}
				}

				form {
					showAccordions = behaviour, prefix, attributes, postProcessor
					accordions {
						postProcessor {
							showPostProcessors = mail, redirect
							postProcessors {
								mail {
									showProperties = recipientEmail, senderEmail, subject
								}
								redirect {
									showProperties = destination
								}
							}
						}
					}
				}
			}
		}

		elements {
			form {
				accordions {
					attributes {
						showProperties = accept, action, dir, enctype, lang, method, novalidate, class, id, style, title
					}
				}
			}

			button {
				showAccordions = label, attributes
				accordions {
					attributes {
						showProperties = name, value, class, id
					}
				}
			}

			checkbox {
				showAccordions = label, attributes, validation
				accordions {
					attributes {
						showProperties = name, value, class, id, checked, required
					}
					validation {
						showRules = required
					}
				}
			}

			fieldset {
				showAccordions = legend, attributes
				accordions {
					attributes {
						showProperties = class, id
					}
				}
			}

			fileupload {
				showAccordions = label, attributes, validation
				accordions {
					attributes {
						showProperties = name, class, id, required
					}
					validation {
						showRules = required, fileallowedtypes, filemaximumsize, fileminimumsize
					}
				}
			}

			hidden {
				showAccordions = attributes
				accordions {
					attributes {
						showProperties = name, value
					}
				}
			}

			password {
				showAccordions = label, attributes, validation
				accordions {
					attributes {
						showProperties = name, placeholder, class, id, autocomplete, required
					}
					validation {
						showRules = required, equals
					}
				}
			}

			radio < .checkbox

			reset < .button
			reset {
				accordions {
					attributes {
						showProperties := removeFromList(name)
					}
				}
			}

			select {
				showAccordions = label, attributes, options, validation
				accordions {
					attributes {
						showProperties = name, size, class, id, multiple, required
					}
					validation {
						showRules = required
					}
				}
			}

			submit < .button
			submit {
				accordions {
					attributes {
						showProperties := removeFromList(name)
					}
				}
			}

			textarea {
				showAccordions = label, attributes, validation, filters
				accordions {
					attributes {
						showProperties = name, placeholder, cols, rows, class, id, required, text
					}
					filtering {
						showFilters = alphabetic, alphanumeric, lowercase, regexp, stripnewlines, titlecase, trim, uppercase
					}
					validation {
						showRules = alphabetic, alphanumeric, length, regexp, required
					}
				}
			}

			textline {
				showAccordions = label, attributes, validation, filters
				accordions {
					attributes {
						showProperties = name, placeholder, type, class, id, autocomplete, required
					}
					validation {
						showRules = alphabetic, alphanumeric, between, date, digit, email, equals, float, greaterthan, inarray, integer, ip, length, lessthan, regexp, required, uri
					}
					filtering {
						showFilters = alphabetic, alphanumeric, currency, digit, integer, lowercase, regexp, titlecase, trim, uppercase
					}
				}
			}

			name {
				showAccordions = legend, various
			}

			email < .textline

			checkboxgroup {
				showAccordions = legend, options, various, validation
				accordions {
					validation {
						showRules = required
					}
				}
			}

			radiogroup < .checkboxgroup

			header {
				showAccordions = various
			}

			textblock {
				showAccordions = various
			}
		}
	}
}