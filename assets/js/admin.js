/* global GFSC, Chart, jQuery */
( function ( $ ) {
	'use strict';

	const i18n = GFSC.i18n;

	let chartInstance = null;
	let chartDays     = [];
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

		filterRecentCards();
		loadChart();
	} );

	// ── Form selector ─────────────────────────────────────────────────────────

	$select.on( 'change', function () {
		formId = parseInt( $( this ).val(), 10 ) || 0;
		filterRecentCards();
		closeDay();
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
		chartDays = data.labels;

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
				// Clicks anywhere in a day's column count, not just on the bar itself.
				interaction: { mode: 'index', intersect: false },
				onClick: ( event, elements ) => {
					if ( elements.length && data.counts[ elements[ 0 ].index ] > 0 ) {
						openDay( chartDays[ elements[ 0 ].index ] );
					}
				},
				onHover: ( event, elements ) => {
					const clickable = elements.length && data.counts[ elements[ 0 ].index ] > 0;
					event.native.target.style.cursor = clickable ? 'pointer' : 'default';
				},
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

	// ── Day drill-down ─────────────────────────────────────────────────────────

	const $day = $( '#gfsc-day' );

	$( '#gfsc-day-close' ).on( 'click', closeDay );

	// A single form maps straight onto GF's Entries list filtered by date; across
	// forms GF has no combined view, so list that day's entries here instead.
	function openDay( date ) {
		if ( formId ) {
			window.location.href = entriesUrl( formId, date );
			return;
		}

		$( '#gfsc-day-title' ).text( '…' );
		$( '#gfsc-day-forms, #gfsc-day-rows' ).empty();
		$( '#gfsc-day-note' ).prop( 'hidden', true );
		$day.prop( 'hidden', false );

		$.post( GFSC.ajaxUrl, {
			action:  'gfsc_get_day',
			nonce:   GFSC.nonce,
			form_id: formId,
			date:    date,
		} )
			.done( function ( response ) {
				if ( response.success ) {
					renderDay( response.data );
				} else {
					$( '#gfsc-day-title' ).text( response.data || i18n.loadError );
				}
			} )
			.fail( function () {
				$( '#gfsc-day-title' ).text( i18n.networkErr );
			} );

		$day[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	}

	function renderDay( data ) {
		$( '#gfsc-day-title' ).text(
			sprintf( i18n.dayTitle, data.label ) + ` · ${ data.total.toLocaleString() } ${ data.total === 1 ? i18n.submission : i18n.submissions }`
		);

		const $forms = $( '#gfsc-day-forms' );
		data.forms.forEach( function ( form ) {
			$forms.append(
				$( '<li>' ).append(
					$( '<a>' ).attr( 'href', form.url ).text( sprintf( i18n.openEntries, form.title ) ),
					$( '<span class="count">' ).text( ` (${ form.count.toLocaleString() })` )
				)
			);
		} );

		const $rows = $( '#gfsc-day-rows' );
		data.entries.forEach( function ( entry ) {
			$rows.append(
				$( '<tr>' ).append(
					$( '<td class="gfsc-col-date">' ).text( entry.date ),
					$( '<td>' ).text( entry.form_title ),
					$( '<td>' ).text( entry.summary || `#${ entry.id }` ),
					$( '<td class="gfsc-col-action">' ).append( $( '<a>' ).attr( 'href', entry.url ).text( i18n.view ) )
				)
			);
		} );

		if ( data.truncated ) {
			$( '#gfsc-day-note' ).text( sprintf( i18n.truncated, data.entries.length, data.total ) ).prop( 'hidden', false );
		}
	}

	function closeDay() {
		$day.prop( 'hidden', true );
	}

	function entriesUrl( id, date ) {
		const params = new URLSearchParams( { page: 'gf_entries', id: id, field_id: 'date_created', operator: 'is', s: date } );
		return `${ GFSC.adminUrl }?${ params }`;
	}

	// ── Recent submissions ─────────────────────────────────────────────────────

	function filterRecentCards() {
		$( '.gfsc-recent-card' ).each( function () {
			$( this ).prop( 'hidden', formId !== 0 && parseInt( $( this ).data( 'form-id' ), 10 ) !== formId );
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

	// Minimal %s / %1$s replacement for the translated strings from PHP.
	function sprintf( format, ...args ) {
		let i = 0;
		return format.replace( /%(?:(\d+)\$)?[sd]/g, ( match, pos ) => String( args[ pos ? pos - 1 : i++ ] ) );
	}

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
