<?php
/**
 * Database handler for form submissions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Salnama_Form_Database {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'salnama_form_submissions';
    }
    
    /**
     * Check if table exists - PUBLIC METHOD
     */
    public function table_exists() {
        global $wpdb;
        return $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
    }
    
    /**
     * Insert form submission - نسخه کاملاً اصلاح شده
     */
    public function insert_submission( $data ) {
        global $wpdb;
        
        // بررسی وجود جدول
        if ( ! $this->table_exists() ) {
            error_log('Salnama Form Database: Table does not exist');
            return false;
        }
        
        // ثبت لاگ برای دیباگ
        error_log('Salnama Form Database: Raw data received: ' . print_r($data, true));
        
        // استخراج form_data از داده‌ها
        $form_data = $data['form_data'] ?? array();
        
        // اگر form_data آرایه است، آن را سریالایز کن
        if ( is_array( $form_data ) ) {
            $serialized_form_data = maybe_serialize( $form_data );
            error_log('Salnama Form Database: Serialized form data: ' . $serialized_form_data );
        } else {
            $serialized_form_data = $form_data;
            error_log('Salnama Form Database: Form data is not array: ' . $form_data );
        }
        
        // آماده‌سازی داده‌ها برای درج
        $insert_data = array(
            'user_id' => get_current_user_id() ?: 0,
            'form_id' => sanitize_text_field( $data['form_id'] ?? 'default_form' ),
            'form_data' => $serialized_form_data,
            'submission_date' => current_time( 'mysql' ),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'status' => 'new',
            'notes' => ''
        );
        
        // فرمت‌های داده برای درج
        $formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
        
        error_log('Salnama Form Database: Final data to insert: ' . print_r($insert_data, true));
        
        // درج در دیتابیس
        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            $formats
        );
        
        if ( $result === false ) {
            error_log('Salnama Form Database: Insert failed - ' . $wpdb->last_error);
            return false;
        } else {
            $insert_id = $wpdb->insert_id;
            error_log('Salnama Form Database: Insert successful, ID: ' . $insert_id);
            return $insert_id;
        }
    }
    
    /**
     * Get submissions by user ID
     */
    public function get_user_submissions( $user_id, $limit = 10, $offset = 0 ) {
        global $wpdb;
        
        // بررسی وجود جدول
        if ( ! $this->table_exists() ) {
            return array();
        }
        
        // اگر limit = -1 است، LIMIT را حذف کن
        $limit_clause = '';
        if ( $limit > 0 ) {
            $limit_clause = $wpdb->prepare( " LIMIT %d OFFSET %d", $limit, $offset );
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE user_id = %d 
             ORDER BY submission_date DESC" . $limit_clause,
            $user_id
        );
        
        return $wpdb->get_results( $query );
    }
    
    /**
     * Get submission by ID
     */
    public function get_submission( $id ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return null;
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );
        
        return $wpdb->get_row( $query );
    }
    
    /**
     * Get all submissions with filters - نسخه کامل و اصلاح شده
     */
    public function get_submissions( $args = array() ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return array();
        }
        
        $defaults = array(
            'user_id' => null,
            'form_id' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'service_filter' => null,
            'search' => null,
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'submission_date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $where = array( '1=1' );
        $prepare_values = array();
        
        if ( $args['user_id'] ) {
            $where[] = 'user_id = %d';
            $prepare_values[] = $args['user_id'];
        }
        
        if ( $args['form_id'] ) {
            $where[] = 'form_id = %s';
            $prepare_values[] = $args['form_id'];
        }
        
        if ( $args['status'] ) {
            $where[] = 'status = %s';
            $prepare_values[] = $args['status'];
        }
        
        if ( $args['date_from'] ) {
            $where[] = 'submission_date >= %s';
            $prepare_values[] = $args['date_from'];
        }
        
        if ( $args['date_to'] ) {
            $where[] = 'submission_date <= %s';
            $prepare_values[] = $args['date_to'];
        }

        // فیلتر بر اساس نوع خدمات
        if ( $args['service_filter'] ) {
            $where[] = 'form_data LIKE %s';
            $prepare_values[] = '%' . $wpdb->esc_like( $args['service_filter'] ) . '%';
        }
        
        // جستجوی عمومی
        if ( $args['search'] ) {
            $where[] = '(form_data LIKE %s OR notes LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }
        
        $where_clause = implode( ' AND ', $where );
        
        // ساخت کویری اصلی بدون LIMIT
        $query = "SELECT * FROM {$this->table_name} 
                  WHERE {$where_clause} 
                  ORDER BY {$args['orderby']} {$args['order']}";
        
        // اضافه کردن LIMIT فقط اگر مقدار مثبت است
        if ( $args['limit'] > 0 ) {
            $query .= " LIMIT %d OFFSET %d";
            $prepare_values[] = $args['limit'];
            $prepare_values[] = $args['offset'];
        }
        
        if ( ! empty( $prepare_values ) ) {
            $query = $wpdb->prepare( $query, $prepare_values );
        }
        
        return $wpdb->get_results( $query );
    }
    
    /**
     * Count submissions - متد جدید برای شمارش
     */
    public function count_submissions( $args = array() ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return 0;
        }
        
        $defaults = array(
            'user_id' => null,
            'form_id' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'service_filter' => null,
            'search' => null
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $where = array( '1=1' );
        $prepare_values = array();
        
        if ( $args['user_id'] ) {
            $where[] = 'user_id = %d';
            $prepare_values[] = $args['user_id'];
        }
        
        if ( $args['form_id'] ) {
            $where[] = 'form_id = %s';
            $prepare_values[] = $args['form_id'];
        }
        
        if ( $args['status'] ) {
            $where[] = 'status = %s';
            $prepare_values[] = $args['status'];
        }
        
        if ( $args['date_from'] ) {
            $where[] = 'submission_date >= %s';
            $prepare_values[] = $args['date_from'];
        }
        
        if ( $args['date_to'] ) {
            $where[] = 'submission_date <= %s';
            $prepare_values[] = $args['date_to'];
        }
        
        // فیلتر بر اساس نوع خدمات
        if ( $args['service_filter'] ) {
            $where[] = 'form_data LIKE %s';
            $prepare_values[] = '%' . $wpdb->esc_like( $args['service_filter'] ) . '%';
        }
        
        // جستجوی عمومی
        if ( $args['search'] ) {
            $where[] = '(form_data LIKE %s OR notes LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }
        
        $where_clause = implode( ' AND ', $where );
        
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        
        if ( ! empty( $prepare_values ) ) {
            $query = $wpdb->prepare( $query, $prepare_values );
        }
        
        return (int) $wpdb->get_var( $query );
    }
    
    /**
     * Update submission status
     */
    public function update_submission_status( $id, $status, $notes = '' ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return false;
        }
        
        $data = array( 'status' => $status );
        
        if ( ! empty( $notes ) ) {
            $data['notes'] = $notes;
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }
    
    /**
     * Delete submission
     */
    public function delete_submission( $id ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return false;
        }
        
        return $wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );
    }
    
    /**
     * Get popular services - متد جدید برای دریافت محبوب‌ترین خدمات
     */
    public function get_popular_services( $limit = 10 ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return array();
        }
        
        // این یک روش ساده برای استخراج خدمات است
        // در نسخه‌های آینده می‌توانید این را بهبود ببخشید
        $submissions = $this->get_submissions( array( 'limit' => 1000 ) );
        $service_counts = array();
        
        foreach ( $submissions as $submission ) {
            $form_data = maybe_unserialize( $submission->form_data );
            
            if ( is_array( $form_data ) ) {
                // جستجو برای فیلدهای مربوط به خدمات
                $service_fields = array( 'service_type', 'field_service_type', 'service', 'خدمات', 'product_type' );
                
                foreach ( $service_fields as $field ) {
                    if ( ! empty( $form_data[ $field ] ) ) {
                        $service = $form_data[ $field ];
                        if ( ! isset( $service_counts[ $service ] ) ) {
                            $service_counts[ $service ] = 0;
                        }
                        $service_counts[ $service ]++;
                        break;
                    }
                }
            }
        }
        
        arsort( $service_counts );
        return array_slice( $service_counts, 0, $limit, true );
    }
    
    /**
     * Get submission statistics - نسخه اصلاح شده
     */
    public function get_statistics( $period = '30days' ) {
        global $wpdb;
        
        if ( ! $this->table_exists() ) {
            return array();
        }
        
        $date_condition = '';
        
        switch ( $period ) {
            case '7days':
                $date_condition = 'submission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case '30days':
                $date_condition = 'submission_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case '90days':
                $date_condition = 'submission_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
                break;
            case 'year':
                $date_condition = 'submission_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                break;
            default:
                $date_condition = '1=1';
                break;
        }
        
        $query = "SELECT 
                    COUNT(*) as total_submissions,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    status,
                    DATE(submission_date) as submission_day
                  FROM {$this->table_name} 
                  WHERE {$date_condition}
                  GROUP BY submission_day, status
                  ORDER BY submission_day DESC, status ASC";
        
        $results = $wpdb->get_results( $query );
        
        // ساخت ساختار داده‌ای مناسب
        $formatted_results = array();
        foreach ( $results as $result ) {
            $formatted_results[] = (object) array(
                'total_submissions' => (int) $result->total_submissions,
                'unique_users' => (int) $result->unique_users,
                'unique_ips' => (int) $result->unique_ips,
                'status' => $result->status,
                'status_count' => (int) $result->total_submissions,
                'submission_day' => $result->submission_day
            );
        }
        
        return $formatted_results;
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
}