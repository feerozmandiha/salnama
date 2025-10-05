import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Import SCSS files
import './editor.scss';
import './style.scss';

registerBlockType('salnama-blocks/advanced-accordion', {
  edit: ({ attributes, setAttributes }) => {
    const { accordionStyle, multipleOpen, animationDuration } = attributes;
    
    const blockProps = useBlockProps({
      className: `salnama-accordion-editor salnama-accordion-${accordionStyle}`
    });

    const ALLOWED_BLOCKS = ['salnama-blocks/accordion-item'];
    
    const template = [
      ['salnama-blocks/accordion-item', { 
        title: 'عنوان آیتم ۱',
        isOpen: false 
      }],
      ['salnama-blocks/accordion-item', { 
        title: 'عنوان آیتم ۲',
        isOpen: false 
      }],
      ['salnama-blocks/accordion-item', { 
        title: 'عنوان آیتم ۳',
        isOpen: false 
      }]
    ];

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('تنظیمات آکاردیون', 'salnama')}>
            <SelectControl
              label={__('سبک نمایش', 'salnama')}
              value={accordionStyle}
              options={[
                { label: 'عمودی', value: 'vertical' },
                { label: 'افقی', value: 'horizontal' }
              ]}
              onChange={(value) => setAttributes({ accordionStyle: value })}
            />
            <ToggleControl
              label={__('امکان باز کردن چند آیتم', 'salnama')}
              checked={multipleOpen}
              onChange={(value) => setAttributes({ multipleOpen: value })}
            />
            <RangeControl
              label={__('مدت انیمیشن (میلی‌ثانیه)', 'salnama')}
              value={animationDuration}
              onChange={(value) => setAttributes({ animationDuration: value })}
              min={100}
              max={1000}
              step={50}
            />
          </PanelBody>
        </InspectorControls>

        <div {...blockProps}>
          <div className="salnama-accordion-notice">
            <p>{__('آکاردیون پیشرفته - آیتم‌ها را در این قسمت اضافه کنید', 'salnama')}</p>
          </div>
          <InnerBlocks
            allowedBlocks={ALLOWED_BLOCKS}
            template={template}
            templateLock={false}
            renderAppender={false}
          />
        </div>
      </>
    );
  },

  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: `salnama-accordion salnama-accordion-${attributes.accordionStyle}`,
      'data-multiple': attributes.multipleOpen,
      'data-duration': attributes.animationDuration
    });

    return (
      <div {...blockProps}>
        <InnerBlocks.Content />
      </div>
    );
  }
});