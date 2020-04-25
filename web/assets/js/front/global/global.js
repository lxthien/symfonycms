'use strict';

require('bxslider/dist/jquery.bxslider');
require('@fancyapps/fancybox');
require('../../../libs/starrating/js/rating.js');
require('slick-carousel');
require('jquery-validation');

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
        maxSlides: 5,
        moveSlides: 1,
        slideMargin: 0,
        touchEnabled: false,
        autoHover: true,
        adaptiveHeight: true
    });

    $('.bxslider-recommendations').show().bxSlider({
        auto: true,
        autoControls: false,
        stopAutoOnClick: true,
        pager: false,
        controls: true,
        minSlides: 1,
        maxSlides: 4,
        moveSlides: 1,
        slideMargin: 0,
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
        minSlides: 5,
        maxSlides: 5,
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
    
        if (scroll >= 35) {
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

function initProductImages() {
    $('.slider-for').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: '.slider-nav'
    });
    $('.slider-nav').slick({
        slidesToShow: 5,
        slidesToScroll: 1,
        asNavFor: '.slider-for',
        dots: false,
        focusOnSelect: true
    });
}

function initProductRating() {
    var $formRating = $('#form-rating-review');
    $formRating.validate();

    $formRating.find('button#form_send').on('click', function(e) {
        if ($formRating.valid()) {
            var name = $formRating.find('input[name="form[name]"]').val();
            var email = $formRating.find('input[name="form[email]"]').val();
            var rating = $formRating.find('input[name="form[rating]"]').val();
            var title = $formRating.find('input[name="form[title]"]').val();
            var contents = $formRating.find('textarea[name="form[contents]').val();
            var productId = $formRating.data('productId');
            var $ratingMessage = $('.rating-message');

            $.ajax({
                type: "POST",
                url: $formRating.attr('action'),
                data: 'rating=' + rating + '&productId=' + productId + '&name=' + name + '&email=' + email + '&title=' + title + '&contents=' + contents,
                success: function(data) {
                    var response = JSON.parse(data);
                    
                    if (response.status === 'success') {
                        $formRating.hide();
                        $ratingMessage.html(response.message);
                    }
                }
            });
        }
    });
}

function initProductHotSidebar() {
    $('.products-hot-sidebar').show().bxSlider({
        auto: true,
        autoControls: false,
        stopAutoOnClick: true,
        pager: false,
        controls: true,
        minSlides: 1,
        maxSlides: 1,
        moveSlides: 1,
        slideMargin: 0,
        touchEnabled: false,
        autoHover: true,
        adaptiveHeight: true
    });
}

exports.init = function () {
    initSearchBox();
    initProjectHotSlider();
    initNewsSlider();
    initProtectedContent();
    initGoToTop();
    initFixedMenu();
    initFixedSidebar();
    initProductRating();
    initProductImages();
    initProductHotSidebar();
};