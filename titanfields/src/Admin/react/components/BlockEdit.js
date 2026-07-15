import { useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * The main Edit component for TitanFields custom Gutenberg blocks.
 * This reads the field_group_key from attributes, looks up the fields in the store,
 * and renders the appropriate UI components to edit the data.
 */
export default function BlockEdit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { field_group_key, data = {} } = attributes;

    // Fetch the fields for this specific group from our custom Redux store
    const fields = useSelect((select) => {
        const store = select('titanfields/groups');
        // Assuming we have a selector or we just get all fields for now if the store structure changes.
        // For a full implementation, `store.getFields(field_group_key)` would be used.
        return store.getFields();
    }, [field_group_key]);

    const updateFieldData = (fieldKey, value) => {
        setAttributes({
            data: {
                ...data,
                [fieldKey]: value,
            },
        });
    };

    return (
        <div {...blockProps}>
            <Panel header={`TitanFields Block: ${field_group_key || 'Unassigned'}`}>
                <PanelBody title="Block Fields" initialOpen={true}>
                    {fields.length === 0 ? (
                        <p>No fields found for this block's assigned group.</p>
                    ) : (
                        fields.map((field) => {
                            // Simple rendering logic for now.
                            // In a full implementation, we'd look up the React component from FieldTypeRegistry (passed via localized script/API)
                            if (field.type === 'text') {
                                return (
                                    <TextControl
                                        key={field.key}
                                        label={field.label}
                                        value={data[field.key] || ''}
                                        onChange={(val) => updateFieldData(field.key, val)}
                                    />
                                );
                            }
                            return (
                                <p key={field.key}>Unsupported field type: {field.type}</p>
                            );
                        })
                    )}
                </PanelBody>
            </Panel>
        </div>
    );
}
