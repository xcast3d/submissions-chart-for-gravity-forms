/* global GFSC, Chart, jQuery */
( function ( $ ) {
	'use strict';

	const i18n = GFSC.i18n;

	let chartInstance = null;
	let currentRange  = '30';
	let formId        = 0;

	const $loading     = $( '#gfsc-loading' );
	const $canvas      = $( '#gfsc-canvas' );
	const $empty       = $( '#gfsc-empty' );
	const $customRange = $( '#gfsc-custom-range' );
	const $select      = $( '#gfsc-form-select' );

	// ── Bootstrap ──────────────────────────────────────────────────────────────

	$( function () {
		// Build options with text nodes so form titles can't inject markup.
		$select.append( $( '<option>' ).val( 0 ).text( i18n.allForms ) );
		( GFSC.forms || [] ).forEach( function ( form ) {
			$select.append( $( '<option>' ).val( form.id ).text( form.title ) );
		} );

		// Allow deep-linking a form: admin.php?page=gfsc_submissions_chart&form_id=3
		const requested = parseInt( new URLSearchParams( window.location.search ).get( 'form_id' ), 10 );
		if ( requested && $select.find( 'option[value="' + requested + '"]' ).length ) {
			formId = requested;
			$select.val( formId );
		}

		loadChart();
	} );

	// ── Form selector ─────────────────────────────────────────────────────────

	$select.on( 'change', function () {
		formId = parseInt( $( this ).val(), 10 ) || 0;
		if ( currentRange === 'custom' ) {
			$( '#gfsc-apply-custom' ).trigger( 'click' );
		} else {
			loadChart();
		}
	} );

	// ── Range toggle ───────────────────────────────────────────────────────────

	$( '.gfsc-range-btn' ).on( 'click', function () {
		const $btn = $( this );
		$( '.gfsc-range-btn' ).removeClass( 'active' ).attr( 'aria-pressed', 'false' );
		$btn.addClass( 'active' ).attr( 'aria-pressed', 'true' );

		currentRange = String( $btn.data( 'range' ) );

		if ( currentRange === 'custom' ) {
			$customRange.prop( 'hidden', false );
			if ( ! $( '#gfsc-end' ).val() ) {
				$( '#gfsc-end' ).val( isoDate( 0 ) );
			}
			if ( ! $( '#gfsc-start' ).val() ) {
				$( '#gfsc-start' ).val( isoDate( 29 ) );
			}
		} else {
			$customRange.prop( 'hidden', true );
			loadChart();
		}
	} );

	$( '#gfsc-apply-custom' ).on( 'click', function () {
		const start = $( '#gfsc-start' ).val();
		const end   = $( '#gfsc-end' ).val();
		if ( ! start || ! end ) {
			window.alert( i18n.pickDates );
			return;
		}
		if ( start > end ) {
			window.alert( i18n.badRange );
			return;
		}
		loadChart( start, end );
	} );

	// ── Data fetch ─────────────────────────────────────────────────────────────

	function loadChart( startDate, endDate ) {
		showLoading();

		const payload = {
			action:  'gfsc_get_data',
			nonce:   GFSC.nonce,
			form_id: formId,
			range:   currentRange,
		};

		if ( currentRange === 'custom' ) {
			payload.start_date = startDate;
			payload.end_date   = endDate;
		}

		$.post( GFSC.ajaxUrl, payload )
			.done( function ( response ) {
				if ( response.success ) {
					renderChart( response.data );
				} else {
					showError( response.data || i18n.loadError );
				}
			} )
			.fail( function () {
				showError( i18n.networkErr );
			} );
	}

	// ── Chart render ───────────────────────────────────────────────────────────

	function renderChart( data ) {
		$loading.hide();
		updateTitle( data.total );

		if ( ! data.labels || data.labels.length === 0 || data.total === 0 ) {
			$canvas.hide();
			$empty.prop( 'hidden', false );
			return;
		}

		$empty.prop( 'hidden', true );
		$canvas.show();

		if ( chartInstance ) {
			chartInstance.destroy();
		}

		const accent = getComputedStyle( document.documentElement )
			.getPropertyValue( '--wp-admin-theme-color' ).trim() || '#2271b1';

		chartInstance = new Chart( $canvas[ 0 ].getContext( '2d' ), {
			type: 'bar',
			data: {
				labels:   data.labels.map( formatLabel ),
				datasets: [ {
					label:                'Submissions',
					data:                 data.counts,
					backgroundColor:      withAlpha( accent, 0.18 ),
					borderColor:          accent,
					borderWidth:          2,
					borderRadius:         4,
					borderSkipped:        false,
					hoverBackgroundColor: withAlpha( accent, 0.35 ),
				} ],
			},
			options: {
				responsive:          true,
				maintainAspectRatio: false,
				animation: {
					duration: 400,
					easing:   'easeOutQuart',
				},
				plugins: {
					legend: { display: false },
					tooltip: {
						backgroundColor: '#1d2327',
						titleColor:      '#fff',
						bodyColor:       '#c3c4c7',
						padding:         10,
						cornerRadius:    6,
						callbacks: {
							label: ( item ) => ` ${ item.raw } ${ item.raw === 1 ? i18n.submission : i18n.submissions }`,
						},
					},
				},
				scales: {
					x: {
						grid:   { display: false },
						border: { display: false },
						ticks: {
							color:         '#646970',
							font:          { size: 11 },
							maxRotation:   45,
							autoSkip:      true,
							maxTicksLimit: 14,
						},
					},
					y: {
						beginAtZero: true,
						grid:        { color: '#f0f0f1' },
						border:      { display: false },
						ticks: {
							color:     '#646970',
							font:      { size: 11 },
							precision: 0,
						},
					},
				},
			},
		} );
	}

	// ── UI helpers ─────────────────────────────────────────────────────────────

	function showLoading() {
		$loading.show();
		$canvas.hide();
		$empty.prop( 'hidden', true );
	}

	function showError( msg ) {
		$loading.hide();
		$canvas.hide();
		$empty.text( msg ).prop( 'hidden', false );
	}

	function updateTitle( total ) {
		const rangeLabel = $( '.gfsc-range-btn.active' ).text();
		$( '#gfsc-title' ).text( `${ rangeLabel } · ${ total.toLocaleString() } ${ total === 1 ? i18n.submission : i18n.submissions }` );
	}

	// ── Utility ────────────────────────────────────────────────────────────────

	function formatLabel( dateStr ) {
		const [ y, m, d ] = dateStr.split( '-' );
		const date = new Date( +y, +m - 1, +d );
		return date.toLocaleDateString( undefined, { month: 'short', day: 'numeric' } );
	}

	// Local-time Y-m-d for `daysAgo` days before today (toISOString would use UTC).
	function isoDate( daysAgo ) {
		const d = new Date();
		d.setDate( d.getDate() - daysAgo );
		const pad = ( n ) => String( n ).padStart( 2, '0' );
		return `${ d.getFullYear() }-${ pad( d.getMonth() + 1 ) }-${ pad( d.getDate() ) }`;
	}

	function withAlpha( hex, alpha ) {
		let clean = hex.replace( '#', '' );
		if ( clean.length === 3 ) {
			clean = clean.split( '' ).map( ( c ) => c + c ).join( '' );
		}
		const r = parseInt( clean.substring( 0, 2 ), 16 );
		const g = parseInt( clean.substring( 2, 4 ), 16 );
		const b = parseInt( clean.substring( 4, 6 ), 16 );
		return `rgba(${ r },${ g },${ b },${ alpha })`;
	}

} )( jQuery );
