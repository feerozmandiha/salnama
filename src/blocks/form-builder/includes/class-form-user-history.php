<?php
/**
 * User history management for form submissions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Salnama_Form_User_History {
    
    private static $instance = null;
    private $database;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->database = Salnama_Form_Database::get_instance();
        
        // Add shortcode for user history
        add_shortcode( 'salnama_form_history', array( $this, 'user_history_shortcode' ) );
        
        // Add user profile section
        add_action( 'show_user_profile', array( $this, 'add_user_profile_section' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_profile_section' ) );
    }
    
    /**
     * Get user form submission history
     */
    public function get_user_history( $user_id = null, $limit = 10 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return array();
        }
        
        return $this->database->get_user_submissions( $user_id, $limit );
    }
    
    /**
     * Get recent user activity
     */
    public function get_recent_activity( $user_id = null, $days = 30 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return array();
        }
        
        $date_from = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        
        return $this->database->get_submissions( array(
            'user_id' => $user_id,
            'date_from' => $date_from,
            'limit' => 50,
            'orderby' => 'submission_date',
            'order' => 'DESC'
        ) );
    }
    
    /**
     * Check if user has previous submissions
     */
    public function has_previous_submissions( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return false;
        }
        
        $submissions = $this->database->get_user_submissions( $user_id, 1 );
        return ! empty( $submissions );
    }
    
    /**
     * Get submission count for user
     */
    public function get_submission_count( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return 0;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'salnama_form_submissions';
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
            $user_id
        );
        
        return (int) $wpdb->get_var( $query );
    }
    
    /**
     * Get user submission statistics
     */
    public function get_user_statistics( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return array();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'salnama_form_submissions';
        
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT form_id) as unique_forms,
                MIN(submission_date) as first_submission,
                MAX(submission_date) as last_submission,
                status,
                COUNT(*) as status_count
             FROM {$table_name} 
             WHERE user_id = %d 
             GROUP BY status",
            $user_id
        );
        
        $results = $wpdb->get_results( $query );
        
        $stats = array(
            'total_submissions' => 0,
            'unique_forms' => 0,
            'first_submission' => null,
            'last_submission' => null,
            'status_breakdown' => array()
        );
        
        foreach ( $results as $row ) {
            if ( $stats['total_submissions'] === 0 ) {
                $stats['first_submission'] = $row->first_submission;
                $stats['last_submission'] = $row->last_submission;
            }
            
            $stats['total_submissions'] += $row->total;
            $stats['unique_forms'] = $row->unique_forms;
            $stats['status_breakdown'][ $row->status ] = $row->status_count;
        }
        
        return $stats;
    }
    
    /**
     * Shortcode to display user form history
     */
    public function user_history_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>لطفاً برای مشاهده تاریخچه درخواست‌ها وارد حساب کاربری خود شوید.</p>';
        }
        
        $atts = shortcode_atts( array(
            'limit' => 10,
            'show_dates' => 'yes',
            'show_status' => 'yes',
            'title' => 'تاریخچه درخواست‌های شما'
        ), $atts );
        
        $user_history = $this->get_user_history( get_current_user_id(), $atts['limit'] );
        
        if ( empty( $user_history ) ) {
            return '<p>هنوز هیچ درخواستی ثبت نکرده‌اید.</p>';
        }
        
        ob_start();
        ?>
        <div class="salnama-user-history">
            <h3><?php echo esc_html( $atts['title'] ); ?></h3>
            
            <div class="history-stats">
                <?php $stats = $this->get_user_statistics(); ?>
                <p>تعداد کل درخواست‌ها: <strong><?php echo esc_html( $stats['total_submissions'] ); ?></strong></p>
            </div>
            
            <div class="history-list">
                <?php foreach ( $user_history as $submission ) : 
                    $form_data = maybe_unserialize( $submission->form_data );
                    ?>
                    <div class="history-item status-<?php echo esc_attr( $submission->status ); ?>">
                        <div class="history-header">
                            <?php if ( $atts['show_dates'] === 'yes' ) : ?>
                                <span class="history-date">
                                    <?php echo esc_html( 
                                        date_i18n( 'j F Y - H:i', strtotime( $submission->submission_date ) ) 
                                    ); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ( $atts['show_status'] === 'yes' ) : ?>
                                <span class="history-status status-<?php echo esc_attr( $submission->status ); ?>">
                                    <?php echo $this->get_status_label( $submission->status ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="history-data">
                            <?php 
                            $preview_data = array();
                            foreach ( array( 'full_name', 'email', 'phone', 'service_type' ) as $field ) {
                                if ( ! empty( $form_data[ $field ] ) ) {
                                    $preview_data[] = esc_html( $form_data[ $field ] );
                                }
                            }
                            
                            if ( ! empty( $preview_data ) ) {
                                echo implode( ' - ', $preview_data );
                            } else {
                                echo 'درخواست مشاوره';
                            }
                            ?>
                        </div>
                        
                        <?php if ( ! empty( $submission->notes ) ) : ?>
                            <div class="history-notes">
                                <strong>یادداشت:</strong> <?php echo esc_html( $submission->notes ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .salnama-user-history {
            font-family: Tahoma, Arial, sans-serif;
        }
        
        .history-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .history-date {
            font-size: 12px;
            color: #666;
        }
        
        .history-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-new { background: #e3f2fd; color: #1976d2; }
        .status-read { background: #fff3e0; color: #f57c00; }
        .status-replied { background: #e8f5e8; color: #388e3c; }
        .status-closed { background: #f5f5f5; color: #757575; }
        
        .history-data {
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .history-notes {
            font-size: 12px;
            color: #666;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-right: 3px solid #007cba;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add user profile section for form submissions
     */
    public function add_user_profile_section( $user ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }
        
        $submission_count = $this->get_submission_count( $user->ID );
        $user_history = $this->get_user_history( $user->ID, 5 );
        ?>
        
        <h3>تاریخچه فرم‌های سالنمای نو</h3>
        
        <table class="form-table">
            <tr>
                <th>تعداد کل درخواست‌ها</th>
                <td><?php echo esc_html( $submission_count ); ?></td>
            </tr>
        </table>
        
        <?php if ( ! empty( $user_history ) ) : ?>
            <h4>آخرین درخواست‌ها</h4>
            <div style="max-height: 300px; overflow-y: auto;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>تاریخ</th>
                            <th>وضعیت</th>
                            <th>اطلاعات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $user_history as $submission ) : 
                            $form_data = maybe_unserialize( $submission->form_data );
                            ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( 
                                        date_i18n( 'Y/m/d H:i', strtotime( $submission->submission_date ) ) 
                                    ); ?>
                                </td>
                                <td>
                                    <span class="history-status status-<?php echo esc_attr( $submission->status ); ?>">
                                        <?php echo $this->get_status_label( $submission->status ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $preview = array();
                                    foreach ( array( 'full_name', 'email', 'phone' ) as $field ) {
                                        if ( ! empty( $form_data[ $field ] ) ) {
                                            $preview[] = esc_html( $form_data[ $field ] );
                                        }
                                    }
                                    echo implode( ' - ', $preview );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <style>
        .history-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-new { background: #e3f2fd; color: #1976d2; }
        .status-read { background: #fff3e0; color: #f57c00; }
        .status-replied { background: #e8f5e8; color: #388e3c; }
        .status-closed { background: #f5f5f5; color: #757575; }
        </style>
        <?php
    }
    
    /**
     * Get status label in Persian
     */
    private function get_status_label( $status ) {
        $labels = array(
            'new' => 'جدید',
            'read' => 'خوانده شده',
            'replied' => 'پاسخ داده شده',
            'closed' => 'بسته شده'
        );
        
        return $labels[ $status ] ?? $status;
    }
}