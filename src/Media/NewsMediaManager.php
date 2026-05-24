<?php

namespace App\Media;

use App\Entity\Media;
use App\Entity\News;
use App\Entity\NewsMedia;
use Doctrine\ORM\EntityManagerInterface;

class NewsMediaManager
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function syncAlbumFromJson(News $news, $json)
    {
        $items = json_decode((string) $json, true);

        if (!is_array($items)) {
            $items = [];
        }

        $existing = [];
        foreach ($this->getAlbumItems($news) as $newsMedia) {
            $existing[$newsMedia->getMedia()->getId()] = $newsMedia;
        }

        $keptMediaIds = [];
        $ordering = 0;

        foreach ($items as $item) {
            $mediaId = isset($item['id']) ? (int) $item['id'] : 0;

            if ($mediaId <= 0 || in_array($mediaId, $keptMediaIds, true)) {
                continue;
            }

            $media = $this->em->getRepository(Media::class)->find($mediaId);

            if (!$media || !$media->isImage()) {
                continue;
            }

            $newsMedia = isset($existing[$mediaId]) ? $existing[$mediaId] : new NewsMedia();
            $newsMedia
                ->setNews($news)
                ->setMedia($media)
                ->setOrdering($ordering++)
                ->setAltOverride(isset($item['alt']) ? $item['alt'] : null)
                ->setCaptionOverride(isset($item['caption']) ? $item['caption'] : null);

            $this->em->persist($newsMedia);
            $keptMediaIds[] = $mediaId;
        }

        foreach ($existing as $mediaId => $newsMedia) {
            if (!in_array($mediaId, $keptMediaIds, true)) {
                $this->em->remove($newsMedia);
            }
        }
    }

    public function getAlbumItems(News $news)
    {
        if (!$news->getId()) {
            return [];
        }

        return $this->em->getRepository(NewsMedia::class)->findOrderedByNews($news);
    }

    public function serializeAlbumForForm(News $news)
    {
        $items = [];

        foreach ($this->getAlbumItems($news) as $newsMedia) {
            $media = $newsMedia->getMedia();

            if (!$media) {
                continue;
            }

            $items[] = [
                'id' => $media->getId(),
                'name' => $media->getOriginalName(),
                'thumb' => '/' . ltrim($media->getThumbnailPath() ?: $media->getPath(), '/'),
                'url' => '/' . ltrim($media->getPath(), '/'),
                'alt' => $newsMedia->getAltOverride() ?: ($media->getAlt() ?: $media->getOriginalName()),
                'caption' => $newsMedia->getCaptionOverride() ?: ($media->getCaption() ?: ''),
            ];
        }

        return json_encode($items);
    }
}
