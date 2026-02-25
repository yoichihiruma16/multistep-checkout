/**
 * Auto-populate billing form from Formidable form data
 * Simple direct mapping from Abonnee form to WooCommerce billing fields
 */
(function() {
	'use strict';

	function populateBilling() {
		// First name
		const billingFirstName = document.querySelector('input[name="billing_first_name"]');
		const formFirstName = document.querySelector('input[id*="field_f9iyg_first"]');
		if (billingFirstName && formFirstName && formFirstName.value) {
			billingFirstName.value = formFirstName.value;
			jQuery(billingFirstName).trigger('change');
		}

		// Last name
		const billingLastName = document.querySelector('input[name="billing_last_name"]');
		const formLastName = document.querySelector('input[id*="field_f9iyg_last"]');
		if (billingLastName && formLastName && formLastName.value) {
			billingLastName.value = formLastName.value;
			jQuery(billingLastName).trigger('change');
		}

		// Email
		const billingEmail = document.querySelector('input[name="billing_email"]');
		const formEmail = document.querySelector('input[id*="field_uq2ba"]');
		if (billingEmail && formEmail && formEmail.value) {
			billingEmail.value = formEmail.value;
			jQuery(billingEmail).trigger('change');
		}

		// Phone (prefer mobile)
		const billingPhone = document.querySelector('input[name="billing_phone"]');
		const formPhoneMobile = document.querySelector('input[id*="field_zzljl"]');
		const formPhoneFixed = document.querySelector('input[id*="field_tz4l3"]');
		if (billingPhone) {
			const phoneValue = (formPhoneMobile && formPhoneMobile.value) ? formPhoneMobile.value : (formPhoneFixed && formPhoneFixed.value ? formPhoneFixed.value : '');
			if (phoneValue) {
				billingPhone.value = phoneValue;
				jQuery(billingPhone).trigger('change');
			}
		}

		// Address - street
		const billingAddress = document.querySelector('input[name="billing_address_1"]');
		const formStreet = document.querySelector('input[id*="field_qbgd5_line1"]');
		const formHouseNumber = document.querySelector('input[id*="field_qbgd5_line2"]');
		if (billingAddress && formStreet && formStreet.value) {
			let addressValue = formStreet.value;
			if (formHouseNumber && formHouseNumber.value) {
				addressValue += ' ' + formHouseNumber.value;
			}
			billingAddress.value = addressValue;
			jQuery(billingAddress).trigger('change');
		}

		// City
		const billingCity = document.querySelector('input[name="billing_city"]');
		const formCity = document.querySelector('input[id*="field_qbgd5_city"]');
		if (billingCity && formCity && formCity.value) {
			billingCity.value = formCity.value;
			jQuery(billingCity).trigger('change');
		}

		// Postcode
		const billingPostcode = document.querySelector('input[name="billing_postcode"]');
		const formPostcode = document.querySelector('input[id*="field_qbgd5_zip"]');
		if (billingPostcode && formPostcode && formPostcode.value) {
			billingPostcode.value = formPostcode.value;
			jQuery(billingPostcode).trigger('change');
		}

		// Country
		const billingCountry = document.querySelector('input[name="billing_country"]');
		const formCountry = document.querySelector('select[id*="field_qbgd5_country"]');
		if (billingCountry && formCountry && formCountry.value) {
			billingCountry.value = formCountry.value;
			jQuery(billingCountry).trigger('change');
		}

		// Trigger checkout update
		jQuery(document.body).trigger('update_checkout');
	}

	// Watch for checkout step to become visible and populate
	function watchCheckoutStep() {
		const checkoutStep = document.getElementById('checkout-step-checkout');
		if (!checkoutStep) return;

		const observer = new MutationObserver(() => {
			const style = checkoutStep.getAttribute('style');
			if (style && style.includes('display: block')) {
				setTimeout(populateBilling, 300);
			}
		});

		observer.observe(checkoutStep, { attributes: true });
	}

	// Initialize
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', watchCheckoutStep);
	} else {
		watchCheckoutStep();
	}

})();
