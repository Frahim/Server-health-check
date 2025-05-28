const { registerBlockType } = wp.blocks;
const { SelectControl, PanelBody, Spinner } = wp.components;
const { InspectorControls } = wp.blockEditor;
const { useState, useEffect } = wp.element;
const apiFetch = wp.apiFetch;

registerBlockType('domain-tools/shortcode-selector', {
    title: 'Mail server Tool',
    icon: 'admin-tools',
    category: 'widgets',

    attributes: {
        shortcodeKey: { type: 'string', default: ' empty_checker' },
        renderedHtml: { type: 'string', default: '' }
    },

    edit({ attributes, setAttributes }) {
        const { shortcodeKey, renderedHtml } = attributes;
        const [loading, setLoading] = useState(false);

        const shortcodes = {
            empty_checker: 'Tesst',
            mx_checker: 'MX Checker',
            spf_checker: 'SPF Checker',
            dkim_checker: 'DKIM Checker',
            smtp_checker: 'SMTP Checker',
            dmarc_checker: 'DMARC Checker',
            a_record_checker: 'A Record Checker',
            txt_checker: 'TXT Checker',
            ip_checker: 'IP Checker',
            ssl_checker: 'SSL Checker',
            email_deliverability_checker: 'Email Deliverability',
            blacklist_checker: 'Backlist Checker'
        };

        // Always fetch preview when shortcodeKey changes or is first set
        useEffect(() => {
            if (!shortcodeKey || !shortcodes[shortcodeKey]) return;

            setLoading(true);
            apiFetch({ path: `/domain-tools/v1/render-shortcode?code=${shortcodeKey}` })
                .then((html) => {
                    setAttributes({ renderedHtml: html });
                    setLoading(false);
                })
                .catch(() => {
                    setAttributes({ renderedHtml: '<p>Error loading preview</p>' });
                    setLoading(false);
                });
        }, [shortcodeKey]);

        // If shortcodeKey is missing (new block), set default
        useEffect(() => {
            if (!shortcodeKey) {
                setAttributes({ shortcodeKey: 'mx_checker' });
            }
        }, []);

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Choose a tool" initialOpen={true}>
                        <SelectControl
                            label="Select Shortcode"
                            value={shortcodeKey}
                            options={Object.entries(shortcodes).map(([value, label]) => ({
                                value,
                                label
                            }))}
                            onChange={(val) => setAttributes({ shortcodeKey: val })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div className="shortcode-preview-block">
                    <h4>{shortcodes[shortcodeKey]}</h4>
                    {loading ? <Spinner /> : (
                        <div dangerouslySetInnerHTML={{ __html: renderedHtml }} />
                    )}
                </div>
            </>
        );
    },

    save() {
        return null; // Server-rendered block
    }
});
