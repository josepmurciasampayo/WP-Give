import axios from 'axios';

// Internal dependencies
import Content from './content';
import DashboardContent from './dashboard-content';
import { store } from './store';
import { getAPIRoot, getAPINonce } from '../../utils';
import { setDonations, setQuerying } from './store/actions';

export const registerDonationHistoryTab = () => {
	const { dispatch } = store;

	dispatch( setQuerying( true ) );
	axios.get( getAPIRoot() + 'give-api/v2/donor-profile/donations', {
		headers: {
			'X-WP-Nonce': getAPINonce(),
		},
	} )
		.then( ( response ) => response.data )
		.then( ( data ) => {
			dispatch( setDonations( data.donations ) );
			dispatch( setQuerying( false ) );
		} );

	window.giveDonorProfile.utils.registerTab( {
		label: 'Donation History',
		icon: 'calendar-alt',
		slug: 'donation-history',
		content: Content,
		dashboardContent: DashboardContent,
	} );
};
