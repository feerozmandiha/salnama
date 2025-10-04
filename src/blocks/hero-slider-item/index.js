import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

const MY_TEMPLATE = [
  [ 'core/heading',   { level: 1, placeholder: __( 'عنوان اصلی اسلاید', 'salnama-blocks' ), className: 'salnama-title', align: 'center' } ],
  [ 'core/paragraph', { placeholder: __( 'متن تبلیغاتی جذاب...', 'salnama-blocks' ), className: 'salnama-subtitle', align: 'center' } ],
  [ 'core/buttons',   { layout: { type: 'flex', justifyContent: 'center' } }, [
    [ 'core/button', { text: __( 'درخواست مشاوره فوری', 'salnama-blocks' ), className: 'salnama-cta-primary' } ],
    [ 'core/button', { text: __( 'مشاهده نمونه کارها', 'salnama-blocks' ), className: 'salnama-cta-secondary' } ]
  ] ],
];

registerBlockType( 'salnama-blocks/hero-slide-item', {
  edit: ( { attributes, setAttributes } ) => {
    const { mediaId, mediaUrl, mediaAlt } = attributes;
    const blockProps = useBlockProps();

    const onSelectMedia = ( media ) => {
      if ( media && media.url ) {
        setAttributes( {
          mediaId: media.id,
          mediaUrl: media.url,
          mediaAlt: media.alt || __( 'تصویر اسلاید', 'salnama-blocks' ),
        } );
      }
    };

    return (
      <div { ...blockProps }>
        <div
          className="salnama-slide-item-wrapper"
          style={{ backgroundImage: mediaUrl ? `url(${ mediaUrl })` : 'none' }}
        >
          {/* دکمه انتخاب/تغییر تصویر */}
          <MediaUploadCheck>
            <MediaUpload
              onSelect={ onSelectMedia }
              allowedTypes={ [ 'image' ] }
              value={ mediaId }
              render={ ( { open } ) => (
                <Button
                  onClick={ open }
                  className={ mediaUrl ? "salnama-change-image-button" : "salnama-select-image-button" }
                >
                  <Dashicon icon={ mediaUrl ? "edit" : "plus" } />
                  { mediaUrl ? __( 'تغییر تصویر', 'salnama-blocks' ) : __( 'انتخاب تصویر', 'salnama-blocks' ) }
                </Button>
              ) }
            />
          </MediaUploadCheck>

          {/* محتوای InnerBlocks */}
          <div className="salnama-hero-content">
            <InnerBlocks
              template={ MY_TEMPLATE }
              templateLock={ false }
            />
          </div>
        </div>
      </div>
    );
  },

  save: () => {
    return <InnerBlocks.Content />;
  }
} );
