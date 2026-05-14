import 'typeahead.js';
import Bloodhound from "bloodhound-js";
import 'bootstrap-tagsinput';

import 'bootstrap-sass/assets/javascripts/bootstrap/modal.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/collapse.js';

$(function() {
    initAdminSidebarState();

    // Build the slug for object entiry from the name
    initBuildSluggable();

    // Init CkEditor and CKfinder
    initCkeditor();

    // Update object when change the enable button toggle
    initEnableToggleButton();

    initMakePrimaryCategory();

    initRevisionAutosave();

    initBulkActions();

    function initAdminSidebarState() {
        var storageKey = 'minhduy_admin_sidebar_open';

        function setSessionCookie(value) {
            document.cookie = storageKey + '=' + value + '; path=/; SameSite=Lax';
        }

        function persistSidebarState() {
            var isCollapsed = $('body').hasClass('open');
            var value = isCollapsed ? '1' : '0';

            try {
                sessionStorage.setItem(storageKey, value);
            } catch (error) {}

            setSessionCookie(value);
        }

        try {
            if (sessionStorage.getItem(storageKey) === '1') {
                $('body').addClass('open');
                setSessionCookie('1');
            }
        } catch (error) {}

        var menuToggle = document.getElementById('menuToggle');

        if (!menuToggle) {
            return;
        }

        menuToggle.addEventListener('click', function() {
            setTimeout(persistSidebarState, 0);
        });
    }

    /**
     * Create sluggable from name
     * 
     **/
    function initBuildSluggable() {
        $("body.new :input.sluggable").keyup(function () {
            $(":input.url").val(remove_vietnamese_accents($(this).val()));
        });

        $(":input.url").click(function () {
            if ($(this).attr('readonly')) {
                $(":input.url").removeAttr('readonly');
            }
        });

        $(":input.url").focusout(function () {
            if (!$(this).attr('readonly')) {
                $(":input.url").attr('readonly', 'readonly');
            }
        });
    }

    /**
     * @var string
     * Remove vietnamese from string
     **/
    function remove_vietnamese_accents(str) {
        var accents_arr = new Array("à", "á", "ạ", "ả", "ã", "â", "ầ", "ấ", "ậ", "ẩ", "ẫ", "ă", "ằ", "ắ", "ặ", "ẳ", "ẵ", "è", "é", "ẹ", "ẻ", "ẽ", "ê", "ề", "ế", "ệ", "ể", "ễ", "ì", "í", "ị", "ỉ", "ĩ", "ò", "ó", "ọ", "ỏ", "õ", "ô", "ồ", "ố", "ộ", "ổ", "ỗ", "ơ", "ờ", "ớ", "ợ", "ở", "ỡ", "ù", "ú", "ụ", "ủ", "ũ", "ư", "ừ", "ứ", "ự", "ử", "ữ", "ỳ", "ý", "ỵ", "ỷ", "ỹ", "đ", "À", "Á", "Ạ", "Ả", "Ã", "Â", "Ầ", "Ấ", "Ậ", "Ẩ", "Ẫ", "Ă", "Ằ", "Ắ", "Ặ", "Ẳ", "Ẵ", "È", "É", "Ẹ", "Ẻ", "Ẽ", "Ê", "Ề", "Ế", "Ệ", "Ể", "Ễ", "Ì", "Í", "Ị", "Ỉ", "Ĩ", "Ò", "Ó", "Ọ", "Ỏ", "Õ", "Ô", "Ồ", "Ố", "Ộ", "Ổ", "Ỗ", "Ơ", "Ờ", "Ớ", "Ợ", "Ở", "Ỡ", "Ù", "Ú", "Ụ", "Ủ", "Ũ", "Ư", "Ừ", "Ứ", "Ự", "Ử", "Ữ", "Ỳ", "Ý", "Ỵ", "Ỷ", "Ỹ", "Đ", " ", "\"", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", ".", ",", ";", "'", "[", "]", "{", "}", ":", "“", "”", "--", '.', '>', '<', '--', '---', '‘', '’', '/', '?', '~', "|");

        var no_accents_arr = new Array("a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "i", "i", "i", "i", "i", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "y", "y", "y", "y", "y", "d", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "i", "i", "i", "i", "i", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "y", "y", "y", "y", "y", "d", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", '-', '-', '-', '-', '---', '-', '-', '-', '', '', '');

        return str_replace(accents_arr, no_accents_arr, str).toLowerCase();
    }

    /**
     * @var string
     * Replace the string
     **/
    function str_replace(search, replace, str) {
        var ra = replace instanceof Array,
            sa = str instanceof Array,
            l = (search = [].concat(search)).length,
            replace = [].concat(replace),
            i = (str = [].concat(str)).length,
            j;

        while (j = 0, i--) {
            while (str[i] = str[i].split(search[j]).join(ra ? replace[j] || "" : replace[0]), ++j < l) {}
        }return sa ? str : str[0];
    }

    /**
     * Init Ckeditor and Ckfinder.
     **/
    function initCkeditor() {
        $('.txt-ckeditor').each(function (e, elements) {
            var height = $(this).data("height") ? $(this).data("height") : "500";
            CKEDITOR.replace(this.id, {
                protectedSource: [
                    /<script[\s\S]*?<\/script>/gi,
                    /<style[\s\S]*?<\/style>/gi
                ],
                height: height + 'px',
                filebrowserBrowseUrl: '/assets/cksourceckfinder/ckfinder/ckfinder.html',
                filebrowserUploadUrl: '/assets/cksourceckfinder/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
                filebrowserWindowWidth: '1000',
                filebrowserWindowHeight: '700'
            });
        });
    }

    /**
     * Update object when change the enable button toggle
     **/
    function initEnableToggleButton() {
        $('.switch-input').on('change', function() {
            let isChecked = $(this).prop('checked');
            isChecked = isChecked ? 1 : 0;
            let id = $(this).data('id');
            let url = $(this).data('action');
            
            $.ajax({
                type: "POST",
                url: url,
                data: 'newsId=' + id + '&enable=' + isChecked,
                success: function(data) {
                    var response = JSON.parse(data);
                }
            });
        });
    }

    function initMakePrimaryCategory() {
        var categoryPrimaryId = $('#news_categoryPrimary').val();

        $("#news_category .checkbox").each(function() {
            var categoryId = $(this).find('input[type="checkbox"]').val();

            if ($(this).find('input[type="checkbox"]').is(':checked') && categoryPrimaryId != categoryId) {
                $(this).append('<label class="label-primary"> <input type="radio" name="categoryPrimary"></input> Chọn làm danh mục chính</label>');
            }
        });

        $('#news_category .checkbox input[type="checkbox"]').change(function() {
            if (!this.checked) {
                $(this).closest('.checkbox').find('label.label-primary').remove();
            } else {
                $(this).parent().parent('.checkbox').append('<label class="label-primary"> <input type="radio" name="categoryPrimary"></input> Chọn làm danh mục chính</label>');
            }
        });

        $(document).on('change', '#news_category .checkbox .label-primary input[type="radio"]', function(e) {
            var categoryId = $(this).closest('.checkbox').find('input[type="checkbox"]').val();

            if (categoryId > 0 ) {
                $('#news_categoryPrimary').val(categoryId);
            }
        });
    }

    function initRevisionAutosave() {
        var $panel = $('.revision-panel');

        if (!$panel.length) {
            return;
        }

        var autosaveUrl = $panel.data('autosave-url');
        var autosaveToken = $panel.data('autosave-token');
        var $status = $('[data-autosave-status]');
        var lastPayload = JSON.stringify(collectRevisionPayload());
        var isSaving = false;

        function getEditorData(fieldId) {
            if (window.CKEDITOR && CKEDITOR.instances[fieldId]) {
                return CKEDITOR.instances[fieldId].getData();
            }

            return $('#' + fieldId).val() || '';
        }

        function collectRevisionPayload() {
            if (window.CKEDITOR) {
                $.each(CKEDITOR.instances, function(id, editor) {
                    editor.updateElement();
                });
            }

            return {
                _token: autosaveToken,
                title: $('#news_title').val() || '',
                url: $('#news_url').val() || '',
                description: $('#news_description').val() || '',
                contents: getEditorData('news_contents'),
                pageTitle: $('#news_pageTitle').val() || '',
                pageDescription: $('#news_pageDescription').val() || '',
                pageKeyword: $('#news_pageKeyword').val() || '',
                qa: $('#news_qa').val() || '',
                template: $('#news_template').val() || ''
            };
        }

        function autosave() {
            if (isSaving) {
                return;
            }

            var payload = collectRevisionPayload();
            var encodedPayload = JSON.stringify(payload);

            if (encodedPayload === lastPayload) {
                return;
            }

            isSaving = true;
            $status.text('Đang autosave...');

            $.ajax({
                type: 'POST',
                url: autosaveUrl,
                data: payload,
                success: function(response) {
                    lastPayload = encodedPayload;

                    if (response.savedAt) {
                        $status.text(response.message + ': ' + response.savedAt);
                    } else {
                        $status.text(response.message);
                    }
                },
                error: function() {
                    $status.text('Autosave lỗi, nội dung trên form vẫn chưa mất.');
                },
                complete: function() {
                    isSaving = false;
                }
            });
        }

        setInterval(autosave, 30000);
    }

    function initBulkActions() {
        $('[data-bulk-check-all]').on('change', function() {
            var checked = $(this).prop('checked');
            $(this).closest('table').find('[data-bulk-check]').prop('checked', checked);
        });

        $('[data-bulk-check]').on('change', function() {
            var $table = $(this).closest('table');
            var total = $table.find('[data-bulk-check]').length;
            var checked = $table.find('[data-bulk-check]:checked').length;

            $table.find('[data-bulk-check-all]').prop('checked', total > 0 && total === checked);
        });

        $('[data-bulk-form]').on('submit', function(event) {
            var $form = $(this);
            var formId = $form.attr('id');
            var action = $form.find('[name="bulk_action"]').val();
            var checkedCount = formId ? $('[data-bulk-check][form="' + formId + '"]:checked').length : $form.find('[data-bulk-check]:checked').length;

            if (!action || checkedCount === 0) {
                event.preventDefault();
                alert('Vui lòng chọn dữ liệu và thao tác.');
                return;
            }

            if (action === 'delete' && !confirm('Bạn chắc chắn muốn xóa các mục đã chọn?')) {
                event.preventDefault();
            }
        });
    }

    // Bootstrap-tagsinput initialization
    // http://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/
    var $input = $('input[data-toggle="tagsinput"]');
    if ($input.length) {
        var source = new Bloodhound({
            local: $input.data('tags'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            datumTokenizer: Bloodhound.tokenizers.whitespace
        });
        source.initialize();

        $input.tagsinput({
            trimValue: true,
            focusClass: 'focus',
            typeaheadjs: {
                name: 'tags',
                source: source.ttAdapter()
            }
        });
    }
});

// Handling the modal confirmation message.
$(document).on('submit', 'form[data-confirmation]', function (event) {
    var $form = $(this),
        $confirm = $($form.find('button').data('target'));

    if ($confirm.data('result') !== 'yes') {
        //cancel submit event
        event.preventDefault();

        $confirm
            .off('click', '#btnYes')
            .on('click', '#btnYes', function () {
                $confirm.data('result', 'yes');
                $form.find('input[type="submit"]').attr('disabled', 'disabled');
                $form.submit();
            })
            .modal('show');
    }
});
