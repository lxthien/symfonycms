'use strict';

require('jquery-validation');

function intHandleFormComment() {
    window.dataLayer = window.dataLayer || [];
    
    var $formComment = $('#form-comment');

    $formComment.on('click', '#form_send', function(e) {
        if ($formComment.valid()) {
            $.ajax({
                type: "POST",
                url: $formComment.attr('action'),
                data: $formComment.serialize(),
                success: function(data) {
                    var response = JSON.parse(data);
                    if (response.status === 'success') {
                        $('p#comment-response').html(response.message);

                        window.dataLayer.push({
                            'event': 'subscriber'
                        });
    
                        // Clear form comment
                        $formComment[0].reset();
                    } else {
                        $('p#comment-response').html(response.message);
                    }
                }
            });
        }
    })
}

function intHandleFormReplyComment() {
    var $commentReply = $('.comment-reply-link');
    var $formComment = $('#form-comment');

    $commentReply.click(function(e) {
        e.preventDefault();

        var postID = $(this).data('postId');

        if (postID) {
            $formComment.find('input#form_comment_id').val(postID);

            $('html, body').animate({
                scrollTop: $formComment.offset().top
            }, 1000);
        }
    });
}

function initPostGallery() {
    $('[data-post-album]').each(function() {
        var $album = $(this);
        var $track = $album.find('[data-album-track]');
        var $items = $album.find('[data-gallery-item]');
        var $modal = $album.find('[data-gallery-modal]');
        var $image = $album.find('[data-gallery-image]');
        var $caption = $album.find('[data-gallery-caption-text]');
        var currentIndex = 0;

        function scrollAlbum(direction) {
            if (!$track.length) {
                return;
            }

            var itemWidth = $items.first().outerWidth(true) || 260;
            $track.stop().animate({
                scrollLeft: $track.scrollLeft() + (direction * itemWidth * 2)
            }, 260);
        }

        function show(index) {
            var $item = $items.eq(index);

            if (!$item.length) {
                return;
            }

            currentIndex = index;
            $image.attr('src', $item.data('gallery-src'));
            $image.attr('alt', $item.data('gallery-alt') || '');
            $caption.text($item.data('gallery-caption') || '');
            $modal.attr('aria-hidden', 'false').addClass('is-open');
            $('body').addClass('gallery-open');
        }

        function close() {
            $modal.attr('aria-hidden', 'true').removeClass('is-open');
            $('body').removeClass('gallery-open');
            $image.attr('src', '');
        }

        $items.on('click', function() {
            show($(this).data('gallery-index') || 0);
        });

        $album.find('[data-album-prev]').on('click', function() {
            scrollAlbum(-1);
        });

        $album.find('[data-album-next]').on('click', function() {
            scrollAlbum(1);
        });

        $album.find('[data-gallery-close]').on('click', close);

        $album.find('[data-gallery-prev]').on('click', function() {
            show((currentIndex - 1 + $items.length) % $items.length);
        });

        $album.find('[data-gallery-next]').on('click', function() {
            show((currentIndex + 1) % $items.length);
        });

        $(document).on('keydown', function(event) {
            if (!$modal.hasClass('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                close();
            } else if (event.key === 'ArrowLeft') {
                show((currentIndex - 1 + $items.length) % $items.length);
            } else if (event.key === 'ArrowRight') {
                show((currentIndex + 1) % $items.length);
            }
        });
    });
}

exports.init = function () {

    $('#form-comment').validate();

    intHandleFormComment();
    intHandleFormReplyComment();
    initPostGallery();
};
