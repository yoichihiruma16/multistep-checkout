/**
 * Form Summary JavaScript
 * Handles form data capture, display, and step navigation with validation
 */
(function () {
	'use strict';

	/**
	 * Form Summary Handler
	 */
	const FormSummary = {

		// Flag to track if repeater counter has been initialized
		repeaterCounterInitialized: false,

		// Flag to track if auto-fill has been initialized
		autoFillInitialized: false,

		// Debounce timer
		repeaterUpdateTimer: null,

		/**
		 * Initialize
		 */
		init: function () {
			this.initializeSteps();
			this.bindEvents();
			this.setupObserver();
			this.initialCapture();
			this.initAutoFill();
			this.setupSubmitHandler();
		},

		/**
		 * Initialize auto-fill functionality
		 */
		initAutoFill: function () {
			const self = this;

			document.addEventListener('change', function (e) {
				const changedElement = e.target;
				
				if (changedElement.id === 'field_abboneehouder-0' || 
					changedElement.closest('#frm_field_178_container') ||
					changedElement.closest('.same-as-abonee')) {
					
					if (changedElement.type === 'checkbox' && changedElement.checked) {
						self.copyAbonneeToContactpersoon();
					} else if (changedElement.type === 'checkbox' && !changedElement.checked) {
						self.clearContactpersoonFields();
					}
				}
				
				if (changedElement.closest('.same-as-contact')) {
					if (changedElement.type === 'radio' && changedElement.value === 'Ja') {
						self.copyContactpersoonToAlarmopvolgers();
					} else if (changedElement.type === 'radio' && changedElement.value === 'Nee') {
						self.clearAlarmopvolgersFields();
					}
				}
			});
		},

		/**
		 * Copy values from Abonnee step to Contactpersoon step
		 */
		copyAbonneeToContactpersoon: function () {
			try {
				const abonneeForm = this.findFormByTitle('Abonnee');
				if (!abonneeForm) { console.warn('Abonnee form not found'); return; }

				const contactpersoonForm = this.findFormByTitle('Contactpersoon');
				if (!contactpersoonForm) { console.warn('Contactpersoon form not found'); return; }

				this.copyFieldByLabel(abonneeForm, contactpersoonForm, 'Aanhef', 'radio');
				this.copyFieldByLabel(abonneeForm, contactpersoonForm, 'Naam', 'name');
				this.copyFieldByLabel(abonneeForm, contactpersoonForm, 'E-mailadres', 'email');
				this.copyPhoneField(abonneeForm, contactpersoonForm);
			} catch (error) {
				console.error('Error copying Abonnee data:', error);
			}
		},

		/**
		 * Clear Contactpersoon fields
		 */
		clearContactpersoonFields: function () {
			try {
				const contactpersoonForm = this.findFormByTitle('Contactpersoon');
				if (!contactpersoonForm) return;

				this.clearFieldByLabel(contactpersoonForm, 'Aanhef', 'radio');
				this.clearFieldByLabel(contactpersoonForm, 'Naam', 'name');
				this.clearFieldByLabel(contactpersoonForm, 'E-mailadres', 'email');
				this.clearFieldByLabel(contactpersoonForm, 'Telefoonnummer', 'text');
			} catch (error) {
				console.error('Error clearing Contactpersoon fields:', error);
			}
		},

		/**
		 * Copy values from Contactpersoon to Alarmopvolgers
		 */
		copyContactpersoonToAlarmopvolgers: function () {
			try {
				const contactpersoonForm = this.findFormByTitle('Contactpersoon');
				if (!contactpersoonForm) { console.warn('Contactpersoon form not found'); return; }

				const alarmopvolgersForm = this.findFormByTitle('Alarmopvolgers');
				if (!alarmopvolgersForm) { console.warn('Alarmopvolgers form not found'); return; }

				this.copyFieldByLabel(contactpersoonForm, alarmopvolgersForm, 'Aanhef', 'radio');
				this.copyFieldByLabel(contactpersoonForm, alarmopvolgersForm, 'Naam', 'name');
				this.copyFieldByLabel(contactpersoonForm, alarmopvolgersForm, 'Telefoonnummer', 'text');
			} catch (error) {
				console.error('Error copying Contactpersoon to Alarmopvolgers:', error);
			}
		},

		/**
		 * Clear Alarmopvolgers fields
		 */
		clearAlarmopvolgersFields: function () {
			try {
				const visibleStep = document.querySelector('.a-item:not([style*="display: none"])');
				if (!visibleStep) { console.warn('No visible step found'); return; }

				const aanhefRadios = visibleStep.querySelectorAll('input[type=radio][id*="field_29ib6"]');
				aanhefRadios.forEach(radio => radio.checked = false);

				const naamFirstInput  = visibleStep.querySelector('input[id*="field_nwipl_first"]');
				const naamMiddleInput = visibleStep.querySelector('input[id*="field_nwipl_middle"]');
				const naamLastInput   = visibleStep.querySelector('input[id*="field_nwipl_last"]');
				if (naamFirstInput)  naamFirstInput.value  = '';
				if (naamMiddleInput) naamMiddleInput.value = '';
				if (naamLastInput)   naamLastInput.value   = '';

				const phoneInput = visibleStep.querySelector('input[id*="field_os7by"]');
				if (phoneInput) phoneInput.value = '';
			} catch (error) {
				console.error('Error clearing Alarmopvolgers fields:', error);
			}
		},

		/**
		 * Copy phone field from Abonnee (vast) to Contactpersoon
		 */
		copyPhoneField: function (sourceForm, targetForm) {
			try {
				let sourcePhone = this.findFieldByLabel(sourceForm, 'Telefoonnummer (vast)');
				
				if (!sourcePhone) {
					const allFields = sourceForm.querySelectorAll('.frm_form_field');
					for (let field of allFields) {
						const label = field.querySelector('.frm_primary_label');
						if (label && label.textContent.toLowerCase().includes('vast')) {
							sourcePhone = field;
							break;
						}
					}
				}

				if (!sourcePhone) { console.warn('Source phone field (vast) not found'); return; }

				const targetPhone = this.findFieldByLabel(targetForm, 'Telefoonnummer');
				if (!targetPhone) { console.warn('Target Telefoonnummer not found'); return; }

				const sourceInput = sourcePhone.querySelector('input[type=tel], input[type=text]');
				const targetInput = targetPhone.querySelector('input[type=tel], input[type=text]');
				
				if (sourceInput && targetInput) {
					targetInput.value = sourceInput.value;
					targetInput.dispatchEvent(new Event('input',  { bubbles: true }));
					targetInput.dispatchEvent(new Event('change', { bubbles: true }));
				}
			} catch (error) {
				console.error('Error copying phone field:', error);
			}
		},

		/**
		 * Find field container by label text
		 */
		findFieldByLabel: function (form, labelText) {
			const fieldContainers = form.querySelectorAll('.frm_form_field');
			
			for (let container of fieldContainers) {
				const label = container.querySelector('.frm_primary_label');
				if (label) {
					const labelContent = label.textContent.trim().toLowerCase();
					const searchText   = labelText.toLowerCase();
					if (labelContent.includes(searchText)) {
						return container;
					}
				}
			}
			
			return this.findFieldByPattern(form, labelText);
		},

		/**
		 * Fallback: Find field by pattern matching on field IDs
		 */
		findFieldByPattern: function (form, labelText) {
			const patterns = {
				'aanhef'       : 'field_.*aanhef|field_2wnsd|field_wgadv|field_29ib6',
				'naam'         : 'field_.*name|field_.*naam|field_f9iyg|field_g5aeg|field_nwipl',
				'e-mailadres'  : 'field_.*email|field_.*mail|field_uq2ba|field_d82tp',
				'email'        : 'field_.*email|field_.*mail|field_uq2ba|field_d82tp',
				'telefoonnummer': 'field_.*phone|field_.*tel|field_tz4l3|field_2hsej|field_os7by'
			};
			
			const searchKey = labelText.toLowerCase().replace(/\s+/g, '').replace(/[()]/g, '');
			const pattern   = patterns[searchKey];
			if (!pattern) return null;
			
			const regex    = new RegExp(pattern, 'i');
			const allInputs = form.querySelectorAll('input');
			
			for (let input of allInputs) {
				if (regex.test(input.id)) {
					return input.closest('.frm_form_field');
				}
			}
			
			return null;
		},

		/**
		 * Copy field value by label (universal method)
		 */
		copyFieldByLabel: function (sourceForm, targetForm, labelText, fieldType) {
			try {
				const sourceField = this.findFieldByLabel(sourceForm, labelText);
				const targetField = this.findFieldByLabel(targetForm, labelText);
				
				if (!sourceField) { console.warn('Source field not found:', labelText); return; }
				if (!targetField) { console.warn('Target field not found:', labelText); return; }
				
				if      (fieldType === 'radio') this.copyRadioValue(sourceField, targetField);
				else if (fieldType === 'name')  this.copyNameValue(sourceField, targetField);
				else if (fieldType === 'email') this.copyEmailValue(sourceField, targetField);
				else if (fieldType === 'text')  this.copyTextValue(sourceField, targetField);
			} catch (error) {
				console.error('Error copying field by label:', labelText, error);
			}
		},

		/**
		 * Clear field value by label
		 */
		clearFieldByLabel: function (form, labelText, fieldType) {
			try {
				const field = this.findFieldByLabel(form, labelText);
				if (!field) { console.warn('Field not found for clearing:', labelText); return; }
				
				if (fieldType === 'radio') {
					field.querySelectorAll('input[type=radio]').forEach(r => r.checked = false);
				} else if (fieldType === 'name') {
					field.querySelectorAll('.frm_combo_inputs_container input[type=text]').forEach(i => i.value = '');
				} else if (fieldType === 'email') {
					const emailInput = field.querySelector('input[type=email], input[type=text]');
					if (emailInput) emailInput.value = '';
				} else if (fieldType === 'text') {
					const textInput = field.querySelector('input[type=text], input[type=tel], textarea');
					if (textInput) textInput.value = '';
				}
			} catch (error) {
				console.error('Error clearing field:', labelText, error);
			}
		},

		copyRadioValue: function (sourceField, targetField) {
			const sourceRadio = sourceField.querySelector('input[type=radio]:checked');
			if (!sourceRadio) return;
			const targetRadio = targetField.querySelector('input[type=radio][value="' + sourceRadio.value + '"]');
			if (targetRadio) { targetRadio.checked = true; targetRadio.dispatchEvent(new Event('change', { bubbles: true })); }
		},

		copyNameValue: function (sourceField, targetField) {
			const sourceInputs = sourceField.querySelectorAll('.frm_combo_inputs_container input[type=text]');
			const targetInputs = targetField.querySelectorAll('.frm_combo_inputs_container input[type=text]');
			sourceInputs.forEach((src, i) => {
				if (targetInputs[i]) {
					targetInputs[i].value = src.value;
					targetInputs[i].dispatchEvent(new Event('input', { bubbles: true }));
				}
			});
		},

		copyEmailValue: function (sourceField, targetField) {
			const src = sourceField.querySelector('input[type=email], input[type=text]');
			const tgt = targetField.querySelector('input[type=email], input[type=text]');
			if (src && tgt) {
				tgt.value = src.value;
				tgt.dispatchEvent(new Event('input',  { bubbles: true }));
				tgt.dispatchEvent(new Event('change', { bubbles: true }));
			}
		},

		copyTextValue: function (sourceField, targetField) {
			const src = sourceField.querySelector('input[type=text], input[type=tel], textarea');
			const tgt = targetField.querySelector('input[type=text], input[type=tel], textarea');
			if (src && tgt) {
				tgt.value = src.value;
				tgt.dispatchEvent(new Event('input', { bubbles: true }));
			}
		},

		findFormByTitle: function (title) {
			const allSteps = document.querySelectorAll('.a-item');
			for (let step of allSteps) {
				const stepId = step.getAttribute('id');
				if (!stepId) continue;
				const stepLi       = document.querySelector('#checkoutSteps li#opc-' + stepId.replace('checkout-step-', ''));
				const titleElement = stepLi ? stepLi.querySelector('.step-title h3') : null;
				const stepTitle    = titleElement ? titleElement.textContent.trim() : '';
				if (stepTitle.toLowerCase().includes(title.toLowerCase())) return step;
			}
			return null;
		},

		/**
		 * Setup submit handler
		 */
		setupSubmitHandler: function () {
			const self = this;
			const checkoutForm = document.querySelector('form.checkout, form.woocommerce-checkout');
			if (!checkoutForm) { console.warn('WooCommerce checkout form not found'); return; }

			checkoutForm.addEventListener('submit', function () { self.makeAllStepsSubmittable(); });

			jQuery(document.body).on('checkout_place_order', function () {
				self.makeAllStepsSubmittable();
				return true;
			});
		},

		makeAllStepsSubmittable: function () {
			document.querySelectorAll('.a-item').forEach(step => {
				step.style.display = 'block';
				if (step.id !== 'checkout-step-checkout') {
					step.style.visibility  = 'hidden';
					step.style.position    = 'absolute';
					step.style.top         = '-9999px';
					step.style.height      = '0';
					step.style.overflow    = 'hidden';
					step.style.pointerEvents = 'none';
				}
			});
		},

		/**
		 * Initialize steps on page load
		 */
		initializeSteps: function () {
			const steps        = document.querySelectorAll('.one-page-checkout .step');
			const contentSteps = document.querySelectorAll('.multistep-checkout-wrapper .a-item');

			steps.forEach((step, index) => {
				const numberSpan = step.querySelector('.number');
				if (numberSpan) numberSpan.textContent = index + 1;
			});

			if (steps.length > 0)        steps[0].classList.add('active');
			if (contentSteps.length > 0) {
				contentSteps[0].style.display = 'block';
				const firstPrevBtn = contentSteps[0].querySelector('.prev-btn');
				if (firstPrevBtn) firstPrevBtn.remove();
			}

			this.updateStepTitleClickability();
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;
			let isNavigating = false;

			document.querySelectorAll('#checkoutSteps li').forEach(stepLink => {
				stepLink.addEventListener('click', function (e) {
					e.preventDefault();
					if (this.classList.contains('disabled')) return;
					self.navigateToStep(this);
				});
			});

			document.addEventListener('click', function (e) {
				if (e.target.closest('.next-btn')) {
					e.preventDefault();
					e.stopImmediatePropagation();
					if (isNavigating) return;
					if (!self.validateCurrentStep()) return;
					isNavigating = true;
					if (!self.repeaterCounterInitialized) {
						self.bindRepeaterEvents();
						self.repeaterCounterInitialized = true;
					}
					self.changeStep('next');
					setTimeout(function () { isNavigating = false; }, 300);
				}
			});

			document.addEventListener('click', function (e) {
				if (e.target.closest('.prev-btn')) {
					e.preventDefault();
					e.stopImmediatePropagation();
					if (isNavigating) return;
					isNavigating = true;
					self.changeStep('prev');
					setTimeout(function () { isNavigating = false; }, 300);
				}
			});
		},

		bindRepeaterEvents: function () {
			const self = this;
			document.addEventListener('click', function (e) {
				if (e.target.closest('.frm_add_form_row') || e.target.closest('.frm_remove_form_row')) {
					self.debouncedUpdateRepeaterCounters();
				}
			}, true);
		},

		debouncedUpdateRepeaterCounters: function () {
			const self = this;
			if (this.repeaterUpdateTimer) clearTimeout(this.repeaterUpdateTimer);
			this.repeaterUpdateTimer = setTimeout(function () { self.updateRepeaterCounters(); }, 300);
		},

		updateRepeaterCounters: function () {
			try {
				const visibleStep = document.querySelector('.a-item:not([style*="display: none"])');
				if (!visibleStep) return;

				const repeaterSections = visibleStep.querySelectorAll('.frm_repeat_sec');
				if (repeaterSections.length === 0) return;

				const repeaterGroups = {};
				repeaterSections.forEach(section => {
					const sectionId = section.getAttribute('id');
					if (!sectionId) return;
					const match = sectionId.match(/frm_section_(\d+)-(\d+)/);
					if (!match) return;
					const parentId = match[1];
					if (!repeaterGroups[parentId]) repeaterGroups[parentId] = [];
					repeaterGroups[parentId].push(section);
				});

				Object.keys(repeaterGroups).forEach(parentId => {
					repeaterGroups[parentId].forEach((section, index) => {
						const counter = section.querySelector('.rep-counter');
						if (counter) counter.textContent = '#' + (index + 2);
					});
				});
			} catch (error) {
				console.warn('Repeater counter update failed:', error);
			}
		},

		/**
		 * Get error message from field inputs (data-reqmsg / data-invmsg)
		 * Reads from the parent field container's OWN inputs, NOT sub-field inputs.
		 *
		 * @param {Element} fieldContainer  - .frm_form_field element
		 * @param {'req'|'inv'} type        - which message to retrieve
		 * @returns {string}
		 */
		getFieldErrorMessage: function (fieldContainer, type) {
			const attr = type === 'req' ? 'data-reqmsg' : 'data-invmsg';

			// For radio / checkbox groups the inputs are direct children of the container
			// For regular inputs there is exactly one primary input
			// We intentionally skip inputs that live inside .frm_form_subfield wrappers
			const inputs = fieldContainer.querySelectorAll(
				'input:not([type=hidden]), select, textarea'
			);

			for (let input of inputs) {
				// Skip sub-field inputs (name compound, address sub-fields, etc.)
				if (input.closest('.frm_form_subfield')) continue;

				const msg = input.getAttribute(attr);
				if (msg) return msg;
			}

			return '';
		},

		/**
		 * Validate required fields in current step — returns true if all valid
		 */
		validateCurrentStep: function () {
			const activeStep = document.querySelector('.one-page-checkout .step.active');
			if (!activeStep) return false;

			const steps        = document.querySelectorAll('.one-page-checkout .step');
			const contentSteps = document.querySelectorAll('.multistep-checkout-wrapper .a-item');
			const currentIndex = Array.from(steps).indexOf(activeStep);
			if (currentIndex === -1 || !contentSteps[currentIndex]) return false;

			const currentContent = contentSteps[currentIndex];
			let isValid = true;

			// Remove previous error messages in this step before re-validating
			currentContent.querySelectorAll('.frm-error-message').forEach(el => el.remove());

			const requiredFields = currentContent.querySelectorAll('.frm_form_field.frm_required_field');

			requiredFields.forEach(fieldContainer => {
				// Skip invisible fields (hidden by conditional logic)
				if (fieldContainer.offsetParent === null) return;
				if (fieldContainer.classList.contains('frm_repeat_buttons')) return;

				// --- validate ---
				const result = this.validateFormidableField(fieldContainer);
				// result: 'valid' | 'empty' | 'invalid'

				if (result !== 'valid') {
					isValid = false;
					this.addErrorFeedback(fieldContainer, result);
				}
			});

			// Also validate non-required number fields that are visible (max/min check)
			const numberFields = currentContent.querySelectorAll(
				'.frm_form_field:not(.frm_required_field) input[type=number]'
			);
			numberFields.forEach(input => {
				const fieldContainer = input.closest('.frm_form_field');
				if (!fieldContainer || fieldContainer.offsetParent === null) return;

				const result = this.validateNumberInput(input);
				if (result === 'invalid') {
					isValid = false;
					this.addErrorFeedback(fieldContainer, 'invalid');
				}
			});

			if (!isValid) {
				const firstError = currentContent.querySelector('.frm-field-error');
				if (firstError) {
					firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
					const firstInput = firstError.querySelector('input:not([type=hidden]), select, textarea');
					if (firstInput) firstInput.focus();
				}
			}

			return isValid;
		},

		/**
		 * Validate a number input against min / max
		 * @returns {'valid'|'invalid'}
		 */
		validateNumberInput: function (input) {
			const value = input.value.trim();
			if (value === '') return 'valid'; // empty non-required = ok

			const num = parseFloat(value);
			if (isNaN(num)) return 'invalid';

			const max = input.getAttribute('max');
			const min = input.getAttribute('min');

			if (max !== null && max !== '' && num > parseFloat(max)) return 'invalid';
			if (min !== null && min !== '' && num < parseFloat(min)) return 'invalid';

			return 'valid';
		},

		/**
		 * Validate individual Formidable field
		 * @returns {'valid'|'empty'|'invalid'}
		 */
		validateFormidableField: function (fieldContainer) {
			// Radio buttons
			const radioButtons = fieldContainer.querySelectorAll('input[type=radio]');
			if (radioButtons.length > 0) {
				return fieldContainer.querySelector('input[type=radio]:checked') ? 'valid' : 'empty';
			}

			// Checkboxes
			const checkboxes = fieldContainer.querySelectorAll('input[type=checkbox]');
			if (checkboxes.length > 0) {
				return fieldContainer.querySelector('input[type=checkbox]:checked') ? 'valid' : 'empty';
			}

			// Compound fields (name, address)
			const compoundContainer = fieldContainer.querySelector('.frm_combo_inputs_container');
			if (compoundContainer) {
				return this.validateCompoundField(compoundContainer) ? 'valid' : 'empty';
			}

			// Number input — check value AND range
			const numberInput = fieldContainer.querySelector('input[type=number]');
			if (numberInput) {
				const value = numberInput.value.trim();
				if (value === '') return 'empty';
				const result = this.validateNumberInput(numberInput);
				return result; // 'valid' or 'invalid'
			}

			// Regular inputs
			const regularInput = fieldContainer.querySelector(
				'input:not([type=hidden]):not([type=radio]):not([type=checkbox]), select, textarea'
			);
			if (regularInput) {
				return regularInput.value.trim() !== '' ? 'valid' : 'empty';
			}

			return 'valid';
		},

		/**
		 * Validate compound field (name, address)
		 */
		validateCompoundField: function (compoundContainer) {
			const subFields = compoundContainer.querySelectorAll(
				'.frm_form_field input:not([type=hidden]), .frm_form_field select'
			);
			let hasValue = false;
			subFields.forEach(input => {
				if (input.value.trim() !== '') hasValue = true;
			});
			return hasValue;
		},

		/**
		 * Add error visual feedback AND inject the error message below the field.
		 * The message text comes from data-reqmsg (empty) or data-invmsg (invalid).
		 * Sub-field inputs are intentionally ignored as message source.
		 *
		 * @param {Element}           fieldContainer
		 * @param {'empty'|'invalid'} errorType
		 */
		addErrorFeedback: function (fieldContainer, errorType) {
			const self = this;
			fieldContainer.classList.add('frm-field-error');

			// Pick the right message attribute
			const msgType  = errorType === 'invalid' ? 'inv' : 'req';
			const msgText  = this.getFieldErrorMessage(fieldContainer, msgType);

			// Inject error message element (only once, right after the field's input wrapper)
			if (msgText) {
				// Remove any existing message first
				const existing = fieldContainer.querySelector('.frm-error-message');
				if (existing) existing.remove();

				const msgEl = document.createElement('span');
				msgEl.className   = 'frm-error-message';
				msgEl.textContent = msgText;

				// Insert after the last direct-child input area
				// For radio/checkbox groups the opt_container is the anchor;
				// for address/name fields the fieldset is; otherwise the input itself.
				const anchor =
					fieldContainer.querySelector(':scope > .frm_opt_container') ||
					fieldContainer.querySelector(':scope > fieldset') ||
					fieldContainer.querySelector(':scope > .iti') ||          // intl-tel-input wrapper
					fieldContainer.querySelector(':scope > input:not([type=hidden]), :scope > select, :scope > textarea') ||
					fieldContainer.querySelector(':scope > div > input:not([type=hidden]), :scope > div > select');

				if (anchor) {
					anchor.insertAdjacentElement('afterend', msgEl);
				} else {
					fieldContainer.appendChild(msgEl);
				}
			}

			// Style individual inputs (not sub-field inputs)
			const inputs = fieldContainer.querySelectorAll(
				'input:not([type=hidden]):not([type=radio]):not([type=checkbox]), select, textarea'
			);
			inputs.forEach(input => {
				if (input.closest('.frm_form_subfield')) return; // skip sub-fields
				input.classList.add('field-error');

				const checkAndRemove = function () {
					// For number inputs run range check too
					let valid;
					if (input.type === 'number') {
						valid = self.validateNumberInput(input) === 'valid' && input.value.trim() !== '';
					} else {
						valid = self.validateFormidableField(fieldContainer) === 'valid';
					}

					if (valid) {
						input.classList.remove('field-error');
						fieldContainer.classList.remove('frm-field-error');
						const msg = fieldContainer.querySelector('.frm-error-message');
						if (msg) msg.remove();

						input.removeEventListener('change', checkAndRemove);
						input.removeEventListener('input',  checkAndRemove);
						input.removeEventListener('blur',   checkAndRemove);
					} else {
						// Update message type while user is still in error state
						const newMsgType = (input.type === 'number' && self.validateNumberInput(input) === 'invalid')
							? 'inv' : 'req';
						const newMsg = self.getFieldErrorMessage(fieldContainer, newMsgType);
						const msgEl  = fieldContainer.querySelector('.frm-error-message');
						if (msgEl && newMsg) msgEl.textContent = newMsg;
					}
				};

				input.addEventListener('change', checkAndRemove);
				input.addEventListener('input',  checkAndRemove);
				input.addEventListener('blur',   checkAndRemove);
			});

			// Radio / checkbox: remove error on any selection
			const radiosOrCheckboxes = fieldContainer.querySelectorAll('input[type=radio], input[type=checkbox]');
			if (radiosOrCheckboxes.length > 0) {
				radiosOrCheckboxes.forEach(input => {
					input.addEventListener('change', function () {
						if (self.validateFormidableField(fieldContainer) === 'valid') {
							fieldContainer.classList.remove('frm-field-error');
							const msg = fieldContainer.querySelector('.frm-error-message');
							if (msg) msg.remove();
						}
					});
				});
			}
		},

		cleanLabel: function (labelText) {
			return labelText.replace(/\*/g, '').trim();
		},

		updateStepTitleClickability: function () {
			const steps      = document.querySelectorAll('.one-page-checkout .step');
			const activeStep = document.querySelector('.one-page-checkout .step.active');
			if (!activeStep) return;

			const activeIndex = Array.from(steps).indexOf(activeStep);
			steps.forEach((step, index) => {
				if (index <= activeIndex) {
					step.classList.remove('disabled');
					step.style.cursor = 'pointer';
				} else {
					step.classList.add('disabled');
					step.style.cursor  = 'not-allowed';
					step.style.opacity = '1';
				}
			});
		},

		changeStep: function (direction) {
			const steps        = document.querySelectorAll('.one-page-checkout .step');
			const contentSteps = document.querySelectorAll('.multistep-checkout-wrapper .a-item');
			const activeStep   = document.querySelector('.one-page-checkout .step.active');
			if (!activeStep) return;

			const currentIndex = Array.from(steps).indexOf(activeStep);
			if (contentSteps[currentIndex]) contentSteps[currentIndex].style.display = 'none';

			activeStep.classList.remove('active');
			activeStep.classList.add('completed');

			let nextIndex = currentIndex;
			if (direction === 'next') {
				nextIndex++;
			} else if (direction === 'prev') {
				nextIndex--;
				if (steps[nextIndex]) steps[nextIndex].classList.remove('completed');
			}

			if (steps[nextIndex]) steps[nextIndex].classList.add('active');

			if (contentSteps[nextIndex]) {
				contentSteps[nextIndex].style.display = 'block';
				const self = this;
				setTimeout(function () { self.updateRepeaterCounters(); }, 150);
			}

			document.querySelectorAll('.frm-field-error').forEach(f => {
				f.classList.remove('frm-field-error');
				const msg = f.querySelector('.frm-error-message');
				if (msg) msg.remove();
			});

			this.updateStepTitleClickability();
			this.scrollAndFocus();

			const self = this;
			setTimeout(function () { self.captureFormData(); }, 100);
		},

		navigateToStep: function (stepLi) {
			const targetStepId = stepLi.getAttribute('id');
			const targetStep   = document.getElementById('checkout-step-' + targetStepId.replace('opc-', ''));

			if (targetStep) {
				document.querySelectorAll('.a-item').forEach(item => item.style.display = 'none');
				targetStep.style.display = 'block';

				const self = this;
				setTimeout(function () { self.updateRepeaterCounters(); }, 150);

				document.querySelectorAll('#checkoutSteps li').forEach(step => {
					step.classList.remove('active', 'completed');
				});

				stepLi.classList.add('active');

				let prevSibling = stepLi.previousElementSibling;
				while (prevSibling) {
					prevSibling.classList.add('completed');
					prevSibling = prevSibling.previousElementSibling;
				}

				document.querySelectorAll('.frm-field-error').forEach(f => {
					f.classList.remove('frm-field-error');
					const msg = f.querySelector('.frm-error-message');
					if (msg) msg.remove();
				});

				this.updateStepTitleClickability();
				this.scrollAndFocus();

				if (targetStepId === 'opc-checkout') {
					const self = this;
					setTimeout(function () { self.captureFormData(); }, 100);
				}
			}
		},

		scrollAndFocus: function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
			setTimeout(function () {
				const visibleItem = document.querySelector('.a-item:not([style*="display: none"])');
				if (visibleItem) {
					const firstField = visibleItem.querySelector('input:not([type=hidden]):not([type=submit]), select, textarea');
					if (firstField) firstField.focus();
				}
			}, 350);
		},

		setupObserver: function () {
			const self         = this;
			const checkoutStep = document.getElementById('checkout-step-checkout');
			if (checkoutStep) {
				const observer = new MutationObserver(function (mutations) {
					mutations.forEach(function (mutation) {
						if (mutation.target.id === 'checkout-step-checkout' &&
							mutation.target.style.display !== 'none') {
							self.captureFormData();
						}
					});
				});
				observer.observe(checkoutStep, { attributes: true, attributeFilter: ['style'] });
			}
		},

		initialCapture: function () {
			const checkoutStep = document.getElementById('checkout-step-checkout');
			if (checkoutStep && checkoutStep.style.display !== 'none') {
				this.captureFormData();
			}
		},

		captureFormData: function () {
			const forms = [];

			document.querySelectorAll('.a-item').forEach(stepDiv => {
				const stepId = stepDiv.getAttribute('id');
				if (stepId === 'checkout-step-checkout') return;

				const stepLi       = document.querySelector('#checkoutSteps li#opc-' + stepId.replace('checkout-step-', ''));
				const titleElement = stepLi ? stepLi.querySelector('.step-title h3') : null;
				const formTitle    = titleElement ? titleElement.textContent.trim() : 'Form';

				stepDiv.querySelectorAll('.frm_forms').forEach(form => {
					const fieldsData = FormSummary.extractFormFields(form);
					if (fieldsData.length > 0) {
						forms.push({ formName: formTitle, fields: fieldsData });
					}
				});
			});

			this.renderSummary(forms);
		},

		renderSummary: function (forms) {
			let formSummaryHtml = '';

			forms.forEach(function (formData) {
				formSummaryHtml += '<div class="form-section mb-6">';
				formSummaryHtml += '<h6>' + formData.formName + '</h6>';
				formSummaryHtml += '<dl class="space-y-2">';

				formData.fields.forEach(function (field) {
					formSummaryHtml += '<div class="flex flex-col lg:flex-row justify-between border-b border-gray-200 pb-2">';
					formSummaryHtml += '<dt class="text-sm lg:text-base text-neutral-700 text-left">' + field.label + ':</dt>';
					formSummaryHtml += '<dd class="text-sm lg:text-base text-brand-900 text-left lg:text-right">' + field.value + '</dd>';
					formSummaryHtml += '</div>';
				});

				formSummaryHtml += '</dl>';
				formSummaryHtml += '</div>';
			});

			const summaryContainer = document.getElementById('msc-form-summary');
			if (summaryContainer) {
				summaryContainer.innerHTML = forms.length > 0
					? '<div class="form-summary-wrapper">' + formSummaryHtml + '</div>'
					: '<p class="text-gray-500">No form data available.</p>';
			}
		},

		extractFormFields: function (form) {
			const fields = [];
			const repeaterSections = form.querySelectorAll('.frm_repeat_sec');

			if (repeaterSections.length > 0) {
				repeaterSections.forEach((section, index) => {
					fields.push(...this.extractRepeaterFields(section, index + 1));
				});
			}

			fields.push(...this.extractRegularFields(form));
			return fields;
		},

		extractRepeaterFields: function (section, rowNumber) {
			const fields = [];
			const formFields = section.querySelectorAll(':scope > .frm_form_field:not(.frm_form_subfield)');

			formFields.forEach(field => {
				if (field.classList.contains('frm_html_container')) return;
				if (field.classList.contains('hint-text'))          return;
				if (field.classList.contains('frm_repeat_buttons')) return;
				if (field.querySelector('textarea[readonly]') || field.querySelector('input[readonly]')) return;

				const labelElement = field.querySelector(':scope > .frm_primary_label, :scope > div > .frm_primary_label');
				if (!labelElement) return;

				const labelClone = labelElement.cloneNode(true);
				labelClone.querySelectorAll('*').forEach(c => c.remove());
				const label = this.cleanLabel(labelClone.textContent.trim());
				if (!label) return;

				const value = this.getFieldValue(field);
				if (value && value !== '') {
					fields.push({ label: '#' + rowNumber + ' - ' + label, value: value });
				}
			});

			return fields;
		},

		extractRegularFields: function (form) {
			const fields      = [];
			const seenFields  = {};
			const formFields  = form.querySelectorAll('.frm_form_field:not(.frm_form_subfield)');

			formFields.forEach(field => {
				if (field.closest('.frm_repeat_sec'))                          return;
				if (field.classList.contains('frm_section_heading'))           return;
				if (field.classList.contains('frm_html_container'))            return;
				if (field.classList.contains('hint-text'))                     return;
				if (field.parentElement && field.parentElement.classList.contains('frm_combo_inputs_container')) return;
				if (field.querySelector('textarea[readonly]') || field.querySelector('input[readonly]')) return;

				const labelElement = field.querySelector(':scope > .frm_primary_label, :scope > div > .frm_primary_label');
				if (!labelElement) return;

				const labelClone = labelElement.cloneNode(true);
				labelClone.querySelectorAll('*').forEach(c => c.remove());
				const label = this.cleanLabel(labelClone.textContent.trim());
				if (!label) return;

				const value = this.getFieldValue(field);
				if (value && value !== '') {
					const fieldKey = label + '::' + value;
					if (!seenFields[fieldKey]) {
						seenFields[fieldKey] = true;
						fields.push({ label, value });
					}
				}
			});

			return fields;
		},

		getFieldValue: function (field) {
			if (field.querySelector('textarea[readonly]') || field.querySelector('input[readonly]')) return '';

			const radioChecked = field.querySelector('input[type=radio]:checked');
			if (radioChecked) {
				const label = radioChecked.closest('label');
				if (label) {
					const clone = label.cloneNode(true);
					clone.querySelectorAll('input').forEach(i => i.remove());
					return clone.textContent.trim();
				}
				const nextLabel = radioChecked.nextElementSibling;
				if (nextLabel && nextLabel.tagName === 'LABEL') return nextLabel.textContent.trim();
			}

			const checkboxes = field.querySelectorAll(':scope > div > .frm_opt_container input[type=checkbox]:checked, :scope > .frm_opt_container input[type=checkbox]:checked');
			if (checkboxes.length > 0) {
				const checkedValues = [];
				checkboxes.forEach(checkbox => {
					const label = checkbox.closest('label');
					let labelText = '';
					if (label) {
						const clone = label.cloneNode(true);
						clone.querySelectorAll('input').forEach(i => i.remove());
						labelText = clone.textContent.trim();
					} else {
						const nextLabel = checkbox.nextElementSibling;
						if (nextLabel && nextLabel.tagName === 'LABEL') labelText = nextLabel.textContent.trim();
					}
					if (labelText) checkedValues.push(labelText);
				});
				return checkedValues.join(', ');
			}

			if (field.querySelector(':scope > fieldset > .frm_combo_inputs_container')) {
				return this.getCompoundFieldValue(field);
			}

			const input = field.querySelector(
				':scope > input:not([type=hidden]):not([type=radio]):not([type=checkbox]):not([readonly]), ' +
				':scope > select, :scope > textarea:not([readonly]), ' +
				':scope > div > input:not([type=hidden]):not([type=radio]):not([type=checkbox]):not([readonly])'
			);

			if (input) {
				if (input.hasAttribute('readonly')) return '';
				if (input.tagName === 'SELECT') {
					const selected = input.querySelector('option:checked');
					return selected ? selected.textContent.trim() : '';
				}
				return input.value;
			}

			return '';
		},

		getCompoundFieldValue: function (field) {
			const parts = [];
			field.querySelectorAll('.frm_combo_inputs_container .frm_form_field').forEach(subField => {
				const input = subField.querySelector('input[type=text]:not([readonly]), input[type=email]:not([readonly]), select');
				if (input && input.value.trim() !== '') parts.push(input.value.trim());
			});
			return parts.join(' ');
		}
	};

	// Initialize on document ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { FormSummary.init(); });
	} else {
		FormSummary.init();
	}

})();
