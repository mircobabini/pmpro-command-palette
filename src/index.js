( function() {
	const settings = window.pmproCommandPalette;

	if ( ! settings || ! window.wp || ! window.wp.plugins || ! window.wp.commands ) {
		return;
	}

	const { registerPlugin } = window.wp.plugins;
	const { useEffect, useMemo, useState } = window.wp.element;
	const { useDispatch } = window.wp.data;
	const { store } = window.wp.commands;
	const { __ } = window.wp.i18n;
	const { addQueryArgs } = window.wp.url;
	const { decodeEntities } = window.wp.htmlEntities;
	const apiFetch = window.wp.apiFetch;

	const stripHtml = ( value ) => {
		if ( ! value ) {
			return '';
		}

		const element = document.createElement( 'div' );
		element.innerHTML = value;
		return decodeEntities( element.textContent || element.innerText || '' );
	};

	const createSearchHook = () => {
		return function usePmproSearchCommands( { search } ) {
			const [ commands, setCommands ] = useState( [] );
			const [ isLoading, setIsLoading ] = useState( false );

			useEffect( () => {
				let isMounted = true;

				if ( ! search || search.trim().length < 2 ) {
					setCommands( [] );
					setIsLoading( false );
					return () => {
						isMounted = false;
					};
				}

				setIsLoading( true );

				apiFetch( {
					path: addQueryArgs( settings.quickSearchPath, {
						search: search.trim(),
						type: 'all',
					} ),
					headers: {
						'X-WP-Nonce': settings.nonce,
					},
				} ).then( ( response ) => {
					if ( ! isMounted ) {
						return;
					}

					const mappedCommands = [];
					const groups = response && typeof response === 'object' ? response : {};

					Object.keys( groups ).forEach( ( groupKey ) => {
						const group = groups[ groupKey ];
						const items = Array.isArray( group && group.items ) ? group.items : [];
						const groupLabel = settings.resultTypeLabels[ groupKey ] || groupKey;

						items.forEach( ( item, index ) => {
							const plainLabel = stripHtml( item.label || '' );
							const searchLabel = `${ plainLabel } ${ groupLabel } PMPro`;

							mappedCommands.push( {
								name: `pmpro/search/${ groupKey }/${ index }/${ plainLabel.toLowerCase().replace( /[^a-z0-9]+/g, '-' ) }`,
								label: plainLabel || __( 'Open result', 'pmpro-command-palette' ),
								searchLabel,
								callback: ( { close } = {} ) => {
									document.location = item.url;
									if ( close ) {
										close();
									}
								},
							} );
						} );
					} );

					setCommands( mappedCommands );
					setIsLoading( false );
				} ).catch( () => {
					if ( isMounted ) {
						setCommands( [] );
						setIsLoading( false );
					}
				} );

				return () => {
					isMounted = false;
				};
			}, [ search ] );

			return useMemo( () => ( {
				commands,
				isLoading,
			} ), [ commands, isLoading ] );
		};
	};

	registerPlugin( 'pmpro-command-palette-registration', {
		render: function PMProCommandPaletteRegistration() {
			const { registerCommand, registerCommandLoader } = useDispatch( store );

			useEffect( () => {
				settings.commands.forEach( ( command ) => {
					registerCommand( {
						name: command.name,
						label: command.label,
						callback: ( { close } = {} ) => {
							document.location = command.url;
							if ( close ) {
								close();
							}
						},
					} );
				} );

				registerCommandLoader( {
					name: settings.searchCommand.name,
					hook: createSearchHook(),
				} );
			}, [ registerCommand, registerCommandLoader ] );

			return null;
		},
	} );
}() );
