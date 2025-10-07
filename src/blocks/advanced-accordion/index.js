import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, RangeControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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

registerBlockType('salnama-blocks/advanced-accordion', {
  edit: ({ attributes, setAttributes }) => {
    const { 
      accordionStyle, 
      multipleOpen, 
      animationDuration,
      salnamaAnimation,
      salnamaAnimationDelay,
      salnamaAnimationDuration,
      isNested

    } = attributes;
    
    // تشخیص خودکار اینکه آیا این آکاردیون nested است یا نه
    const blockProps = useBlockProps({
      className: `salnama-accordion-editor salnama-accordion-${accordionStyle} ${isNested ? 'salnama-nested-accordion' : ''}`
    });

    // اجازه دادن به nested accordion‌ها
    const ALLOWED_BLOCKS = ['salnama-blocks/accordion-item', 'salnama-blocks/advanced-accordion'];
    
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
            <ToggleControl
              label={__('آکاردیون تو در تو', 'salnama')}
              checked={isNested}
              onChange={(value) => setAttributes({ isNested: value })}
              help={__('اگر این آکاردیون داخل آیتم دیگری است، این گزینه را فعال کنید')}
            />
          </PanelBody>

          {/* پنل انیمیشن‌های سفارشی */}
          <PanelBody title={__('انیمیشن‌های پیشرفته', 'salnama')} initialOpen={false}>
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
          <div className="salnama-accordion-notice">
            <p>
              {isNested 
                ? __('آکاردیون تو در تو - می‌توانید آیتم‌ها یا آکاردیون‌های دیگر اضافه کنید', 'salnama')
                : __('آکاردیون پیشرفته - آیتم‌ها را در این قسمت اضافه کنید', 'salnama')
              }
            </p>
          </div>
          <InnerBlocks
            allowedBlocks={ALLOWED_BLOCKS}
            template={isNested ? null : template} // فقط برای آکاردیون اصلی template داشته باش
            templateLock={false}
            renderAppender={false}
          />
        </div>
      </>
    );
  },

  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: `salnama-accordion salnama-accordion-${attributes.accordionStyle} ${attributes.isNested ? 'salnama-nested-accordion' : ''}`,
      'data-multiple': attributes.multipleOpen,
      'data-duration': attributes.animationDuration,
      'data-nested': attributes.isNested
    });

    return (
      <div {...blockProps}>
        <InnerBlocks.Content />
      </div>
    );
  }
});