import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextControl, ToggleControl, PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

// Import SCSS files
import './editor.scss';
import './style.scss';

// لیست انیمیشن‌ها مطابق با قالب شما
const ANIMATION_OPTIONS = [
    { label: 'بدون انیمیشن', value: '' },
    { label: 'Bounce', value: 'bounce' },
    { label: 'Flash', value: 'flash' },
    { label: 'Pulse', value: 'pulse' },
    { label: 'Rubber Band', value: 'rubberBand' },
    { label: 'Shake', value: 'shake' },
    { label: 'Swing', value: 'swing' },
    { label: 'Tada', value: 'tada' },
    { label: 'Wobble', value: 'wobble' },
    { label: 'Jello', value: 'jello' },
    { label: 'Bounce In', value: 'bounceIn' },
    { label: 'Bounce In Down', value: 'bounceInDown' },
    { label: 'Bounce In Left', value: 'bounceInLeft' },
    { label: 'Bounce In Right', value: 'bounceInRight' },
    { label: 'Bounce In Up', value: 'bounceInUp' },
    { label: 'Fade In', value: 'fadeIn' },
    { label: 'Fade In Down', value: 'fadeInDown' },
    { label: 'Fade In Down Big', value: 'fadeInDownBig' },
    { label: 'Fade In Left', value: 'fadeInLeft' },
    { label: 'Fade In Left Big', value: 'fadeInLeftBig' },
    { label: 'Fade In Right', value: 'fadeInRight' },
    { label: 'Fade In Right Big', value: 'fadeInRightBig' },
    { label: 'Fade In Up', value: 'fadeInUp' },
    { label: 'Fade In Up Big', value: 'fadeInUpBig' },
    { label: 'Zoom In', value: 'zoomIn' },
    { label: 'Zoom In Down', value: 'zoomInDown' },
    { label: 'Zoom In Left', value: 'zoomInLeft' },
    { label: 'Zoom In Right', value: 'zoomInRight' },
    { label: 'Zoom In Up', value: 'zoomInUp' },
    { label: 'Slide In Down', value: 'slideInDown' },
    { label: 'Slide In Left', value: 'slideInLeft' },
    { label: 'Slide In Right', value: 'slideInRight' },
    { label: 'Slide In Up', value: 'slideInUp' },
    { label: 'Flip In X', value: 'flipInX' },
    { label: 'Flip In Y', value: 'flipInY' },
    { label: 'Light Speed In', value: 'lightSpeedIn' },
    { label: 'Rotate In', value: 'rotateIn' },
    { label: 'Rotate In Down Left', value: 'rotateInDownLeft' },
    { label: 'Rotate In Down Right', value: 'rotateInDownRight' },
    { label: 'Rotate In Up Left', value: 'rotateInUpLeft' },
    { label: 'Rotate In Up Right', value: 'rotateInUpRight' },
    { label: 'Roll In', value: 'rollIn' }
];

registerBlockType('salnama-blocks/accordion-item', {
  edit: ({ attributes, setAttributes, clientId }) => {
    const { 
      title, 
      isOpen, 
      itemId,
      salnamaAnimation,
      salnamaAnimationDelay,
      salnamaAnimationDuration 
    } = attributes;

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

          {/* پنل انیمیشن‌های سفارشی برای آیتم */}
          <PanelBody title={__('انیمیشن‌های آیتم', 'salnama')} initialOpen={false}>
            <SelectControl
              label={__('نوع انیمیشن', 'salnama')}
              value={salnamaAnimation}
              options={ANIMATION_OPTIONS}
              onChange={(value) => setAttributes({ salnamaAnimation: value })}
            />
            <TextControl
              label={__('تأخیر انیمیشن (میلی‌ثانیه)', 'salnama')}
              value={salnamaAnimationDelay}
              onChange={(value) => setAttributes({ salnamaAnimationDelay: value })}
              type="number"
              min="0"
              max="5000"
            />
            <TextControl
              label={__('مدت انیمیشن (میلی‌ثانیه)', 'salnama')}
              value={salnamaAnimationDuration}
              onChange={(value) => setAttributes({ salnamaAnimationDuration: value })}
              type="number"
              min="100"
              max="5000"
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
            allowedBlocks={['core/paragraph', 'core/heading', 'core/image', 'core/list', 'core/buttons', 'core/columns', 'salnama-blocks/advanced-accordion']}
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