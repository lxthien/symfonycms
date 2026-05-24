<?php

namespace App\Media;

use App\Entity\Media;
use App\Entity\MediaFolder;
use App\Entity\MediaTag;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class MediaLibraryManager
{
    const MAX_UPLOAD_FILES = 20;

    private $em;
    private $mediaManager;

    public function __construct(EntityManagerInterface $em, MediaManager $mediaManager)
    {
        $this->em = $em;
        $this->mediaManager = $mediaManager;
    }

    public function normalizeFilters(array $filters)
    {
        return [
            'q' => trim((string) (isset($filters['q']) ? $filters['q'] : '')),
            'folder' => (string) (isset($filters['folder']) ? $filters['folder'] : ''),
            'tag' => (string) (isset($filters['tag']) ? $filters['tag'] : ''),
            'type' => (string) (isset($filters['type']) ? $filters['type'] : ''),
            'alt' => (string) (isset($filters['alt']) ? $filters['alt'] : ''),
        ];
    }

    public function createFilteredQueryBuilder(array $filters)
    {
        return $this->em->getRepository(Media::class)
            ->createFilteredQueryBuilder($this->normalizeFilters($filters));
    }

    public function getFolders()
    {
        return $this->em->getRepository(MediaFolder::class)->findBy([], ['name' => 'ASC']);
    }

    public function getTags()
    {
        return $this->em->getRepository(MediaTag::class)->findBy([], ['name' => 'ASC']);
    }

    public function uploadBatch(array $files, MediaFolder $folder = null, $newFolderName = null, $tagsText = null, User $uploadedBy = null)
    {
        $folder = $this->resolveFolder($folder, $newFolderName);
        $tags = $this->resolveTags($tagsText);
        $uploaded = 0;
        $errors = [];

        if (count($files) > self::MAX_UPLOAD_FILES) {
            return [
                'uploaded' => 0,
                'errors' => ['Chỉ upload tối đa ' . self::MAX_UPLOAD_FILES . ' file mỗi lần.'],
            ];
        }

        foreach ($files as $file) {
            try {
                $media = $this->mediaManager->createFromUpload($file, $folder, $uploadedBy);

                foreach ($tags as $tag) {
                    $media->addTag($tag);
                }

                $this->em->persist($media);
                $uploaded++;
            } catch (\InvalidArgumentException $exception) {
                $errors[] = $file->getClientOriginalName() . ': ' . $exception->getMessage();
            } catch (\RuntimeException $exception) {
                $errors[] = $file->getClientOriginalName() . ': Không lưu được file upload.';
            }
        }

        if ($uploaded > 0) {
            $this->em->flush();
        }

        return [
            'uploaded' => $uploaded,
            'errors' => $errors,
        ];
    }

    public function updateMetadata(Media $media, $newFolderName = null, $tagsText = null)
    {
        $media->setFolder($this->resolveFolder($media->getFolder(), $newFolderName));
        $this->syncTags($media, $tagsText);
        $this->em->flush();
    }

    public function delete(Media $media)
    {
        $this->mediaManager->deleteFiles($media);
        $this->em->remove($media);
        $this->em->flush();
    }

    public function getTagsText(Media $media)
    {
        $names = [];

        foreach ($media->getTags() as $tag) {
            $names[] = $tag->getName();
        }

        return implode(', ', $names);
    }

    private function resolveFolder(MediaFolder $selectedFolder = null, $newFolderName = null)
    {
        $newFolderName = trim((string) $newFolderName);

        if ($newFolderName === '') {
            return $selectedFolder;
        }

        $folder = $this->em->getRepository(MediaFolder::class)->findOneBy(['name' => $newFolderName]);

        if ($folder) {
            return $folder;
        }

        $folder = (new MediaFolder())->setName($newFolderName);
        $this->em->persist($folder);

        return $folder;
    }

    private function resolveTags($tagsText)
    {
        $tags = [];
        $names = array_filter(array_map('trim', preg_split('/[,;]+/', (string) $tagsText)));

        foreach (array_unique($names) as $name) {
            $tag = $this->em->getRepository(MediaTag::class)->findOneBy(['name' => $name]);

            if (!$tag) {
                $tag = (new MediaTag())->setName($name);
                $this->em->persist($tag);
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    private function syncTags(Media $media, $tagsText)
    {
        $media->clearTags();

        foreach ($this->resolveTags($tagsText) as $tag) {
            $media->addTag($tag);
        }
    }
}
