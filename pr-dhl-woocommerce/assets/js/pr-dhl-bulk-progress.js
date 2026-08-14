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

		if ( data.stalled ) {
			stop();
			$title.text( i18n.stalled );
			$panel.removeClass( 'notice-info' ).addClass( 'notice-warning' );
			$download.toggle( !! data.can_download );
			$retry.toggle( !! data.has_failed );
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
		$retry.toggle( !! data.has_failed );
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
