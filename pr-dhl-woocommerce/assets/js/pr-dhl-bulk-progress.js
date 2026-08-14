/**
 * Live progress panel for background bulk DHL label creation on the WooCommerce Orders screen.
 *
 * Polls the batch-progress AJAX endpoint, updates a progress bar, and once every job has settled
 * exposes "Download all labels" (merged PDF), "Retry failed" and "Dismiss" actions.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.prDhlLabelBatch || {};
	var $panel = $( '#pr-dhl-label-batch' );

	if ( ! $panel.length || ! cfg.ajaxUrl ) {
		return;
	}

	var i18n     = cfg.i18n || {};
	var $title   = $panel.find( '.pr-dhl-label-batch__title strong' );
	var $fill    = $panel.find( '.pr-dhl-label-batch__fill' );
	var $status  = $panel.find( '.pr-dhl-label-batch__status' );
	var $details = $panel.find( '.pr-dhl-label-batch__details' );
	var $actions = $panel.find( '.pr-dhl-label-batch__actions' );
	var $download = $panel.find( '.pr-dhl-label-batch__download' );
	var $retry   = $panel.find( '.pr-dhl-label-batch__retry' );
	var $dismiss = $panel.find( '.pr-dhl-label-batch__dismiss' );

	var timer = null;
	var downloading = false;

	function format( tpl, a, b, c ) {
		return String( tpl ).replace( '%1$d', a ).replace( '%2$d', b ).replace( '%3$d', c );
	}

	function post( action, extra ) {
		return $.post( cfg.ajaxUrl, $.extend( { action: action, nonce: cfg.nonce }, extra || {} ) );
	}

	function stop() {
		if ( timer ) {
			window.clearInterval( timer );
			timer = null;
		}
	}

	function start() {
		stop();
		timer = window.setInterval( poll, cfg.interval || 5000 );
	}

	function renderFailures( data ) {
		$details.empty();

		var failures = data.failures || [];
		if ( ! failures.length ) {
			return;
		}

		// Flag that some failed orders may already have a label at DHL and must not be blindly retried.
		var anyPurchased = false;

		// Group identical failure messages so a systematic cause (e.g. one bad setting) reads as
		// "10× <reason>" rather than ten separate lines.
		var groups = {};
		var order = [];
		$.each( failures, function ( i, f ) {
			if ( f.purchased ) {
				anyPurchased = true;
			}
			if ( ! groups[ f.message ] ) {
				groups[ f.message ] = [];
				order.push( f.message );
			}
			groups[ f.message ].push( f );
		} );

		$( '<p/>' ).css( 'margin', '4px 0 2px' ).append( $( '<strong/>' ).text( i18n.failTitle ) ).appendTo( $details );

		var $list = $( '<ul/>' ).css( { margin: '0 0 4px 18px', 'list-style': 'disc' } ).appendTo( $details );

		$.each( order, function ( i, message ) {
			var items = groups[ message ];
			var $li = $( '<li/>' );
			$( '<strong/>' ).text( items.length + '× ' ).appendTo( $li );
			$li.append( document.createTextNode( message + ' — ' ) );

			$.each( items, function ( j, f ) {
				if ( j ) {
					$li.append( document.createTextNode( ', ' ) );
				}
				var $ref = $( '<a/>', { target: '_blank', rel: 'noopener' } ).text( '#' + f.number );
				if ( f.edit_url ) {
					$ref.attr( 'href', f.edit_url );
				}
				$ref.appendTo( $li );
			} );

			// Per-group guidance: is this a "just Retry" transient error or a "fix the order first" one?
			var hint = items[ 0 ].category === 'transient' ? i18n.catTransient
				: ( items[ 0 ].category === 'action' ? i18n.catAction : '' );
			if ( hint ) {
				$( '<em/>' ).css( { display: 'block', opacity: 0.8 } ).text( hint ).appendTo( $li );
			}

			$li.appendTo( $list );
		} );

		if ( anyPurchased ) {
			$( '<p/>' ).css( { margin: '4px 0', color: '#8a6d00' } ).text( i18n.purchased ).appendTo( $details );
		}
	}

	function render( data ) {
		if ( ! data || ! data.active ) {
			stop();
			$panel.hide();
			return;
		}

		$panel.show();

		var handled = data.created + data.failed;
		var pct = data.total ? Math.round( ( handled / data.total ) * 100 ) : 0;
		$fill.css( 'width', pct + '%' );
		$status.text( format( i18n.status, data.created, data.failed, data.pending ) );
		renderFailures( data );

		if ( data.stalled ) {
			stop();
			$title.text( i18n.stalled );
			$panel.removeClass( 'notice-info' ).addClass( 'notice-warning' );
			$download.toggle( !! data.can_download );
			$retry.toggle( !! data.retryable );
			$actions.show();
			return;
		}

		if ( ! data.done ) {
			$title.text( i18n.creating );
			$actions.hide();
			return;
		}

		stop();
		$title.text( data.has_failed ? i18n.doneErrors : i18n.doneOk );
		$panel.removeClass( 'notice-info' ).addClass( data.has_failed ? 'notice-warning' : 'notice-success' );
		$download.toggle( !! data.can_download );
		$retry.toggle( !! data.retryable );
		$actions.show();
	}

	function poll() {
		post( 'pr_dhl_label_batch_progress' ).done( function ( res ) {
			if ( res && res.success ) {
				render( res.data );
			}
		} );
	}

	$download.on( 'click', function ( e ) {
		e.preventDefault();

		if ( downloading ) {
			return;
		}
		downloading = true;

		post( 'pr_dhl_label_batch_download' ).done( function ( res ) {
			if ( res && res.success && res.data && res.data.url ) {
				window.location.href = res.data.url;
			} else {
				$status.text( ( res && res.data && res.data.message ) || i18n.error );
			}
		} ).fail( function () {
			$status.text( i18n.error );
		} ).always( function () {
			downloading = false;
		} );
	} );

	$retry.on( 'click', function ( e ) {
		e.preventDefault();

		post( 'pr_dhl_label_batch_retry' ).done( function ( res ) {
			if ( ! res || ! res.success ) {
				$status.text( ( res && res.data && res.data.message ) || i18n.error );
				return;
			}

			$panel.removeClass( 'notice-success notice-warning' ).addClass( 'notice-info' );
			$actions.hide();
			poll();
			start();
		} );
	} );

	$dismiss.on( 'click', function ( e ) {
		e.preventDefault();
		stop();
		post( 'pr_dhl_label_batch_dismiss' );
		$panel.slideUp();
	} );

	poll();
	start();
} )( jQuery );
