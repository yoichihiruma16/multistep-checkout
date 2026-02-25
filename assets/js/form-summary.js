/**
 * Form Summary JavaScript
 * Handles form data capture, display, and step navigation
 */
(function() {
	'use strict';

	/**
	 * Form Summary Handler
	 */
	const FormSummary = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.initializeSteps();
			this.bindEvents();
			this.setupObserver();
			this.initialCapture();
		},

		/**
		 * Initialize steps on page load
		 */
		initializeSteps: function() {
			const steps = document.querySelectorAll('.one-page-checkout .step');
			const contentSteps = document.querySelectorAll('.multistep-checkout-wrapper .a-item');
			
			// Number the steps dynamically
			steps.forEach((step, index) => {
				const numberSpan = step.querySelector('.number');
				if (numberSpan) {
					numberSpan.textContent = index + 1;
				}
			});

			// Initialize first step
			if (steps.length > 0) {
				steps[0].classList.add('active');
			}
			if (contentSteps.length > 0) {
				contentSteps[0].style.display = 'block';
				
				// Remove prev button from first step only
				const firstPrevBtn = contentSteps[0].querySelector('.prev-btn');
				if (firstPrevBtn) {
					firstPrevBtn.remove();
				}
			}
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function() {
			const self = this;
			let isNavigating = false;
			
			// Make step numbers clickable
			const stepLinks = document.querySelectorAll('#checkoutSteps li');
			stepLinks.forEach(stepLink => {
				stepLink.addEventListener('click', function(e) {
					e.preventDefault();
					self.navigateToStep(this);
				});
			});
			
			// Next button click - handles navigation with scroll/focus
			document.addEventListener('click', function(e) {
				if (e.target.closest('.next-btn')) {
					e.preventDefault();
					e.stopImmediatePropagation();
					
					if (isNavigating) return;
					isNavigating = true;
					
					self.changeStep('next');
					
					setTimeout(function() {
						isNavigating = false;
					}, 300);
				}
			});
			
			// Previous button click - handles navigation with scroll/focus
			document.addEventListener('click', function(e) {
				if (e.target.closest('.prev-btn')) {
					e.preventDefault();
					e.stopImmediatePropagation();
					
					if (isNavigating) return;
					isNavigating = true;
					
					self.changeStep('prev');
					
					setTimeout(function() {
						isNavigating = false;
					}, 300);
				}
			});
		},

		/**
		 * Change step (next or prev)
		 */
		changeStep: function(direction) {
			const steps = document.querySelectorAll('.one-page-checkout .step');
			const contentSteps = document.querySelectorAll('.multistep-checkout-wrapper .a-item');
			const activeStep = document.querySelector('.one-page-checkout .step.active');
			
			if (!activeStep) return;
			
			const currentIndex = Array.from(steps).indexOf(activeStep);
			
			// Hide current content
			if (contentSteps[currentIndex]) {
				contentSteps[currentIndex].style.display = 'none';
			}
			
			// Update step classes
			activeStep.classList.remove('active');
			activeStep.classList.add('completed');
			
			// Calculate next index
			let nextIndex = currentIndex;
			if (direction === 'next') {
				nextIndex++;
			} else if (direction === 'prev') {
				nextIndex--;
				if (steps[nextIndex]) {
					steps[nextIndex].classList.remove('completed');
				}
			}
			
			// Activate new step
			if (steps[nextIndex]) {
				steps[nextIndex].classList.add('active');
			}
			
			// Show new content
			if (contentSteps[nextIndex]) {
				contentSteps[nextIndex].style.display = 'block';
			}
			
			// Scroll and focus
			this.scrollAndFocus();
			
			// Capture form data if navigating to checkout
			const self = this;
			setTimeout(function() {
				self.captureFormData();
			}, 100);
		},

		/**
		 * Navigate to a specific step
		 */
		navigateToStep: function(stepLi) {
			const targetStepId = stepLi.getAttribute('id');
			const targetStep = document.getElementById('checkout-step-' + targetStepId.replace('opc-', ''));
			
			// Only navigate if step exists
			if (targetStep) {
				// Hide all steps
				const allItems = document.querySelectorAll('.a-item');
				allItems.forEach(item => item.style.display = 'none');
				
				// Show target step
				targetStep.style.display = 'block';
				
				// Update active state
				const allSteps = document.querySelectorAll('#checkoutSteps li');
				allSteps.forEach(step => {
					step.classList.remove('active', 'completed');
				});
				
				stepLi.classList.add('active');
				
				// Mark previous steps as completed
				let prevSibling = stepLi.previousElementSibling;
				while (prevSibling) {
					prevSibling.classList.add('completed');
					prevSibling = prevSibling.previousElementSibling;
				}
				
				// Scroll and focus
				this.scrollAndFocus();
				
				// Capture form data if navigating to checkout
				if (targetStepId === 'opc-checkout') {
					const self = this;
					setTimeout(function() {
						self.captureFormData();
					}, 100);
				}
			}
		},

		/**
		 * Scroll to top and focus first field
		 */
		scrollAndFocus: function() {
			// Scroll to top of page
			window.scrollTo({
				top: 0,
				behavior: 'smooth'
			});
			
			// Find and focus first visible input field
			setTimeout(function() {
				const visibleItem = document.querySelector('.a-item:not([style*="display: none"])');
				if (visibleItem) {
					const firstField = visibleItem.querySelector('input:not([type=hidden]):not([type=submit]), select, textarea');
					if (firstField) {
						firstField.focus();
					}
				}
			}, 350);
		},

		/**
		 * Setup mutation observer for checkout step
		 */
		setupObserver: function() {
			const self = this;
			const checkoutStep = document.getElementById('checkout-step-checkout');
			
			if (checkoutStep) {
				const observer = new MutationObserver(function(mutations) {
					mutations.forEach(function(mutation) {
						if (mutation.target.id === 'checkout-step-checkout' && 
							mutation.target.style.display !== 'none') {
							self.captureFormData();
						}
					});
				});
				
				observer.observe(checkoutStep, {
					attributes: true,
					attributeFilter: ['style']
				});
			}
		},

		/**
		 * Initial capture if checkout step is already visible
		 */
		initialCapture: function() {
			const checkoutStep = document.getElementById('checkout-step-checkout');
			if (checkoutStep && checkoutStep.style.display !== 'none') {
				this.captureFormData();
			}
		},

		/**
		 * Capture and display form data from all previous steps
		 */
		captureFormData: function() {
			const forms = [];
			
			// Find all Formidable forms in previous steps
			const allItems = document.querySelectorAll('.a-item');
			allItems.forEach(stepDiv => {
				const stepId = stepDiv.getAttribute('id');
				
				// Skip the checkout step itself
				if (stepId === 'checkout-step-checkout') {
					return;
				}
				
				// Get the form title from the corresponding step in the navigation
				const stepLi = document.querySelector('#checkoutSteps li#opc-' + stepId.replace('checkout-step-', ''));
				const titleElement = stepLi ? stepLi.querySelector('.step-title h3') : null;
				const formTitle = titleElement ? titleElement.textContent.trim() : 'Form';
				
				// Find Formidable forms in this step
				const frmForms = stepDiv.querySelectorAll('.frm_forms');
				frmForms.forEach(form => {
					const fieldsData = FormSummary.extractFormFields(form);
					
					// Add form data if it has fields
					if (fieldsData.length > 0) {
						forms.push({
							formName: formTitle,
							fields: fieldsData
						});
					}
				});
			});
			
			// Render the summary
			this.renderSummary(forms);
		},

		/**
		 * Render the summary HTML from forms data
		 */
		renderSummary: function(forms) {
			let formSummaryHtml = '';
			
			// Build HTML from forms array
			forms.forEach(function(formData) {
				formSummaryHtml += '<div class="form-section mb-6">';
				formSummaryHtml += '<h6>' + formData.formName + '</h6>';
				formSummaryHtml += '<dl class="space-y-2">';
				
				formData.fields.forEach(function(field) {
					formSummaryHtml += '<div class="flex justify-between border-b border-gray-200 pb-2">';
					formSummaryHtml += '<dt class="text-sm text-neutral-700">' + field.label + ':</dt>';
					formSummaryHtml += '<dd class="text-brand-900">' + field.value + '</dd>';
					formSummaryHtml += '</div>';
				});
				
				formSummaryHtml += '</dl>';
				formSummaryHtml += '</div>';
			});
			
			// Update the summary container
			const summaryContainer = document.getElementById('msc-form-summary');
			if (summaryContainer) {
				if (forms.length > 0) {
					summaryContainer.innerHTML = '<div class="form-summary-wrapper">' + formSummaryHtml + '</div>';
				} else {
					summaryContainer.innerHTML = '<p class="text-gray-500">No form data available.</p>';
				}
			}
		},

		/**
		 * Extract field data from a form
		 */
		extractFormFields: function(form) {
			const fields = [];
			const seenFields = {}; // Track fields by label to avoid duplicates
			
			// Loop through top-level form fields only (not nested sub-fields)
			const formFields = form.querySelectorAll('.frm_form_field:not(.frm_form_subfield)');
			formFields.forEach(field => {
				// Skip if this field contains a combo container (we'll get it from parent)
				if (field.parentElement && field.parentElement.classList.contains('frm_combo_inputs_container')) {
					return;
				}
				
				const labelElement = field.querySelector(':scope > .frm_primary_label, :scope > div > .frm_primary_label');
				if (!labelElement) return;
				
				const labelClone = labelElement.cloneNode(true);
				const childElements = labelClone.querySelectorAll('*');
				childElements.forEach(child => child.remove());
				const label = labelClone.textContent.trim();
				
				// Skip empty labels
				if (!label) return;
				
				const value = FormSummary.getFieldValue(field);

				// Only add if there's a value and we haven't seen this label+value combination
				if (value && value !== '') {
					const fieldKey = label + '::' + value;
					if (!seenFields[fieldKey]) {
						seenFields[fieldKey] = true;
						fields.push({
							label: label,
							value: value
						});
					}
				}
			});
			
			return fields;
		},

		/**
		 * Get value from a form field
		 */
		getFieldValue: function(field) {
			// Check for radio buttons first
			const radioChecked = field.querySelector('input[type=radio]:checked');
			if (radioChecked) {
				// Try to get label text - could be parent label or sibling label
				const label = radioChecked.closest('label');
				if (label) {
					// Input is inside label - get label text without the input
					const labelClone = label.cloneNode(true);
					const inputs = labelClone.querySelectorAll('input');
					inputs.forEach(input => input.remove());
					return labelClone.textContent.trim();
				} else {
					// Label is a sibling
					const nextLabel = radioChecked.nextElementSibling;
					if (nextLabel && nextLabel.tagName === 'LABEL') {
						return nextLabel.textContent.trim();
					}
				}
			}
			
			// Check for checkboxes
			const checkboxes = field.querySelectorAll('input[type=checkbox]:checked');
			if (checkboxes.length > 0) {
				const checkedValues = [];
				checkboxes.forEach(checkbox => {
					const label = checkbox.closest('label');
					let labelText = '';
					if (label) {
						// Input is inside label
						const labelClone = label.cloneNode(true);
						const inputs = labelClone.querySelectorAll('input');
						inputs.forEach(input => input.remove());
						labelText = labelClone.textContent.trim();
					} else {
						// Label is a sibling
						const nextLabel = checkbox.nextElementSibling;
						if (nextLabel && nextLabel.tagName === 'LABEL') {
							labelText = nextLabel.textContent.trim();
						}
					}
					if (labelText) {
						checkedValues.push(labelText);
					}
				});
				return checkedValues.join(', ');
			}
			
			// Check if this is a compound field (name, address, etc.)
			if (field.querySelector(':scope > fieldset > .frm_combo_inputs_container')) {
				return this.getCompoundFieldValue(field);
			}
			
			// Find inputs that are direct children or within immediate div/fieldset
			const input = field.querySelector(':scope > input:not([type=hidden]):not([type=radio]):not([type=checkbox]), :scope > select, :scope > textarea, :scope > div > input:not([type=hidden]):not([type=radio]):not([type=checkbox]), :scope > div > select, :scope > div > textarea, :scope > fieldset > input:not([type=hidden]):not([type=radio]):not([type=checkbox]), :scope > fieldset > select, :scope > fieldset > textarea');
			
			let value = '';
			
			// Regular inputs and selects
			if (input) {
				if (input.tagName === 'SELECT') {
					const selectedOption = input.querySelector('option:checked');
					value = selectedOption ? selectedOption.textContent.trim() : '';
				} else {
					value = input.value;
				}
			}
			
			return value;
		},

		/**
		 * Get value from compound fields (name, address, etc.)
		 */
		getCompoundFieldValue: function(field) {
			const parts = [];
			
			// Find all sub-fields within the compound field
			const subFields = field.querySelectorAll('.frm_combo_inputs_container .frm_form_field');
			subFields.forEach(subField => {
				const input = subField.querySelector('input[type=text], input[type=email], select');
				if (input) {
					const value = input.value;
					
					// Only add non-empty values
					if (value && value.trim() !== '') {
						parts.push(value.trim());
					}
				}
			});
			
			// Join parts with a space
			return parts.join(' ');
		}
	};

	// Initialize on document ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			FormSummary.init();
		});
	} else {
		FormSummary.init();
	}

})();
