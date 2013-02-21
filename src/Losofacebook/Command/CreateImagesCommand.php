<?php

namespace Losofacebook\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Keboola\Csv\CsvFile;
use Losofacebook\Service\ImageService;
use Losofacebook\Image;
use Doctrine\DBAL\Connection;

use DateTime;

class CreateImagesCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('dev:create-images')
            ->setDescription('Creates images for users');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Will create images. brb ->");
        
        $db = $this->getDb();
        $imageservice = $this->getImageService();
        
        $images = $db->fetchAll("SELECT * FROM image WHERE type = 1");
        
        
        //$imageservice->createVersions($image['id']);
        
        foreach ($images as $image){
            $imageservice->createVersions($image['id']);
            $output->writeln("Douing image -> #{$image['id']}");
        }
        
        $images = $db->fetchAll("SELECT * FROM image WHERE type = 2");
        
        
        //$imageservice->createVersions($image['id']);
        
        foreach ($images as $image){
            $imageservice->createCorporateVersions($image['id']);
            $output->writeln("Douing image -> #{$image['id']}");
        }
    }

    /**
     * @return ImageService
     */
    public function getImageService()
    {
        return $this->getSilexApplication()['imageService'];
    }

    /**
     * @return Connection
     */
    public function getDb()
    {
        return $this->getSilexApplication()['db'];
    }
}
