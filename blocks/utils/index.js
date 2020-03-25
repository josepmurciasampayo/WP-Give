/**
 * getSiteUrl from API root
 * @returns {string} siteurl
 */
export function getSiteUrl() {
	return wpApiSettings.root.replace( '/wp-json/', '' );
}

/**
 * Convert forms object in option
 *
 * @since 2.7.0
 *
 * @param {object} forms
 *
 * @return {[]}
 */
export function getFormOptions( forms ) {
	let formOptions = [];

	if ( forms ) {
		formOptions = forms.map(
			( { id, title: { rendered: title } } ) => {
				return {
					value: id,
					label: title === '' ? `${ id } : ${ __( 'No form title' ) }` : title,
				};
			}
		);
	}

	// Add Default option
	formOptions.unshift( { value: '0', label: __( '-- Select Form --' ) } );

	return formOptions;
}

/**
 * Convert forms object in option
 *
 * @since 2.7.0
 *
 * @param {object} forms
 * @param {number} SelectedFormId
 *
 * @return {boolean}
 */
export function isShowOldSettings( forms, SelectedFormId ) {
	if ( forms ) {
		const data = forms.find(
			( form ) => {
				return parseInt( form.id ) === parseInt( SelectedFormId );
			}
		);

		return data && ( ! data.formTemplate || data.formTemplate === 'legacy' );
	}

	return false;
}
