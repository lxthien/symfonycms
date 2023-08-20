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

    $goToTop.click(function() {
        $("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });
}

function initProjectHotSlider() {
    $('.bxslider').show().bxSlider({
        auto: true,
        autoControls: false,
        stopAutoOnClick: true,
        pager: false,
        controls: true,
        minSlides: 1,
        maxSlides: 4,
        moveSlides: 1,
        slideMargin: 20,
        touchEnabled: false,
        autoHover: true,
        adaptiveHeight: true
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
    $(window).scroll(function() {
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
    $(window).scroll(function() {
        var $sidebar = $("#sidebar .sidebar"),
            $pageDetail = $('.wrapper-post-container'),
            $pageDetailLeft = $('.wrapper-post-container-left'),
            scrollTop = $(this).scrollTop(),
            pageDetailHeight =  $pageDetail.outerHeight(),
            pageDetailLeftHeight =  $pageDetailLeft.outerHeight(),
            sidebarHeight = $sidebar.height(),
            parentSidebarWidth = $sidebar.parent('.col-md-12').width(),
            positionFixedMax = pageDetailHeight - sidebarHeight,
            positionFixed = scrollTop < 65 ? 65 : positionFixedMax > scrollTop ? 65 : positionFixedMax - scrollTop ;
        
        if (pageDetailLeftHeight > sidebarHeight) {
            if (scrollTop > 220) {
                $sidebar.css({
                    'top': positionFixed,
                    'position': 'fixed',
                    'width': parentSidebarWidth
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

    $formType.change(function(e) {
        if ($(this).val() == 3) {
            $formFloor.val(1);
            $formFloor.attr('disabled', 'disabled');
        } else {
            $formFloor.removeAttr('disabled');
        }
    })
}

function initFancybox() {
    var $rating = $('.rating-container .rating');
    var $ratingMessage = $('p.rating-message');
    var $star = $('#form-rating-review .rating-well .star');
    var $formRating = $('#form-rating-review');

    $rating.click(function() {
        $formRating.show();
        $ratingMessage.html('');
        
        $.fancybox.open({
            src: '#form-rating-container',
            touch : false
        });

        return false;
    });

    $('a#rating').click(function(e) {
        e.preventDefault();

        $formRating.show();
        $ratingMessage.html('');
        
        $.fancybox.open({
            src: '#form-rating-container',
            touch : false
        });

        return false;
    });

    $star.on('click', function(e) {
        var rating = $(this).data('value');
        var newsId = $formRating.data('newsId');

        $.ajax({
            type: "POST",
            url: $formRating.attr('action'),
            data: 'rating=' + rating + '&newsId=' + newsId,
            success: function(data) {
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
        "MINH DUY CONSTRUCTION|A45 ĐƯỜNG SỐ 2, KDC KIM SƠN, P.TÂN PHONG, Q.7, TP.HCM"
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

exports.init = function () {
    initSearchBox();
    initProjectHotSlider();
    //initNewsSlider();
    initProtectedContent();
    initGoToTop();
    initFixedMenu();
    initCostConstruction();
    initFancybox();
    initTypewriterEffect();
};