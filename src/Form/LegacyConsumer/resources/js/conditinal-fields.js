window.addEventListener('load', async () => {
	const state = {};

	/**
	 * Get list of watched fields.
	 * @unreleased
	 *
	 * @return object
	 */
	function getWatchedElementNames(donationForm) {
		const fields = {};

		donationForm.querySelectorAll('[data-field-visibility-conditions]').forEach(function (inputField) {
			const visibilityConditions = JSON.parse(inputField.getAttribute('data-field-visibility-conditions'));
			const visibilityCondition = visibilityConditions[0]; // Currently we support only one visibility condition.
			const {field} = visibilityCondition;

			fields[field] = {
				...fields[field],
				[inputField.name]: visibilityConditions
			}
		})

		return fields;
	}

	/**
	 * Handle fields visibility.
	 * @unreleased
	 */
	function handleVisibility(donationForm, visibilityConditions) {
		for (const [inputFieldName, visibilityConditions] of Object.entries(visibilityConditions)) {
			const inputField = donationForm.querySelector(`[name="${inputFieldName}"]`);
			const fieldWrapper = inputField.closest('.form-row');
			const visibilityCondition = visibilityConditions[0]; // Currently we support only one visibility condition.
			let visible = false;
			const {field, value} = visibilityCondition;

			const inputs = donationForm.querySelectorAll(`[name="${field}"]`);
			let hasFieldController = !!inputs.length;

			// Do not apply visibility conditions if field controller does not exit in DOM.
			if (!hasFieldController) {
				return;
			}

			inputs.forEach((input) => {
				const fieldType = input.getAttribute('type');

				if (fieldType && (fieldType === 'radio' || fieldType === 'checkbox')) {
					if (input.checked && input.value === value) {
						visible = true;
					}
				} else if (input.value === value) {
					visible = true;
				}
			});

			// Show or Hide field wrapper.
			visible ?
				fieldWrapper.classList.remove('give-hidden') :
				fieldWrapper.classList.add('give-hidden');
		}
	}

	/**
	 * Setup state for condition visibility settings.
	 * state contains list of watched elements per donation form.
	 *
	 * @unreleased
	 * @returns {Promise<void>}
	 */
	 function setupState() {
		document.querySelectorAll('form.give-form')
			.forEach(function (donationForm) {
				const uniqueDonationFormId = donationForm.getAttribute('data-id');
				state[uniqueDonationFormId] = {
					watchedElements: getWatchedElementNames(donationForm),
					...state[uniqueDonationFormId]
				}
			});
	}

	await setupState();

	 // Apply conditional visibility settings to donation form.
	for (const [ uniqueDonationFormId, donationFormState ] of Object.entries(state) ) {
		for (const [ watchedFieldName, visibilityConditions ] of Object.entries( donationFormState.watchedElements )) {
			handleVisibility(
				document.querySelector(`form[data-id="${uniqueDonationFormId}"]`)
					.closest('.give-form'),
				visibilityConditions
			);
		}
	}

	// Look for change in watched elements.
	document.addEventListener('change', function (event) {
		const donationForm = event.target.closest('form.give-form');

		// Exit if field is not element of donation form.
		if (!donationForm) {
			return false;
		}

		const uniqueDonationFormId = donationForm.getAttribute('data-id');
		const formState = state[uniqueDonationFormId];
		const fieldName = event.target.getAttribute('name');
		const watchedElementNames = Object.keys(formState.watchedElements);

		// Exit if field is not in list of watched elements.
		if (!watchedElementNames.includes(fieldName)) {
			return false;
		}

		const watchedFieldState = formState.watchedElements[fieldName];
		handleVisibility(donationForm, watchedFieldState)
	});
});
