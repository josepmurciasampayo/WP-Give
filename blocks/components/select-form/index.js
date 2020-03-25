/**
 * External dependencies
 */
import { isUndefined } from 'lodash';

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { withSelect } = wp.data;
const { Placeholder, Spinner } = wp.components;

/**
 * Internal dependencies
 */
import './index.scss';
import GiveBlankSlate from '../blank-slate';
import NoForms from '../no-form';
import ChosenSelect from '../chosen-select';

/**
 * Render form select UI
 */
const giveFormOptionsDefault = { value: '0', label: __( '-- Select Form --' ) };

const SelectForm = ( { forms, setAttributes } ) => {
	// Event(s)
	const getFormOptions = () => {
		// Add API Data To Select Options

		let formOptions = [];

		if ( ! isUndefined( forms ) ) {
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
		formOptions.unshift( giveFormOptionsDefault );

		return formOptions;
	};

	const setFormIdTo = id => {
		setAttributes( { id: Number( id ) } );
	};

	// Render Component UI
	let componentUI;

	if ( ! forms ) {
		componentUI = <Placeholder><Spinner /></Placeholder>;
	} else if ( forms && forms.length === 0 ) {
		componentUI = <NoForms />;
	} else {
		componentUI = (
			<GiveBlankSlate title={ __( 'Donation Form' ) }>
				<ChosenSelect
					className="give-blank-slate__select"
					options={ getFormOptions() }
					onChange={ setFormIdTo }
				/>
			</GiveBlankSlate>
		);
	}

	return componentUI;
};

/**
 * Export with forms data
 */
export default withSelect( ( select ) => {
	return {
		forms: select( 'core' ).getEntityRecords( 'postType', 'give_forms', { per_page: 30 } ),
	};
} )( SelectForm );
