<?php
/**
 * Plugin Name:       Salnama - Custom Blocks
 * Plugin URI:        https://salnamanow.com
 * Description:       افزونه اختصاصی برای بلوک‌های سفارشی وب‌سایت سالنمای نو با ساختار OOP و مدرن.
 * Version:           1.0.1
 * Author:            Salnama Dev Team
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */
// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * تعریف ثابت نسخه افزونه برای مدیریت کش و به‌روزرسانی‌ها
 */
if ( ! defined( 'SALNAMA_VERSION' ) ) {
    define( 'SALNAMA_VERSION', '1.0.0' );
}

/**
 * مسیر فیزیکی افزونه
 */
if ( ! defined( 'SALNAMA_PLUGIN_DIR' ) ) {
    define( 'SALNAMA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * آدرس URL افزونه
 */
if ( ! defined( 'SALNAMA_PLUGIN_URL' ) ) {
    define( 'SALNAMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * فایل اصلی افزونه برای register_activation_hook
 */
if ( ! defined( 'SALNAMA_PLUGIN_FILE' ) ) {
    define( 'SALNAMA_PLUGIN_FILE', __FILE__ );
}

// بارگذاری کلاس هسته
require_once SALNAMA_PLUGIN_DIR . 'src/class-salnama-init.php';

/**
 * تابعی برای اجرای افزونه به صورت OOP
 */
function salnama_run_blocks() {
    $salnama_init = new Salnama_Init();
    $salnama_init->run();
}

// ثبت هوک فعال‌سازی در فایل اصلی
function salnama_activate_plugin() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'salnama_form_submissions';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL DEFAULT 0,
        form_id varchar(100) NOT NULL,
        form_data longtext NOT NULL,
        submission_date datetime NOT NULL,
        ip_address varchar(45) NOT NULL,
        user_agent text NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'new',
        notes text,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY form_id (form_id),
        KEY status (status),
        KEY submission_date (submission_date)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
    update_option( 'salnama_db_version', '1.0.0' );
    
    // لاگ برای دیباگ
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'Salnama Plugin Activated - Tables Created' );
    }
}
register_activation_hook( SALNAMA_PLUGIN_FILE, 'salnama_activate_plugin' );

// اجرای افزونه
add_action( 'plugins_loaded', 'salnama_run_blocks' );