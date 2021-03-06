<?php
final class Image
{
    private function __construct()
    {
    }

    public static function isJpeg($fname)
    {
        return fnmatch('*.jpg', $fname) || fnmatch('*.jpeg', $fname);
    }

    public static function optimizeJpegFile($old, $new)
    {
        if (self::isJpeg($old)) {
            if (extension_loaded('imagick')) {
                $img = new Imagick($old);
                $img->setImageCompressionQuality(80);
                $img->setOption('jpeg:optimize-coding', 'on');
                $img->setOption('jpeg:dct-method', 'islow');
                $img->stripImage();
                $img->writeImage($new);
            } else if (extension_loaded('gd')) {
                $img = imagecreatefromjpeg($old);
                imagejpeg($img, $new, 80);
                imagedestroy($img);
            } else {
                throw new RuntimeException('No image processing module found.');
            }
        } else {
            copy($old, $new);
        }
    }

    public static function getExifDate($file)
    {
        $info = @exif_read_data($file);
        if (!$info || !isset($info['DateTimeOriginal'])) {
            return false;
        }
        $time = $info['DateTimeOriginal'];
        return strtotime($time);
    }

    public static function createThumbnail($origFile, $file, $mx, $my)
    {
        if (!is_file($origFile) || !self::isJpeg($origFile)) {
            return;
        }
        $imOrig = imagecreatefromjpeg($origFile);
        $sx = imagesx($imOrig);
        $sy = imagesy($imOrig);
        if ($sx * $my > $sy * $mx) {
            $width = $mx;
            $height = $sy * $mx / $sx;
        } else {
            $height = $my;
            $width =  $sx * $my / $sy;
        }
        $thumb = imagecreatetruecolor($width, $height);
        $success = false;
        if (imagecopyresampled($thumb, $imOrig, 0, 0, 0, 0, $width, $height, $sx, $sy)) {
            File::mkdir(dirname($file));
            imagejpeg($thumb, $file);
            $success = true;
        }
        imagedestroy($imOrig);
        return $success;
    }
}
