<?php
/**
 * Helper functions for form builder - نسخه حرفه‌ای و کامل
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// فقط اگر از قبل تعریف نشده باشد
if ( ! function_exists( 'salnama_form_init' ) ) {

/**
 * Initialize form system
 */
function salnama_form_init() {
    // Load required classes
    $database = Salnama_Form_Database::get_instance();
    $processor = Salnama_Form_Processor::get_instance();
    $user_history = Salnama_Form_User_History::get_instance();
    
    // Add admin scripts and styles
    add_action( 'admin_enqueue_scripts', 'salnama_admin_scripts' );
    
    // Add dashboard widget
    add_action( 'wp_dashboard_setup', 'salnama_add_dashboard_widget' );
    
    // Add export functionality
    add_action( 'admin_init', 'salnama_export_submissions' );
    
    // Add bulk actions
    add_action( 'admin_init', 'salnama_handle_bulk_actions' );
    
    error_log('Salnama: Professional form system initialized');
}

/**
 * Add admin scripts and styles
 */
/**
 * Add admin scripts and styles - نسخه اصلاح شده
 */
function salnama_admin_scripts( $hook ) {
    if ( strpos( $hook, 'salnama-submissions' ) !== false ) {
        // CSS files
        wp_enqueue_style( 'salnama-admin', SALNAMA_PLUGIN_URL . 'src/blocks/form-builder/includes/admin-style.css', array(), SALNAMA_VERSION );
        
        // از CDN استفاده نکنید، از کتابخانه‌های محلی استفاده کنید
        wp_enqueue_style( 'datatables-css', SALNAMA_PLUGIN_URL . 'assets/css/dataTables.min.css', array(), '1.13.6' );
        
        // JS files - اول jQuery سپس بقیه
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'datatables-js', SALNAMA_PLUGIN_URL . 'assets/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.6', true );
        
        wp_enqueue_script( 'salnama-admin', SALNAMA_PLUGIN_URL . 'src/blocks/form-builder/includes/admin-script.js', array( 'jquery', 'datatables-js' ), SALNAMA_VERSION, true );
        
        // Localize script for AJAX
        wp_localize_script( 'salnama-admin', 'salnama_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'salnama_admin_nonce' ),
            'texts' => array(
                'delete_confirm' => 'آیا از حذف این درخواست مطمئن هستید؟',
                'bulk_delete_confirm' => 'آیا از حذف درخواست‌های انتخاب شده مطمئن هستید؟',
                'no_selection' => 'لطفاً حداقل یک درخواست را انتخاب کنید.',
                'exporting' => 'در حال آماده‌سازی فایل اکسل...',
                'loading' => 'در حال بارگذاری...'
            )
        ) );
    }
}

/**
 * Add dashboard widget
 */
function salnama_add_dashboard_widget() {
    if ( current_user_can( 'manage_options' ) ) {
        wp_add_dashboard_widget(
            'salnama_dashboard_widget',
            'آمار درخواست‌های مشاوره',
            'salnama_render_dashboard_widget'
        );
    }
}

/**
 * Render dashboard widget
 */
function salnama_render_dashboard_widget() {
    $database = Salnama_Form_Database::get_instance();
    
    $stats = array(
        'total' => $database->count_submissions(),
        'today' => $database->count_submissions( array( 
            'date_from' => date( 'Y-m-d 00:00:00' ) 
        ) ),
        'new' => $database->count_submissions( array( 
            'status' => 'new' 
        ) ),
        'week' => $database->count_submissions( array( 
            'date_from' => date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) ) 
        ) )
    );
    
    ?>
    <div class="salnama-dashboard-widget">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format_i18n( $stats['total'] ); ?></div>
                <div class="stat-label">کل درخواست‌ها</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format_i18n( $stats['today'] ); ?></div>
                <div class="stat-label">امروز</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" style="color: #e74c3c;"><?php echo number_format_i18n( $stats['new'] ); ?></div>
                <div class="stat-label">جدید</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format_i18n( $stats['week'] ); ?></div>
                <div class="stat-label">هفته گذشته</div>
            </div>
        </div>
        
        <div class="recent-submissions">
            <h4>آخرین درخواست‌ها</h4>
            <?php
            $recent = $database->get_submissions( array( 'limit' => 5 ) );
            if ( empty( $recent ) ) : ?>
                <p>هیچ درخواستی وجود ندارد.</p>
            <?php else : ?>
                <ul>
                    <?php foreach ( $recent as $submission ) : 
                        $form_data = maybe_unserialize( $submission->form_data );
                        $time_diff = human_time_diff( strtotime( $submission->submission_date ), current_time( 'timestamp' ) );
                    ?>
                        <li>
                            <strong><?php echo esc_html( $form_data['full_name'] ?? 'نامشخص' ); ?></strong>
                            <span class="submission-meta">
                                <?php echo esc_html( $form_data['phone'] ?? '' ); ?> - 
                                <span class="time-ago"><?php echo $time_diff; ?> پیش</span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="widget-actions">
            <a href="<?php echo admin_url( 'admin.php?page=salnama-submissions' ); ?>" class="button button-primary">مشاهده همه درخواست‌ها</a>
            <a href="<?php echo admin_url( 'admin.php?page=salnama-submissions-stats' ); ?>" class="button">گزارشات کامل</a>
        </div>
    </div>
    
    <style>
    .salnama-dashboard-widget {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #007cba;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 12px;
        color: #6c757d;
    }
    
    .recent-submissions {
        margin: 15px 0;
    }
    
    .recent-submissions h4 {
        margin: 0 0 10px 0;
        font-size: 14px;
    }
    
    .recent-submissions ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .recent-submissions li {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .recent-submissions li:last-child {
        border-bottom: none;
    }
    
    .submission-meta {
        font-size: 11px;
        color: #6c757d;
    }
    
    .time-ago {
        color: #28a745;
    }
    
    .widget-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .widget-actions .button {
        flex: 1;
        text-align: center;
    }
    </style>
    <?php
}

