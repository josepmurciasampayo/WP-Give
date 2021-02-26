import Button from '../button';
import { logoutWithAPI } from './utils';

const { __ } = wp.i18n;

import './style.scss';

const LogoutModal = ( { onRequestClose } ) => {
	const handleLogout = async() => {
		await logoutWithAPI();
		window.location.reload();
	};

	return (
		<div className="give-donor-profile-logout-modal">
			<div className="give-donor-profile-logout-modal__frame">
				<div className="give-donor-profile-logout-modal__header">
					{ __( 'Are you sure you want to logout?', 'give' ) }
				</div>
				<div className="give-donor-profile-logout-modal__body">
					<div className="give-donor-profile-logout-modal__buttons">
						<Button onClick={ () => handleLogout() }>
							{ __( 'Yes, logout', 'give' ) }
						</Button>
						<a onClick={ () => onRequestClose() }>
							{ __( 'Nevermind', 'give' ) }
						</a>
					</div>
				</div>
			</div>
			<div className="give-donor-profile-logout-modal__bg" onClick={ () => onRequestClose() } />
		</div>
	);
};

export default LogoutModal;
