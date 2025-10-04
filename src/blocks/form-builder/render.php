<?php
/**
 * Template for rendering the Form Builder block on frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load form processor
$form_processor = Salnama_Form_Processor::get_instance();
$user_history = Salnama_Form_User_History::get_instance();

// Get block attributes
$form_title = $attributes['formTitle'] ?? 'فرم درخواست مشاوره';
$form_description = $attributes['formDescription'] ?? 'لطفاً اطلاعات خود را وارد کنید تا کارشناسان ما با شما تماس بگیرند.';
$submit_button_text = $attributes['submitButtonText'] ?? 'ارسال درخواست';
$success_message = $attributes['successMessage'] ?? 'درخواست شما با موفقیت ثبت شد. همکاران ما به زودی با شما تماس خواهند گرفت.';
$fields = $attributes['fields'] ?? [];
$enable_user_history = $attributes['enableUserHistory'] ?? true;
$form_style = $attributes['formStyle'] ?? 'modern';
$background_color = $attributes['backgroundColor'] ?? '#ffffff';
$text_color = $attributes['textColor'] ?? '#333333';

// Get form submission status
$submission_status = $form_processor->get_submission_status();
$submitted_data = $form_processor->get_submitted_data();

// Generate unique form ID
$form_id = 'salnama_form_' . wp_generate_uuid4();

// Get user history if enabled and user is logged in
$user_form_history = [];
if ($enable_user_history && is_user_logged_in()) {
    $user_form_history = $user_history->get_user_history(get_current_user_id());
}
?>

<div class="salnama-form-builder salnama-form-style-<?php echo esc_attr($form_style); ?>"
     data-form-id="<?php echo esc_attr($form_id); ?>">

    <div class="salnama-form-container" style="background-color: <?php echo esc_attr($background_color); ?>;">
        
        <?php if ($submission_status === 'success') : ?>
            <div class="form-message success">
                <?php echo wp_kses_post($success_message); ?>
            </div>
        <?php elseif ($submission_status === 'error') : ?>
            <div class="form-message error">
                <?php echo esc_html($form_processor->get_error_message()); ?>
            </div>
        <?php endif; ?>

        <form id="<?php echo esc_attr($form_id); ?>" 
              class="salnama-form" 
              method="post" 
              novalidate>
            
            <?php wp_nonce_field('salnama_form_submit', 'salnama_form_nonce'); ?>
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
            <input type="hidden" name="action" value="salnama_form_submit">

            <div class="form-header">
                <?php if (!empty($form_title)) : ?>
                    <h3 class="form-title"><?php echo esc_html($form_title); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($form_description)) : ?>
                    <p class="form-description"><?php echo esc_html($form_description); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-fields">
                <?php foreach ($fields as $index => $field) : 
                    $field_id = $field['id'] ?? 'field_' . $index;
                    $field_type = $field['type'] ?? 'text';
                    $field_label = $field['label'] ?? '';
                    $field_required = $field['required'] ?? false;
                    $field_placeholder = $field['placeholder'] ?? '';
                    $field_value = $submitted_data[$field_id] ?? '';
                    
                    $field_classes = ['form-field'];
                    if ($form_processor->has_field_error($field_id)) {
                        $field_classes[] = 'has-error';
                    }
                ?>
                    <div class="<?php echo esc_attr(implode(' ', $field_classes)); ?>">
                        <?php if (!empty($field_label)) : ?>
                            <label for="<?php echo esc_attr($field_id); ?>">
                                <?php if ($field_required) : ?>
                                    <span class="required-star">*</span>
                                <?php endif; ?>
                                <?php echo esc_html($field_label); ?>
                            </label>
                        <?php endif; ?>

                        <?php switch ($field_type):
                            case 'textarea': ?>
                                <textarea 
                                    id="<?php echo esc_attr($field_id); ?>"
                                    name="<?php echo esc_attr($field_id); ?>"
                                    placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                    <?php echo $field_required ? 'required' : ''; ?>
                                    class="<?php echo $form_processor->has_field_error($field_id) ? 'error' : ''; ?>"
                                ><?php echo esc_textarea($field_value); ?></textarea>
                                <?php break;
                            
                            case 'select': 
                                $field_options = $field['options'] ?? [];
                                ?>
                                <select 
                                    id="<?php echo esc_attr($field_id); ?>"
                                    name="<?php echo esc_attr($field_id); ?>"
                                    <?php echo $field_required ? 'required' : ''; ?>
                                    class="<?php echo $form_processor->has_field_error($field_id) ? 'error' : ''; ?>"
                                >
                                    <option value=""><?php echo esc_attr($field_placeholder ?: '-- انتخاب کنید --'); ?></option>
                                    <?php foreach ($field_options as $option) : 
                                        $option = trim($option);
                                        if (!empty($option)) : ?>
                                            <option value="<?php echo esc_attr($option); ?>" 
                                                <?php echo ($field_value === $option) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <?php 
                                break;

                            
                            case 'checkbox': ?>
                                <label class="checkbox-label">
                                    <input 
                                        type="checkbox"
                                        id="<?php echo esc_attr($field_id); ?>"
                                        name="<?php echo esc_attr($field_id); ?>"
                                        value="1"
                                        <?php echo $field_required ? 'required' : ''; ?>
                                        <?php echo $field_value ? 'checked' : ''; ?>
                                        class="<?php echo $form_processor->has_field_error($field_id) ? 'error' : ''; ?>"
                                    >
                                    <span><?php echo esc_html($field_placeholder ?: 'تایید می‌کنم'); ?></span>
                                </label>
                                <?php break;
                            
                            default: ?>
                                <input 
                                    type="<?php echo esc_attr($field_type); ?>"
                                    id="<?php echo esc_attr($field_id); ?>"
                                    name="<?php echo esc_attr($field_id); ?>"
                                    value="<?php echo esc_attr($field_value); ?>"
                                    placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                    <?php echo $field_required ? 'required' : ''; ?>
                                    class="<?php echo $form_processor->has_field_error($field_id) ? 'error' : ''; ?>"
                                />
                        <?php endswitch; ?>

                        <?php if ($form_processor->has_field_error($field_id)) : ?>
                            <span class="field-error">
                                <?php echo esc_html($form_processor->get_field_error($field_id)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-button">
                    <?php echo esc_html($submit_button_text); ?>
                </button>
            </div>
        </form>

        <?php if ($enable_user_history && is_user_logged_in() && !empty($user_form_history)) : ?>
            <div class="user-history-section">
                <h4 class="history-title">تاریخچه درخواست‌های قبلی شما</h4>
                <div class="history-list">
                    <?php foreach (array_slice($user_form_history, 0, 5) as $history) : ?>
                        <div class="history-item">
                            <div class="history-date">
                                <?php echo esc_html(
                                    date_i18n('j F Y - H:i', strtotime($history->submission_date))
                                ); ?>
                            </div>
                            <div class="history-data">
                                <?php 
                                $data = maybe_unserialize($history->form_data);
                                if (is_array($data)) {
                                    $preview = [];
                                    foreach (['full_name', 'email', 'phone'] as $key) {
                                        if (!empty($data[$key])) {
                                            $preview[] = $data[$key];
                                        }
                                    }
                                    echo esc_html(implode(' - ', $preview));
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('<?php echo esc_js($form_id); ?>');
        if (!form) return;

        // جلوگیری از ارسال چندباره
        let isSubmitting = false;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (isSubmitting) {
                return; // اگر در حال ارسال است، خروج
            }
            
            isSubmitting = true;
            
            const submitButton = form.querySelector('.submit-button');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.textContent = 'در حال ارسال...';
            submitButton.classList.add('loading');
            
            // Collect form data
            const formData = new FormData(form);
            
            // Submit via AJAX
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'form-message success';
                    successMessage.textContent = data.data.message;
                    
                    form.parentNode.insertBefore(successMessage, form);
                    form.style.display = 'none';
                    
                    // Reload page after 3 seconds to show updated history
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Show error message
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'form-message error';
                    errorMessage.textContent = data.data.message;
                    
                    // حذف پیام قبلی اگر وجود دارد
                    const existingMessage = form.parentNode.querySelector('.form-message');
                    if (existingMessage) {
                        existingMessage.remove();
                    }
                    
                    form.parentNode.insertBefore(errorMessage, form);
                    
                    // نمایش خطاهای فیلدها
                    if (data.data.field_errors) {
                        Object.keys(data.data.field_errors).forEach(fieldName => {
                            const field = form.querySelector(`[name="${fieldName}"]`);
                            if (field) {
                                field.classList.add('error');
                                const errorSpan = document.createElement('span');
                                errorSpan.className = 'field-error';
                                errorSpan.textContent = data.data.field_errors[fieldName];
                                field.parentNode.appendChild(errorSpan);
                            }
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessage = document.createElement('div');
                errorMessage.className = 'form-message error';
                errorMessage.textContent = 'خطا در ارسال فرم. لطفاً مجدداً تلاش کنید.';
                form.parentNode.insertBefore(errorMessage, form);
            })
            .finally(() => {
                // Reset button state
                isSubmitting = false;
                submitButton.disabled = false;
                submitButton.textContent = originalText;
                submitButton.classList.remove('loading');
            });
        });
    });
</script>