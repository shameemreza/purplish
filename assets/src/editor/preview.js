/**
 * Shows the looks of blocks marked `preview` in the editor canvas: the same
 * wrapper classes and effect color as on the front end. The effect styles
 * load into the canvas from PHP; the front scripts don't.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';

import { classesFor, colorStyle } from './preview-classes';

const MAP = window.purplishPreview || {};

const withPurplishPreview = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const block = MAP[ props.name ];
		const saved = props.attributes && props.attributes.purplish;
		const classes = block ? classesFor( block, saved ) : [];
		if ( ! classes.length ) {
			return <BlockListBlock { ...props } />;
		}

		const wrapperProps = props.wrapperProps || {};
		return (
			<BlockListBlock
				{ ...props }
				className={ [ props.className, ...classes ]
					.filter( Boolean )
					.join( ' ' ) }
				wrapperProps={ {
					...wrapperProps,
					style: { ...wrapperProps.style, ...colorStyle( saved ) },
				} }
			/>
		);
	},
	'withPurplishPreview'
);

addFilter( 'editor.BlockListBlock', 'purplish/preview', withPurplishPreview );
