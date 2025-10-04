<?php
/**
 * Form submission processor class - نسخه اصلاح شده برای جلوگیری از ثبت دوباره
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Salnama_Form_Processor {
    
    private static $instance = null;
    private $submission_status = null;
    private $error_message = '';
    private $field_errors = [];
    private $submitted_data = [];
    private $database;
    private $processed = false; // فلگ برای جلوگیری از پردازش دوباره
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->database = Salnama_Form_Database::get_instance();
        
        // همیشه اکشن‌ها را ثبت کن
        add_action( 'wp_ajax_salnama_form_submit', array( $this, 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_nopriv_salnama_form_submit', array( $this, 'handle_ajax_submission' ) );
        
        // پردازش معمولی فقط اگر فرم از طریق POST ارسال شده و AJAX نیست
        if ( $this->is_form_submitted() && ! $this->is_ajax_request() ) {
            $this->process_form_submission();
        }
    }
    
    /**
     * بررسی آیا فرم ارسال شده است
     */
    private function is_form_submitted() {
        return isset( $_POST['salnama_form_nonce'] ) && 
               isset( $_POST['action'] ) && 
               $_POST['action'] === 'salnama_form_submit';
    }
    
    /**
     * بررسی آیا درخواست AJAX است
     */
    private function is_ajax_request() {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }
    
    /**
     * هندلر AJAX
     */
    public function handle_ajax_submission() {
        // اگر قبلاً پردازش شده، خروج
        if ( $this->processed ) {
            return;
        }
        
        $this->process_form_submission();
        
        if ( $this->submission_status === 'success' ) {
            wp_send_json_success( array( 
                'message' => 'درخواست شما با موفقیت ثبت شد. همکاران ما به زودی با شما تماس خواهند گرفت.'
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => $this->error_message,
                'field_errors' => $this->field_errors
            ) );
        }
        
        // خاتمه اجرا پس از ارسال پاسخ AJAX
        wp_die();
    }
    
    /**
     * Process form submission
     */
    public function process_form_submission() {
        // اگر قبلاً پردازش شده، خروج
        if ( $this->processed ) {
            return;
        }
        
        if ( ! $this->is_form_submitted() ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['salnama_form_nonce'], 'salnama_form_submit' ) ) {
            $this->submission_status = 'error';
            $this->error_message = 'خطای امنیتی: درخواست نامعتبر است.';
            $this->processed = true;
            return;
        }
        
        $this->validate_and_process_form();
        $this->processed = true; // علامتگذاری که پردازش انجام شده
    }
    
    /**
     * Validate and process form data
     */
    private function validate_and_process_form() {
        // ابتدا بررسی کنیم جدول آماده است
        if ( ! $this->database->table_exists() ) {
            $this->submission_status = 'error';
            $this->error_message = 'سیستم فرم در حال راه‌اندازی است. لطفاً مجدداً تلاش کنید.';
            return;
        }
        
        $form_data = $this->sanitize_form_data( $_POST );
        
        // Basic validation
        if ( empty( $form_data ) ) {
            $this->submission_status = 'error';
            $this->error_message = 'خطا: اطلاعات فرم خالی است.';
            return;
        }
        
        // Validate required fields
        $this->validate_required_fields( $form_data );
        
        // Validate email format
        if ( isset( $form_data['email'] ) && ! is_email( $form_data['email'] ) ) {
            $this->field_errors['email'] = 'لطفاً یک ایمیل معتبر وارد کنید.';
        }
        
        // Validate phone format (Iranian mobile)
        if ( isset( $form_data['phone'] ) && ! $this->validate_iranian_phone( $form_data['phone'] ) ) {
            $this->field_errors['phone'] = 'لطفاً شماره موبایل معتبر وارد کنید (09xxxxxxxxx).';
        }
        
        // If there are field errors, stop processing
        if ( ! empty( $this->field_errors ) ) {
            $this->submission_status = 'error';
            $this->error_message = 'لطفاً خطاهای فرم را برطرف کنید.';
            $this->submitted_data = $form_data;
            return;
        }
        
        // بررسی تکراری نبودن درخواست (بر اساس ایمیل و تلفن در 5 دقیقه گذشته)
        if ( $this->is_duplicate_submission( $form_data ) ) {
            $this->submission_status = 'error';
            $this->error_message = 'درخواست مشابهی به تازگی ثبت شده است. لطفاً چند دقیقه دیگر مجدداً تلاش کنید.';
            return;
        }
        
        // Save form submission
        $saved = $this->database->insert_submission( array(
            'form_id' => $_POST['form_id'] ?? 'default_form',
            'form_data' => $form_data
        ) );
        
        if ( $saved ) {
            $this->submission_status = 'success';
            $this->send_notifications( $form_data );
        } else {
            $this->submission_status = 'error';
            $this->error_message = 'خطا در ذخیره‌سازی اطلاعات. لطفاً مجدداً تلاش کنید.';
        }
    }
    
    /**
     * بررسی درخواست تکراری
     */
    private function is_duplicate_submission( $form_data ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'salnama_form_submissions';
        $five_minutes_ago = date( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );
        
        // بررسی بر اساس ایمیل
        if ( ! empty( $form_data['email'] ) ) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE form_data LIKE %s 
                 AND submission_date > %s",
                '%' . $wpdb->esc_like( $form_data['email'] ) . '%',
                $five_minutes_ago
            );
            
            $count = $wpdb->get_var( $query );
            if ( $count > 0 ) {
                return true;
            }
        }
        
        // بررسی بر اساس تلفن
        if ( ! empty( $form_data['phone'] ) ) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE form_data LIKE %s 
                 AND submission_date > %s",
                '%' . $wpdb->esc_like( $form_data['phone'] ) . '%',
                $five_minutes_ago
            );
            
            $count = $wpdb->get_var( $query );
            if ( $count > 0 ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize form data
     */
    private function sanitize_form_data( $data ) {
        $sanitized = [];
        
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, array( 'salnama_form_nonce', 'action', 'form_id', '_wp_http_referer' ) ) ) {
                continue;
            }
            
            if ( is_array( $value ) ) {
                $sanitized[ sanitize_text_field( $key ) ] = array_map( 'sanitize_text_field', $value );
            } else {
                $sanitized[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
            }
        }
        
        return $sanitized;
    }

    /**
 * Save form submission - نسخه اصلاح شده
 */
    private function save_form_submission( $form_data ) {
        // ابتدا بررسی کنیم جدول آماده است
        if ( ! $this->database->table_exists() ) {
            error_log('Salnama: Database table not ready for submission');
            return false;
        }
        
        // ثبت لاگ برای دیباگ
        error_log('Salnama: Form data to save: ' . print_r($form_data, true));
        
        // Save form submission
        $saved = $this->database->insert_submission( array(
            'form_id' => $_POST['form_id'] ?? 'default_form',
            'form_data' => $form_data
        ) );
        
        if ( $saved ) {
            error_log('Salnama: Submission saved successfully with ID: ' . $saved);
            $this->submission_status = 'success';
            $this->send_notifications( $form_data );
        } else {
            error_log('Salnama: Failed to save submission');
            $this->submission_status = 'error';
            $this->error_message = 'خطا در ذخیره‌سازی اطلاعات. لطفاً مجدداً تلاش کنید.';
        }
        
        return $saved;
    }
    
    /**
     * Validate required fields
     */
    private function validate_required_fields( $form_data ) {
        // This would be dynamically checked based on form configuration
        $required_fields = array( 'full_name', 'email', 'phone' );
        
        foreach ( $required_fields as $field ) {
            if ( empty( $form_data[ $field ] ) ) {
                $this->field_errors[ $field ] = 'این فیلد اجباری است.';
            }
        }
    }
    
    /**
     * Validate Iranian phone number
     */
    private function validate_iranian_phone( $phone ) {
        $pattern = '/^09[0-9]{9}$/';
        return preg_match( $pattern, $phone );
    }
    
    /**
     * Send email notifications
     */
    private function send_notifications( $form_data ) {
        $to = get_option( 'admin_email' );
        $subject = 'درخواست مشاوره جدید - ' . get_bloginfo( 'name' );
        
        $message = "یک درخواست مشاوره جدید دریافت شده است:\n\n";
        
        foreach ( $form_data as $key => $value ) {
            $label = $this->get_field_label( $key );
            $message .= "{$label}: {$value}\n";
        }
        
        $message .= "\nتاریخ ثبت: " . current_time( 'j F Y H:i' );
        $message .= "\nآی‌پی کاربر: " . $this->get_client_ip();
        
        wp_mail( $to, $subject, $message );
    }
    
    /**
     * Get field label for display
     */
    private function get_field_label( $field_key ) {
        $labels = array(
            'full_name' => 'نام کامل',
            'email' => 'ایمیل',
            'phone' => 'شماره تماس'
        );
        
        return $labels[ $field_key ] ?? $field_key;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
        
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = $_SERVER[ $key ];
                if ( strpos( $ip, ',' ) !== false ) {
                    $ips = explode( ',', $ip );
                    $ip = trim( $ips[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        
        return 'UNKNOWN';
    }
    
    // Getters
    public function get_submission_status() {
        return $this->submission_status;
    }
    
    public function get_error_message() {
        return $this->error_message;
    }
    
    public function get_field_errors() {
        return $this->field_errors;
    }
    
    public function has_field_error( $field_name ) {
        return isset( $this->field_errors[ $field_name ] );
    }
    
    public function get_field_error( $field_name ) {
        return $this->field_errors[ $field_name ] ?? '';
    }
    
    public function get_submitted_data() {
        return $this->submitted_data;
    }
}