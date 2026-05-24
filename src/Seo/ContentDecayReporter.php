<?php

namespace App\Seo;

use App\Entity\News;

class ContentDecayReporter
{
    private $seoAnalyzer;

    public function __construct(SeoAnalyzer $seoAnalyzer)
    {
        $this->seoAnalyzer = $seoAnalyzer;
    }

    public function analyze(News $post, \DateTimeInterface $now = null)
    {
        $now = $now ?: new \DateTime();
        $updatedAt = $post->getUpdatedAt() ?: $post->getCreatedAt() ?: $now;
        $ageDays = (int) $updatedAt->diff($now)->format('%a');
        $seoAudit = $this->seoAnalyzer->analyze($post);
        $score = 0;
        $reasons = [];
        $recommendations = [];
        $viewCounts = (int) $post->getViewCounts();

        if ($ageDays >= 365) {
            $score += 35;
            $reasons[] = 'Chưa cập nhật hơn 1 năm';
        } elseif ($ageDays >= 180) {
            $score += 24;
            $reasons[] = 'Chưa cập nhật hơn 6 tháng';
        } elseif ($ageDays >= 90) {
            $score += 12;
            $recommendations[] = 'Nên rà soát lại nội dung sau 3 tháng';
        }

        if ($seoAudit['score'] < 60) {
            $score += 32;
            $reasons[] = 'SEO score thấp';
        } elseif ($seoAudit['score'] < 80) {
            $score += 18;
            $reasons[] = 'SEO score cần cải thiện';
        } elseif ($seoAudit['score'] < 90) {
            $score += 8;
            $recommendations[] = 'SEO score còn có thể tối ưu';
        }

        if ($viewCounts < 100) {
            $score += 18;
            $reasons[] = 'Lượt xem thấp';
        } elseif ($viewCounts < 500) {
            $score += 10;
            $recommendations[] = 'Lượt xem chưa tốt';
        }

        if (!$post->getImages()) {
            $score += 8;
            $reasons[] = 'Thiếu ảnh đại diện';
        }

        if ($seoAudit['wordCount'] < 300) {
            $score += 12;
            $reasons[] = 'Nội dung mỏng';
        } elseif ($seoAudit['wordCount'] < 600) {
            $score += 6;
            $recommendations[] = 'Có thể mở rộng nội dung';
        }

        if (!$post->getIsIndex()) {
            $score -= 20;
            $recommendations[] = 'Đang noindex nên ít ưu tiên SEO';
        }

        if (!$post->getIsFollow()) {
            $score += 4;
            $recommendations[] = 'Đang nofollow';
        }

        $score = max(0, min(100, $score));

        return [
            'post' => $post,
            'decayScore' => $score,
            'decayStatus' => $this->getStatus($score),
            'ageDays' => $ageDays,
            'views' => $viewCounts,
            'seo' => $seoAudit,
            'reasons' => array_values(array_unique(array_merge($reasons, $seoAudit['reasons']))),
            'recommendations' => array_values(array_unique(array_merge($recommendations, $seoAudit['recommendations']))),
            'isIndexable' => (bool) $post->getIsIndex(),
        ];
    }

    public function summarize(array $items)
    {
        $highRisk = 0;
        $needsRefresh = 0;
        $totalScore = 0;

        foreach ($items as $item) {
            $totalScore += $item['decayScore'];

            if ($item['decayScore'] >= 70) {
                $highRisk++;
            } elseif ($item['decayScore'] >= 40) {
                $needsRefresh++;
            }
        }

        return [
            'total' => count($items),
            'highRisk' => $highRisk,
            'needsRefresh' => $needsRefresh,
            'averageDecayScore' => count($items) ? round($totalScore / count($items)) : 0,
        ];
    }

    private function getStatus($score)
    {
        if ($score >= 70) {
            return 'Ưu tiên cao';
        }

        if ($score >= 40) {
            return 'Nên cập nhật';
        }

        if ($score >= 20) {
            return 'Theo dõi';
        }

        return 'Ổn';
    }
}
