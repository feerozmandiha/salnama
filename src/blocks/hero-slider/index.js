import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ColorPicker } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

const MY_TEMPLATE = [
  [ 'salnama-blocks/hero-slide-item' ]
];

registerBlockType( 'salnama-blocks/hero-slider', {
  edit: ( { attributes, setAttributes } ) => {
    const { animationSpeed, transitionEffect, overlayColor, overlayOpacity } = attributes;

    const dynamicOverlayStyle = {
      '--overlay-color': overlayColor,
      '--overlay-opacity': overlayOpacity,
    };
    const blockProps = useBlockProps( { style: dynamicOverlayStyle } );

    return (
      <>
        <InspectorControls>
          <PanelBody title={ __( 'تنظیمات اسلایدر و انیمیشن', 'salnama-blocks' ) } initialOpen={ true }>
            <div style={{ marginBottom: '8px', fontWeight: 600 }}>
              { __( 'رنگ فیلتر (Overlay)', 'salnama-blocks' ) }
            </div>
            <ColorPicker
              color={ overlayColor }
              onChangeComplete={ ( colorObject ) => setAttributes( { overlayColor: colorObject.hex } ) }
              disableAlpha={ true }
            />
            <RangeControl
              label={ __( 'شفافیت فیلتر', 'salnama-blocks' ) }
              value={ overlayOpacity }
              onChange={ ( newOpacity ) => setAttributes( { overlayOpacity: newOpacity } ) }
              min={ 0 }
              max={ 1 }
              step={ 0.05 }
            />
            <SelectControl
              label={ __( 'افکت انتقال', 'salnama-blocks' ) }
              value={ transitionEffect }
              options={ [
                { label: __( 'محو شدن (Fade)', 'salnama-blocks' ), value: 'fade' },
                { label: __( 'اسلاید (Slide)', 'salnama-blocks' ), value: 'slide' },
                { label: __( 'زوم به داخل (Zoom In)', 'salnama-blocks' ), value: 'zoom-in' },
                { label: __( 'کاهش مقیاس (Scale Down)', 'salnama-blocks' ), value: 'scale-down' },
                { label: __( 'محو شدگی (Blur)', 'salnama-blocks' ), value: 'blur' },
              ] }
              onChange={ ( newEffect ) => setAttributes( { transitionEffect: newEffect } ) }
            />
            <RangeControl
              label={ __( 'سرعت انیمیشن (میلی‌ثانیه)', 'salnama-blocks' ) }
              value={ animationSpeed }
              onChange={ ( newSpeed ) => setAttributes( { animationSpeed: newSpeed } ) }
              min={ 3000 }
              max={ 15000 }
              step={ 500 }
            />
          </PanelBody>
        </InspectorControls>

        <div { ...blockProps }>
          <InnerBlocks
            allowedBlocks={ [ 'salnama-blocks/hero-slide-item' ] }
            template={ MY_TEMPLATE }
            templateLock={ false }
          />
        </div>
      </>
    );
  },

  save: () => {
    return <InnerBlocks.Content />;
  }
} );