/**
 * Admin submissions page - نسخه حرفه‌ای
 */
function salnama_submissions_page() {
    $database = Salnama_Form_Database::get_instance();
    
    // Handle actions
    if ( isset( $_GET['action'] ) && isset( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        salnama_handle_single_action( $id, $_GET['action'] );
    }
    
    // Handle bulk actions
    if ( isset( $_POST['bulk_action'] ) && isset( $_POST['submissions'] ) ) {
        salnama_handle_bulk_action( $_POST['bulk_action'], $_POST['submissions'] );
    }
    
    // Get filters
    $filters = salnama_get_filters();
    
    // Get submissions with pagination
    $paged = max( 1, isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 );
    $limit = 20;
    $offset = ( $paged - 1 ) * $limit;
    
    $args = array(
        'limit' => $limit,
        'offset' => $offset,
        'orderby' => 'submission_date',
        'order' => 'DESC'
    );
    
    // Apply filters
    if ( ! empty( $filters['status'] ) ) {
        $args['status'] = $filters['status'];
    }
    
    if ( ! empty( $filters['date_from'] ) ) {
        $args['date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    
    if ( ! empty( $filters['date_to'] ) ) {
        $args['date_to'] = $filters['date_to'] . ' 23:59:59';
    }
    
    if ( ! empty( $filters['search'] ) ) {
        $args['search'] = $filters['search'];
    }
    
    $submissions = $database->get_submissions( $args );
    $total_count = $database->count_submissions( $args );
    $total_pages = ceil( $total_count / $limit );
    
    // Status counts for filters
    $status_counts = array(
        'all' => $database->count_submissions(),
        'new' => $database->count_submissions( array( 'status' => 'new' ) ),
        'read' => $database->count_submissions( array( 'status' => 'read' ) ),
        'replied' => $database->count_submissions( array( 'status' => 'replied' ) ),
        'closed' => $database->count_submissions( array( 'status' => 'closed' ) )
    );
    
    ?>
    <div class="wrap salnama-admin-page">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-email-alt2"></span>
            درخواست‌های مشاوره
            <?php if ( $filters['status'] ) : ?>
                <span class="filter-badge">فیلتر: <?php echo salnama_get_status_label( $filters['status'] ); ?></span>
            <?php endif; ?>
        </h1>
        
        <a href="<?php echo admin_url( 'admin.php?page=salnama-submissions&action=export' . ( $filters['status'] ? '&status=' . $filters['status'] : '' ) ); ?>" class="page-title-action">
            <span class="dashicons dashicons-download"></span>
            خروجی اکسل
        </a>
        
        <a href="<?php echo admin_url( 'admin.php?page=salnama-submissions-stats' ); ?>" class="page-title-action">
            <span class="dashicons dashicons-chart-bar"></span>
            گزارشات پیشرفته
        </a>
        
        <hr class="wp-header-end">
        
        
        <!-- فیلترهای پیشرفته -->
        <div class="salnama-filters-card">
            <div class="filter-header">
                <h3><span class="dashicons dashicons-filter"></span> فیلترهای پیشرفته</h3>
                <button type="button" class="button button-secondary" id="toggle-filters">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    نمایش فیلترها
                </button>
            </div>
            
            <div class="filter-content" id="filter-content" style="display: none;">
                <form method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
                    <input type="hidden" name="page" value="salnama-submissions">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="service_filter">نوع خدمات:</label>
                            <select name="service_filter" id="service_filter" class="salnama-select2">
                                <option value="">همه خدمات</option>
                                <option value="طراحی سایت" <?php selected( $filters['service_filter'] ?? '', 'طراحی سایت' ); ?>>طراحی سایت</option>
                                <option value="سئو" <?php selected( $filters['service_filter'] ?? '', 'سئو' ); ?>>سئو</option>
                                <option value="دیجیتال مارکتینگ" <?php selected( $filters['service_filter'] ?? '', 'دیجیتال مارکتینگ' ); ?>>دیجیتال مارکتینگ</option>
                                <option value="مشاوره" <?php selected( $filters['service_filter'] ?? '', 'مشاوره' ); ?>>مشاوره</option>
                                <!-- سایر خدمات -->
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">از تاریخ:</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" class="regular-text">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">تا تاریخ:</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" class="regular-text">
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group" style="flex: 2;">
                            <label for="search">جستجو:</label>
                            <input type="text" name="search" id="search" value="<?php echo esc_attr( $filters['search'] ); ?>" 
                                   placeholder="جستجو در نام، ایمیل، تلفن، یادداشت‌ها..." class="regular-text" style="width: 300px;">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-search"></span>
                                اعمال فیلتر
                            </button>
                            <a href="<?php echo admin_url( 'admin.php?page=salnama-submissions' ); ?>" class="button">
                                <span class="dashicons dashicons-update"></span>
                                حذف فیلتر
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- کارت آمار سریع -->
        <div class="salnama-quick-stats">
            <div class="stat-card total">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $status_counts['all'] ); ?></div>
                    <div class="stat-label">کل درخواست‌ها</div>
                </div>
            </div>
            
            <div class="stat-card new">
                <div class="stat-icon">🆕</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $status_counts['new'] ); ?></div>
                    <div class="stat-label">جدید</div>
                </div>
            </div>
            
            <div class="stat-card replied">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $status_counts['replied'] ); ?></div>
                    <div class="stat-label">پاسخ داده شده</div>
                </div>
            </div>
            
            <div class="stat-card today">
                <div class="stat-icon">📅</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $database->count_submissions( array( 'date_from' => date( 'Y-m-d 00:00:00' ) ) ) ); ?></div>
                    <div class="stat-label">امروز</div>
                </div>
            </div>
        </div>
        
        <!-- فرم عملیات گروهی -->
        <form method="post" action="" id="bulk-action-form">
            <?php wp_nonce_field( 'salnama_bulk_action', 'salnama_bulk_nonce' ); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action" id="bulk-action-selector">
                        <option value="">عملیات گروهی</option>
                        <option value="mark_read">علامت‌گذاری به عنوان خوانده شده</option>
                        <option value="mark_replied">علامت‌گذاری به عنوان پاسخ داده شده</option>
                        <option value="mark_closed">علامت‌گذاری به عنوان بسته شده</option>
                        <option value="delete">حذف</option>
                    </select>
                    <button type="submit" class="button action" id="do-bulk-action">اعمال</button>
                </div>
                
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo number_format_i18n( $total_count ); ?> مورد
                    </span>
                    
                    <?php if ( $total_pages > 1 ) : ?>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links( array(
                                'base' => add_query_arg( 'paged', '%#%' ),
                                'format' => '',
                                'prev_text' => '&lsaquo; قبلی',
                                'next_text' => 'بعدی &rsaquo;',
                                'total' => $total_pages,
                                'current' => $paged
                            ) );
                            ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="alignleft actions">
                    <input type="text" id="search-table" placeholder="جستجو در جدول..." style="margin-top: 0;">
                </div>
                
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo number_format_i18n( $total_count ); ?> مورد
                    </span>
                    
                    <?php if ( $total_pages > 1 ) : ?>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links( array(
                                'base' => add_query_arg( 'paged', '%#%' ),
                                'format' => '',
                                'prev_text' => '&lsaquo; قبلی',
                                'next_text' => 'بعدی &rsaquo;',
                                'total' => $total_pages,
                                'current' => $paged
                            ) );
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="salnama-submissions-table">
                <thead>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </th>
                        <th scope="col" width="5%">ID</th>
                        <th scope="col" width="12%">کاربر</th>
                        <th scope="col" width="15%">تاریخ ثبت</th>
                        <th scope="col" width="10%">وضعیت</th>
                        <th scope="col" width="25%">اطلاعات تماس</th>
                        <th scope="col" width="18%">خدمات درخواستی</th>
                        <th scope="col" width="15%">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $submissions ) ) : ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px;">
                                <div class="no-submissions">
                                    <span class="dashicons dashicons-email-alt" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></span>
                                    <h3>هیچ درخواستی یافت نشد</h3>
                                    <p>هنوز هیچ درخواست مشاوره‌ای ثبت نشده است.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $submissions as $submission ) : 
                            $form_data = maybe_unserialize( $submission->form_data );
                            $user = $submission->user_id ? get_userdata( $submission->user_id ) : null;
                            $service_type = $form_data['service_type'] ?? 'مشاوره عمومی';
                            $company = $form_data['company'] ?? '';
                            $budget = $form_data['budget'] ?? '';
                            ?>
                            <tr class="submission-<?php echo $submission->id; ?> <?php echo $submission->status === 'new' ? 'unread-submission' : ''; ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="submissions[]" value="<?php echo $submission->id; ?>">
                                </th>
                                <td><?php echo $submission->id; ?></td>
                                <td>
                                    <?php if ( $user ) : ?>
                                        <div class="user-info">
                                            <a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" target="_blank" class="user-name">
                                                <?php echo esc_html( $user->display_name ); ?>
                                            </a>
                                            <small class="user-email"><?php echo esc_html( $user->user_email ); ?></small>
                                        </div>
                                    <?php else : ?>
                                        <span class="guest-user">👤 مهمان</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="submission-date">
                                        <div class="date"><?php echo date_i18n( 'Y/m/d', strtotime( $submission->submission_date ) ); ?></div>
                                        <div class="time"><?php echo date_i18n( 'H:i', strtotime( $submission->submission_date ) ); ?></div>
                                        <div class="time-ago"><?php echo human_time_diff( strtotime( $submission->submission_date ), current_time( 'timestamp' ) ); ?> پیش</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr( $submission->status ); ?>">
                                        <?php echo salnama_get_status_label( $submission->status ); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <div class="contact-name">
                                            <strong><?php echo esc_html( $form_data['full_name'] ?? 'نامشخص' ); ?></strong>
                                        </div>
                                        <?php if ( ! empty( $form_data['phone'] ) ) : ?>
                                            <div class="contact-phone">
                                                <span class="dashicons dashicons-phone"></span>
                                                <a href="tel:<?php echo esc_attr( $form_data['phone'] ); ?>">
                                                    <?php echo esc_html( $form_data['phone'] ); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $form_data['email'] ) ) : ?>
                                            <div class="contact-email">
                                                <span class="dashicons dashicons-email"></span>
                                                <a href="mailto:<?php echo esc_attr( $form_data['email'] ); ?>">
                                                    <?php echo esc_html( $form_data['email'] ); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $company ) ) : ?>
                                            <div class="contact-company">
                                                <span class="dashicons dashicons-building"></span>
                                                <?php echo esc_html( $company ); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="service-info">
                                        <?php
                                        // پیدا کردن نوع خدمات - بررسی تمام کلیدهای ممکن
                                        $service_type = '';
                                        $possible_service_fields = [
                                            'service_type', 'field_service_type', 'service', 'خدمات',
                                            'product_type', 'product', 'محصول', 'دسته‌بندی',
                                            'category', 'دسته', 'نوع_خدمت', 'نوع_محصول'
                                        ];
                                        
                                        foreach ($possible_service_fields as $field) {
                                            if (!empty($form_data[$field])) {
                                                $service_type = $form_data[$field];
                                                break;
                                            }
                                        }
                                        
                                        // اگر سرویس تایپ پیدا نشد، سعی کنیم از فیلدهای select پیدا کنیم
                                        if (empty($service_type)) {
                                            foreach ($form_data as $key => $value) {
                                                if (strpos($key, 'select') !== false || 
                                                    strpos($key, 'dropdown') !== false ||
                                                    strpos($key, 'خدمات') !== false ||
                                                    strpos($key, 'محصول') !== false) {
                                                    $service_type = $value;
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        
                                        <div class="service-type">
                                            <?php if (!empty($service_type)) : ?>
                                                <span class="service-badge">
                                                    <?php echo esc_html($service_type); ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="service-badge unknown">مشاوره عمومی</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php 
                                        // نمایش بودجه اگر وجود دارد
                                        $budget = $form_data['budget'] ?? $form_data['field_budget'] ?? '';
                                        if (!empty($budget)) : ?>
                                            <div class="service-budget">
                                                <small>💰 بودجه: <?php echo esc_html($budget); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        // نمایش زمان‌بندی اگر وجود دارد
                                        $timeframe = $form_data['timeframe'] ?? $form_data['field_timeframe'] ?? '';
                                        if (!empty($timeframe)) : ?>
                                            <div class="service-timeframe">
                                                <small>⏰ زمان: <?php echo esc_html($timeframe); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        // نمایش پیام اگر وجود دارد (خلاصه شده)
                                        $message = $form_data['message'] ?? $form_data['field_message'] ?? '';
                                        if (!empty($message)) : ?>
                                            <div class="service-message">
                                                <small>📝 <?php echo esc_html(wp_trim_words($message, 5, '...')); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'view', 'id' => $submission->id ) ) ); ?>" 
                                           class="button button-small button-primary view-action">
                                            <span class="dashicons dashicons-visibility"></span>
                                            مشاهده
                                        </a>
                                        
                                        <div class="action-dropdown">
                                            <button type="button" class="button button-small more-actions">
                                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                            </button>
                                            <div class="dropdown-content">
                                                <?php if ( $submission->status === 'new' ) : ?>
                                                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'mark_read', 'id' => $submission->id ) ), 'mark_read_' . $submission->id ) ); ?>" class="mark-read">
                                                        <span class="dashicons dashicons-yes"></span>
                                                        علامت خوانده شده
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $submission->id ) ) ); ?>" class="edit-action">
                                                    <span class="dashicons dashicons-edit"></span>
                                                    ویرایش
                                                </a>
                                                
                                                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $submission->id ) ), 'delete_submission_' . $submission->id ) ); ?>" 
                                                   class="delete-action" 
                                                   onclick="return confirm('آیا از حذف این درخواست مطمئن هستید؟');">
                                                    <span class="dashicons dashicons-trash"></span>
                                                    حذف
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action2" id="bulk-action-selector-2">
                        <option value="">عملیات گروهی</option>
                        <option value="mark_read">علامت‌گذاری به عنوان خوانده شده</option>
                        <option value="mark_replied">علامت‌گذاری به عنوان پاسخ داده شده</option>
                        <option value="mark_closed">علامت‌گذاری به عنوان بسته شده</option>
                        <option value="delete">حذف</option>
                    </select>
                    <button type="submit" class="button action" id="do-bulk-action-2">اعمال</button>
                </div>
                
                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php echo number_format_i18n( $total_count ); ?> مورد
                        </span>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links( array(
                                'base' => add_query_arg( 'paged', '%#%' ),
                                'format' => '',
                                'prev_text' => '&lsaquo; قبلی',
                                'next_text' => 'بعدی &rsaquo;',
                                'total' => $total_pages,
                                'current' => $paged
                            ) );
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // فعال‌سازی DataTable
        // $('#salnama-submissions-table').DataTable({
        //     "paging": false,
        //     "searching": false,
        //     "info": false,
        //     "ordering": true,
        //     "order": [[3, 'desc']],
        //     "language": {
        //         "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/fa.json"
        //     }
        // });
        
        // فعال‌سازی Select2
        // $('.salnama-select2').select2({
        //     width: '200px',
        //     placeholder: "انتخاب کنید...",
        //     allowClear: true
        // });
        
            // نمایش/مخفی کردن فیلترها
            $('#toggle-filters').on('click', function() {
                $('#filter-content').slideToggle(300);
                var icon = $(this).find('.dashicons');
                if (icon.hasClass('dashicons-arrow-down-alt2')) {
                    icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                    $(this).html('<span class="dashicons dashicons-arrow-up-alt2"></span> مخفی کردن فیلترها');
                } else {
                    icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                    $(this).html('<span class="dashicons dashicons-arrow-down-alt2"></span> نمایش فیلترها');
                }
            });
        
        // انتخاب همه
            $('#cb-select-all').on('change', function() {
                $('input[name="submissions[]"]').prop('checked', this.checked);
            });
            
            // دراپ‌داون عملیات
            $('.more-actions').on('click', function(e) {
                e.stopPropagation();
                $('.dropdown-content').hide();
                $(this).siblings('.dropdown-content').toggle();
            });
            
            $(document).on('click', function() {
                $('.dropdown-content').hide();
            });
            
            // جستجوی ساده در جدول
            $('#search-table').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#salnama-submissions-table tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
    });
    </script>

    <style>
        .salnama-quick-stats {
            display: flex;
            justify-content: space-around;
        }

        .service-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #1976d2;
            margin-bottom: 5px;
        }

        .service-badge.unknown {
            background: #f5f5f5;
            color: #757575;
            border-color: #ddd;
        }

        .service-budget,
        .service-timeframe,
        .service-message {
            margin-top: 3px;
            line-height: 1.3;
        }

        .service-info small {
            color: #666;
            font-size: 11px;
        }

        /* استایل برای وضعیت‌های مختلف خدمات */
        .service-badge[data-service="طراحی سایت"] {
            background: #e8f5e8;
            color: #388e3c;
            border-color: #388e3c;
        }

        .service-badge[data-service="سئو"] {
            background: #fff3e0;
            color: #f57c00;
            border-color: #f57c00;
        }

        .service-badge[data-service="دیجیتال مارکتینگ"] {
            background: #f3e5f5;
            color: #7b1fa2;
            border-color: #7b1fa2;
        }
    </style>

    <?php
}

