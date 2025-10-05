import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextControl, ToggleControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

// Import SCSS files
import './editor.scss';
import './style.scss';

registerBlockType('salnama-blocks/accordion-item', {
  edit: ({ attributes, setAttributes, clientId }) => {
    const { title, isOpen, itemId } = attributes;

    useEffect(() => {
      if (!itemId) {
        setAttributes({ itemId: 'accordion-item-' + clientId });
      }
    }, []);

    const blockProps = useBlockProps({
      className: `salnama-accordion-item-editor ${isOpen ? 'is-open' : ''}`
    });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('تنظیمات آیتم', 'salnama')}>
            <TextControl
              label={__('عنوان آیتم', 'salnama')}
              value={title}
              onChange={(value) => setAttributes({ title: value })}
            />
            <ToggleControl
              label={__('باز شده در حالت اولیه', 'salnama')}
              checked={isOpen}
              onChange={(value) => setAttributes({ isOpen: value })}
            />
          </PanelBody>
        </InspectorControls>

        <div {...blockProps}>
          <div className="salnama-accordion-header-editor">
            <div className="salnama-accordion-title-editor">
              {title || __('عنوان آیتم', 'salnama')}
            </div>
            <div className="salnama-accordion-icon-editor">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d={isOpen ? "M8 12L2 6l1.4-1.4L8 9.2l4.6-4.6L14 6z" : "M8 4l6 6-1.4 1.4L8 6.8 3.4 11.4 2 10z"} />
              </svg>
            </div>
          </div>
          <div className="salnama-accordion-content-editor">
            <InnerBlocks 
              template={[['core/paragraph', { 
                placeholder: __('محتوای آیتم آکاردیون را اینجا وارد کنید...', 'salnama')
              }]]}
              templateLock={false}
            />
          </div>
        </div>
      </>
    );
  },

  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: `salnama-accordion-item ${attributes.isOpen ? 'is-open' : ''}`,
      id: attributes.itemId
    });

    return (
      <div {...blockProps}>
        <button className="salnama-accordion-header" aria-expanded={attributes.isOpen ? 'true' : 'false'} aria-controls={attributes.itemId + '-content'}>
          <span className="salnama-accordion-title">{attributes.title}</span>
          <span className="salnama-accordion-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
              <path d="M8 12L2 6l1.4-1.4L8 9.2l4.6-4.6L14 6z"/>
            </svg>
          </span>
        </button>
        <div className="salnama-accordion-content" id={attributes.itemId + '-content'} hidden={!attributes.isOpen}>
          <InnerBlocks.Content />
        </div>
      </div>
    );
  }
});