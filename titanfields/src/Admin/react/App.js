import { render, useState, useEffect } from '@wordpress/element';
import { Panel, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import './store';
import './style.scss'; // Import SCSS for Webpack to process

// --- Placeholder Page Components ---

const FieldGroupsPage = () => {
    const fields = useSelect((select) => select('titanfields/groups').getFields(), []);
    return (
        <div>
            <h2>Field Groups</h2>
            <PanelBody title="Registered Fields" initialOpen={true}>
                {fields.length === 0 ? (
                    <p>No fields added yet.</p>
                ) : (
                    <ul>
                        {fields.map((field, i) => (
                            <li key={i}>{field.label} ({field.type})</li>
                        ))}
                    </ul>
                )}
            </PanelBody>
        </div>
    );
};

const PostTypesPage = () => <h2>Post Types</h2>;
const TaxonomiesPage = () => <h2>Taxonomies</h2>;
const OptionsPagesPage = () => <h2>Options Pages</h2>;
const ToolsPage = () => <h2>Tools</h2>;

// --- Main App Shell Component ---

const AppShell = ({ initialPage }) => {
    // Determine the active route
    const [currentPage, setCurrentPage] = useState(initialPage);

    const navItems = [
        { slug: 'titanfields', label: 'Field Groups' },
        { slug: 'titanfields-post-types', label: 'Post Types' },
        { slug: 'titanfields-taxonomies', label: 'Taxonomies' },
        { slug: 'titanfields-options-pages', label: 'Options Pages' },
        { slug: 'titanfields-tools', label: 'Tools' },
    ];

    // Simple router switch
    const renderContent = () => {
        switch (currentPage) {
            case 'titanfields':
                return <FieldGroupsPage />;
            case 'titanfields-post-types':
                return <PostTypesPage />;
            case 'titanfields-taxonomies':
                return <TaxonomiesPage />;
            case 'titanfields-options-pages':
                return <OptionsPagesPage />;
            case 'titanfields-tools':
                return <ToolsPage />;
            default:
                return <h2>Page Not Found</h2>;
        }
    };

    return (
        <div className="titanfields-app-shell">
            {/* Left Sidebar Navigation */}
            <nav className="titanfields-nav">
                <ul>
                    {navItems.map((item) => (
                        <li key={item.slug}>
                            <a
                                href={`?page=${item.slug}`}
                                className={currentPage === item.slug ? 'active' : ''}
                                onClick={(e) => {
                                    // Optional: Prevent default and use pushState for SPA feel,
                                    // but standard WP reloading is acceptable here.
                                    // e.preventDefault();
                                    // window.history.pushState({}, '', `?page=${item.slug}`);
                                    // setCurrentPage(item.slug);
                                }}
                            >
                                {item.label}
                            </a>
                        </li>
                    ))}
                </ul>
            </nav>

            {/* Right Content Area */}
            <main className="titanfields-content">
                {renderContent()}
            </main>
        </div>
    );
};

// --- Initialization ---

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('titanfields-react-root');
    if (rootElement) {
        // Read the initial route from the data attribute provided by PHP
        const initialPage = rootElement.getAttribute('data-current-page') || 'titanfields';
        render(<AppShell initialPage={initialPage} />, rootElement);
    }
});
