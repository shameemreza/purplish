/**
 * Saves the inner blocks only. PHP prints the wrapper, so it can change in
 * a later release without a block validation error.
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
