'use strict';

require('bxslider/dist/jquery.bxslider');
require('@fancyapps/fancybox');
require('../../../libs/starrating/js/rating.js');

function initSearchBox() {
    var $formSearch = $('#form-search');
    var $searchField = $('.search-field');

    $searchField.keypress(function (e) {
        if (e.which == 13) {
            e.preventDefault();

            if ($searchField.val() === '') {
                $searchField.focus();
            } else {
                $formSearch.submit();
            }
        }
    });
}

function initProtectedContent() {
    $('body').bind('cut copy', function (e) {
        e.preventDefault();
    });
}

function initGoToTop() {
    var $goToTop = $('.go-to-top');

    $goToTop.click(function () {
        $("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });
}

function initProjectHotSlider() {
    var slideWidth = 280;

    // Responsive: 1 slide on mobile
    if ($(window).width() < 576) {
        slideWidth = $(window).width() - 40;
    } else if ($(window).width() < 768) {
        slideWidth = ($(window).width() - 60);
    }

    $('.bxslider').show().bxSlider({
        auto: true,
        autoControls: false,
        stopAutoOnClick: true,
        pager: true,
        controls: true,
        minSlides: 1,
        maxSlides: 4,
        moveSlides: 1,
        slideWidth: slideWidth,
        slideMargin: 20,
        touchEnabled: true,
        autoHover: true,
        adaptiveHeight: false,
        shrinkItems: true
    });
}

function initNewsSlider() {
    $('.post-sidebar-bxslider').bxSlider({
        mode: 'vertical',
        auto: true,
        speed: 300,
        autoControls: false,
        stopAutoOnClick: true,
        pager: false,
        controls: false,
        minSlides: 10,
        maxSlides: 10,
        moveSlides: 1,
        slideWidth: 375,
        touchEnabled: false,
        autoHover: true
    });
}

function initFixedMenu() {
    $(window).scroll(function () {
        var $nav = $("#nav");
        var $scrollUp = $('.td-scroll-up');
        var scroll = $(window).scrollTop();

        if (scroll >= 160) {
            $nav.addClass("navbar-fixed-top");
            $scrollUp.removeClass("hidden");
        } else {
            $nav.removeClass("navbar-fixed-top");
            $scrollUp.addClass("hidden");
        }
    });
}

function initFixedSidebar() {
    $(window).scroll(function () {
        var $sidebar = $("#sidebar .sidebar"),
            $pageDetailLeft = $('.wrapper-post-container-left'),
            scrollTop = $(this).scrollTop(),
            pageDetailLeftHeight = $pageDetailLeft.outerHeight(),
            sidebarHeight = $sidebar.height(),
            positionFixedMax = pageDetailLeftHeight - sidebarHeight,
            positionFixed = scrollTop < 65 ? 65 : positionFixedMax > scrollTop ? 65 : positionFixedMax - scrollTop + 290;

        if (pageDetailLeftHeight > sidebarHeight) {
            if (scrollTop > 220) {
                $sidebar.css({
                    'top': positionFixed,
                    'position': 'fixed',
                    'width': $sidebar.width()
                });
            } else {
                $sidebar.removeAttr("style");
            }
        }
    });
}

function initCostConstruction() {
    var $formType = $('.costs #form_type');
    var $formFloor = $('.costs #form_floor');

    if ($formType.val() == 3) {
        $formFloor.val(1);
        $formFloor.attr('disabled', 'disabled');
    }

    $formType.change(function (e) {
        if ($(this).val() == 3) {
            $formFloor.val(1);
            $formFloor.attr('disabled', 'disabled');
        } else {
            $formFloor.removeAttr('disabled');
        }
    })
}

function initFancybox() {
    var $rating = $('.post-rating .rating');
    var $ratingMessage = $('p.rating-message');
    var $star = $('#form-rating-review .rating-well .star');
    var $formRating = $('#form-rating-review');

    $rating.click(function () {
        $formRating.show();
        $ratingMessage.html('');

        $.fancybox.open({
            src: '#form-rating-container',
            touch: false
        });

        return false;
    });

    $('a#rating').click(function (e) {
        e.preventDefault();

        $formRating.show();
        $ratingMessage.html('');

        $.fancybox.open({
            src: '#form-rating-container',
            touch: false
        });

        return false;
    });

    $star.on('click', function (e) {
        var rating = $(this).data('value');
        var newsId = $formRating.data('newsId');

        $.ajax({
            type: "POST",
            url: $formRating.attr('action'),
            data: 'rating=' + rating + '&newsId=' + newsId,
            success: function (data) {
                var response = JSON.parse(data);

                if (response.status === 'success') {
                    $formRating.hide();
                    $ratingMessage.html(response.message);
                }
            }
        });
    });
}

function initTypewriterEffect() {
    var i = 0,
        a = 0,
        isBackspacing = false,
        isParagraph = false;

    var textArray = [
        "MINH DUY CONSTRUCTION|95/121 ĐƯỜNG LÊ VĂN LƯƠNG, P. TÂN HƯNG, TP.HCM"
    ];

    // Speed (in milliseconds) of typing.
    var speedForward = 80, //Typing Speed
        speedWait = 1500, // Wait between typing and backspacing
        speedBetweenLines = 1500, //Wait between first and second lines
        speedBackspace = 25; //Backspace Speed

    //Run the loop
    typeWriter("output", textArray);

    function typeWriter(id, ar) {
        var element = $("#" + id),
            aString = ar[a],
            eHeader = element.children("p#header-company"), //Header element
            eParagraph = element.children("p#header-address"); //Subheader element

        // Determine if animation should be typing or backspacing
        if (!isBackspacing) {

            // If full string hasn't yet been typed out, continue typing
            if (i < aString.length) {
                // If character about to be typed is a pipe, switch to second line and continue.
                if (aString.charAt(i) == "|") {
                    isParagraph = true;
                    eHeader.removeClass("cursor");
                    eParagraph.addClass("cursor");
                    i++;
                    setTimeout(function () { typeWriter(id, ar); }, speedBetweenLines);
                    // If character isn't a pipe, continue typing.
                } else {
                    // Type header or subheader depending on whether pipe has been detected
                    if (!isParagraph) {
                        eHeader.text(eHeader.text() + aString.charAt(i));
                    } else {
                        eParagraph.text(eParagraph.text() + aString.charAt(i));
                    }
                    i++;
                    setTimeout(function () { typeWriter(id, ar); }, speedForward);
                }
                // If full string has been typed, switch to backspace mode.
            } else if (i == aString.length) {

                isBackspacing = true;
                setTimeout(function () { typeWriter(id, ar); }, speedWait);
            }
            // If backspacing is enabled
        } else {
            // If either the header or the paragraph still has text, continue backspacing
            if (eHeader.text().length > 0 || eParagraph.text().length > 0) {
                // If paragraph still has text, continue erasing, otherwise switch to the header.
                if (eParagraph.text().length > 0) {
                    eParagraph.text(eParagraph.text().substring(0, eParagraph.text().length - 1));
                } else if (eHeader.text().length > 0) {
                    eParagraph.removeClass("cursor");
                    eHeader.addClass("cursor");
                    eHeader.text(eHeader.text().substring(0, eHeader.text().length - 1));
                }
                setTimeout(function () { typeWriter(id, ar); }, speedBackspace);
                // If neither head or paragraph still has text, switch to next quote in array and start typing.
            } else {
                isBackspacing = false;
                i = 0;
                isParagraph = false;
                a = (a + 1) % ar.length; //Moves to next position in array, always looping back to 0
                setTimeout(function () { typeWriter(id, ar); }, 50);
            }
        }
    }
}

function slugifyHeading(text, fallbackIndex) {
    var slug = $.trim(text || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return slug || ('muc-' + fallbackIndex);
}

function buildAutoTableOfContents($container) {
    var $headings = $container.find('h2, h3, h4').filter(function () {
        var $el = $(this);
        if ($el.closest('.table-of-contents, .key-takeaways, .post-list-shortcode, .cta-box').length > 0) {
            return false;
        }
        return $.trim($el.text()).length > 0;
    });

    if ($headings.length < 2) {
        return $();
    }

    // Xóa id cũ (vd: id tiếng Việt có dấu còn sót từ plugin TOC cũ) và tạo lại id slug sạch
    $headings.removeAttr('id');

    var usedIds = {};
    $('[id]').each(function () {
        usedIds[this.id] = true;
    });

    var $rootList = $('<ul></ul>');
    var $lastH2Item = null;
    var $lastH3Item = null;

    $headings.each(function (index) {
        var $heading = $(this);
        var tag = this.tagName.toLowerCase();
        var text = $.trim($heading.text());
        var baseSlug = slugifyHeading(text, index + 1);
        var id = baseSlug;
        var suffix = 2;

        while (usedIds[id]) {
            id = baseSlug + '-' + suffix;
            suffix++;
        }
        usedIds[id] = true;
        $heading.attr('id', id);

        var $link = $('<a></a>').attr('href', '#' + id).text(text);
        var $item = $('<li></li>').append($link);

        if (tag === 'h2') {
            $rootList.append($item);
            $lastH2Item = $item;
            $lastH3Item = null;
        } else if (tag === 'h3') {
            if ($lastH2Item) {
                var $subList = $lastH2Item.children('ul');
                if ($subList.length === 0) {
                    $subList = $('<ul></ul>').appendTo($lastH2Item);
                }
                $subList.append($item);
            } else {
                $rootList.append($item);
            }
            $lastH3Item = $item;
        } else {
            var $parentItem = $lastH3Item || $lastH2Item;
            if ($parentItem) {
                var $nestedList = $parentItem.children('ul');
                if ($nestedList.length === 0) {
                    $nestedList = $('<ul></ul>').appendTo($parentItem);
                }
                $nestedList.append($item);
            } else {
                $rootList.append($item);
            }
        }
    });

    var $toc = $('<div class="table-of-contents"></div>');
    var $tocHeader = $('<div class="toc-header">' +
        '<span class="toc-title"><i class="fas fa-list-ul"></i> Nội dung bài viết</span>' +
        '<span class="toc-toggle collapsed">' +
        '<span class="toggle-text">Hiện</span>' +
        '<i class="fas fa-chevron-down toggle-icon"></i>' +
        '</span>' +
        '</div>');
    var $tocContent = $('<div class="toc-content collapsed"></div>').append($rootList);

    $toc.append($tocHeader).append($tocContent);

    var $firstH2 = $headings.filter('h2').first();
    if ($firstH2.length > 0) {
        $firstH2.before($toc);
    } else {
        $container.prepend($toc);
    }

    if (window.location.hash && window.location.hash.length > 1) {
        var hashTarget = document.getElementById(decodeURIComponent(window.location.hash.slice(1)));
        if (hashTarget) {
            setTimeout(function () {
                $('html, body').animate({ scrollTop: Math.max(0, $(hashTarget).offset().top - 90) }, 300);
            }, 50);
        }
    }

    return $toc;
}

function initTableOfContents() {
    var $toc = $('.table-of-contents');

    // Nếu bài viết/landing chưa chèn sẵn TOC thủ công, tự động tạo từ các thẻ heading
    if ($toc.length === 0) {
        var $articleContainer = $('.post-content.news-container, .landing-article-content').first();
        if ($articleContainer.length === 1) {
            $toc = buildAutoTableOfContents($articleContainer);
        }
    }

    // Only process if there is exactly 1 table of contents
    if ($toc.length !== 1) return;

    var $this = $toc;

    // Skip building if already initialized
    if ($this.find('.toc-header').length === 0) {
        var $children = $this.children().not('hr#hr-toc');

        // Create wrapper for existing content
        var $tocContent = $('<div class="toc-content collapsed"></div>');
        $children.appendTo($tocContent);

        // Create header
        var $tocHeader = $('<div class="toc-header">' +
            '<span class="toc-title"><i class="fas fa-list-ul"></i> Nội dung bài viết</span>' +
            '<span class="toc-toggle collapsed">' +
            '<span class="toggle-text">Hiện</span>' +
            '<i class="fas fa-chevron-down toggle-icon"></i>' +
            '</span>' +
            '</div>');

        // Build new structure
        $this.empty().append($tocHeader).append($tocContent);
    }

    // Toggle functionality
    $this.find('.toc-header').off('click').on('click', function () {
        var $toggle = $(this).find('.toc-toggle');
        var $content = $(this).siblings('.toc-content');

        $toggle.toggleClass('collapsed');
        $content.toggleClass('collapsed');

        // Update text
        if ($content.hasClass('collapsed')) {
            $toggle.find('.toggle-text').text('Hiện');
        } else {
            $toggle.find('.toggle-text').text('Ẩn');
        }
    });

    // Smooth scroll có bù chiều cao menu cố định khi bấm mục lục
    $this.find('.toc-content a[href^="#"]').off('click.tocScroll').on('click.tocScroll', function (e) {
        var targetId = $(this).attr('href');
        if (!targetId || targetId === '#') return;
        var targetEl = document.getElementById(targetId.slice(1));
        if (!targetEl) return;

        e.preventDefault();
        var topOffset = $(targetEl).offset().top - 90;
        $('html, body').animate({ scrollTop: Math.max(0, topOffset) }, 300);
    });
}

exports.init = function () {
    initSearchBox();
    initProjectHotSlider();
    //initNewsSlider();
    initProtectedContent();
    initGoToTop();
    initFixedMenu();
    //initFixedSidebar();
    initCostConstruction();
    initFancybox();
    //initTypewriterEffect();
    initTableOfContents();
};