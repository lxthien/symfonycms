<?php

namespace AppBundle\Seo;

use AppBundle\Entity\News;

class SeoAnalyzer
{
    public function analyze(News $post)
    {
        $score = 100;
        $reasons = [];
        $recommendations = [];

        $title = trim((string) $post->getTitle());
        $seoTitle = trim((string) $post->getPageTitle());
        $effectiveTitle = $seoTitle !== '' ? $seoTitle : $title;

        $summaryDescription = $this->cleanText($post->getDescription());
        $metaDescription = $this->cleanText($post->getPageDescription());
        $effectiveDescription = $metaDescription !== '' ? $metaDescription : $summaryDescription;

        $rawContent = (string) $post->getContents();
        $content = $this->cleanText($rawContent);
        $wordCount = $this->countWords($content);
        $primaryKeyword = $this->getPrimaryKeyword($post);

        if ($effectiveTitle === '') {
            $score -= 25;
            $reasons[] = 'Thiếu tiêu đề SEO';
        } else {
            $titleLength = mb_strlen($effectiveTitle);

            if ($titleLength < 30) {
                $score -= 8;
                $reasons[] = 'Tiêu đề SEO ngắn';
            } elseif ($titleLength > 70) {
                $score -= 8;
                $reasons[] = 'Tiêu đề SEO dài';
            }
        }

        if ($effectiveDescription === '') {
            $score -= 25;
            $reasons[] = 'Thiếu meta description';
        } else {
            $descriptionLength = mb_strlen($effectiveDescription);

            if ($descriptionLength < 120) {
                $score -= 8;
                $reasons[] = 'Meta description ngắn';
            } elseif ($descriptionLength > 170) {
                $score -= 8;
                $reasons[] = 'Meta description dài';
            }
        }

        if (!$post->getImages()) {
            $score -= 10;
            $reasons[] = 'Thiếu ảnh đại diện';
        }

        if ($wordCount < 300) {
            $score -= 15;
            $reasons[] = 'Nội dung mỏng';
        } elseif ($wordCount < 600) {
            $score -= 6;
            $recommendations[] = 'Có thể mở rộng nội dung';
        }

        if (stripos($rawContent, '<h2') === false) {
            $score -= 5;
            $recommendations[] = 'Thiếu heading H2';
        }

        if (stripos($rawContent, '<a ') === false) {
            $score -= 5;
            $recommendations[] = 'Thiếu liên kết nội bộ/ngoài';
        }

        if ($post->getCategory()->isEmpty()) {
            $score -= 8;
            $reasons[] = 'Chưa gán danh mục';
        }

        if ($summaryDescription === '') {
            $score -= 4;
            $recommendations[] = 'Thiếu mô tả tóm tắt cho danh sách';
        }

        if ($primaryKeyword !== '') {
            $keyword = mb_strtolower($primaryKeyword);
            $titleContainsKeyword = mb_stripos(mb_strtolower($effectiveTitle), $keyword) !== false;
            $descriptionContainsKeyword = mb_stripos(mb_strtolower($effectiveDescription), $keyword) !== false;

            if (!$titleContainsKeyword) {
                $score -= 5;
                $recommendations[] = 'Từ khóa chính chưa có trong tiêu đề';
            }

            if (!$descriptionContainsKeyword) {
                $score -= 5;
                $recommendations[] = 'Từ khóa chính chưa có trong mô tả';
            }
        }

        $score = max(0, min(100, $score));

        return [
            'post' => $post,
            'score' => $score,
            'status' => $this->getStatus($score),
            'reasons' => $reasons,
            'recommendations' => $recommendations,
            'effectiveTitle' => $effectiveTitle,
            'effectiveDescription' => $effectiveDescription,
            'primaryKeyword' => $primaryKeyword,
            'wordCount' => $wordCount,
        ];
    }

    public function calculateAverageScore(array $audits)
    {
        if (count($audits) === 0) {
            return 100;
        }

        $total = 0;

        foreach ($audits as $audit) {
            $total += $audit['score'];
        }

        return round($total / count($audits));
    }

    private function cleanText($value)
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8')));
    }

    private function countWords($text)
    {
        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    private function getPrimaryKeyword(News $post)
    {
        $keywords = trim((string) $post->getPageKeyword());

        if ($keywords === '') {
            return '';
        }

        $parts = preg_split('/[,;]+/', $keywords);

        return trim($parts[0]);
    }

    private function getStatus($score)
    {
        if ($score >= 90) {
            return 'Tốt';
        }

        if ($score >= 80) {
            return 'Khá';
        }

        if ($score >= 60) {
            return 'Cần cải thiện';
        }

        return 'Yếu';
    }
}
