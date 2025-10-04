import { registerBlockType } from '@wordpress/blocks';
import { 
    InspectorControls, 
    useBlockProps,
    RichText
} from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
    TextareaControl,  // این خط باید وجود داشته باشد
    ToggleControl,
    SelectControl,
    ColorPicker,
    Button
} from '@wordpress/components';
import { plus } from '@wordpress/icons';
import { useState, useEffect } from '@wordpress/element';
import './editor.scss';

registerBlockType('salnama-blocks/form-builder', {
    edit: ({ attributes, setAttributes }) => {
        const {
            formTitle,
            formDescription,
            submitButtonText,
            successMessage,
            fields,
            enableUserHistory,
            formStyle,
            backgroundColor,
            textColor
        } = attributes;

        const blockProps = useBlockProps({
            className: `salnama-form-builder salnama-form-style-${formStyle}`
        });

        const [localFields, setLocalFields] = useState(fields || []);

        useEffect(() => {
            setAttributes({ fields: localFields });
        }, [localFields]);

        const addField = () => {
            const newField = {
                id: `field_${Date.now()}`,
                type: 'text',
                label: 'فیلد جدید',
                required: false,
                placeholder: '',
                options: []  // اضافه کردن options برای فیلدهای select
            };
            setLocalFields([...localFields, newField]);
        };

        const updateField = (index, key, value) => {
            const updatedFields = [...localFields];
            updatedFields[index][key] = value;
            setLocalFields(updatedFields);
        };

        const removeField = (index) => {
            const updatedFields = localFields.filter((_, i) => i !== index);
            setLocalFields(updatedFields);
        };

        const fieldTypes = [
            { label: 'متن', value: 'text' },
            { label: 'ایمیل', value: 'email' },
            { label: 'تلفن', value: 'tel' },
            { label: 'عدد', value: 'number' },
            { label: 'متن چند خطی', value: 'textarea' },
            { label: 'انتخاب', value: 'select' },
            { label: 'چک باکس', value: 'checkbox' }
        ];

        const formStyles = [
            { label: 'مدرن', value: 'modern' },
            { label: 'کلاسیک', value: 'classic' },
            { label: 'مینیمال', value: 'minimal' }
        ];

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title="تنظیمات عمومی فرم" initialOpen={true}>
                        <TextControl
                            label="عنوان فرم"
                            value={formTitle}
                            onChange={(value) => setAttributes({ formTitle: value })}
                        />
                        <TextareaControl
                            label="توضیحات فرم"
                            value={formDescription}
                            onChange={(value) => setAttributes({ formDescription: value })}
                        />
                        <TextControl
                            label="متن دکمه ارسال"
                            value={submitButtonText}
                            onChange={(value) => setAttributes({ submitButtonText: value })}
                        />
                        <TextareaControl
                            label="پیام موفقیت"
                            value={successMessage}
                            onChange={(value) => setAttributes({ successMessage: value })}
                        />
                    </PanelBody>

                    <PanelBody title="تنظیمات فیلدها" initialOpen={false}>
                        <Button
                            variant="primary"
                            onClick={addField}
                            icon={plus}
                        >
                            افزودن فیلد جدید
                        </Button>
                        
                        {localFields.map((field, index) => (
                            <div key={field.id} style={{ 
                                border: '1px solid #ddd', 
                                padding: '15px', 
                                margin: '10px 0',
                                borderRadius: '4px'
                            }}>
                                <TextControl
                                    label="برچسب فیلد"
                                    value={field.label}
                                    onChange={(value) => updateField(index, 'label', value)}
                                />
                                <SelectControl
                                    label="نوع فیلد"
                                    value={field.type}
                                    options={fieldTypes}
                                    onChange={(value) => updateField(index, 'type', value)}
                                />
                                
                                {/* اگر فیلد از نوع select است، گزینه‌ها را نمایش بده */}
                                {field.type === 'select' && (
                                    <TextareaControl
                                        label="گزینه‌ها (هر خط یک گزینه)"
                                        value={field.options ? field.options.join('\n') : ''}
                                        onChange={(value) => {
                                            // تبدیل متن به آرایه - هر خط یک گزینه
                                            const options = value.split('\n')
                                                .map(opt => opt.trim())
                                                .filter(opt => opt !== '');
                                            updateField(index, 'options', options);
                                        }}
                                        help="هر گزینه را در یک خط جدید وارد کنید. با کلید Enter به خط بعدی بروید."
                                        rows={6}  // افزایش ارتفاع کادر متن
                                        style={{ minHeight: '120px' }}  // حداقل ارتفاع
                                    />
                                )}
                                
                                <TextControl
                                    label="متن راهنما"
                                    value={field.placeholder}
                                    onChange={(value) => updateField(index, 'placeholder', value)}
                                />
                                <ToggleControl
                                    label="اجباری"
                                    checked={field.required}
                                    onChange={(value) => updateField(index, 'required', value)}
                                />
                                <Button
                                    variant="secondary"
                                    isDestructive
                                    onClick={() => removeField(index)}
                                >
                                    حذف فیلد
                                </Button>
                            </div>
                        ))}
                    </PanelBody>

                    <PanelBody title="تنظیمات پیشرفته" initialOpen={false}>
                        <ToggleControl
                            label="فعال‌سازی تاریخچه کاربر"
                            checked={enableUserHistory}
                            onChange={(value) => setAttributes({ enableUserHistory: value })}
                            help="ذخیره و نمایش تاریخچه فرم‌های ارسال شده توسط کاربر"
                        />
                        <SelectControl
                            label="سبک فرم"
                            value={formStyle}
                            options={formStyles}
                            onChange={(value) => setAttributes({ formStyle: value })}
                        />
                        <div>
                            <label>رنگ پس‌زمینه</label>
                            <ColorPicker
                                color={backgroundColor}
                                onChange={(value) => setAttributes({ backgroundColor: value })}
                            />
                        </div>
                        <div>
                            <label>رنگ متن</label>
                            <ColorPicker
                                color={textColor}
                                onChange={(value) => setAttributes({ textColor: value })}
                            />
                        </div>
                    </PanelBody>
                </InspectorControls>

                {/* بقیه کد مربوط به پیش نمایش فرم */}
                <div className="form-preview" style={{ 
                    backgroundColor, 
                    color: textColor,
                    padding: '30px',
                    borderRadius: '8px'
                }}>
                    <RichText
                        tagName="h3"
                        value={formTitle}
                        onChange={(value) => setAttributes({ formTitle: value })}
                        placeholder="عنوان فرم را وارد کنید..."
                    />
                    
                    <RichText
                        tagName="p"
                        value={formDescription}
                        onChange={(value) => setAttributes({ formDescription: value })}
                        placeholder="توضیحات فرم را وارد کنید..."
                    />

                    <div className="form-fields-preview">
                        {localFields.map((field, index) => (
                            <div key={field.id} className="form-field-preview">
                                <label>
                                    {field.label}
                                    {field.required && <span style={{color: 'red'}}> *</span>}
                                </label>
                                {field.type === 'textarea' ? (
                                    <textarea 
                                        placeholder={field.placeholder}
                                        disabled
                                        style={{width: '100%', padding: '8px'}}
                                    />
                                ) : field.type === 'select' ? (
                                    <select disabled style={{width: '100%', padding: '8px'}}>
                                        <option value="">{field.placeholder || '-- انتخاب کنید --'}</option>
                                        {field.options && field.options.map((option, optIndex) => (
                                            <option key={optIndex} value={option}>
                                                {option}
                                            </option>
                                        ))}
                                    </select>
                                ) : field.type === 'checkbox' ? (
                                    <input 
                                        type="checkbox" 
                                        disabled
                                    />
                                ) : (
                                    <input 
                                        type={field.type} 
                                        placeholder={field.placeholder}
                                        disabled
                                        style={{width: '100%', padding: '8px'}}
                                    />
                                )}
                            </div>
                        ))}
                    </div>

                    <button 
                        className="submit-button-preview"
                        style={{
                            backgroundColor: '#007cba',
                            color: 'white',
                            padding: '12px 30px',
                            border: 'none',
                            borderRadius: '4px',
                            cursor: 'pointer'
                        }}
                    >
                        {submitButtonText}
                    </button>

                    {enableUserHistory && (
                        <div className="user-history-notice" style={{
                            marginTop: '20px',
                            padding: '10px',
                            backgroundColor: '#f0f0f1',
                            borderRadius: '4px',
                            fontSize: '12px'
                        }}>
                            📝 تاریخچه کاربر فعال است - کاربران می‌توانند فرم‌های قبلی خود را مشاهده کنند.
                        </div>
                    )}
                </div>
            </div>
        );
    },

    save: () => {
        return null; // استفاده از render.php برای فرانت‌اند
    }
});