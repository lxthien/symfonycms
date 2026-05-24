<?php

namespace App\Media;

use App\Entity\Media;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MediaSelectionManager
{
    private $em;
    private $kernel;
    private $slugger;

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel, Slugger $slugger)
    {
        $this->em = $em;
        $this->kernel = $kernel;
        $this->slugger = $slugger;
    }

    public function applyToNewsCategoryById(NewsCategory $category, $mediaId)
    {
        $mediaId = (int) $mediaId;

        if ($mediaId <= 0) {
            return false;
        }

        $media = $this->em->getRepository(Media::class)->find($mediaId);

        if (!$media) {
            throw new \InvalidArgumentException('Media không tồn tại.');
        }

        $this->applyToNewsCategory($category, $media);

        return true;
    }

    public function applyToNewsCategory(NewsCategory $category, Media $media)
    {
        if (!$media->isImage()) {
            throw new \InvalidArgumentException('Chỉ có thể chọn ảnh cho danh mục.');
        }

        $sourcePath = $this->getWebRoot() . '/' . ltrim($media->getPath(), '/');

        if (!is_file($sourcePath)) {
            throw new \InvalidArgumentException('File media không tồn tại trên máy chủ.');
        }

        $targetDir = $this->getWebRoot() . '/uploads/images/newscategory';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $extension = strtolower(pathinfo($media->getFilename(), PATHINFO_EXTENSION)) ?: 'jpg';
        $baseName = pathinfo($media->getOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slugifyVn($baseName);
        $safeName = trim(preg_replace('/[^a-z0-9-]+/', '-', $safeName), '-') ?: 'newscategory';
        $filename = $this->createUniqueFilename($targetDir, $safeName, $extension);
        $targetPath = $targetDir . '/' . $filename;

        if (!copy($sourcePath, $targetPath)) {
            throw new \RuntimeException('Không copy được ảnh từ Media Library.');
        }

        $this->deleteCurrentNewsCategoryImage($category, $filename);
        $category->setImages($filename);
        $category->setUpdatedAt(new \DateTime());
    }

    public function applyToNewsById(News $news, $mediaId)
    {
        $mediaId = (int) $mediaId;

        if ($mediaId <= 0) {
            return false;
        }

        $media = $this->em->getRepository(Media::class)->find($mediaId);

        if (!$media) {
            throw new \InvalidArgumentException('Media không tồn tại.');
        }

        $this->applyToNews($news, $media);

        return true;
    }

    public function applyToNews(News $news, Media $media)
    {
        if (!$media->isImage()) {
            throw new \InvalidArgumentException('Chỉ có thể chọn ảnh cho bài viết/page.');
        }

        $sourcePath = $this->getWebRoot() . '/' . ltrim($media->getPath(), '/');

        if (!is_file($sourcePath)) {
            throw new \InvalidArgumentException('File media không tồn tại trên máy chủ.');
        }

        $targetDir = $this->getWebRoot() . '/uploads/images/news';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $extension = strtolower(pathinfo($media->getFilename(), PATHINFO_EXTENSION)) ?: 'jpg';
        $baseName = pathinfo($media->getOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slugifyVn($baseName);
        $safeName = trim(preg_replace('/[^a-z0-9-]+/', '-', $safeName), '-') ?: 'news';
        $filename = $this->createUniqueFilename($targetDir, $safeName, $extension);
        $targetPath = $targetDir . '/' . $filename;

        if (!copy($sourcePath, $targetPath)) {
            throw new \RuntimeException('Không copy được ảnh từ Media Library.');
        }

        $this->deleteCurrentNewsImage($news, $filename);
        $news->setImages($filename);
        $news->setUpdatedAt(new \DateTime());
    }

    private function createUniqueFilename($targetDir, $safeName, $extension)
    {
        $suffix = substr(sha1(uniqid('', true)), 0, 10);
        $filename = $safeName . '-' . $suffix . '.' . $extension;

        while (is_file($targetDir . '/' . $filename)) {
            $suffix = substr(sha1(uniqid('', true)), 0, 10);
            $filename = $safeName . '-' . $suffix . '.' . $extension;
        }

        return $filename;
    }

    private function deleteCurrentNewsCategoryImage(NewsCategory $category, $newFilename)
    {
        $oldFilename = $category->getImages();

        if (!$oldFilename || $oldFilename === $newFilename) {
            return;
        }

        $oldPath = $this->getWebRoot() . '/uploads/images/newscategory/' . ltrim($oldFilename, '/');

        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    private function deleteCurrentNewsImage(News $news, $newFilename)
    {
        $oldFilename = $news->getImages();

        if (!$oldFilename || $oldFilename === $newFilename) {
            return;
        }

        $oldPath = $this->getWebRoot() . '/uploads/images/news/' . ltrim($oldFilename, '/');

        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    private function getWebRoot()
    {
        return realpath($this->kernel->getProjectDir() . '/public');
    }
}
