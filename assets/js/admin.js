/**
 * VGSR Widgets admin scripts
 *
 * @package VGSR Widgets
 * @subpackage Administration
 */

( function( $ ) {

	/**
	 * Widget: Latest Post
	 *
	 * Since the widget interfaces (both admin and Customizer)
	 * dynamically add widget blocks, the event handlers have
	 * to be delegated to a parent element.
	 */
	$( document )

		// Toggle post type details
		.on( 'change', '.widget[id*="_vgsr-latest-post"] select.post_type', function( e ) {
			$(this).closest( '.widget' ).find( '[class*="post-type_"]' ).show().not( '.post-type_' + e.target.value ).hide();
		})

		// Toggle gallery section
		.on( 'change', '.widget[id*="_vgsr-latest-post"] input.post_gallery', function( e ) {
			$(this).closest( '.widget' ).find( '.post-gallery' ).css({
				'display': e.target.checked ? '' : 'none'
			});
		});

})( jQuery );