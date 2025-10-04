<?php
/**
 * کلاس اصلی برای مدیریت بارگذاری بلوک‌ها و تنظیمات افزونه Salnama.
 */

class Salnama_Init {

    public function run() {
        // ابتدا بررسی و ایجاد جداول
        add_action( 'init', array( $this, 'check_database_tables' ) );
        
        // سپس بارگذاری بقیه کامپوننت‌ها
        add_action( 'init', array( $this, 'load_form_dependencies' ) );
        
        // ثبت بلوک‌ها
        add_action( 'init', array( $this, 'register_blocks' ) );
        
        // ثبت دسته‌بندی بلوک
        add_filter( 'block_categories_all', array( $this, 'register_custom_block_category' ) );
        
        // اضافه کردن منوی مدیریت
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    /**
     * بررسی وجود جداول دیتابیس
     */
    public function check_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'salnama_form_submissions';
        
        // اگر جدول وجود ندارد، ایجادش کن
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            $this->create_database_tables();
        }
    }

    /**
     * ایجاد جداول دیتابیس
     */
    private function create_database_tables() {
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
        $result = dbDelta( $sql );
        
        update_option( 'salnama_db_version', '1.0.0' );
        
        error_log('Salnama Tables Created: ' . print_r($result, true));
    }

    /**
     * بارگذاری وابستگی‌های فرم‌ساز
     */
    public function load_form_dependencies() {
        // مسیر فایل‌های شامل
        $includes_dir = SALNAMA_PLUGIN_DIR . 'src/blocks/form-builder/includes/';
        
        // فایل‌های مورد نیاز
        $files = [
            'class-form-database.php',
            'class-form-processor.php',
            'class-form-user-history.php', 
            'form-functions.php'
        ];
        
        foreach ( $files as $file ) {
            $file_path = $includes_dir . $file;
            if ( file_exists( $file_path ) ) {
                require_once $file_path;
                error_log('Salnama: Loaded ' . $file);
            } else {
                error_log('Salnama: File not found - ' . $file_path);
            }
        }
        
        // مقداردهی اولیه اگر کلاس‌ها موجود هستند
        if ( class_exists( 'Salnama_Form_Database' ) ) {
            Salnama_Form_Database::get_instance();
            error_log('Salnama: Database class initialized');
        }
        
        if ( class_exists( 'Salnama_Form_Processor' ) ) {
            Salnama_Form_Processor::get_instance();
            error_log('Salnama: Processor class initialized');
        }
        
        if ( class_exists( 'Salnama_Form_User_History' ) ) {
            Salnama_Form_User_History::get_instance();
            error_log('Salnama: User History class initialized');
        }
    }

    /**
     * اضافه کردن منوی مدیریت
     */
    public function add_admin_menu() {
        add_menu_page(
            'درخواست‌های مشاوره',
            'درخواست‌ها',
            'manage_options',
            'salnama-submissions',
            array( $this, 'render_submissions_page' ),
            'dashicons-email-alt2',
            30
        );
        
        add_submenu_page(
            'salnama-submissions',
            'همه درخواست‌ها',
            'همه درخواست‌ها',
            'manage_options',
            'salnama-submissions',
            array( $this, 'render_submissions_page' )
        );
        
        add_submenu_page(
            'salnama-submissions',
            'آمار و گزارشات',
            'آمار و گزارشات',
            'manage_options',
            'salnama-submissions-stats',
            array( $this, 'render_stats_page' )
        );
        
        error_log('Salnama: Admin menu added');
    }

    /**
     * رندر صفحه درخواست‌ها
     */
    public function render_submissions_page() {
        // اگر تابع وجود دارد از آن استفاده کن، در غیر این صورت صفحه ساده نشان بده
        if ( function_exists( 'salnama_submissions_page' ) ) {
            salnama_submissions_page();
        } else {
            $this->render_simple_submissions_page();
        }
    }

    /**
     * رندر صفحه آمار
     */
    public function render_stats_page() {
        if ( function_exists( 'salnama_submissions_stats_page' ) ) {
            salnama_submissions_stats_page();
        } else {
            $this->render_simple_stats_page();
        }
    }

    /**
     * صفحه ساده درخواست‌ها (فقط برای تست)
     */
    private function render_simple_submissions_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'salnama_form_submissions';
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submission_date DESC LIMIT 50");
        ?>
        <div class="wrap">
            <h1>درخواست‌های مشاوره - نسخه ساده</h1>
            
            <?php if ( empty( $submissions ) ) : ?>
                <div class="notice notice-info">
                    <p>هنوز هیچ درخواستی ثبت نشده است.</p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>کاربر</th>
                            <th>تاریخ</th>
                            <th>وضعیت</th>
                            <th>نام</th>
                            <th>ایمیل</th>
                            <th>تلفن</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $submissions as $submission ) : 
                            $form_data = maybe_unserialize( $submission->form_data );
                            $user = $submission->user_id ? get_userdata( $submission->user_id ) : null;
                        ?>
                            <tr>
                                <td><?php echo $submission->id; ?></td>
                                <td>
                                    <?php if ( $user ) : ?>
                                        <?php echo $user->display_name; ?>
                                    <?php else : ?>
                                        مهمان
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date_i18n( 'Y/m/d H:i', strtotime( $submission->submission_date ) ); ?></td>
                                <td><?php echo $submission->status; ?></td>
                                <td><?php echo esc_html( $form_data['full_name'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $form_data['email'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $form_data['phone'] ?? '' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div style="margin-top: 20px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                <h3>اطلاعات دیباگ:</h3>
                <p>تعداد درخواست‌ها: <?php echo count( $submissions ); ?></p>
                <p>مسیر افزونه: <?php echo SALNAMA_PLUGIN_DIR; ?></p>
                <p>فایل form-functions.php: <?php echo file_exists( SALNAMA_PLUGIN_DIR . 'src/blocks/form-builder/includes/form-functions.php' ) ? 'موجود' : 'مفقود'; ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * صفحه ساده آمار (فقط برای تست)
     */
    private function render_simple_stats_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'salnama_form_submissions';
        
        // استفاده از کویری ساده برای شمارش
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(submission_date) = CURDATE()");
        $new = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'new'");
        ?>
        <div class="wrap">
            <h1>آمار و گزارشات - نسخه ساده</h1>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
                <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>کل درخواست‌ها</h3>
                    <div style="font-size: 2em; font-weight: bold;"><?php echo $total; ?></div>
                </div>
                
                <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>درخواست‌های امروز</h3>
                    <div style="font-size: 2em; font-weight: bold;"><?php echo $today; ?></div>
                </div>
                
                <div style="background: #fff3e0; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>درخواست‌های جدید</h3>
                    <div style="font-size: 2em; font-weight: bold;"><?php echo $new; ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * دسته‌بندی سفارشی "Salnama" را برای بلوک‌ها ثبت می‌کند.
     */
    public function register_custom_block_category( $categories ) {
        $custom_category = array(
            'slug'  => 'salnama-blocks',
            'title' => __( 'بلوک‌های سفارشی سالنمای نو', 'salnama-blocks' ),
        );

        return array_merge(
            array( $custom_category ), 
            $categories
        );
    }

    /**
     * ثبت بلوک‌ها
     */
    public function register_blocks() {
        $blocks_dir = SALNAMA_PLUGIN_DIR . 'build/blocks/';
        
        if ( ! is_dir( $blocks_dir ) ) {
            error_log( 'Salnama Blocks Error: build/blocks directory not found at: ' . $blocks_dir );
            return;
        }

        $block_folders = array_diff( scandir( $blocks_dir ), array( '..', '.' ) );

        foreach ( $block_folders as $folder_name ) {
            $block_path = $blocks_dir . $folder_name;

            if ( is_dir( $block_path ) && file_exists( $block_path . '/block.json' ) ) {
                register_block_type_from_metadata( $block_path );
                error_log('Salnama: Registered block - ' . $folder_name);
            }
        }
    }
}