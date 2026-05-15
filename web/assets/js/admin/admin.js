import 'typeahead.js';
import Bloodhound from "bloodhound-js";
import 'bootstrap-tagsinput';
import Chart from 'chart.js';

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

    initSeoRealtimeChecklist();

    initDashboardCharts();

    initMediaUploadDropzone();

    initMediaEditModal();

    initMediaPicker();

    initMediaAlbumFields();

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
        $('[data-slug-helper]').each(function() {
            var $helper = $(this);
            var prefix = $helper.data('form-prefix');
            var originalSlug = normalizeSlug(String($helper.data('original-slug') || ''));
            var currentId = $helper.data('current-id') || '';
            var checkUrl = $helper.data('check-url');
            var canonicalPattern = String($helper.data('canonical-pattern') || '/__slug__.html');
            var $form = $helper.closest('form');
            var $source = $('#' + prefix + '_title');
            var $slug = $('#' + prefix + '_url');
            var isNewForm = !currentId;
            var userTouchedSlug = !isNewForm && normalizeSlug($slug.val()) !== '';
            var checkTimer = null;
            var lastRequest = null;
            var $status;

            if (!$source.length || !$slug.length) {
                return;
            }

            $slug.removeAttr('readonly');
            $slug.attr('autocomplete', 'off');
            $slug.attr('data-original-slug', originalSlug);

            $status = $('<div>')
                .addClass('slug-check-status text-muted')
                .attr('data-slug-status', '')
                .insertAfter($slug);

            function renderStatus(type, message) {
                $status
                    .removeClass('text-muted text-success text-warning text-danger')
                    .addClass('text-' + type)
                    .html(message);
            }

            function renderCanonicalWarning(slug) {
                var canonicalUrl = canonicalPattern.replace('__slug__', slug || 'duong-dan-bai-viet');

                if (!originalSlug || slug === originalSlug) {
                    return '';
                }

                return '<div class="slug-canonical-warning">' +
                    '<i class="fa fa-random" aria-hidden="true"></i> URL đã đổi. Canonical mới sẽ là <strong>' + escapeHtml(canonicalUrl) + '</strong>, hệ thống sẽ tạo redirect 301 từ URL cũ khi lưu.' +
                    '</div>';
            }

            function checkDuplicate() {
                var slug = normalizeSlug($slug.val());

                if ($slug.val() !== slug) {
                    $slug.val(slug);
                }

                if (!slug) {
                    renderStatus('warning', 'URL không được để trống.');
                    return;
                }

                renderStatus('muted', 'Đang kiểm tra URL...' + renderCanonicalWarning(slug));

                if (lastRequest) {
                    lastRequest.abort();
                }

                lastRequest = $.ajax({
                    type: 'GET',
                    url: checkUrl,
                    data: {
                        slug: slug,
                        id: currentId
                    },
                    success: function(response) {
                        var canonicalWarning = renderCanonicalWarning(response.slug || slug);

                        if (response.duplicate) {
                            renderStatus(
                                'danger',
                                'URL này đã được dùng bởi <a href="' + response.duplicate.editUrl + '" target="_blank">' + escapeHtml(response.duplicate.title) + '</a>.' + canonicalWarning
                            );
                            return;
                        }

                        if (response.redirectConflict) {
                            renderStatus(
                                'warning',
                                'URL này đang là nguồn redirect sang <strong>' + escapeHtml(response.redirectConflict.targetPath) + '</strong>.' + canonicalWarning
                            );
                            return;
                        }

                        renderStatus('success', 'URL có thể dùng. Canonical: <strong>' + escapeHtml(response.canonicalUrl) + '</strong>' + canonicalWarning);
                    },
                    error: function(xhr) {
                        if (xhr.statusText === 'abort') {
                            return;
                        }

                        renderStatus('warning', 'Chưa kiểm tra được URL, server vẫn sẽ kiểm tra khi lưu.' + renderCanonicalWarning(slug));
                    }
                });
            }

            function scheduleCheck() {
                window.clearTimeout(checkTimer);
                checkTimer = window.setTimeout(checkDuplicate, 250);
            }

            $source.on('keyup input change', function() {
                if (userTouchedSlug && $slug.val()) {
                    return;
                }

                $slug.val(normalizeSlug($source.val())).trigger('input');
                scheduleCheck();
            });

            $slug.on('keyup input change', function() {
                userTouchedSlug = true;
                scheduleCheck();
            });

            $slug.on('blur', function() {
                $slug.val(normalizeSlug($slug.val()));
                checkDuplicate();
            });

            $form.on('submit', function() {
                $slug.val(normalizeSlug($slug.val()));
            });

            if (isNewForm && !$slug.val()) {
                $slug.val(normalizeSlug($source.val()));
            }

            checkDuplicate();
        });
    }

    function normalizeSlug(value) {
        return remove_vietnamese_accents(value || '')
            .replace(/[^a-z0-9-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
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

        setInterval(autosave, 300000);
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

    function initSeoRealtimeChecklist() {
        $('[data-seo-checklist]').each(function() {
            var $checker = $(this);
            var prefix = $checker.data('form-prefix');
            var previewUrlPattern = String($checker.data('preview-url-pattern') || '/__slug__');
            var hasExistingImage = String($checker.data('has-image')) === '1';
            var checkCategory = String($checker.data('check-category')) === '1';
            var fieldIds = {
                title: prefix + '_title',
                url: prefix + '_url',
                description: prefix + '_description',
                contents: prefix + '_contents',
                imageFile: prefix + '_imageFile_file',
                pageTitle: prefix + '_pageTitle',
                pageDescription: prefix + '_pageDescription',
                pageKeyword: prefix + '_pageKeyword',
                category: prefix + '_category'
            };
            var debounceTimer = null;

            function getFieldValue(fieldId) {
                if (window.CKEDITOR && CKEDITOR.instances[fieldId]) {
                    return CKEDITOR.instances[fieldId].getData();
                }

                return $('#' + fieldId).val() || '';
            }

            function cleanText(value) {
                var text = $('<div>').html(value || '').text();

                return $.trim(text.replace(/\s+/g, ' '));
            }

            function countWords(text) {
                text = cleanText(text);

                if (!text) {
                    return 0;
                }

                return text.split(/\s+/).length;
            }

            function getPrimaryKeyword(value) {
                var parts = String(value || '').split(/[,;]+/);

                return $.trim(parts[0] || '');
            }

            function containsText(haystack, needle) {
                haystack = String(haystack || '').toLowerCase();
                needle = String(needle || '').toLowerCase();

                return needle !== '' && haystack.indexOf(needle) !== -1;
            }

            function getStatus(score) {
                if (score >= 90) {
                    return 'Tốt';
                }

                if (score >= 80) {
                    return 'Khá';
                }

                if (score >= 60) {
                    return 'Cần cải thiện';
                }

                return 'Yếu';
            }

            function getScoreClass(score) {
                if (score >= 90) {
                    return 'success';
                }

                if (score >= 80) {
                    return 'primary';
                }

                if (score >= 60) {
                    return 'warning';
                }

                return 'danger';
            }

            function appendChecklistItem($list, type, text) {
                var icon = type === 'danger' ? 'fa-times-circle' : 'fa-exclamation-circle';

                $('<li>')
                    .addClass('seo-score-item seo-score-item-' + type)
                    .append($('<i>').addClass('fa ' + icon).attr('aria-hidden', 'true'))
                    .append(document.createTextNode(text))
                    .appendTo($list);
            }

            function renderList($list, items, emptyText, type) {
                $list.empty();

                if (!items.length) {
                    $('<li>').addClass('text-muted').text(emptyText).appendTo($list);
                    return;
                }

                $.each(items, function(index, item) {
                    appendChecklistItem($list, type, item);
                });
            }

            function hasImage() {
                var input = document.getElementById(fieldIds.imageFile);

                if (input && input.files && input.files.length > 0) {
                    return true;
                }

                return hasExistingImage;
            }

            function hasCategory() {
                if (!checkCategory) {
                    return true;
                }

                return $('#' + fieldIds.category + ' input[type="checkbox"]:checked').length > 0;
            }

            function analyze() {
                var score = 100;
                var errors = [];
                var warnings = [];
                var title = $.trim(getFieldValue(fieldIds.title));
                var seoTitle = $.trim(getFieldValue(fieldIds.pageTitle));
                var effectiveTitle = seoTitle || title;
                var summaryDescription = cleanText(getFieldValue(fieldIds.description));
                var metaDescription = cleanText(getFieldValue(fieldIds.pageDescription));
                var effectiveDescription = metaDescription || summaryDescription;
                var rawContent = getFieldValue(fieldIds.contents);
                var wordCount = countWords(rawContent);
                var primaryKeyword = getPrimaryKeyword(getFieldValue(fieldIds.pageKeyword));
                var titleLength = effectiveTitle.length;
                var descriptionLength = effectiveDescription.length;

                if (!effectiveTitle) {
                    score -= 25;
                    errors.push('Thiếu tiêu đề SEO');
                } else if (titleLength < 30) {
                    score -= 8;
                    errors.push('Tiêu đề SEO ngắn');
                } else if (titleLength > 70) {
                    score -= 8;
                    errors.push('Tiêu đề SEO dài');
                }

                if (!effectiveDescription) {
                    score -= 25;
                    errors.push('Thiếu meta description');
                } else if (descriptionLength < 120) {
                    score -= 8;
                    errors.push('Meta description ngắn');
                } else if (descriptionLength > 170) {
                    score -= 8;
                    errors.push('Meta description dài');
                }

                if (!hasImage()) {
                    score -= 10;
                    errors.push('Thiếu ảnh đại diện');
                }

                if (wordCount < 300) {
                    score -= 15;
                    errors.push('Nội dung mỏng');
                } else if (wordCount < 600) {
                    score -= 6;
                    warnings.push('Có thể mở rộng nội dung');
                }

                if (String(rawContent || '').toLowerCase().indexOf('<h2') === -1) {
                    score -= 5;
                    warnings.push('Thiếu heading H2');
                }

                if (String(rawContent || '').toLowerCase().indexOf('<a ') === -1) {
                    score -= 5;
                    warnings.push('Thiếu liên kết nội bộ/ngoài');
                }

                if (!hasCategory()) {
                    score -= 8;
                    errors.push('Chưa gán danh mục');
                }

                if (!summaryDescription) {
                    score -= 4;
                    warnings.push('Thiếu mô tả tóm tắt cho danh sách');
                }

                if (primaryKeyword) {
                    if (!containsText(effectiveTitle, primaryKeyword)) {
                        score -= 5;
                        warnings.push('Từ khóa chính chưa có trong tiêu đề');
                    }

                    if (!containsText(effectiveDescription, primaryKeyword)) {
                        score -= 5;
                        warnings.push('Từ khóa chính chưa có trong mô tả');
                    }
                }

                score = Math.max(0, Math.min(100, score));

                return {
                    score: score,
                    status: getStatus(score),
                    scoreClass: getScoreClass(score),
                    errors: errors,
                    warnings: warnings,
                    effectiveTitle: effectiveTitle,
                    effectiveDescription: effectiveDescription,
                    primaryKeyword: primaryKeyword,
                    wordCount: wordCount,
                    titleLength: titleLength,
                    descriptionLength: descriptionLength,
                    url: $.trim(getFieldValue(fieldIds.url))
                };
            }

            function render() {
                var audit = analyze();
                var $scoreBox = $checker.find('[data-seo-score-box]');
                var previewUrl = previewUrlPattern.replace('__slug__', audit.url || 'duong-dan-bai-viet');

                $scoreBox
                    .removeClass('seo-score-meter-success seo-score-meter-primary seo-score-meter-warning seo-score-meter-danger')
                    .addClass('seo-score-meter-' + audit.scoreClass);

                $checker.find('[data-seo-score]').text(audit.score);
                $checker.find('[data-seo-status]').text(audit.status);
                $checker.find('[data-seo-preview-title]').text(audit.effectiveTitle || 'Chưa có tiêu đề');
                $checker.find('[data-seo-preview-url]').text(previewUrl);
                $checker.find('[data-seo-preview-description]').text(audit.effectiveDescription || 'Chưa có meta description hoặc mô tả tóm tắt.');
                $checker.find('[data-seo-primary-keyword]').text(audit.primaryKeyword || 'Chưa đặt');
                $checker.find('[data-seo-word-count]').text(audit.wordCount);
                $checker.find('[data-seo-title-length]').text(audit.titleLength);
                $checker.find('[data-seo-description-length]').text(audit.descriptionLength);

                renderList($checker.find('[data-seo-errors]'), audit.errors, 'Không có lỗi SEO nghiêm trọng.', 'danger');
                renderList($checker.find('[data-seo-warnings]'), audit.warnings, 'Không có gợi ý bổ sung.', 'warning');
            }

            function scheduleRender() {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(render, 150);
            }

            $checker.closest('form').on('keyup change input', 'input, textarea, select', scheduleRender);

            if (window.CKEDITOR) {
                CKEDITOR.on('instanceReady', function(event) {
                    if (event.editor.name === fieldIds.contents || event.editor.name === fieldIds.description) {
                        event.editor.on('change', scheduleRender);
                        scheduleRender();
                    }
                });

                $.each(CKEDITOR.instances, function(id, editor) {
                    if (id === fieldIds.contents || id === fieldIds.description) {
                        editor.on('change', scheduleRender);
                    }
                });
            }

            render();
        });
    }

    function initDashboardCharts() {
        var dataNode = document.getElementById('dashboard-chart-data');

        if (!dataNode || !window.Chart && !Chart) {
            return;
        }

        var chartData;

        try {
            chartData = JSON.parse(dataNode.textContent || '{}');
        } catch (error) {
            return;
        }

        Chart.defaults.global.defaultFontFamily = "'Open Sans', Arial, sans-serif";
        Chart.defaults.global.defaultFontColor = '#5f6b7a';
        Chart.defaults.global.elements.line.tension = 0.25;

        var gridColor = 'rgba(148, 163, 184, 0.22)';

        function hasCanvas(id) {
            return document.getElementById(id);
        }

        function axisOptions() {
            return {
                xAxes: [{
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 8
                    }
                }],
                yAxes: [{
                    gridLines: {
                        color: gridColor,
                        zeroLineColor: gridColor
                    },
                    ticks: {
                        beginAtZero: true,
                        precision: 0
                    }
                }]
            };
        }

        function baseOptions(extra) {
            return $.extend(true, {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    labels: {
                        boxWidth: 12,
                        padding: 16
                    }
                },
                tooltips: {
                    mode: 'index',
                    intersect: false
                },
                scales: axisOptions()
            }, extra || {});
        }

        if (hasCanvas('dashboardViewsChart') && chartData.views) {
            new Chart(document.getElementById('dashboardViewsChart'), {
                type: 'line',
                data: {
                    labels: chartData.views.labels || [],
                    datasets: [{
                        label: 'Lượt xem',
                        data: chartData.views.views || [],
                        backgroundColor: 'rgba(0, 123, 255, 0.12)',
                        borderColor: '#007bff',
                        pointBackgroundColor: '#007bff',
                        pointRadius: 2,
                        borderWidth: 2
                    }]
                },
                options: baseOptions({
                    legend: {
                        display: false
                    }
                })
            });
        }

        if (hasCanvas('dashboardTopPostsChart') && chartData.topPosts) {
            new Chart(document.getElementById('dashboardTopPostsChart'), {
                type: 'horizontalBar',
                data: {
                    labels: chartData.topPosts.labels || [],
                    datasets: [{
                        label: 'Lượt xem',
                        data: chartData.topPosts.views || [],
                        backgroundColor: '#17a2b8',
                        borderColor: '#138496',
                        borderWidth: 1
                    }]
                },
                options: baseOptions({
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                color: gridColor,
                                zeroLineColor: gridColor
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                fontSize: 11
                            }
                        }]
                    }
                })
            });
        }

        if (hasCanvas('dashboardPostGrowthChart') && chartData.posts) {
            new Chart(document.getElementById('dashboardPostGrowthChart'), {
                type: 'bar',
                data: {
                    labels: chartData.posts.labels || [],
                    datasets: [{
                        label: 'Bài mới',
                        data: chartData.posts.daily || [],
                        backgroundColor: 'rgba(40, 167, 69, 0.35)',
                        borderColor: '#28a745',
                        borderWidth: 1
                    }, {
                        label: 'Tổng bài viết',
                        type: 'line',
                        data: chartData.posts.cumulative || [],
                        backgroundColor: 'rgba(255, 193, 7, 0.12)',
                        borderColor: '#ffc107',
                        pointBackgroundColor: '#ffc107',
                        pointRadius: 2,
                        borderWidth: 2,
                        yAxisID: 'total'
                    }]
                },
                options: baseOptions({
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 8
                            }
                        }],
                        yAxes: [{
                            id: 'daily',
                            position: 'left',
                            gridLines: {
                                color: gridColor,
                                zeroLineColor: gridColor
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }, {
                            id: 'total',
                            position: 'right',
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }]
                    }
                })
            });
        }

        if (hasCanvas('dashboardCommentTrendChart') && chartData.comments) {
            new Chart(document.getElementById('dashboardCommentTrendChart'), {
                type: 'line',
                data: {
                    labels: chartData.comments.labels || [],
                    datasets: [{
                        label: 'Tổng bình luận',
                        data: chartData.comments.total || [],
                        backgroundColor: 'rgba(220, 53, 69, 0.10)',
                        borderColor: '#dc3545',
                        pointBackgroundColor: '#dc3545',
                        pointRadius: 2,
                        borderWidth: 2
                    }, {
                        label: 'Đã duyệt',
                        data: chartData.comments.approved || [],
                        backgroundColor: 'rgba(40, 167, 69, 0.10)',
                        borderColor: '#28a745',
                        pointBackgroundColor: '#28a745',
                        pointRadius: 2,
                        borderWidth: 2
                    }]
                },
                options: baseOptions()
            });
        }
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function initMediaUploadDropzone() {
        var $dropzone = $('[data-media-dropzone]');

        if (!$dropzone.length) {
            return;
        }

        var inputSelector = $dropzone.data('file-input');
        var input = document.querySelector(inputSelector);
        var $preview = $('[data-media-upload-preview]');
        var $form = $dropzone.closest('[data-media-upload-form]');
        var $status = $('[data-media-upload-status]');
        var $submit = $('[data-media-upload-submit]');

        if (!input) {
            return;
        }

        function updateStatus(message, type) {
            if (!$status.length) {
                return;
            }

            $status
                .removeClass('is-idle is-uploading is-success is-error')
                .addClass(type ? 'is-' + type : 'is-idle')
                .html(message || '');
        }

        function setPreviewStatus(text, type) {
            $preview.find('[data-media-upload-item-status]')
                .removeClass('is-uploading is-success is-error')
                .addClass(type ? 'is-' + type : '')
                .text(text);
        }

        function setFiles(files) {
            if (window.DataTransfer) {
                var dataTransfer = new DataTransfer();

                $.each(files, function(index, file) {
                    dataTransfer.items.add(file);
                });

                input.files = dataTransfer.files;
            }

            renderPreview(input.files || files);
        }

        function renderPreview(files) {
            $preview.empty();

            if (!files || !files.length) {
                return;
            }

            $.each(files, function(index, file) {
                var $item = $('<div>').addClass('media-upload-preview-item');
                var $thumb = $('<div>').addClass('media-upload-preview-thumb').appendTo($item);

                if (file.type && file.type.indexOf('image/') === 0 && window.FileReader) {
                    var reader = new FileReader();
                    reader.onload = function(event) {
                        $('<img>').attr('src', event.target.result).appendTo($thumb);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('<i>').addClass('fa fa-file-o').appendTo($thumb);
                }

                $('<span>').text(file.name).appendTo($item);
                $('<small>').text(Math.round(file.size / 1024) + ' KB').appendTo($item);
                $('<small>')
                    .attr('data-media-upload-item-status', '')
                    .addClass('media-upload-item-status')
                    .text('Chờ upload')
                    .appendTo($item);
                $preview.append($item);
            });

            updateStatus(files.length + ' file đã sẵn sàng upload.', 'idle');
        }

        $dropzone.on('click', function() {
            input.click();
        });

        $dropzone.on('dragenter dragover', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $dropzone.addClass('is-dragover');
        });

        $dropzone.on('dragleave dragend drop', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $dropzone.removeClass('is-dragover');
        });

        $dropzone.on('drop', function(event) {
            var files = event.originalEvent.dataTransfer.files;

            if (files && files.length) {
                setFiles(files);
            }
        });

        $(input).on('change', function() {
            renderPreview(input.files);
        });

        if ($form.length) {
            $form.on('submit', function(event) {
                event.preventDefault();

                if (!input.files || !input.files.length) {
                    updateStatus('Vui lòng chọn hoặc kéo thả ít nhất 1 file.', 'error');
                    return;
                }

                var formData = new FormData($form[0]);

                $submit.prop('disabled', true);
                setPreviewStatus('Đang upload 0%', 'uploading');
                updateStatus(
                    '<div class="media-upload-progress"><span style="width: 0%"></span></div><strong>Đang upload 0%</strong>',
                    'uploading'
                );

                $.ajax({
                    type: $form.attr('method') || 'POST',
                    url: $form.attr('action'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    xhr: function() {
                        var xhr = $.ajaxSettings.xhr();

                        if (xhr.upload) {
                            xhr.upload.addEventListener('progress', function(event) {
                                if (!event.lengthComputable) {
                                    return;
                                }

                                var percent = Math.round((event.loaded / event.total) * 100);
                                setPreviewStatus('Đang upload ' + percent + '%', 'uploading');
                                updateStatus(
                                    '<div class="media-upload-progress"><span style="width: ' + percent + '%"></span></div><strong>Đang upload ' + percent + '%</strong>',
                                    'uploading'
                                );
                            });
                        }

                        return xhr;
                    },
                    success: function(response) {
                        setPreviewStatus('Hoàn tất', 'success');
                        updateStatus(response.message || 'Upload hoàn tất.', 'success');

                        setTimeout(function() {
                            window.location.reload();
                        }, 800);
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        var errors = response.errors && response.errors.length
                            ? '<ul><li>' + $.map(response.errors, escapeHtml).join('</li><li>') + '</li></ul>'
                            : '';

                        setPreviewStatus('Lỗi', 'error');
                        updateStatus(escapeHtml(response.message || 'Upload thất bại.') + errors, 'error');
                    },
                    complete: function() {
                        $submit.prop('disabled', false);
                    }
                });
            });
        }
    }

    function initMediaEditModal() {
        var $modal = $('#mediaEditModal');
        var $content = $('[data-media-edit-modal-content]');

        if (!$modal.length || !$content.length) {
            return;
        }

        $modal.appendTo('body');
        $content = $modal.find('[data-media-edit-modal-content]');

        $modal.on('shown.bs.modal', function() {
            $modal.addClass('show');
            $('.modal-backdrop').addClass('show');
        });

        $modal.on('hidden.bs.modal', function() {
            $modal.removeClass('show');
            $content.html('<div class="modal-body text-center text-muted">Đang tải...</div>');
        });

        $(document).on('click', '[data-media-edit]', function(event) {
            event.preventDefault();

            var url = $(this).attr('href');
            $content.html('<div class="modal-body text-center text-muted">Đang tải...</div>');
            $modal.modal('show');
            $modal.addClass('show');
            $('.modal-backdrop').addClass('show');

            $.ajax({
                type: 'GET',
                url: url,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $content.html(response);
                },
                error: function() {
                    $content.html('<div class="modal-body text-center text-danger">Không tải được form media.</div>');
                }
            });
        });

        $(document).on('submit', '[data-media-edit-form]', function(event) {
            event.preventDefault();

            var $form = $(this);
            var $button = $form.find('[type="submit"]');

            $button.prop('disabled', true);
            $form.find('[data-media-edit-status]').remove();
            $form.prepend('<div class="alert alert-info" data-media-edit-status>Đang lưu media...</div>');

            $.ajax({
                type: $form.attr('method') || 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $form.find('[data-media-edit-status]')
                        .removeClass('alert-info')
                        .addClass('alert-success')
                        .text(response.message || 'Media đã được cập nhật.');

                    setTimeout(function() {
                        $modal.modal('hide');
                        window.location.reload();
                    }, 500);
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseText) {
                        $content.html(xhr.responseText);
                        return;
                    }

                    $form.find('[data-media-edit-status]')
                        .removeClass('alert-info')
                        .addClass('alert-danger')
                        .text('Không lưu được media. Vui lòng thử lại.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    }

    function initMediaPicker() {
        var $modal = $('#mediaPickerModal');
        var $content = $('[data-media-picker-content]');
        var pickerState = {
            input: null,
            preview: null,
            mode: 'single',
            albumInput: null,
            albumGrid: null
        };

        if (!$modal.length || !$content.length) {
            return;
        }

        $modal.appendTo('body');
        $content = $modal.find('[data-media-picker-content]');

        function loadPicker(url) {
            $content.html('<div class="text-center text-muted">Đang tải...</div>');

            $.ajax({
                type: 'GET',
                url: url,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $content.html(response);
                    markSelectedPickerItems();
                },
                error: function() {
                    $content.html('<div class="text-center text-danger">Không tải được Media Library.</div>');
                }
            });
        }

        function showModal() {
            $modal.modal('show');
            $modal.addClass('show');
            $('.modal-backdrop').addClass('show');
        }

        function renderPickerUploadPreview($form, files) {
            var $preview = $form.find('[data-media-picker-upload-preview]');
            $preview.empty();

            if (!files || !files.length) {
                return;
            }

            $.each(files, function(index, file) {
                var $item = $('<div>').addClass('media-upload-preview-item');
                var $thumb = $('<div>').addClass('media-upload-preview-thumb').appendTo($item);

                if (file.type && file.type.indexOf('image/') === 0 && window.FileReader) {
                    var reader = new FileReader();
                    reader.onload = function(event) {
                        $('<img>').attr('src', event.target.result).appendTo($thumb);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('<i>').addClass('fa fa-file-o').appendTo($thumb);
                }

                $('<span>').text(file.name).appendTo($item);
                $('<small>').text(Math.round(file.size / 1024) + ' KB').appendTo($item);
                $preview.append($item);
            });
        }

        function setPickerUploadStatus($form, message, type) {
            $form.find('[data-media-picker-upload-status]')
                .removeClass('is-idle is-uploading is-success is-error')
                .addClass(type ? 'is-' + type : 'is-idle')
                .html(message || '');
        }

        function setPickerUploadFiles($form, files) {
            var input = $form.find('input[type="file"]')[0];

            if (!input) {
                return;
            }

            if (window.DataTransfer) {
                var dataTransfer = new DataTransfer();

                $.each(files, function(index, file) {
                    dataTransfer.items.add(file);
                });

                input.files = dataTransfer.files;
            }

            renderPickerUploadPreview($form, input.files || files);
            setPickerUploadStatus($form, files.length + ' ảnh đã sẵn sàng upload.', 'idle');
        }

        function getSelectedAlbumMediaIds() {
            var selected = {};

            if (!pickerState.albumGrid || !pickerState.albumGrid.length) {
                return selected;
            }

            pickerState.albumGrid.find('[data-media-album-item]').each(function() {
                selected[String($(this).data('media-id'))] = true;
            });

            return selected;
        }

        function markSelectedPickerItems() {
            if (pickerState.mode !== 'multiple') {
                return;
            }

            var selected = getSelectedAlbumMediaIds();

            $content.find('[data-media-picker-select]').each(function() {
                var $item = $(this);
                var isSelected = !!selected[String($item.data('media-id'))];
                $item.toggleClass('is-selected', isSelected);
                $item.attr('aria-pressed', isSelected ? 'true' : 'false');
            });
        }

        $modal.on('shown.bs.modal', function() {
            $modal.addClass('show');
            $('.modal-backdrop').addClass('show');
        });

        $modal.on('hidden.bs.modal', function() {
            $modal.removeClass('show');
        });

        $(document).on('click', '[data-media-picker-open]', function(event) {
            event.preventDefault();

            var $button = $(this);
            pickerState.input = $($button.data('target-input'));
            pickerState.preview = $($button.data('target-preview'));
            pickerState.mode = $button.data('picker-mode') || 'single';
            pickerState.albumInput = $($button.data('album-input'));
            pickerState.albumGrid = $($button.data('album-grid'));

            showModal();
            loadPicker($button.data('picker-url'));
        });

        $(document).on('submit', '[data-media-picker-filter]', function(event) {
            event.preventDefault();
            loadPicker($(this).attr('action') + '?' + $(this).serialize());
        });

        $(document).on('click', '.media-picker-pagination a', function(event) {
            event.preventDefault();
            loadPicker($(this).attr('href'));
        });

        $(document).on('click', '[data-media-picker-select]', function(event) {
            event.preventDefault();

            var $item = $(this);

            if (pickerState.mode === 'multiple') {
                var added = addMediaToAlbum(pickerState.albumGrid, pickerState.albumInput, {
                    id: $item.data('media-id'),
                    name: $item.data('media-name'),
                    thumb: $item.data('media-thumb'),
                    url: $item.data('media-url'),
                    alt: $item.data('media-alt') || $item.data('media-name'),
                    caption: ''
                });

                if (added) {
                    $item.addClass('is-selected').attr('aria-pressed', 'true');
                }
                return;
            }

            if (pickerState.input && pickerState.input.length) {
                pickerState.input.val($item.data('media-id'));
            }

            if (pickerState.preview && pickerState.preview.length) {
                pickerState.preview.html(
                    $('<img>')
                        .attr('src', $item.data('media-thumb'))
                        .attr('alt', $item.data('media-alt') || $item.data('media-name'))
                );
            }

            $modal.modal('hide');
        });

        $(document).on('click', '[data-media-picker-clear]', function(event) {
            event.preventDefault();

            var $button = $(this);
            $($button.data('target-input')).val('');
            $($button.data('target-preview')).html('<span>Chưa chọn ảnh từ Media Library</span>');
        });

        $(document).on('click', '[data-media-picker-upload-dropzone]', function() {
            $(this).closest('[data-media-picker-upload-form]').find('input[type="file"]').trigger('click');
        });

        $(document).on('dragenter dragover', '[data-media-picker-upload-dropzone]', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $(this).addClass('is-dragover');
        });

        $(document).on('dragleave dragend drop', '[data-media-picker-upload-dropzone]', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $(this).removeClass('is-dragover');
        });

        $(document).on('drop', '[data-media-picker-upload-dropzone]', function(event) {
            var files = event.originalEvent.dataTransfer.files;

            if (files && files.length) {
                setPickerUploadFiles($(this).closest('[data-media-picker-upload-form]'), files);
            }
        });

        $(document).on('change', '[data-media-picker-upload-form] input[type="file"]', function() {
            renderPickerUploadPreview($(this).closest('[data-media-picker-upload-form]'), this.files);
        });

        $(document).on('submit', '[data-media-picker-upload-form]', function(event) {
            event.preventDefault();

            var $form = $(this);
            var input = $form.find('input[type="file"]')[0];
            var $button = $form.find('[data-media-picker-upload-submit]');

            if (!input || !input.files || !input.files.length) {
                setPickerUploadStatus($form, 'Vui lòng chọn hoặc kéo thả ít nhất 1 ảnh.', 'error');
                return;
            }

            $button.prop('disabled', true);
            setPickerUploadStatus(
                $form,
                '<div class="media-upload-progress"><span style="width: 0%"></span></div><strong>Đang upload 0%</strong>',
                'uploading'
            );

            $.ajax({
                type: $form.attr('method') || 'POST',
                url: $form.attr('action'),
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                xhr: function() {
                    var xhr = $.ajaxSettings.xhr();

                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', function(event) {
                            if (!event.lengthComputable) {
                                return;
                            }

                            var percent = Math.round((event.loaded / event.total) * 100);
                            setPickerUploadStatus(
                                $form,
                                '<div class="media-upload-progress"><span style="width: ' + percent + '%"></span></div><strong>Đang upload ' + percent + '%</strong>',
                                'uploading'
                            );
                        });
                    }

                    return xhr;
                },
                success: function(response) {
                    setPickerUploadStatus($form, escapeHtml(response.message || 'Upload hoàn tất.'), 'success');

                    setTimeout(function() {
                        loadPicker($form.data('picker-refresh-url'));
                    }, 500);
                },
                error: function(xhr) {
                    var response = xhr.responseJSON || {};
                    var errors = response.errors && response.errors.length
                        ? '<ul><li>' + $.map(response.errors, escapeHtml).join('</li><li>') + '</li></ul>'
                        : '';

                    setPickerUploadStatus($form, escapeHtml(response.message || 'Upload thất bại.') + errors, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    }

    function initMediaAlbumFields() {
        $('[data-media-album-grid]').each(function() {
            var $grid = $(this);
            var $input = $($grid.closest('[data-media-album-field]').data('album-input'));
            var raw = $grid.attr('data-initial-album') || '[]';
            var items = [];

            try {
                items = JSON.parse(raw);
            } catch (error) {
                items = [];
            }

            $.each(items, function(index, item) {
                renderAlbumItem($grid, item);
            });

            syncAlbumInput($grid, $input);
        });

        $(document).on('click', '[data-media-album-remove]', function(event) {
            event.preventDefault();

            var $grid = $(this).closest('[data-media-album-grid]');
            var $input = $($grid.closest('[data-media-album-field]').data('album-input'));
            var $albumItem = $(this).closest('[data-media-album-item]');
            var mediaId = $albumItem.data('media-id');
            $albumItem.remove();
            syncAlbumInput($grid, $input);
            $('[data-media-picker-content] [data-media-picker-select][data-media-id="' + mediaId + '"]')
                .removeClass('is-selected')
                .attr('aria-pressed', 'false');
        });

        $(document).on('input', '[data-media-album-alt], [data-media-album-caption]', function() {
            var $grid = $(this).closest('[data-media-album-grid]');
            var $input = $($grid.closest('[data-media-album-field]').data('album-input'));
            syncAlbumInput($grid, $input);
        });

        $(document).on('dragstart', '[data-media-album-item]', function(event) {
            event.originalEvent.dataTransfer.setData('text/plain', $(this).index());
            $(this).addClass('is-dragging');
        });

        $(document).on('dragend', '[data-media-album-item]', function() {
            $(this).removeClass('is-dragging');
        });

        $(document).on('dragover', '[data-media-album-item]', function(event) {
            event.preventDefault();
        });

        $(document).on('drop', '[data-media-album-item]', function(event) {
            event.preventDefault();

            var fromIndex = parseInt(event.originalEvent.dataTransfer.getData('text/plain'), 10);
            var $target = $(this);
            var $grid = $target.closest('[data-media-album-grid]');
            var $items = $grid.find('[data-media-album-item]');
            var $dragged = $items.eq(fromIndex);

            if (!$dragged.length || $dragged[0] === $target[0]) {
                return;
            }

            if (fromIndex < $target.index()) {
                $target.after($dragged);
            } else {
                $target.before($dragged);
            }

            syncAlbumInput($grid, $($grid.closest('[data-media-album-field]').data('album-input')));
        });
    }

    function addMediaToAlbum($grid, $input, item) {
        if (!$grid || !$grid.length || !$input || !$input.length || !item.id) {
            return false;
        }

        var exists = false;
        $grid.find('[data-media-album-item]').each(function() {
            if (String($(this).data('media-id')) === String(item.id)) {
                exists = true;
            }
        });

        if (exists) {
            return false;
        }

        renderAlbumItem($grid, item);
        syncAlbumInput($grid, $input);

        return true;
    }

    function renderAlbumItem($grid, item) {
        var $item = $('<div>')
            .addClass('media-album-item')
            .attr('data-media-album-item', '')
            .attr('data-media-id', item.id)
            .attr('draggable', 'true');

        $('<div>')
            .addClass('media-album-thumb')
            .append($('<img>').attr('src', item.thumb).attr('alt', item.alt || item.name || ''))
            .appendTo($item);

        $('<button>')
            .attr('type', 'button')
            .attr('data-media-album-remove', '')
            .addClass('media-album-remove')
            .html('&times;')
            .appendTo($item);

        $('<strong>').text(item.name || '').appendTo($item);
        $('<input>')
            .attr('type', 'text')
            .attr('data-media-album-alt', '')
            .addClass('form-control')
            .attr('placeholder', 'Alt override')
            .val(item.alt || '')
            .appendTo($item);
        $('<textarea>')
            .attr('data-media-album-caption', '')
            .addClass('form-control')
            .attr('rows', 2)
            .attr('placeholder', 'Caption override')
            .val(item.caption || '')
            .appendTo($item);

        $grid.append($item);
    }

    function syncAlbumInput($grid, $input) {
        if (!$input || !$input.length) {
            return;
        }

        var items = [];

        $grid.find('[data-media-album-item]').each(function() {
            var $item = $(this);

            items.push({
                id: $item.data('media-id'),
                alt: $item.find('[data-media-album-alt]').val() || '',
                caption: $item.find('[data-media-album-caption]').val() || ''
            });
        });

        $input.val(JSON.stringify(items));
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
