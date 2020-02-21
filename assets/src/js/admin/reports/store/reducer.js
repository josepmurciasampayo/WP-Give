import moment from 'moment';

export const reducer = ( state, action ) => {
	switch ( action.type ) {
		case 'SET_DATES':
			return {
				...state,
				period: {
					startDate: action.payload.startDate,
					endDate: action.payload.endDate,
					range: 'custom',
				},
			};
		case 'SET_RANGE':
			//determine new startDate based on selected range
			let startDate;
			let endDate = state.period.endDate;
			switch ( action.payload.range ) {
				case 'day':
					startDate = moment( state.period.endDate ).subtract( 1, 'days' );
					break;
				case 'week':
					startDate = moment( state.period.endDate ).subtract( 7, 'days' );
					break;
				case 'month':
					startDate = moment( state.period.endDate ).subtract( 1, 'months' );
					break;
				case 'year':
					startDate = moment( state.period.endDate ).subtract( 1, 'years' );
					break;
				case 'alltime':
					startDate = moment( window.giveReportsData.allTimeStart );
					endDate = moment();
					break;
			}
			return {
				...state,
				period: { ...state.period,
					startDate,
					endDate,
					range: action.payload.range,
				},
			};
		case 'SET_GIVE_STATUS':
			return {
				...state,
				giveStatus: action.payload,
			};
		case 'SET_PAGE_LOADED':
			return {
				...state,
				pageLoaded: action.payload,
			};
		default:
			return state;
	}
};
