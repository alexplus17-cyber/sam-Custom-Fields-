import { render } from '@wordpress/element';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import './store'; // Initialize the data store

const FieldList = () => {
    const fields = useSelect((select) => select('titanfields/groups').getFields(), []);

    return (
        <PanelBody title="Fields" initialOpen={true}>
            {fields.length === 0 ? (
                <p>No fields added yet. Click "Add Field" to start.</p>
            ) : (
                <ul>
                    {fields.map((field, index) => (
                        <li key={index}>{field.label} ({field.type})</li>
                    ))}
                </ul>
            )}
        </PanelBody>
    );
};

const LocationRulesPanel = () => {
    const rules = useSelect((select) => select('titanfields/groups').getLocationRules(), []);

    return (
        <PanelBody title="Location Rules" initialOpen={true}>
            <p>Show this field group if...</p>
            {/* Future UI for building AND/OR rule groups */}
            <div className="titanfields-rules-container">
                {rules.length === 0 && <p>No rules defined. Group will be available everywhere.</p>}
            </div>
        </PanelBody>
    );
};

const FieldGroupEditor = () => {
    return (
        <div className="titanfields-group-editor">
            <Panel header="TitanFields: Edit Field Group">
                <FieldList />
                <LocationRulesPanel />
            </Panel>
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('titanfields-react-root');
    if (rootElement) {
        render(<FieldGroupEditor />, rootElement);
    }
});
