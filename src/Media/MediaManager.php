<?php

namespace App\Media;

use App\Entity\Media;
use App\Entity\MediaFolder;
use App\Entity\User;
use App\Utils\Slugger;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class MediaManager
{
    const MAX_FILE_SIZE = 15728640; // 15 MB
    const MAX_IMAGE_PIXELS = 25000000;

    private $kernel;
    private $slugger;

    public function __construct(KernelInterface $kernel, Slugger $slugger)
    {
        $this->kernel = $kernel;
        $this->slugger = $slugger;
    }

    public function createFromUpload(UploadedFile $file, MediaFolder $folder = null, User $uploadedBy = null)
    {
        $this->assertAllowedFile($file);

        $now = new \DateTime();
        $relativeDir = 'uploads/media/' . $now->format('Y/m');
        $absoluteDir = $this->getWebRoot() . '/' . $relativeDir;

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $extension = $this->getExtensionForMimeType($file->getMimeType());
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slugifyVn($baseName);
        $safeName = trim(preg_replace('/[^a-z0-9-]+/', '-', $safeName), '-') ?: 'media';
        $filename = $safeName . '-' . substr(sha1(uniqid('', true)), 0, 10) . '.' . $extension;

        $file->move($absoluteDir, $filename);

        $relativePath = $relativeDir . '/' . $filename;
        $absolutePath = $this->getWebRoot() . '/' . $relativePath;
        $mimeType = $this->detectMimeType($absolutePath) ?: $file->getMimeType();
        $imageSize = $this->getImageSize($absolutePath);

        try {
            $this->assertStoredFileIsSafe($absolutePath, $mimeType, $imageSize);
        } catch (\InvalidArgumentException $exception) {
            @unlink($absolutePath);
            throw $exception;
        }

        $imageSize = $this->getImageSize($absolutePath);

        $media = new Media();
        $media
            ->setFilename($filename)
            ->setOriginalName($file->getClientOriginalName())
            ->setPath($relativePath)
            ->setMimeType($mimeType)
            ->setSize(filesize($absolutePath) ?: 0)
            ->setWidth($imageSize ? $imageSize[0] : null)
            ->setHeight($imageSize ? $imageSize[1] : null)
            ->setHash(hash_file('sha256', $absolutePath))
            ->setFolder($folder)
            ->setUploadedBy($uploadedBy);

        if ($imageSize) {
            $media->setThumbnailPath($this->createThumbnail($absolutePath, $relativeDir, $filename));
            $media->setWebpPath($this->createWebp($absolutePath, $relativeDir, $filename));
        }

        return $media;
    }

    public function deleteFiles(Media $media)
    {
        foreach ([$media->getPath(), $media->getThumbnailPath(), $media->getWebpPath()] as $path) {
            if (!$path) {
                continue;
            }

            $absolutePath = $this->getWebRoot() . '/' . ltrim($path, '/');

            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function assertAllowedFile(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('File upload không hợp lệ.');
        }

        if ($file->getSize() <= 0) {
            throw new \InvalidArgumentException('File rỗng, không thể upload.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File vượt quá dung lượng cho phép 15MB.');
        }

        $mimeType = $file->getMimeType();

        if (!array_key_exists($mimeType, $this->getAllowedMimeTypes())) {
            throw new \InvalidArgumentException('File không được hỗ trợ: ' . $mimeType);
        }
    }

    private function assertStoredFileIsSafe($absolutePath, $mimeType, array $imageSize = null)
    {
        if (!is_file($absolutePath)) {
            throw new \InvalidArgumentException('Không lưu được file upload.');
        }

        if (!array_key_exists($mimeType, $this->getAllowedMimeTypes())) {
            throw new \InvalidArgumentException('File không đúng định dạng sau khi upload: ' . $mimeType);
        }

        if (strpos($mimeType, 'image/') === 0) {
            if (!$imageSize) {
                throw new \InvalidArgumentException('File ảnh bị lỗi hoặc không đọc được.');
            }

            if (($imageSize[0] * $imageSize[1]) > self::MAX_IMAGE_PIXELS) {
                throw new \InvalidArgumentException('Ảnh quá lớn, vui lòng dùng ảnh dưới 25 megapixel.');
            }
        }
    }

    private function getAllowedMimeTypes()
    {
        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
    }

    private function getExtensionForMimeType($mimeType)
    {
        $allowed = $this->getAllowedMimeTypes();

        return isset($allowed[$mimeType]) ? $allowed[$mimeType] : 'bin';
    }

    private function detectMimeType($absolutePath)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo) {
                $mimeType = finfo_file($finfo, $absolutePath);
                finfo_close($finfo);

                return $mimeType ?: null;
            }
        }

        return function_exists('mime_content_type') ? mime_content_type($absolutePath) : null;
    }

    private function getImageSize($absolutePath)
    {
        $imageSize = @getimagesize($absolutePath);

        return $imageSize ?: null;
    }

    private function createThumbnail($absolutePath, $relativeDir, $filename)
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = $this->createImageResource($absolutePath);

        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $thumbWidth = 320;
        $thumbHeight = max(1, (int) round($height * ($thumbWidth / $width)));
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);

        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        $thumbRelativePath = $relativeDir . '/thumb-' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        imagejpeg($thumb, $this->getWebRoot() . '/' . $thumbRelativePath, 82);
        imagedestroy($thumb);
        imagedestroy($image);

        return $thumbRelativePath;
    }

    private function createWebp($absolutePath, $relativeDir, $filename)
    {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $image = $this->createImageResource($absolutePath);

        if (!$image) {
            return null;
        }

        $webpRelativePath = $relativeDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagewebp($image, $this->getWebRoot() . '/' . $webpRelativePath, 82);
        imagedestroy($image);

        return $webpRelativePath;
    }

    private function createImageResource($absolutePath)
    {
        $imageInfo = @getimagesize($absolutePath);

        if (!$imageInfo) {
            return null;
        }

        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($absolutePath);
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($absolutePath);
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($absolutePath);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null;
        }

        return null;
    }

    private function getWebRoot()
    {
        return realpath($this->kernel->getProjectDir() . '/public');
    }
}
