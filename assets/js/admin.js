/* global GFSC, Chart, jQuery */
( function ( $ ) {
	'use strict';

	const i18n = GFSC.i18n;

	// Categorical palette in fixed order, one color per slot from GFSC_Data::color_slots().
	// Keep the length in sync with GFSC_Data::MAX_COLOR_SERIES.
	const SERIES_COLORS = [ '#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948' ];
	const OTHER_COLOR   = '#8c8f94';
	const TOTAL_COLOR   = '#1d2327';
	const BY_FORM_TYPES = { 'lines-by-form': 'line', 'stacked-by-form': 'bar' };
	const STORAGE_KEY   = 'gfsc_chart_type';

	let chartInstance = null;
	let chartDays     = [];
	let lastData      = null;
	let currentRange  = '30';
	let formId        = 0;
	let chartType     = loadChartType();
	let chartRequest  = 0;
	let openDate      = null;

	const $loading     = $( '#gfsc-loading' );
	const $canvas      = $( '#gfsc-canvas' );
	const $empty       = $( '#gfsc-empty' );
	const $customRange = $( '#gfsc-custom-range' );
	const $select      = $( '#gfsc-form-select' );
	const $typeSelect  = $( '#gfsc-chart-type' );

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
		syncTypeControl();
		loadChart();
	} );

	// ── Form selector ─────────────────────────────────────────────────────────

	$select.on( 'change', function () {
		formId = parseInt( $( this ).val(), 10 ) || 0;
		filterRecentCards();
		syncTypeControl();
		closeDay();
		if ( currentRange === 'custom' ) {
			$( '#gfsc-apply-custom' ).trigger( 'click' );
		} else {
			loadChart();
		}
	} );

	// ── Chart type ─────────────────────────────────────────────────────────────

	$typeSelect.on( 'change', function () {
		chartType = $( this ).val();
		saveChartType( chartType );
		if ( lastData ) {
			renderChart( lastData );
		}
	} );

	// By-form types need several forms, so with one form selected they fall back
	// to the matching single-series type. The preference returns with All forms.
	function effectiveType() {
		return formId && BY_FORM_TYPES[ chartType ] ? BY_FORM_TYPES[ chartType ] : chartType;
	}

	function syncTypeControl() {
		Object.keys( BY_FORM_TYPES ).forEach( function ( type ) {
			$typeSelect.find( `option[value="${ type }"]` ).prop( 'disabled', !! formId );
		} );
		$typeSelect.val( effectiveType() );
	}

	function loadChartType() {
		try {
			const saved = window.localStorage.getItem( STORAGE_KEY );
			return saved && $( `#gfsc-chart-type option[value="${ saved }"]` ).length ? saved : 'bar';
		} catch ( e ) {
			return 'bar';
		}
	}

	function saveChartType( type ) {
		try {
			window.localStorage.setItem( STORAGE_KEY, type );
		} catch ( e ) {
			// Only a convenience; the chart still works without it.
		}
	}

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
		const request = ++chartRequest;
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
				// Ignore a slow response once a newer request has been made.
				if ( request !== chartRequest ) {
					return;
				}
				if ( response.success ) {
					renderChart( response.data );
				} else {
					showError( response.data || i18n.loadError );
				}
			} )
			.fail( function () {
				if ( request === chartRequest ) {
					showError( i18n.networkErr );
				}
			} );
	}

	function reloadChart() {
		if ( currentRange === 'custom' ) {
			loadChart( $( '#gfsc-start' ).val(), $( '#gfsc-end' ).val() );
		} else {
			loadChart();
		}
	}

	// ── Chart render ───────────────────────────────────────────────────────────

	function renderChart( data ) {
		lastData = data;
		$loading.hide();
		updateTitle( data.total );

		if ( ! data.labels || data.labels.length === 0 || data.total === 0 ) {
			if ( chartInstance ) {
				chartInstance.destroy();
				chartInstance = null;
			}
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

		const type    = effectiveType();
		const byForm  = !! BY_FORM_TYPES[ type ];
		const stacked = type === 'stacked-by-form';
		const isLine  = type === 'line' || type === 'lines-by-form';

		chartInstance = new Chart( $canvas[ 0 ].getContext( '2d' ), {
			type: isLine ? 'line' : 'bar',
			data: {
				labels:   data.labels.map( formatLabel ),
				datasets: buildDatasets( type, data ),
			},
			options: {
				responsive:          true,
				maintainAspectRatio: false,
				// Clicks anywhere in a day's column count, not just on the mark itself.
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
					legend: {
						display:  isLine || byForm,
						position: 'bottom',
						labels: {
							color:          '#50575e',
							font:           { size: 12 },
							boxHeight:      8,
							usePointStyle:  true,
							pointStyle:     isLine ? 'line' : 'rect',
							generateLabels: legendLabels,
						},
					},
					tooltip: {
						backgroundColor: '#1d2327',
						titleColor:      '#fff',
						bodyColor:       '#c3c4c7',
						footerColor:     '#fff',
						padding:         10,
						cornerRadius:    6,
						boxPadding:      4,
						filter:          ( item ) => ! byForm || item.raw > 0,
						itemSort:        ( a, b ) => b.raw - a.raw,
						callbacks: {
							label: ( item ) => tooltipLabel( item, byForm ),
							footer: ( items ) => stacked ? `${ i18n.total }: ${ data.counts[ items[ 0 ].dataIndex ] }` : '',
						},
					},
				},
				scales: {
					x: {
						stacked: stacked,
						grid:    { display: false },
						border:  { display: false },
						ticks: {
							color:         '#646970',
							font:          { size: 11 },
							maxRotation:   45,
							autoSkip:      true,
							maxTicksLimit: 14,
						},
					},
					y: {
						stacked:     stacked,
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

	function buildDatasets( type, data ) {
		const accent = getComputedStyle( document.documentElement )
			.getPropertyValue( '--wp-admin-theme-color' ).trim() || '#2271b1';

		if ( type === 'bar' ) {
			return [ {
				label:                currentSeriesName(),
				data:                 data.counts,
				backgroundColor:      withAlpha( accent, 0.18 ),
				borderColor:          accent,
				borderWidth:          2,
				borderRadius:         4,
				borderSkipped:        false,
				maxBarThickness:      24,
				hoverBackgroundColor: withAlpha( accent, 0.35 ),
			} ];
		}

		if ( type === 'line' ) {
			return [ lineDataset( currentSeriesName(), data.counts, accent, data.labels.length ) ];
		}

		const series = ( data.series || [] ).map( function ( item ) {
			return {
				label:  item.slot === null ? i18n.otherForms : item.title,
				counts: item.counts,
				color:  item.slot === null ? OTHER_COLOR : SERIES_COLORS[ item.slot ],
			};
		} );

		if ( type === 'stacked-by-form' ) {
			return series.map( function ( item ) {
				return {
					label:           item.label,
					data:            item.counts,
					backgroundColor: item.color,
					borderColor:     '#fff',
					borderWidth:     1,
					maxBarThickness: 24,
				};
			} );
		}

		// Total goes first in the legend and draws over the form lines (lower order is on top).
		return [ Object.assign( lineDataset( i18n.total, data.counts, TOTAL_COLOR, data.labels.length ), { order: 0 } ) ].concat(
			series.map( ( item ) => Object.assign( lineDataset( item.label, item.counts, item.color, data.labels.length ), { order: 1 } ) )
		);
	}

	function lineDataset( label, counts, color, days ) {
		return {
			label:                label,
			data:                 counts,
			borderColor:          color,
			backgroundColor:      color,
			borderWidth:          2,
			borderCapStyle:       'round',
			borderJoinStyle:      'round',
			tension:              0,
			fill:                 false,
			pointRadius:          days > 31 ? 0 : 2,
			pointHoverRadius:     5,
			pointBorderColor:     '#fff',
			pointHoverBorderColor: '#fff',
			pointHoverBorderWidth: 2,
		};
	}

	// Names each series and carries its total for the range, so identity is never color alone.
	function legendLabels( chart ) {
		return Chart.defaults.plugins.legend.labels.generateLabels( chart ).map( function ( item ) {
			const dataset = chart.data.datasets[ item.datasetIndex ];
			const total   = dataset.data.reduce( ( sum, value ) => sum + value, 0 );
			const name    = dataset.label.length > 32 ? `${ dataset.label.slice( 0, 31 ) }…` : dataset.label;
			item.text      = `${ name } (${ total.toLocaleString() })`;
			item.fontColor = chart.options.plugins.legend.labels.color;
			if ( chart.config.type === 'line' ) {
				// The default key takes the point's white border, which is invisible for the line style.
				item.strokeStyle = dataset.borderColor;
				item.lineWidth   = 2;
			}
			return item;
		} );
	}

	function tooltipLabel( item, byForm ) {
		if ( byForm ) {
			return ` ${ item.dataset.label }: ${ item.raw }`;
		}
		return ` ${ item.raw } ${ item.raw === 1 ? i18n.submission : i18n.submissions }`;
	}

	function currentSeriesName() {
		const form = ( GFSC.forms || [] ).find( ( candidate ) => candidate.id === formId );
		return form ? form.title : i18n.allForms;
	}

	// ── Day drill-down ─────────────────────────────────────────────────────────

	const $day = $( '#gfsc-day' );

	$( '#gfsc-day-close' ).on( 'click', closeDay );

	// A single form maps straight onto GF's Entries list filtered by date; across
	// forms GF has no combined view, so list that day's entries here instead.
	function openDay( date ) {
		openDate = date;

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
		openDate = null;
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

	// Spam / Trash row actions, plus the Undo link that follows them.
	$( '#gfsc-recent' ).on( 'click', '.gfsc-entry-action', function ( event ) {
		event.preventDefault();

		const $link   = $( this );
		const $card   = $link.closest( '.gfsc-recent-card' );
		const entryId = $link.data( 'entry-id' );
		const status  = $link.data( 'status' );

		if ( $card.hasClass( 'gfsc-busy' ) ) {
			return;
		}
		$card.addClass( 'gfsc-busy' );

		$.post( GFSC.ajaxUrl, {
			action:   'gfsc_update_entry',
			nonce:    GFSC.nonce,
			entry_id: entryId,
			status:   status,
		} )
			.done( function ( response ) {
				if ( ! response.success ) {
					cardError( $card, response.data );
					return;
				}

				const $fresh = $( response.data.card.trim() );
				$card.replaceWith( $fresh );
				if ( status !== 'active' ) {
					showUndo( $fresh, entryId, status );
				}

				reloadChart();
				if ( openDate && ! $day.prop( 'hidden' ) ) {
					openDay( openDate );
				}
			} )
			.fail( function ( xhr ) {
				cardError( $card, xhr.responseJSON && xhr.responseJSON.data );
			} );
	} );

	function showUndo( $card, entryId, status ) {
		const $undo = $( '<a href="#" role="button" class="gfsc-entry-action">' )
			.attr( { 'data-entry-id': entryId, 'data-status': 'active' } )
			.text( i18n.undo );

		$( '<p class="gfsc-card-notice" role="status">' )
			.text( `${ status === 'spam' ? i18n.movedSpam : i18n.movedTrash } ` )
			.append( $undo )
			.insertAfter( $card.find( '.gfsc-card-head' ) );

		$undo.trigger( 'focus' );
	}

	function cardError( $card, message ) {
		$card.removeClass( 'gfsc-busy' );
		$card.find( '.gfsc-card-notice' ).remove();
		$( '<p class="gfsc-card-notice" role="alert">' )
			.text( message || i18n.updateError )
			.insertAfter( $card.find( '.gfsc-card-head' ) );
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
