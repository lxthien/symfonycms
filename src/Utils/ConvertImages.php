<?php

namespace App\Utils;

class ConvertImages
{
    private function resolvePaths($file, $prifexPath, $filter, $stripResolve = false)
    {
        if ($filter) {
            $strpos = strpos($file, 'media/cache');
            if (false === $strpos) {
                return null;
            }
            $webPath = mb_substr($file, $strpos, strlen($file), 'UTF-8');
            if ($stripResolve) {
                $webPath = str_replace('/resolve', '', $webPath);
            }
        } else {
            if (preg_match('#^https?://#i', $file)) {
                $parsedPath = parse_url($file, PHP_URL_PATH);
                $file = $parsedPath !== null ? $parsedPath : $file;
            }
            $webPath = ltrim((string) $file, '/');
        }

        if ($webPath === '') {
            return null;
        }

        if (@file_exists($webPath)) {
            $diskFile = $webPath;
            $diskPrefix = '';
        } elseif (@file_exists('public/' . $webPath)) {
            $diskFile = 'public/' . $webPath;
            $diskPrefix = 'public/';
        } else {
            return null;
        }

        $outputWebPath = $prifexPath . $webPath . '.webp';
        $outputDiskPath = $diskPrefix . $outputWebPath;

        return array($diskFile, $outputWebPath, $outputDiskPath);
    }

    public function webpConvert2($file, $prifexPath, $filter = false, $compression_quality = 70)
    {
        $paths = $this->resolvePaths($file, $prifexPath, (bool) $filter, false);
        if ($paths === null) {
            return false;
        }

        list($file, $output_file, $output_disk_file) = $paths;

        $file_type = exif_imagetype($file);

        //https://www.php.net/manual/en/function.exif-imagetype.php
        //exif_imagetype($file);
        // 1    IMAGETYPE_GIF
        // 2    IMAGETYPE_JPEG
        // 3    IMAGETYPE_PNG
        // 6    IMAGETYPE_BMP
        // 15   IMAGETYPE_WBMP
        // 16   IMAGETYPE_XBM

        if (@file_exists($output_disk_file)) {
            return $output_file;
        }

        if (function_exists('imagewebp')) {
            switch ($file_type) {
                case '1': //IMAGETYPE_GIF
                    $image = imagecreatefromgif($file);
                    break;
                case '2': //IMAGETYPE_JPEG
                    $image = imagecreatefromjpeg($file);
                    break;
                case '3': //IMAGETYPE_PNG
                        $image = imagecreatefrompng($file);
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        break;
                case '6': // IMAGETYPE_BMP
                    $image = imagecreatefrombmp($file);
                    break;
                case '15': //IMAGETYPE_Webp
                return false;
                    break;
                case '16': //IMAGETYPE_XBM
                    $image = imagecreatefromxbm($file);
                    break;
                default:
                    return false;
            }
            
            // Save the image
            $result = imagewebp($image, $output_disk_file, $compression_quality);
            if (false === $result) {
                return false;
            }
            
            // Free up memory
            imagedestroy($image);
            return $output_file;
        } elseif (class_exists('Imagick')) {
            $image = new Imagick();
            $image->readImage($file);
            
            if ($file_type === "3") {
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality($compression_quality);
                $image->setOption('webp:lossless', 'true');
            }
            
            $image->writeImage($output_disk_file);
            return $output_file;
        }
        return false;
    }

    public function webpConvert($file, $prifexPath, $filter = false, $compression_quality = 70)
    {
        $paths = $this->resolvePaths($file, $prifexPath, (bool) $filter, true);
        if ($paths === null) {
            return false;
        }

        list($file, $output_file, $output_disk_file) = $paths;

        $file_type = exif_imagetype($file);

        if (@file_exists($output_disk_file)) {
            return $output_file;
        }

        if (function_exists('imagewebp')) {
            switch ($file_type) {
                case '1': //IMAGETYPE_GIF
                    $image = imagecreatefromgif($file);
                    break;
                case '2': //IMAGETYPE_JPEG
                    $image = imagecreatefromjpeg($file);
                    break;
                case '3': //IMAGETYPE_PNG
                        $image = imagecreatefrompng($file);
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        break;
                case '6': // IMAGETYPE_BMP
                    $image = imagecreatefrombmp($file);
                    break;
                case '15': //IMAGETYPE_Webp
                return false;
                    break;
                case '16': //IMAGETYPE_XBM
                    $image = imagecreatefromxbm($file);
                    break;
                default:
                    return false;
            }
            
            // Save the image
            $result = imagewebp($image, $output_disk_file, $compression_quality);
            if (false === $result) {
                return false;
            }
            
            // Free up memory
            imagedestroy($image);
            return $output_file;
        } elseif (class_exists('Imagick')) {
            $image = new Imagick();
            $image->readImage($file);
            
            if ($file_type === "3") {
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality($compression_quality);
                $image->setOption('webp:lossless', 'true');
            }
            
            $image->writeImage($output_file);
            return $output_file;
        }
        return false;
    }

    public function webpFileExists($file, $filterSets)
    {
        $outputFile = $filterSets . $file . '.webp';
        
        if (@file_exists($outputFile)) {
            return true;
        }

        return false;
    }

    public function fileExists($file)
    {
        $file = substr($file, 1);
        
        if (@file_exists($file)) {
            return true;
        }

        return false;
    }
}
