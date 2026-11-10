/**
 * Saves the panel's blocks only. PHP prints the menu item and the panel
 * around them, so the markup can change in a later release without a block
 * validation error.
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
