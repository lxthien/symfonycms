<?php

namespace Tests\AppBundle\Seo;

use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use AppBundle\Seo\SeoAnalyzer;
use PHPUnit\Framework\TestCase;

class SeoAnalyzerTest extends TestCase
{
    public function testAnalyzeRewardsCompletePost()
    {
        $post = $this->createCompletePost();

        $result = (new SeoAnalyzer())->analyze($post);

        $this->assertGreaterThanOrEqual(90, $result['score']);
        $this->assertSame('Tốt', $result['status']);
        $this->assertNotContains('Chưa gán danh mục', $result['reasons']);
        $this->assertSame('xây nhà trọn gói', $result['primaryKeyword']);
    }

    public function testAnalyzeFlagsMissingRequiredSeoInputs()
    {
        $post = new News();
        $post
            ->setTitle('')
            ->setDescription('')
            ->setContents('Nội dung ngắn')
            ->setPageKeyword('');

        $result = (new SeoAnalyzer())->analyze($post);

        $this->assertLessThan(60, $result['score']);
        $this->assertContains('Thiếu tiêu đề SEO', $result['reasons']);
        $this->assertContains('Thiếu meta description', $result['reasons']);
        $this->assertContains('Thiếu ảnh đại diện', $result['reasons']);
        $this->assertContains('Nội dung mỏng', $result['reasons']);
        $this->assertContains('Chưa gán danh mục', $result['reasons']);
    }

    public function testCalculateAverageScoreDefaultsToPerfectWhenEmpty()
    {
        $this->assertSame(100, (new SeoAnalyzer())->calculateAverageScore(array()));
    }

    private function createCompletePost()
    {
        $category = new NewsCategory();
        $category->setName('Xây dựng');

        $post = new News();
        $post
            ->setTitle('Xây nhà trọn gói tại TPHCM: báo giá và quy trình thi công chi tiết')
            ->setUrl('xay-nha-tron-goi-tphcm')
            ->setDescription('Xây nhà trọn gói giúp chủ nhà tối ưu chi phí, kiểm soát tiến độ và giảm rủi ro trong quá trình thi công thực tế tại TPHCM.')
            ->setPageTitle('Xây nhà trọn gói tại TPHCM: báo giá và quy trình thi công chi tiết')
            ->setPageDescription('Xây nhà trọn gói giúp chủ nhà tối ưu chi phí, kiểm soát tiến độ và giảm rủi ro trong quá trình thi công thực tế tại TPHCM.')
            ->setPageKeyword('xây nhà trọn gói, báo giá xây nhà')
            ->setImages('xay-nha.jpg')
            ->setContents($this->longContent());
        $post->addCategory($category);

        return $post;
    }

    private function longContent()
    {
        $paragraph = 'xây nhà trọn gói cần kế hoạch rõ ràng, dự toán minh bạch, kiểm soát vật tư, nhân công, tiến độ và nghiệm thu từng hạng mục. ';

        return '<h2>Quy trình xây nhà trọn gói</h2><p>' . str_repeat($paragraph, 45) . '</p><p><a href="/xay-dung.html">Xem thêm dịch vụ xây dựng</a></p>';
    }
}
