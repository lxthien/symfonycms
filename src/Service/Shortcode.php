<?php

namespace App\Service;

/**
 * Tiện ích cho shortcode dạng khối trong nội dung CKEditor (như [bai_viet]).
 */
final class Shortcode
{
    private const SPACE = '(?:\s|&nbsp;|\x{00A0})*';
    private const INLINE_TAGS = 'strong|b|em|i|u|s|span|font|code|mark|small|sup|sub';

    public static function pattern(string $name): string
    {
        return '\[' . preg_quote($name, '#') . '\b[^\]]*\]';
    }

    /**
     * Thay mọi shortcode $name bằng HTML do $render trả về.
     *
     * CKEditor luôn đặt chữ gõ vào trong <p> — có khi kèm &nbsp;/<br>, bị bọc
     * thêm <strong>/<span>… hoặc nằm chung đoạn với chữ khác. Khối <div>
     * không được nằm trong <p>: trình duyệt tự đóng <p> sớm và để lại </p> thừa
     * làm vỡ bố cục. Nên: gỡ thẻ inline chỉ bọc riêng shortcode, tách đoạn <p>
     * quanh shortcode (giữ nguyên thuộc tính của <p>) và bỏ phần còn lại trống.
     *
     * @param callable(string): string $render nhận shortcode gốc
     */
    public static function replaceBlocks(string $content, string $name, callable $render): string
    {
        if (!str_contains($content, '[' . $name)) {
            return $content;
        }

        $shortcode = self::pattern($name);

        // Lặp vì thẻ inline có thể lồng nhiều lớp: <strong><span>[…]</span></strong>
        do {
            $content = preg_replace(
                '#<(' . self::INLINE_TAGS . ')\b[^>]*>' . self::SPACE . '(' . $shortcode . ')' . self::SPACE . '</\1>#u',
                '$2',
                $content,
                -1,
                $count
            );
        } while ($count > 0);

        $content = preg_replace_callback(
            '#<p\b([^>]*)>((?:(?!</p>).)*?' . $shortcode . '.*?)</p>#su',
            static function (array $m) use ($shortcode): string {
                $parts = preg_split('#(' . $shortcode . ')#u', $m[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                $html = '';
                foreach ($parts as $i => $part) {
                    if ($i % 2 === 1) {
                        $html .= $part;
                        continue;
                    }
                    $part = preg_replace('#^(?:\s|&nbsp;|\x{00A0}|<br\s*/?>)+|(?:\s|&nbsp;|\x{00A0}|<br\s*/?>)+$#u', '', $part);
                    if (self::isBlank($part)) {
                        continue;
                    }
                    $html .= '<p' . $m[1] . '>' . $part . '</p>';
                }

                return $html;
            },
            $content
        );

        return preg_replace_callback('#' . $shortcode . '#u', static fn (array $m): string => $render($m[0]), $content);
    }

    /**
     * Tham số của shortcode. Dấu nháy có thể đã bị mã hoá (&quot;), là nháy
     * cong “…” (gõ từ Word) hoặc không có; khoảng trắng có thể là &nbsp;.
     *
     * @return array<string, string>
     */
    public static function attributes(string $shortcode): array
    {
        $shortcode = str_replace("\u{00A0}", ' ', html_entity_decode($shortcode, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        preg_match_all('/(\w+)\s*=\s*["\'“”]?([^"\'“”\]]*)/u', $shortcode, $pairs, PREG_SET_ORDER);

        $attributes = [];
        foreach ($pairs as $pair) {
            $attributes[strtolower($pair[1])] = trim($pair[2]);
        }

        return $attributes;
    }

    private static function isBlank(string $html): bool
    {
        if (preg_match('#<(?:img|iframe|video|audio|svg|object|embed|input|hr)\b#i', $html)) {
            return false;
        }

        return trim(str_replace(["\u{00A0}", '&nbsp;'], '', strip_tags($html))) === '';
    }
}

