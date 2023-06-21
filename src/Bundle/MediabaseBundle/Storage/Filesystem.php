<?php
namespace Trollfjord\Bundle\MediaBaseBundle\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use \Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;
use \Trollfjord\Bundle\MediaBaseBundle\Entity\Media;

/**
 * Class Filesystem
 * @package Trollfjord\Bundle\MediaBaseBundle\Storage
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
class Filesystem implements StorageInterface
{

    /**
     * Path on FileSystem
     *
     * @var string
     */
    protected $path = null;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * {@inheritDoc}
     */
    public function __construct(string $path, EntityManagerInterface $entityManager)
    {
        if (! isset($path)) {
            throw new \Exception("path not set!");
        }

        $this->path = $path;
        $this->entityManager = $entityManager;

        if (! is_dir($this->path) && ! mkdir($this->path)) {
            throw new \Exception("path not exists and unable to create!");
        }
    }

    /**
     * @param MediaInterface $media
     * @param null $variation
     * @return string
     */
    private function getFileName(MediaInterface $media, $variation = null): string {
        if ($variation !== null) {
            if (! preg_match("/^[0-9a-z_]*$/Uis", $variation)) {
                throw new \Exception("Invalid variation format!");
            }
        }
        $file = $media->getFile();
        if($file) {
            $ext = substr($file->getClientOriginalName(), strrpos($file->getClientOriginalName(), ".") + 1);
            return $media->getId() . ($variation !== null ? "_" . $variation : '') . "." . $ext;
        }else {
            return $media->getId() . ($variation !== null ? '_' . $variation : '').".".$media->getExtension();
        }
    }

    public function getPathForMedia(MediaInterface $media, $useOriginalParent=false) {
        $_path = "";
        $_media = $media;
        $_parent = null;
        while(true) {
            if($_media->getIsDirectory()) {
                $_path = $_media->getName().DIRECTORY_SEPARATOR.$_path;
            }

            if($useOriginalParent) {
                $_oMedia = $this->entityManager->getUnitOfWork()->getOriginalEntityData($_media);
                $_parent = $_oMedia["parent"];
            }else {
                $_parent = $_media->getParent();
            }
            if($_parent === null) break;

            $_media = $_parent;
        }

        return $_path;
    }

    /**
     * Gibt den Dateipfad wieder
     * @param MediaInterface $media
     * @param bool $creatPath
     * @return string
     */
    public function getFilepath(MediaInterface $media, $creatPath=true)
    {
        $_path = $this->getPathForMedia($media);
        $_path = $this->path . '/' . $_path;
        if(!file_exists($_path) && $creatPath) {
            mkdir($_path, 0777, true);
        }
        return $_path;
    }

    /**
     * {@inheritDoc}
     */
    public function read(MediaInterface $media, $variation = null)
    {
        //var_dump($this->getFilepath($media).$this->getFileName($media, $variation));exit;
        return file_get_contents($this->getFilepath($media).$this->getFileName($media, $variation));
    }

    /**
     * {@inheritDoc}
     */
    public function write(MediaInterface $media, $variation = null)
    {
        if($media->getFile()) {
            try {
                $this->delete($media, $variation, true);
                $media->getFile()->move($this->getFilepath($media), $this->getFileName($media, $variation));
            }catch(FileException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Write data
     *
     * @param mixed $data
     * @param Media $media
     * @param mixed $variation
     * @return bool
     */
    public function writeData($data, MediaInterface $media, $variation = null) {
        return false !== file_put_contents($this->getFilepath($media).$this->getFileName($media, $variation), $data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(MediaInterface $media, $variation = null, $deleteAll = false, $butNotWithString = null)
    {
        $_mediaPath = $this->getPathForMedia($media);
        $_file = $this->getFilepath($media, $variation).$this->getFileName($media, $variation);


        if($media->getIsDirectory()) {
            return rmdir($this->path . '/' .$_mediaPath );
        }

        $return = false;
        $dirHandle = opendir($this->path."/".$_mediaPath);
        while (($file = readdir($dirHandle)) !== false) {
            if((preg_match("/" . $media->getId() . ($variation ? '_'.$variation : '') . "/Uis", $file))) {
                $return = unlink($this->path . '/' . $_mediaPath . $file);
            }
            if ($deleteAll) {
                if (preg_match("/^" . $media->getId() . ($variation ? '_' . $variation : '') . "_[0-9a-z_]*$/Uis", $file)) {
                    if (strpos($file, $butNotWithString) === false) {
                        $r = unlink($this->path . '/' . $_mediaPath . $file);
                    }
                }
            }
        }
        return $return;
    }

    /**
     * @param MediaInterface $media
     * @param MediaInterface|null $to
     * @return mixed|void
     */
    public function move(MediaInterface $media, MediaInterface $to=null) {
        $_mediaPath = $this->getPathForMedia($media, true);
        $pathTo = ($to!=null)? $this->getPathForMedia($to):'';

        if($media->getIsDirectory()) {
            if(file_exists($this->path . "/" . $pathTo . $media->getName())) {
                $media->setName($media->getName()."_".time());
            }
            //var_dump("mv '".$this->path . "/" . $_mediaPath."' '".$this->path . "/" . $pathTo . $media->getName()."'"); exit;
            //rename($this->path . "/" . $_mediaPath, $this->path . "/" . $pathTo . $media->getName());
            exec("mv '".$this->path . "/" . $_mediaPath."' '".$this->path . "/" . $pathTo . $media->getName()."'");
        }
        else {
            $dirHandle = opendir($this->path . "/" . $_mediaPath);
            while (($file = readdir($dirHandle)) !== false) {
                if ((preg_match("/" . $media->getId() . "/Uis", $file))) {
                    rename($this->path . '/' . $_mediaPath . $file, $this->path . '/' . $pathTo . $file);
                }
            }
            if (!$media->getIsDirectory() && count($media->getChildren()) > 0) {
                foreach ($media->getChildren() as $_media) {
                    $dirHandle = opendir($this->path . "/" . $_mediaPath);
                    while (($file = readdir($dirHandle)) !== false) {
                        if ((preg_match("/" . $_media->getId() . "/Uis", $file))) {
                            rename($this->path . '/' . $_mediaPath . $file, $this->path . '/' . $pathTo . $file);
                        }
                    }
                }
            }
        }

        $media->setParent($to);
        $this->entityManager->persist($media);
        $this->entityManager->flush($media);
    }

    /**
     * @param MediaInterface $newFolder
     * @return mixed|void
     * @throws \Exception
     */
    public function renameFolder(MediaInterface $folder) {
        $pathTo = $this->getFilepath($folder, false);
        $pathTo = substr($pathTo, 0, strlen($pathTo)-1);
        $oldPath = str_replace($folder->getName(), $folder->getOldName(), $pathTo);
        if(file_exists($pathTo)) {
            $prefix = '_'.time();
            $pathTo .= $prefix;
            $folder->setName($folder->getName().$prefix);
        }
        if(file_exists($oldPath) && $folder->getIsDirectory()) {
            rename($oldPath, $pathTo);
        }else {
            throw new \Exception('File "'.$oldPath.'" not found or pros not allowed!');
        }
    }

    public function creatFolder(MediaInterface $folder) {
        return $this->getFilepath($folder);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(MediaInterface $media, $variation = null)
    {
        $exists =  file_exists($this->getFilepath($media).$this->getFileName($media, $variation));
        if($exists) {
            $size = filesize($this->getFilepath($media).$this->getFileName($media, $variation));
            if(!$size || $size == 0) $exists = false;
        }
        return $exists;
    }
    
    /**
     * {@inheritDoc}
     * @see StorageInterface::getModificationTime()
     */
    public function getModificationTime(MediaInterface $media, $variation = null)
    {
        return filemtime($this->getFilepath($media, $variation));
    }

    public function getFileInfos(MediaInterface $media, $variation = null): array
    {
        if(preg_match("'image/'", $media->getMimeType())) {
            $path = $this->getFilepath($media).$this->getFileName($media, $variation);
            if(file_exists($path)) {
                $_data = getimagesize($path);
                if ($_data) {
                $res = new \stdClass();
                $res->width = ((int) $_data[0] > 0)? $_data[0]:null;
                $res->height = ((int) $_data[1] > 0)? $_data[1]:null;
                return (array) $res;
                } else {
                    //svg files: check if it is the mimeType 'image/svg+xml'
                    if ($media->getMimeType() == 'image/svg+xml') {
                        //get the dimensions of thew svg
                        $xml = simplexml_load_file($path);
                        if (is_object($xml) && (method_exists($xml, 'attributes'))) {
                            //check if width  and height exist
                            if (isset($xml->attributes()['width']) && isset($xml->attributes()['height'])){
                                $res = new \stdClass();
                                $res->width = ((int)$xml->attributes()['width'] > 0) ? (int)$xml->attributes()['width'] : null;
                                $res->height = ((int)$xml->attributes()['height'] > 0) ? (int)$xml->attributes()['height'] : null;
                                return (array)$res;
                            }
                        }
                    }
                }
            }
        }
        return [];
    }
}