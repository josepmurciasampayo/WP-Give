/* globals jQuery, ajaxurl, give_addon_var */

( function( $ ) {
	// $(document).ready(function(){
// 	var $Container = $('#give-license-activator-wrap'),
// 	    $form = $('form', $Container),
// 		$errorContainer = $( '.give-errors', $Container ),
// 		apiURL = 'http://givewp.test/chechout',
// 		data = {
// 			edd_action: 'check_license',
// 			license : '',
// 			url: window.location.origin
// 		};
//
// 	$form.on( 'submit', function(){
// 		data.license = $( 'input[name="give_license_key"]', $(this) ).val().trim();
//
// 		// Remove all errors.
// 		$errorContainer.empty();
//
// 		if( ! data.license ) {
// 			$errorContainer.html( '<div class="give-notice notice error"><p>License is empty</p></div>' );
// 			return false;
// 		}
//
// 		fetch( apiURL + '?' + encodeQueryData(data) )
// 			.then(function(response){ return JSON.parse( response )})
// 			.then(function ( response ) {
// 				console.log( response );
// 			});
//
// 		return false;
// 	});
//
// 	function encodeQueryData(data) {
// 		const ret = [];
// 		for (let d in data)
// 			ret.push(encodeURIComponent(d) + '=' + encodeURIComponent(data[d]));
// 		return ret.join('&');
// 	}
// });

	$( document ).ready( function() {
		const $container = $( '#give-addon-uploader-wrap' ),
			$form = $( 'form', $container ),
			$file = $( 'input[type="file"]', $form ),
			$noticeContainer = $( '.give-notices', $container );

		// Drop
		// @todo: add validation to upload only zip files
		$container.on( 'drop', function( e ) {
			e.stopPropagation();
			e.preventDefault();

			$( this ).removeClass( 'thick-border' );

			const file = e.originalEvent.dataTransfer.files,
				fd = new FormData();

			fd.append( 'file', file[ 0 ] );

			giveUploadData( fd );
		} );

		// Drag over
		$container.on( 'dragover', function( e ) {
			$( this ).addClass( 'thick-border' );
		} ).on( 'dragleave', function( e ) {
			$( this ).removeClass( 'thick-border' );
		} );

		$file.on( 'change', function( e ) {
			e.stopPropagation();
			e.preventDefault();

			const fd = new FormData(),
				files = $file[ 0 ].files[ 0 ];

			if ( ! files ) {
				return false;
			}

			fd.append( 'file', files );
			giveUploadData( fd );
		} );

		/**
		 * Sending AJAX request and upload file
		 *
		 * @since 2.5.0
		 * @param {FormData} formData Form Data.
		 */
		function giveUploadData( formData ) {
			$noticeContainer.empty();

			$.ajax( {
				url: `${ ajaxurl }?action=give_upload_addon&_wpnonce=${ $( 'input[name="_give_upload_addon"]', $form ).val().trim() }`,
				method: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				dataType: 'json',
				beforeSend: function() {
					$noticeContainer.html(`<div class="give-notice notice notice-info"><p>${ give_addon_var.notices.uploading }</p></div>`);
				},
				success: function( response ) {
					if ( true === response.success ) {
						$noticeContainer.html(`<div class="give-notice notice notice-success"><p>${ give_addon_var.notices.uploaded }</p></div>` );
						return;
					}

					$noticeContainer.html( `<div class="give-notice notice notice-error"><p>${ response.data.errorMsg }</p></div>` );
				},
			} );
		}
	} );
}( jQuery ) );
