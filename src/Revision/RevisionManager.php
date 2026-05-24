<?php

namespace App\Revision;

use App\Entity\ContentRevision;
use App\Entity\News;
use App\Entity\User;

class RevisionManager
{
    private $fields = [
        'title' => 'Tiêu đề',
        'url' => 'URL',
        'description' => 'Mô tả',
        'contents' => 'Nội dung',
        'pageTitle' => 'SEO title',
        'pageDescription' => 'SEO description',
        'pageKeyword' => 'SEO keyword',
        'qa' => 'Q&A',
        'template' => 'Template',
    ];

    public function createFromNews(News $news, User $author = null, $revisionType = 'manual')
    {
        return $this->createFromData($news, $this->extractNewsData($news), $author, $revisionType);
    }

    public function createFromRequestData(News $news, array $data, User $author = null, $revisionType = 'autosave')
    {
        return $this->createFromData($news, $this->normalizeData($data), $author, $revisionType);
    }

    public function createFromDataWithBaseline(News $news, array $data, array $baseline, User $author = null, $revisionType = 'manual')
    {
        $revision = $this->createFromData($news, $data, $author, $revisionType);
        $revision->setDiffSummary($this->buildDiffSummary($baseline, $data));

        return $revision;
    }

    public function applyRevision(News $news, ContentRevision $revision)
    {
        $news
            ->setTitle($revision->getTitle())
            ->setUrl($revision->getUrl())
            ->setDescription($revision->getDescription())
            ->setContents($revision->getContents())
            ->setPageTitle($revision->getPageTitle())
            ->setPageDescription($revision->getPageDescription())
            ->setPageKeyword($revision->getPageKeyword())
            ->setQa($revision->getQa())
            ->setTemplate($revision->getTemplate());
    }

    public function buildDiff(ContentRevision $left, ContentRevision $right)
    {
        $leftData = $this->extractRevisionData($left);
        $rightData = $this->extractRevisionData($right);

        return $this->buildDiffFromData($leftData, $rightData);
    }

    public function buildDiffWithNews(ContentRevision $revision, News $news)
    {
        return $this->buildDiffFromData($this->extractRevisionData($revision), $this->extractNewsData($news));
    }

    private function buildDiffFromData(array $leftData, array $rightData)
    {
        $leftData = $this->normalizeData($leftData);
        $rightData = $this->normalizeData($rightData);
        $diff = [];

        foreach ($this->fields as $field => $label) {
            if ($this->normalizeValue($field, $leftData[$field]) === $this->normalizeValue($field, $rightData[$field])) {
                continue;
            }

            $diff[] = [
                'field' => $field,
                'label' => $label,
                'left' => $leftData[$field],
                'right' => $rightData[$field],
            ];
        }

        return $diff;
    }

    public function buildDiffSummary(array $oldData, array $newData)
    {
        $oldData = $this->normalizeData($oldData);
        $newData = $this->normalizeData($newData);
        $changed = [];

        foreach ($this->fields as $field => $label) {
            if ($this->normalizeValue($field, $oldData[$field]) !== $this->normalizeValue($field, $newData[$field])) {
                $changed[] = $label;
            }
        }

        if (!$changed) {
            return 'Không có thay đổi nội dung';
        }

        return 'Thay đổi: ' . implode(', ', $changed);
    }

    public function extractNewsData(News $news)
    {
        return [
            'title' => $news->getTitle(),
            'url' => $news->getUrl(),
            'description' => $news->getDescription(),
            'contents' => $news->getContents(),
            'pageTitle' => $news->getPageTitle(),
            'pageDescription' => $news->getPageDescription(),
            'pageKeyword' => $news->getPageKeyword(),
            'qa' => $news->getQa(),
            'template' => $news->getTemplate(),
        ];
    }

    public function extractRevisionData(ContentRevision $revision)
    {
        return [
            'title' => $revision->getTitle(),
            'url' => $revision->getUrl(),
            'description' => $revision->getDescription(),
            'contents' => $revision->getContents(),
            'pageTitle' => $revision->getPageTitle(),
            'pageDescription' => $revision->getPageDescription(),
            'pageKeyword' => $revision->getPageKeyword(),
            'qa' => $revision->getQa(),
            'template' => $revision->getTemplate(),
        ];
    }

    public function hasChanged(ContentRevision $revision, array $data)
    {
        return $this->hasDataChanged($this->extractRevisionData($revision), $data);
    }

    public function hasDataChanged(array $oldData, array $newData)
    {
        $summary = $this->buildDiffSummary($oldData, $newData);

        return $summary !== 'Không có thay đổi nội dung';
    }

    private function createFromData(News $news, array $data, User $author = null, $revisionType = 'manual')
    {
        $data = $this->normalizeData($data);
        $summary = $this->buildDiffSummary($this->extractNewsData($news), $data);

        $revision = new ContentRevision();
        $revision
            ->setNews($news)
            ->setAuthor($author)
            ->setRevisionType($revisionType)
            ->setTitle($data['title'])
            ->setUrl($data['url'])
            ->setDescription($data['description'])
            ->setContents($data['contents'])
            ->setPageTitle($data['pageTitle'])
            ->setPageDescription($data['pageDescription'])
            ->setPageKeyword($data['pageKeyword'])
            ->setQa($data['qa'])
            ->setTemplate($data['template'])
            ->setDiffSummary($summary);

        return $revision;
    }

    private function normalizeData(array $data)
    {
        $normalized = [];

        foreach (array_keys($this->fields) as $field) {
            $normalized[$field] = array_key_exists($field, $data) ? $data[$field] : null;
        }

        return $normalized;
    }

    private function normalizeValue($field, $value)
    {
        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);

        if (in_array($field, ['contents', 'qa'], true)) {
            return $this->normalizeRichContent($value);
        }

        return $this->normalizeText($value);
    }

    private function normalizeRichContent($value)
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace('/\sdata-cke-[a-z0-9_-]+="[^"]*"/i', '', $value);
        $value = preg_replace('/\sdata-cke-[a-z0-9_-]+=\'[^\']*\'/i', '', $value);
        $value = preg_replace('/<!--.*?-->/s', '', $value);
        $value = preg_replace('/<br\s*\/?>/i', '<br>', $value);
        $value = preg_replace('/\s+\/>/', '>', $value);
        $value = preg_replace('/>\s+</u', '><', $value);
        $value = preg_replace('/[ \t\n]+/u', ' ', $value);

        return trim($value);
    }

    private function normalizeText($value)
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8')));
    }
}
