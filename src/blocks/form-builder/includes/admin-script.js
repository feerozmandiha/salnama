jQuery(document).ready(function($) {
    // به‌روزرسانی وضعیت با AJAX
    $('.status-update').on('change', function() {
        var submissionId = $(this).data('id');
        var newStatus = $(this).val();
        
        $.ajax({
            url: salnama_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'salnama_update_status',
                submission_id: submissionId,
                status: newStatus,
                nonce: salnama_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // به‌روزرسانی ظاهری وضعیت
                    location.reload();
                }
            }
        });
    });
    
    
    // جستجو در درخواست‌ها
    $('.salnama-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.wp-list-table tbody tr').each(function() {
            var rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // تأیید حذف
    $('.salnama-delete').on('click', function(e) {
        if (!confirm('آیا از حذف این درخواست مطمئن هستید؟ این عمل غیرقابل بازگشت است.')) {
            e.preventDefault();
        }
    });

    // بررسی وجود DataTable
    if (typeof $.fn.DataTable !== 'undefined') {
        // فعال‌سازی DataTable با تنظیمات فارسی
        $('#salnama-submissions-table').DataTable({
            "paging": false,
            "searching": true,
            "info": false,
            "ordering": true,
            "order": [[3, 'desc']],
            "language": {
                "emptyTable": "هیچ داده‌ای در جدول وجود ندارد",
                "info": "نمایش _START_ تا _END_ از _TOTAL_ رکورد",
                "infoEmpty": "نمایش 0 تا 0 از 0 رکورد",
                "infoFiltered": "(فیلتر شده از _MAX_ رکورد)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "نمایش _MENU_ رکورد",
                "loadingRecords": "در حال بارگذاری...",
                "processing": "در حال پردازش...",
                "search": "جستجو:",
                "zeroRecords": "رکوردی با این مشخصات پیدا نشد",
                "paginate": {
                    "first": "اولین",
                    "last": "آخرین",
                    "next": "بعدی",
                    "previous": "قبلی"
                },
                "aria": {
                    "sortAscending": ": فعال سازی برای مرتب سازی ستون به صورت صعودی",
                    "sortDescending": ": فعال سازی برای مرتب سازی ستون به صورت نزولی"
                }
            }
        });
    } else {
        console.log('DataTables not loaded, using basic table functionality');
        
        // جستجوی ساده برای جدول
        $('#search-table').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#salnama-submissions-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    }
    
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
    
    // تأیید عملیات گروهی
    $('#do-bulk-action, #do-bulk-action-2').on('click', function(e) {
        var selectedAction = $(this).closest('.bulkactions').find('select').val();
        var checkedItems = $('input[name="submissions[]"]:checked').length;
        
        if (selectedAction === '') {
            e.preventDefault();
            alert('لطفاً یک عمل گروهی انتخاب کنید.');
            return false;
        }
        
        if (checkedItems === 0) {
            e.preventDefault();
            alert('لطفاً حداقل یک درخواست را انتخاب کنید.');
            return false;
        }
        
        if (selectedAction === 'delete') {
            if (!confirm('آیا از حذف ' + checkedItems + ' درخواست انتخاب شده مطمئن هستید؟')) {
                e.preventDefault();
                return false;
            }
        }
    });

});