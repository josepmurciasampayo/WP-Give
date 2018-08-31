const give = require( './test-utility' );

describe( 'Display option: Button', () => {
	beforeAll( async () => await page.goto( `${give.utility.vars.rootUrl}/donations/button-form/` ) )

	it( 'INTERACTION: click donate button to reveal the form', async () => {
		await expect( page ).toClick( 'button', { text: 'Donate Now' } )
	})

	give.utility.fn.verifyExistence( page, [

		{
			desc: 'verify form title as "Button Form"',
			selector: '.give-form-title',
			innerText: 'Button Form',
		},

		{
			desc: 'verify form content as "Form Content of the Button Form."',
			selector: '.give-form-content-wrap p',
			innerText: 'Form Content of the Button Form.',
		},

		{
			desc: 'verify currency symbol as "$"',
			selector: '.give-currency-symbol',
			innerText: '$',
		},

		{
			desc: 'verify currency value "10.00"',
			selector: '.give-text-input',
			value: '10.00',
		},

		{
			desc: 'verify donation level 1 as "10.00"',
			selector: '.give-btn-level-0',
			innerText: 'Bronze',
			value: '10.00',
		},

		{
			desc: 'verify custom level as "custom"',
			selector: '.give-btn-level-custom',
			innerText: 'or donate what you like!',
			value: 'custom',
		},

		{
			desc: 'verify select payment method label as "Select Payment Method"',
			selector: '.give-payment-mode-label',
			innerText: 'Select Payment Method',
		},

		{
			desc: 'verify test donation label as "Test Donation"',
			selector: '#give-gateway-option-manual',
			innerText: 'Test Donation',
		},

		{
			desc: 'verify offline donation label as "Offline Donation"',
			selector: '#give-gateway-option-offline',
			innerText: 'Offline Donation',
		},

		{
			desc: 'verify paypal donation label as "PayPal"',
			selector: '#give-gateway-option-paypal',
			innerText: 'PayPal',
		},

		{
			desc: 'verify manual payment radio with value as "manual"',
			selector: 'input[id^="give-gateway-manual"]',
			value: 'manual',
		},

		{
			desc: 'verify offline payment radio with value as "offline"',
			selector: 'input[id^="give-gateway-offline"]',
			value: 'offline',
		},

		{
			desc: 'verify paypal payment radio with value as "paypal"',
			selector: 'input[id^="give-gateway-paypal"]',
			value: 'paypal',
		},

		{
			desc: 'verify personal info title as "Personal Info"',
			selector: '#give_purchase_form_wrap legend',
			innerText: 'Personal Info',
		},

		{
			desc: 'verify first name label',
			selector: 'label[for="give-first"]',
			innerText: 'First Name',
		},

		{
			desc: 'verify first name label input field',
			selector: '#give-first',
			placeholder: 'First Name',
		},

		{
			desc: 'verify last name label',
			selector: 'label[for="give-last"]',
			innerText: 'Last Name',
		},

		{
			desc: 'verify last name input field',
			selector: '#give-last',
			placeholder: 'Last Name',
		},

		{
			desc: 'verify company name label',
			selector: 'label[for="give-company"]',
			innerText: 'Company Name',
		},

		{
			desc: 'verify company name input field',
			selector: '#give-company',
			placeholder: 'Company Name',
		},

		{
			desc: 'verify email address label',
			selector: 'label[for="give-email"]',
			innerText: 'Email Address',
		},

		{
			desc: 'verify email address input field',
			selector: '#give-email',
			placeholder: 'Email Address',
		},

		{
			desc: 'verify anonymous donation label',
			selector: 'label[for="give-anonymous-donation"]',
			innerText: 'Make this an anonymous donation',
		},

		{
			desc: 'verify anonymous donation checkbox',
			selector: '#give-anonymous-donation',
		},

		{
			desc: 'verify comment label',
			selector: 'label[for="give-comment"]',
			innerText: 'Comment',
		},

		{
			desc: 'verify comment textarea',
			selector: '#give-comment',
			placeholder: 'Leave a comment',
		},

		{
			desc: 'verify create an account label',
			selector: 'label[for^="give-create-account"]',
			innerText: 'Create an account',
		},

		{
			desc: 'verify create an account checkbox',
			selector: 'input[id^="give-create-account"]',
		},

		{
			desc: 'verify submit donation button',
			selector: '#give-purchase-button',
			value: 'Make a Donation',
		},
	])

	give.utility.fn.verifyInteraction( page, [
		{
			desc: 'verify hover on title tooltip',
			selector: 'label[for="give-title"] .give-tooltip',
			event: 'hover',
		},

		{
			desc: 'verify hover on first name tooltip',
			selector: 'label[for="give-first"] .give-tooltip',
			event: 'hover',
		},

		{
			desc: 'verify hover on last name tooltip',
			selector: 'label[for="give-last"] .give-tooltip',
			event: 'hover',
		},

		{
			desc: 'verify hover on company tooltip',
			selector: 'label[for="give-company"] .give-tooltip',
			event: 'hover',
		},

		{
			desc: 'verify hover on email tooltip',
			selector: 'label[for="give-email"] .give-tooltip',
			event: 'hover',
		},

		{
			desc: 'verify hover on comment tooltip',
			selector: 'label[for="give-comment"] .give-tooltip',
			event: 'hover',
		},

		{
			desc: 'verify hover on create account tooltip',
			selector: 'label[for^="give-create-account"] .give-tooltip',
			event: 'hover',
		},
	])

	it( 'INTERACTION: verify select offline payment method', async () => {
		await page.click( 'label[id="give-gateway-option-offline"]' )
	})

	it( 'EXISTENCE: verify offline payment method output', async () => {
		await expect( page ).toMatch( 'Make a check payable to "Give Automation"' )
	})

	it( 'INTERACTION: verify select paypal payment method', async () => {
		await page.click( 'label[id="give-gateway-option-paypal"]' )
	})

	it( 'EXISTENCE: verify paypal payment method output', async () => {
		await expect( page ).toMatch( 'Billing Details' )
	})

	give.utility.fn.makeDonation( page, {
		give_first: 'Erin',
		give_last: 'Hannon',
		give_email: 'erin.hannon@gmail.com',
	})

	give.utility.fn.verifyDonation( page, [
		'Payment Complete: Thank you for your donation.',
		'Dr. Erin Hannon',
		'Button Form',
	])
})