/**
 * Statistics page - نسخه حرفه‌ای با نمودار
 */
function salnama_submissions_stats_page() {
    $database = Salnama_Form_Database::get_instance();
    
    // Get statistics for different periods
    $today_stats = $database->get_statistics( '7days' );
    $month_stats = $database->get_statistics( '30days' );
    
    // Calculate basic stats
    $total_submissions = $database->count_submissions();
    $today_submissions = $database->count_submissions( array( 
        'date_from' => date( 'Y-m-d 00:00:00' )
    ) );
    $week_submissions = $database->count_submissions( array( 
        'date_from' => date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) )
    ) );
    $month_submissions = $database->count_submissions( array( 
        'date_from' => date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) )
    ) );
    
    // Status distribution
    $status_distribution = array(
        'new' => $database->count_submissions( array( 'status' => 'new' ) ) ?: 0,
        'read' => $database->count_submissions( array( 'status' => 'read' ) ) ?: 0,
        'replied' => $database->count_submissions( array( 'status' => 'replied' ) ) ?: 0,
        'closed' => $database->count_submissions( array( 'status' => 'closed' ) ) ?: 0
    );
    
    // Top services
    $all_submissions = $database->get_submissions( array( 'limit' => 1000 ) );
    $service_counts = array();
    foreach ( $all_submissions as $submission ) {
        $form_data = maybe_unserialize( $submission->form_data );
        $service = $form_data['service_type'] ?? 'مشاوره عمومی';
        if ( ! isset( $service_counts[$service] ) ) {
            $service_counts[$service] = 0;
        }
        $service_counts[$service]++;
    }
    arsort( $service_counts );
    $top_services = array_slice( $service_counts, 0, 5, true );
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-chart-bar"></span>
            آمار و گزارشات پیشرفته
        </h1>
        
        <a href="<?php echo admin_url( 'admin.php?page=salnama-submissions' ); ?>" class="page-title-action">
            <span class="dashicons dashicons-list-view"></span>
            مشاهده لیست
        </a>
        
        <hr class="wp-header-end">
        
        <!-- کارت‌های آمار کلی -->
        <div class="salnama-stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $total_submissions ?: 0 ); ?></div>
                    <div class="stat-label">کل درخواست‌ها</div>
                </div>
                <div class="stat-trend">📈 همه زمان‌ها</div>
            </div>
            
            <div class="stat-card today">
                <div class="stat-icon">📅</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $today_submissions ?: 0 ); ?></div>
                    <div class="stat-label">امروز</div>
                </div>
                <div class="stat-trend">🆕 جدید</div>
            </div>
            
            <div class="stat-card week">
                <div class="stat-icon">📆</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $week_submissions ?: 0 ); ?></div>
                    <div class="stat-label">هفته جاری</div>
                </div>
                <div class="stat-trend">🔥 فعال</div>
            </div>
            
            <div class="stat-card month">
                <div class="stat-icon">🗓️</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format_i18n( $month_submissions ?: 0 ); ?></div>
                    <div class="stat-label">ماه جاری</div>
                </div>
                <div class="stat-trend">📈 رشد</div>
            </div>
        </div>
        
        <!-- نمودارها و جداول -->
        <div class="salnama-charts-grid">
            <!-- توزیع وضعیت -->
            <div class="chart-card">
                <h3>توزیع وضعیت درخواست‌ها</h3>
                <div class="chart-container">
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
                <div class="chart-legend">
                    <?php foreach ( $status_distribution as $status => $count ) : ?>
                        <div class="legend-item">
                            <span class="legend-color status-<?php echo $status; ?>"></span>
                            <span class="legend-label"><?php echo salnama_get_status_label( $status ); ?></span>
                            <span class="legend-count">(<?php echo number_format_i18n( $count ); ?>)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- خدمات پرطرفدار -->
            <div class="chart-card">
                <h3>خدمات پرطرفدار</h3>
                <div class="services-list">
                    <?php if ( empty( $top_services ) ) : ?>
                        <p class="no-data">هیچ داده‌ای موجود نیست</p>
                    <?php else : ?>
                        <?php foreach ( $top_services as $service => $count ) : 
                            $percentage = $total_submissions > 0 ? round( ( $count / $total_submissions ) * 100, 1 ) : 0;
                        ?>
                            <div class="service-item">
                                <div class="service-name"><?php echo esc_html( $service ); ?></div>
                                <div class="service-bar">
                                    <div class="service-progress" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="service-count">
                                    <?php echo number_format_i18n( $count ); ?>
                                    <span class="service-percentage">(<?php echo $percentage; ?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- آمار هفتگی -->
        <div class="stats-table-card">
            <h3>آمار ۷ روز گذشته</h3>
            <?php 
            $week_stats = $database->get_submissions( array( 
                'date_from' => date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) ),
                'limit' => -1
            ) );
            
            // گروه‌بندی بر اساس تاریخ و وضعیت
            $grouped_stats = array();
            foreach ( $week_stats as $submission ) {
                $date = date( 'Y-m-d', strtotime( $submission->submission_date ) );
                if ( ! isset( $grouped_stats[$date] ) ) {
                    $grouped_stats[$date] = array(
                        'date' => $date,
                        'total' => 0,
                        'new' => 0,
                        'read' => 0,
                        'replied' => 0,
                        'closed' => 0
                    );
                }
                
                $grouped_stats[$date]['total']++;
                
                // شمارش بر اساس وضعیت
                switch ( $submission->status ) {
                    case 'new':
                        $grouped_stats[$date]['new']++;
                        break;
                    case 'read':
                        $grouped_stats[$date]['read']++;
                        break;
                    case 'replied':
                        $grouped_stats[$date]['replied']++;
                        break;
                    case 'closed':
                        $grouped_stats[$date]['closed']++;
                        break;
                }
            }
            
            // مرتب سازی بر اساس تاریخ (نزولی)
            krsort( $grouped_stats );
            
            if ( empty( $grouped_stats ) ) : ?>
                <p class="no-data">هیچ داده‌ای برای نمایش وجود ندارد.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>تاریخ</th>
                            <th>تعداد کل</th>
                            <th>جدید</th>
                            <th>خوانده شده</th>
                            <th>پاسخ داده شده</th>
                            <th>بسته شده</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $grouped_stats as $date_stat ) : ?>
                            <tr>
                                <td><?php echo esc_html( date_i18n( 'l j F Y', strtotime( $date_stat['date'] ) ) ); ?></td>
                                <td><strong><?php echo number_format_i18n( $date_stat['total'] ); ?></strong></td>
                                <td><?php echo number_format_i18n( $date_stat['new'] ); ?></td>
                                <td><?php echo number_format_i18n( $date_stat['read'] ); ?></td>
                                <td><?php echo number_format_i18n( $date_stat['replied'] ); ?></td>
                                <td><?php echo number_format_i18n( $date_stat['closed'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // داده‌های نمودار وضعیت
        const statusData = {
            labels: [<?php echo '"' . implode('","', array_map( 'salnama_get_status_label', array_keys( $status_distribution ) ) ) . '"'; ?>],
            datasets: [{
                data: [<?php echo implode(',', array_values( $status_distribution ) ); ?>],
                backgroundColor: [
                    '#e74c3c', // جدید - قرمز
                    '#f39c12', // خوانده شده - نارنجی
                    '#27ae60', // پاسخ داده شده - سبز
                    '#95a5a6'  // بسته شده - خاکستری
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };
        
        // ایجاد نمودار دایره‌ای
        const statusChart = new Chart(
            document.getElementById('statusChart'),
            {
                type: 'doughnut',
                data: statusData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            }
        );
    });
    </script>
    
    <style>
    .salnama-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .stat-card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 4px solid;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-card.total { border-left-color: #3498db; }
    .stat-card.today { border-left-color: #e74c3c; }
    .stat-card.week { border-left-color: #f39c12; }
    .stat-card.month { border-left-color: #27ae60; }
    
    .stat-icon {
        font-size: 2.5em;
        opacity: 0.8;
    }
    
    .stat-info {
        flex: 1;
    }
    
    .stat-number {
        font-size: 2em;
        font-weight: bold;
        color: #2c3e50;
        line-height: 1;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .stat-trend {
        font-size: 12px;
        color: #95a5a6;
    }
    
    .salnama-charts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 30px 0;
    }
    
    .chart-card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .chart-card h3 {
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f8f9fa;
        color: #2c3e50;
    }
    
    .chart-container {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .chart-legend {
        margin-top: 20px;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 13px;
    }
    
    .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-left: 10px;
    }
    
    .legend-color.status-new { background: #e74c3c; }
    .legend-color.status-read { background: #f39c12; }
    .legend-color.status-replied { background: #27ae60; }
    .legend-color.status-closed { background: #95a5a6; }
    
    .services-list {
        margin-top: 15px;
    }
    
    .service-item {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        padding: 8px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .service-name {
        flex: 1;
        font-size: 13px;
        color: #2c3e50;
    }
    
    .service-bar {
        flex: 2;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin: 0 15px;
    }
    
    .service-progress {
        height: 100%;
        background: linear-gradient(90deg, #3498db, #2980b9);
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    
    .service-count {
        font-size: 12px;
        color: #7f8c8d;
        min-width: 60px;
        text-align: left;
    }
    
    .service-percentage {
        color: #95a5a6;
    }
    
    .stats-table-card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    
    .stats-table-card h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #2c3e50;
    }
    
    .no-data {
        text-align: center;
        color: #95a5a6;
        font-style: italic;
        padding: 40px;
    }
    
    @media (max-width: 768px) {
        .salnama-charts-grid {
            grid-template-columns: 1fr;
        }
        
        .salnama-stats-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}

// Initialize the form system
add_action( 'init', 'salnama_form_init' );

} // endif function_exists

/**
 * Get filters from request
 */
function salnama_get_filters() {
    return array(
        'status' => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '',
        'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '',
        'date_to' => isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '',
        'search' => isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : ''
    );
}

/**
 * Handle single action
 */
function salnama_handle_single_action( $id, $action ) {
    $database = Salnama_Form_Database::get_instance();
    
    switch ( $action ) {
        case 'mark_read':
            if ( wp_verify_nonce( $_GET['_wpnonce'], 'mark_read_' . $id ) ) {
                $database->update_submission_status( $id, 'read' );
                salnama_admin_notice( 'وضعیت به خوانده شده تغییر کرد.', 'success' );
            }
            break;
            
        case 'delete':
            if ( wp_verify_nonce( $_GET['_wpnonce'], 'delete_submission_' . $id ) ) {
                $database->delete_submission( $id );
                salnama_admin_notice( 'درخواست با موفقیت حذف شد.', 'success' );
            }
            break;
    }
}

/**
 * Handle bulk actions
 */
function salnama_handle_bulk_action( $action, $submission_ids ) {
    if ( ! wp_verify_nonce( $_POST['salnama_bulk_nonce'], 'salnama_bulk_action' ) ) {
        salnama_admin_notice( 'خطای امنیتی.', 'error' );
        return;
    }
    
    if ( empty( $submission_ids ) ) {
        salnama_admin_notice( 'لطفاً حداقل یک درخواست را انتخاب کنید.', 'error' );
        return;
    }
    
    $database = Salnama_Form_Database::get_instance();
    $count = 0;
    
    foreach ( $submission_ids as $id ) {
        $id = intval( $id );
        
        switch ( $action ) {
            case 'mark_read':
                if ( $database->update_submission_status( $id, 'read' ) ) {
                    $count++;
                }
                break;
                
            case 'mark_replied':
                if ( $database->update_submission_status( $id, 'replied' ) ) {
                    $count++;
                }
                break;
                
            case 'mark_closed':
                if ( $database->update_submission_status( $id, 'closed' ) ) {
                    $count++;
                }
                break;
                
            case 'delete':
                if ( $database->delete_submission( $id ) ) {
                    $count++;
                }
                break;
        }
    }
    
    if ( $count > 0 ) {
        $messages = array(
            'mark_read' => 'علامت‌گذاری به عنوان خوانده شده',
            'mark_replied' => 'علامت‌گذاری به عنوان پاسخ داده شده',
            'mark_closed' => 'علامت‌گذاری به عنوان بسته شده',
            'delete' => 'حذف'
        );
        
        salnama_admin_notice( sprintf( 'عملیات %s روی %d درخواست انجام شد.', $messages[$action], $count ), 'success' );
    }
}

/**
 * Show admin notice
 */
function salnama_admin_notice( $message, $type = 'info' ) {
    add_action( 'admin_notices', function() use ( $message, $type ) {
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr( $type ),
            esc_html( $message )
        );
    } );
}

/**
 * Get status label
 */
function salnama_get_status_label( $status ) {
    $labels = array(
        'new' => 'جدید',
        'read' => 'خوانده شده',
        'replied' => 'پاسخ داده شده',
        'closed' => 'بسته شده'
    );
    
    return $labels[ $status ] ?? $status;
}

/**
 * Render submission details page
 */
function salnama_render_submission_details( $submission ) {
    $form_data = maybe_unserialize( $submission->form_data );
    $user = $submission->user_id ? get_userdata( $submission->user_id ) : null;
    
    // اگر form_data رشته است، سعی کنیم آن را تبدیل کنیم
    if ( is_string( $form_data ) && $form_data !== '' ) {
        $decoded_data = json_decode( $form_data, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            $form_data = $decoded_data;
        } else {
            $unserialized_data = maybe_unserialize( $form_data );
            if ( is_array( $unserialized_data ) ) {
                $form_data = $unserialized_data;
            }
        }
    }
    
    if ( ! is_array( $form_data ) ) {
        $form_data = array();
    }
    ?>
    
    <div class="wrap">
        <h1>مشاهده درخواست #<?php echo esc_html( $submission->id ); ?></h1>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div>
                <div class="card">
                    <h2>📋 اطلاعات اصلی</h2>
                    <table class="form-table">
                        <tr>
                            <th>تاریخ ثبت:</th>
                            <td><?php echo esc_html( 
                                date_i18n( 'j F Y - ساعت H:i', strtotime( $submission->submission_date ) ) 
                            ); ?></td>
                        </tr>
                        <tr>
                            <th>وضعیت:</th>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr( $submission->status ); ?>">
                                    <?php echo salnama_get_status_label( $submission->status ); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>کاربر:</th>
                            <td>
                                <?php if ( $user ) : ?>
                                    <a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" target="_blank">
                                        <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)
                                    </a>
                                <?php else : ?>
                                    <span class="guest-user">مهمان</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>آی‌پی:</th>
                            <td><?php echo esc_html( $submission->ip_address ); ?></td>
                        </tr>
                        <tr>
                            <th>مرورگر:</th>
                            <td><small><?php echo esc_html( $submission->user_agent ); ?></small></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <h2>👤 اطلاعات تماس</h2>
                    <table class="form-table">
                        <?php
                        // فیلدهای مهم تماس
                        $important_fields = array(
                            'full_name' => 'نام کامل',
                            'field_full_name' => 'نام کامل',
                            'email' => 'ایمیل',
                            'field_email' => 'ایمیل',
                            'phone' => 'تلفن',
                            'field_phone' => 'تلفن',
                            'company' => 'شرکت',
                            'field_company' => 'شرکت'
                        );
                        
                        foreach ( $important_fields as $field => $label ) {
                            if ( ! empty( $form_data[ $field ] ) ) {
                                echo '<tr>';
                                echo '<th width="30%">' . esc_html( $label ) . ':</th>';
                                echo '<td>' . esc_html( $form_data[ $field ] ) . '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>📝 تمام اطلاعات فرم</h2>
            <table class="form-table">
                <?php foreach ( $form_data as $key => $value ) : 
                    $label = salnama_get_field_label( $key );
                    ?>
                    <tr>
                        <th width="25%"><?php echo esc_html( $label ); ?>:</th>
                        <td>
                            <?php if ( is_array( $value ) ) : ?>
                                <pre><?php echo esc_html( print_r( $value, true ) ); ?></pre>
                            <?php else : ?>
                                <?php echo esc_html( $value ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>⚙ مدیریت درخواست</h2>
            <form method="post">
                <?php wp_nonce_field( 'change_status_' . $submission->id, '_wpnonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th>تغییر وضعیت:</th>
                        <td>
                            <select name="status">
                                <option value="new" <?php selected( $submission->status, 'new' ); ?>>جدید</option>
                                <option value="read" <?php selected( $submission->status, 'read' ); ?>>خوانده شده</option>
                                <option value="replied" <?php selected( $submission->status, 'replied' ); ?>>پاسخ داده شده</option>
                                <option value="closed" <?php selected( $submission->status, 'closed' ); ?>>بسته شده</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>یادداشت‌ها:</th>
                        <td>
                            <textarea name="notes" rows="5" style="width: 100%;" placeholder="یادداشت‌های داخلی..."><?php 
                                echo esc_textarea( $submission->notes ); 
                            ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="submit" class="button button-primary">ذخیره تغییرات</button>
                    <a href="<?php echo esc_url( remove_query_arg( array( 'action', 'id' ) ) ); ?>" class="button">
                        بازگشت به لیست
                    </a>
                </p>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Get field label - تابع کامل شده
 */
function salnama_get_field_label( $field_key ) {
    $labels = array(
        // فیلدهای اصلی
        'full_name' => 'نام کامل',
        'field_full_name' => 'نام کامل',
        'name' => 'نام',
        'نام' => 'نام',
        'username' => 'نام کاربری',
        
        // فیلدهای ایمیل
        'email' => 'ایمیل',
        'field_email' => 'ایمیل',
        'e-mail' => 'ایمیل',
        'ایمیل' => 'ایمیل',
        'user_email' => 'ایمیل کاربر',
        
        // فیلدهای تلفن
        'phone' => 'شماره تلفن',
        'field_phone' => 'شماره تلفن',
        'tel' => 'تلفن',
        'telephone' => 'تلفن',
        'mobile' => 'موبایل',
        'شماره تلفن' => 'شماره تلفن',
        'تلفن' => 'تلفن',
        'موبایل' => 'موبایل',
        'phone_number' => 'شماره تلفن',
        'phone_num' => 'شماره تلفن',
        'contact_phone' => 'تلفن تماس',
        'user_phone' => 'تلفن کاربر',
        
        // فیلدهای شرکت و سازمان
        'company' => 'شرکت',
        'field_company' => 'شرکت',
        'organization' => 'سازمان',
        'شرکت' => 'شرکت',
        'سازمان' => 'سازمان',
        
        // فیلدهای خدمات
        'service_type' => 'نوع خدمات',
        'field_service_type' => 'نوع خدمات',
        'service' => 'خدمات',
        'نوع خدمات' => 'نوع خدمات',
        
        // سایر فیلدها
        'message' => 'پیام',
        'field_message' => 'پیام',
        'budget' => 'بودجه',
        'field_budget' => 'بودجه',
        'timeframe' => 'زمانبندی',
        'field_timeframe' => 'زمانبندی',
        'پیام' => 'پیام',
        'بودجه' => 'بودجه',
        'زمانبندی' => 'زمانبندی'
    );
    
    return $labels[ $field_key ] ?? $field_key;
}