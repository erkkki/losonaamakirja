<?php

namespace Losofacebook\Service;
use Doctrine\DBAL\Connection;
use Imagick;
use ImagickPixel;
use Symfony\Component\HttpFoundation\Response;
use Losofacebook\Image;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Image service
 */
class ImageService
{
    const COMPRESSION_TYPE = Imagick::COMPRESSION_JPEG;

    /**
     * @var Connection
     */
    private $conn;



    /**
     * @param $basePath
     */
    public function __construct(Connection $conn, $basePath)
    {
        $this->conn = $conn;
        $this->basePath = $basePath;
    }

    /**
     * Creates image
     *
     * @param string $path
     * @param int $type
     * @return integer
     */
    public function createImage($path, $type)
    {
        $this->conn->insert(
            'image',
            [
                'upload_path' => $path,
                'type' => $type
            ]
        );
        $id = $this->conn->lastInsertId();

        $img = new Imagick($path);
        $img->setbackgroundcolor(new ImagickPixel('white'));
        $img = $img->flattenImages();

        $img->setImageFormat("jpeg");

        $img->setImageCompression(self::COMPRESSION_TYPE);
        $img->setImageCompressionQuality(90);
        $img->scaleImage(1200, 1200, true);
        $img->writeImage($this->basePath . '/' . $id);

        if ($type == Image::TYPE_PERSON) {
            $this->createVersions($id);
        } else {
            $this->createCorporateVersions($id);
        }
        return $id;
    }


    public function createCorporateVersions($id)
    {
        $img = new Imagick($this->basePath . '/' . $id);
        $img->thumbnailimage(290, 360, true);

        $geo = $img->getImageGeometry();

        $x = (290 - $geo['width']) / 2;
        $y = (360 - $geo['height']) / 2;

        $image = new Imagick();
        $image->newImage(290, 360, new ImagickPixel('white'));
        $image->setImageFormat('jpeg');
        $image->compositeImage($img, $img->getImageCompose(), $x, $y);

        $thumb = clone $image;
        $thumb->cropThumbnailimage(290, 360);
        $thumb->setImageCompression(self::COMPRESSION_TYPE);
        $thumb->setImageCompressionQuality(90);
        $thumb->writeImage($this->basePath . '/' . $id . '-thumb-corp290');
        //290x360
    }


    public function createVersions($id)
    {
        $imgsizes = [
            '70' => ['x' => 70,
                     'y' => 70],
            '260' => ['x' => 210,
                     'y' => 210],
            '75' => ['x' => 75,
                     'y' => 75],
            '50' => ['x' => 50,
                     'y' => 50],
            '20' => ['x' => 20,
                     'y' => 20],
            '126' => ['x' => 126,
                     'y' => 126]
        ];
        foreach ($imgsizes as $size){
            $img = new Imagick($this->basePath . '/' . $id);
            $thumb = clone $img;

            $thumb->cropThumbnailimage($size['x'], $size['y']);
            $thumb->setImageCompression(self::COMPRESSION_TYPE);
            $thumb->setImageCompressionQuality(90);
            $thumb->writeImage($this->basePath . '/' . $id . '-thumb-' . $size['x'] . '-' . $size['y']);
            if (!is_link('/home/user/losofacebook/web/images/' . $id . '-thumb-' . $size['x'] . '-' . $size['y'] . '.jpeg')) {
                symlink($this->basePath . '/' . $id . '-thumb-' . $size['x'] . '-' . $size['y'],
                    '/home/user/losofacebook/web/images/' . $id . '-thumb-' . $size['x'] . '-' . $size['y'] . '.jpeg');
            }    
            
        }
    }

    public function getImageResponse($id, $version = null)
    {
        $path = $this->basePath . '/' . $id;

        if ($version) {
            $path .= '-' . $version;
        }

        if (!is_readable($path)) {
            throw new NotFoundHttpException('Image not found');
        }

        $response = new Response();
        $response->setContent(file_get_contents($path));
        $response->headers->set('Content-type', 'image/jpeg');
        return $response;
    }
    

}
