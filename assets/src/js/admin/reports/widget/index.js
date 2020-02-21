// Reports admin dashboard widget

// Dependencies
const { __ } = wp.i18n;

// Store-related dependencies
import { useStoreValue } from '../store';

import './style.scss';

// Components
import Grid from '../components/grid';
import Card from '../components/card';
import RESTChart from '../components/rest-chart';
import RESTMiniChart from '../components/rest-mini-chart';
import NotFoundNotice from '../components/not-found-notice';
import NoDataNotice from '../components/no-data-notice';
import LoadingNotice from '../components/loading-notice';
import MiniPeriodSelector from '../components/mini-period-selector';

const Widget = () => {
	const [ { giveStatus, pageLoaded } ] = useStoreValue();

	return (
		<div className="givewp-reports-widget-container">
			{ giveStatus === 'donations_found_on_site' && (
				<NotFoundNotice version={ 'dashboard' } />
			) }
			{ giveStatus === 'no_donations_found' && (
				<NoDataNotice version={ 'dashboard' } />
			) }
			{ pageLoaded === false && (
				<LoadingNotice />
			) }
			<Grid gap="12px" visible={ pageLoaded }>
				<Card width={ 12 }>
					<RESTChart
						title={ __( 'All Time Income', 'give' ) }
						headerEls={ <MiniPeriodSelector /> }
						type="line"
						aspectRatio={ 0.8 }
						endpoint="income"
						showLegend={ false }
					/>
				</Card>
				<Card width={ 6 }>
					<RESTMiniChart
						title={ __( 'Total Income', 'give' ) }
						endpoint="total-income"
					/>
				</Card>
				<Card width={ 6 }>
					<RESTMiniChart
						title={ __( 'Avg. Donation', 'give' ) }
						endpoint="average-donation"
					/>
				</Card>
				<Card width={ 6 }>
					<RESTMiniChart
						title={ __( 'Total Donors', 'give' ) }
						endpoint="total-donors"
					/>
				</Card>
				<Card width={ 6 }>
					<RESTMiniChart
						title={ __( 'Total Refunds', 'give' ) }
						endpoint="total-refunds"
					/>
				</Card>
			</Grid>
		</div>
	);
};
export default Widget;
