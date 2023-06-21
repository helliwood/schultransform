<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Trollfjord\Bundle\MediaBaseBundle\Arranger\Arranger;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaType;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Meta;
use Trollfjord\Bundle\MediaBaseBundle\Repository\MediaRepository;
use Trollfjord\Bundle\MediaBaseBundle\Storage\IndexCache;
use Trollfjord\Bundle\MediaBaseBundle\Storage\StorageInterface;
use Trollfjord\Bundle\MediaBaseBundle\Arranger\ArrangerInterface;
use Trollfjord\Bundle\MediaBaseBundle\Arranger\DefaultArranger;
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Trollfjord\Bundle\MediaBaseBundle\Form\Folder;
use Symfony\Component\Security\Core\Security;
use Trollfjord\Bundle\MediaBaseBundle\Storage\Store;

/**
 * Class MediaService
 * @package Trollfjord\Bundle\MediaBaseBundle\Service
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
class MediaService
{

    /**
     * @var array
     */
    protected array $filesnumber = [];

    /**
     * @var Store
     */
    protected Store $store;

    /**
     *
     * @var StorageInterface
     */
    protected StorageInterface $storage;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var Security
     */
    protected Security $security;

    /**
     *
     * @var ArrangerInterface[]
     */
    protected $arrangers = [];

    /**
     *
     * @var ArrangerInterface
     */
    protected $defaultArranger;

    /**
     * @var MediaServiceInterface[]
     */
    protected $mediaModulDependencies = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        Store                  $store,
        Arranger               $arranger,
        Security               $security
    )
    {
        $this->entityManager = $entityManager;
        $this->store = $store;
        $this->storage = $store->getStorage();
        $this->arrangers = $arranger->getArrangers();
        $this->security = $security;
    }

    /**
     * Gibt eine Media-Entity wieder
     *
     * @param int $mediaId
     * @return MediaInterface|null
     */
    public function getMediaById($mediaId)
    {
        /** @var Media $media */
        $media = $this->entityManager->getRepository(Media::class)->find($mediaId);
        return $media;
    }

    public function saveFolder(Media $entity)
    {
        if ($entity->getId() && $entity->getOldName()) {
            $this->getStorage()->renameFolder($entity);
        } else {
            $this->getStorage()->creatFolder($entity);
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush($entity);

        $this->getIndexCache()->deleteItem('index');
    }

    /**
     * Löscht eine Media-Entity inkl.
     * Daten
     *
     * @param MediaInterface $media
     */
    public function deleteMedia($media)
    {
        if (is_null($media->getParent()) && $this->security->isGranted('ROLE_MB_FILE_DELETE')) {
            throw new Exception('Keine Berechtigung!');
        }
        if ($media) {
            if ($media->getIsDirectory()) {
                if (!$this->checkPermissionsForFolder($media->getId())) {
                    throw new Exception('Keine Berechtigung!');
                }
                foreach ($media->getChildren() as $child) {
                    $this->deleteMedia($child);
                }
                $this->entityManager->remove($media);
                $this->entityManager->flush($media);
                try {
                    $this->getStorage()->delete($media, null, true);
                }catch (Exception $e){

                }

            } else {
                if ($media->getParent() && !$this->checkPermissionsForFolder(
                        $media->getParent()
                            ->getId())) {
                    throw new Exception('Keine Berechtigung!');
                }
                foreach ($media->getChildren() as $child) {
                    $this->deleteMedia($child);
                }

                /**
                 * @todo Wenn das Media-Object nicht gelöscht werden kann (foreign-keys) dann werden die Medien trotzdem gelöscht.
                 */
                $this->getStorage()->delete($media, null, true);
                $this->entityManager->remove($media);
                $this->entityManager->flush($media);
            }
        }
    }

    /**
     * Liefert alle Medien eines Parent
     *
     * @param int $parent
     * @return Paginator
     */
    public function fetchAll($parent = null, $type = null, $orderBy = null, $order = null)
    {
        if ($parent == null) {
            $qb = $this->entityManager
                ->getRepository(Media::class)
                ->createQueryBuilder('m')
                ->where('m.parent IS NULL');
        } else {
            $index = $this->getDirectoryIndex();
            if (!isset($index[$parent])) {
                throw new Exception("Ordner nicht vorhanden! (Fehlercode: 02)");
            }
            if (!$this->checkPermissionsForFolder($parent)) {
                throw new Exception("Keine Berechtigung für diesen Ordner!");
            }
            $qb = $this->entityManager
                ->getRepository(Media::class)
                ->createQueryBuilder('m')
                ->where('m.parent = :parent')
                ->setParameter('parent', $parent);
        }

        if ($type && $type != 'file') {
            $extension = $this->getAllowedExtensions($type);
            $qb->andWhere('(m.extension IN(:extension) OR (m.extension IS NULL OR m.isDirectory = 1))')->setParameter(
                'extension', $extension);
        }

        if ($orderBy) {
            if (!in_array($orderBy,
                [
                    'name',
                    'mimeType',
                    'fileSize',
                    'creationDate'
                ])) {
                $orderBy = 'name';
            }
            if (!in_array($order, [
                'ASC',
                'DESC'
            ])) {
                $order = 'ASC';
            }

            $qb->addOrderBy('m.isDirectory', 'DESC')->addOrderBy('m.' . $orderBy, $order);
        } else {
            $qb->addOrderBy('m.isDirectory', 'DESC')->addOrderBy('m.name', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Führt eine Suche auf den Medien durch.
     *
     * @param string $searchPhrase
     */
    public function searchMedia($searchPhrase)
    {
        $qb = $this->entityManager
            ->getRepository(Media::class)
            ->createQueryBuilder('m')
            ->addSelect('p')
            ->leftJoin('m.parent', 'p');

        $searchPhrases = $this->getSearchPhrasesFromString($searchPhrase);

        $i = 0;
        if (count($searchPhrases['or']) > 0) {
            $whereOr = new \Doctrine\ORM\Query\Expr\Orx();
            foreach ($searchPhrases['or'] as $keyword) {
                $qb->setParameter($i, '%' . $keyword . '%');
                $whereOr->add('(m.name LIKE ?' . $i . ' OR m.description LIKE ?' . $i . ')');
                $i++;
            }
            $qb->andWhere((string)$whereOr);
        }

        if (count($searchPhrases['not']) > 0) {
            $whereNot = new \Doctrine\ORM\Query\Expr\Andx();
            foreach ($searchPhrases['not'] as $keyword) {
                $qb->setParameter($i, '%' . $keyword . '%');
                $whereNot->add(
                    '((m.name NOT LIKE ?' . $i . ' OR m.name IS NULL) AND (m.description NOT LIKE ?' . $i .
                    ' OR m.description IS NULL))');
                $i++;
            }
            $qb->andWhere((string)$whereNot);
        }

        if (count($searchPhrases['must']) > 0) {
            $whereMust = new \Doctrine\ORM\Query\Expr\Andx();
            foreach ($searchPhrases['must'] as $keyword) {
                $qb->setParameter($i, '%' . $keyword . '%');
                $whereMust->add('(m.name LIKE ?' . $i . ' OR m.description LIKE ?' . $i . ')');
                $i++;
            }
            $qb->andWhere((string)$whereMust);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Gibt einen Array mit Suchworten und deren Bedeutung wieder
     *
     * @param string $searchPhrase
     */
    public function getSearchPhrasesFromString($searchPhrase)
    {
        $words = explode(" ", strtr(str_replace("%", "", $searchPhrase), "  ", " "));
        $result = [
            'or' => [],
            'not' => [],
            'must' => []
        ];

        foreach ($words as $word) {
            $word = trim($word);
            switch (substr($word, 0, 1)) {
                case '+':
                    $result['must'][] = substr($word, 1);
                    break;
                case '-':
                    $result['not'][] = substr($word, 1);
                    break;
                default:
                    $result['or'][] = $word;
                    break;
            }
        }

        return $result;
    }

    /**
     * Prüft ob der aktuelle Benutzer Zugang zu einem Ordner hat
     *
     * @param int $folderId
     * @return boolean
     */
    public function checkPermissionsForFolder($folderId)
    {
        return true;
    }

    /**
     * Baut einen Index über die Ordner mit ihren Root-Ordnern auf.
     *
     * Speichert den Index in einen Filecache.
     *
     * @return array
     */
    protected function getDirectoryIndex()
    {
        if ($this->getIndexCache()->hasItem('index')) {
            return unserialize($this->getIndexCache()->getItem('index'));
        } else {
            $qb = $this->entityManager
                ->getRepository(Media::class)
                ->createQueryBuilder('m', 'm.id')
                ->select('m', 'p')
                ->leftJoin('m.parent', 'p')
                ->where('m.isDirectory = 1')
                ->addOrderBy('m.parent', 'ASC')
                ->addOrderBy('m.name', 'ASC');

            $folders = $qb->getQuery()->getArrayResult();

            $index = [];
            $getRootByFolderId = null;
            $getRootByFolderId = function ($id, $firstRun = true) use ($folders, &$getRootByFolderId) {
                if ($firstRun && is_null($folders[$id]['parent'])) {
                    return null;
                }
                foreach ($folders as $folderId => $folder) {
                    if ($folderId == $id) {
                        if (is_null($folder['parent'])) {
                            return $id;
                        } else {
                            return $getRootByFolderId($folder['parent']['id'], false);
                        }
                    }
                }
            };
            foreach ($folders as $folderId => $folder) {
                $index[$folderId] = [
                    'root' => $getRootByFolderId($folderId)
                ];
            }
            $this->getIndexCache()->setItem('index', serialize($index));
            return $index;
        }
    }

    /**
     * Gibt die Ordnerstruktur wieder
     *
     * @return array
     */
    public function getFoldersAsDepthArray()
    {
        $qb = $this->entityManager
            ->getRepository(Media::class)
            ->createQueryBuilder('m', 'm.id')
            ->select('m', 'p')
            ->leftJoin('m.parent', 'p')
            ->where('m.isDirectory = 1')
            ->addOrderBy('m.parent', 'ASC');

        $folders = $qb->getQuery()->getResult();
        $_folders = [];
        /** @var Media $folder */
        foreach ($folders as $folder) {
            $_folders[$folder->getId()] = $folder;
        }
        $tree = [];

        $addFolderByParent = null;
        $addFolderByParent = function ($parent, $depth = 0) use (&$_folders, &$tree, &$addFolderByParent) {
            /**
             * @var int $folderId
             * @var Media $folder
             */
            foreach ($_folders as $folderId => $folder) {
                if (is_null($parent) && !$this->security->isGranted('ROLE_MB_ADMIN') && !$this->checkPermissionsForFolder($folderId)) {
                    unset($_folders[$folderId]);
                } else {
                    if ($parent == ($folder->getParent() ? $folder->getParent()->getId() : null)) {
                        $folder->setOptViewName(str_pad(' ' . $folder->getName(), $depth + strlen(' ' . $folder->getName()), '-', STR_PAD_LEFT));
                        $tree[] = $folder;
                        unset($_folders[$folderId]);
                        $addFolderByParent($folderId, $depth + 1);
                    }
                }
            }
        };

        $addFolderByParent(null);

        return $tree;
    }

    /**
     * Speichert die Hochgeladenen Dateien
     *
     * @param mixes $media
     * @param MediaInterface $parent
     */
    public function saveUploadedData($media, MediaInterface $parent = null)
    {
        if ($media instanceof Media && $media->getFile()) {
            $media = $this->_saveUploadFileData($media, $parent);
            if ($media->getThumbnail()) {
                $thumb = new Media();
                $thumb->setParent($media);
                $thumb->setFile($media->getThumbnail());
                $thumb = $this->_saveUploadFileData($thumb, $media);
            }
        }
    }

    /**
     *
     * @param type $file
     * @param type $data
     * @param MediaInterface $parent
     * @return Media
     * @throws Exception
     */
    private function _saveUploadFileData(MediaInterface $media, MediaInterface $parent = null)
    {

        $file = $media->getFile();
        $name = $this->getUniqueNameByParent($file->getClientOriginalName(), $parent);
        $ext = substr($name, strrpos($name, ".") + 1);

        if ($parent) {
            $media->setParent($parent);
        }

        $media->setFileSize($file->getSize());
        $media->setName($name);
        $media->setExtension($ext);
        $media->setMimeType($file->getMimeType());
        $media->setOwner($this->security->getUser());
        $this->entityManager->persist($media);
        $this->entityManager->flush($media);

        $this->getStorage()->write($media);
        $this->updateMetas($media);
        return $media;
    }

    public function updateUploadedData(Media $media, $parent)
    {
        $name = ($media->getName()) ? $media->getName() : '';
        $name = ($media->getFile()) ? $media->getFile()->getClientOriginalName() : $name;
        $ext = substr($name, strrpos($name, ".") + 1);

        if ($parent) {
            $media->setParent($parent);
        }

        if ($media->getName() == '') $media->setName($name);

        if ($media->getFile()) {
            $media->setFileSize($media->getFile()->getSize());
            $media->setExtension($ext);
            $media->setMimeType($media->getFile()->getMimeType());
            $this->getStorage()->delete($media, null, true, 'preview');
            $this->getStorage()->write($media);
            $this->updateMetas($media);
        }
        if ($media->getThumbnail()) {
            $thumb = $this->entityManager->getRepository(Media::class)->findOneBy(['parent' => $media->getId()]);
            if (!$thumb) $thumb = new Media();
            $thumb->setParent($media);
            $thumb->setFile($media->getThumbnail());
            $thumb = $this->_saveUploadFileData($thumb, $media);
            //$this->updateMetas($thumb);
        }
        $this->updateMetas($media);
        /*
        $nameExp = explode('.', trim($name) );
        if(end($nameExp) != $media->getExtension()) {
            $media->setName($media->getName().'.'.$media->getExtension());
            $this->getStorage()->rename($media);
        }else {
            $media->setName($name);
        }*/

        $this->entityManager->persist($media);
        $this->entityManager->flush($media);

        return;
    }

    private function updateMetas(Media $media)
    {
        $infos = $this->getStorage()->getFileInfos($media);
        /** @var MediaRepository $mediaRep */
        $mediaRep = $this->entityManager->getRepository(Media::class);
        /** @var Meta[] $metas */
        $metas = $mediaRep->getMetasAsKeyArray($media);
        foreach ($infos as $name => $val) {
            if (isset($metas[$name]) && $metas[$name]) {
                $metas[$name]->setValue($val);
                $this->entityManager->persist($metas[$name]);
                $this->entityManager->flush($metas[$name]);
            } else {
                $meta = new Meta();
                $meta->setName($name);
                $meta->setMedia($media);
                $meta->setValue($val);
                $this->entityManager->persist($meta);
                $this->entityManager->flush($meta);
            }
        }
    }

    /**
     * @param int $id
     * @param int $to
     */
    public function move($id, $to = null)
    {
        /** @var Media $media */
        $media = $this->entityManager->find(Media::class, $id);
        /** @var Media $parent */
        $parent = $this->entityManager->find(Media::class, $to);

        if ($media && (($parent && $parent->getIsDirectory()) || $parent == null)) {
            $this->storage->move($media, $parent);
        } else {
        }
    }

    /**
     * Gibt einen eindeutigen Dateinamen auf dieser <code>$parent</code> Ebene wieder.
     *
     * @param string $name
     * @param MediaInterface $parent
     * @return string
     */
    public function getUniqueNameByParent($name, MediaInterface $parent = null)
    {
        $name = $this->formatName($name);
        $files = $this->entityManager
            ->getRepository(Media::class)
            ->findBy(array(
                "parent" => $parent,
                "name" => $name
            ));
        if (count($files) > 0) {
            $qb = $this->entityManager
                ->getRepository(Media::class)
                ->createQueryBuilder('m');

            $qb->where('m.isDirectory = false')
                ->addOrderBy($qb->expr()
                    ->length('m.name'), 'DESC')
                ->addOrderBy('m.name', 'DESC');
            if (!is_null($parent)) {
                $qb->andWhere('m.parent = :parent')->setParameter('parent', $parent);
            } else {
                $qb->andWhere('m.parent IS NULL');
            }
            $nameWithoutExtension = substr($name, 0, strrpos($name, "."));
            $extension = substr($name, strrpos($name, "."));

            $qb->andWhere('m.name LIKE :name')->setParameter('name', $nameWithoutExtension . '_%' . $extension);

            $files = $qb->getQuery()->getResult();

            if (count($files) > 0 && preg_match('/^.*_([0-9]+)\..*$/Uis', $files[0]->getName(), $matches)) {
                $name = $nameWithoutExtension . '_' . ($matches[1] + 1) . $extension;
            } else {
                $name = $nameWithoutExtension . '_1' . $extension;
            }
        }

        return $name;
    }

    /**
     * Überprüft einen Namen und wandelt ihn ggf um
     *
     * @param string $name
     * @return string
     */
    public function formatName($name)
    {
        // Umlaute-Hack für jquery.form: das Plugin liefert den Doppelpunkt der Umlaute als unicode
        $name = json_decode(str_replace('\u0308', "e", json_encode($name)));
        $search = array(
            "Ä",
            "Ö",
            "Ü",
            "ä",
            "ö",
            "ü",
            "ß",
            " ",
            "--"
        );
        $replace = array(
            "Ae",
            "Oe",
            "Ue",
            "ae",
            "oe",
            "ue",
            "ss",
            "_",
            "-"
        );
        $name = str_replace($search, $replace, $name);
        /*$allowedChars = "abcdefghijklmnopqrstuvwxyz0123456789_-.";
        $newName = "";
        for ($i = 0; $i < mb_strlen($name); $i ++) {
            if (mb_stripos($allowedChars, mb_substr($name, $i, 1)) !== false) {
                $newName .= $name[$i];
            }
        }
        return $newName;*/
        return $name;
    }

    /**
     * Gibt das Ordnerformular wieder
     *
     * @param string $subjectMode
     * @return Folder
     */
    public function getFolderForm($subjectMode = false)
    {
        return new Folder($this->entityManager, $subjectMode);
    }

    /**
     * Gets Arranger by mimeType
     *
     * @param string $mimeType
     * @return ArrangerInterface
     */
    public function getArrangerByMimeType($mimeType)
    {
        foreach ($this->arrangers as $arrangerMimeType => $arranger) {
            if (preg_match('/^' . $arrangerMimeType . '$/Uism', $mimeType)) {
                return $arranger;
            }
        }
        return $this->getDefaultArranger();
    }

    /**
     *
     * @return ArrangerInterface
     */
    public function getDefaultArranger()
    {
        if (null === $this->defaultArranger) {
            $this->defaultArranger = new DefaultArranger();
            $this->defaultArranger->setStorage($this->getStorage());
        }
        return $this->defaultArranger;
    }

    /**
     *
     * @param ArrangerInterface $defaultArranger
     */
    public function setDefaultArranger($defaultArranger)
    {
        $this->defaultArranger = $defaultArranger;
    }

    /**
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     *
     * @param StorageInterface $storage
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     *
     * @return multitype:ArrangerInterface
     */
    public function getArrangers()
    {
        return $this->arrangers;
    }

    /**
     *
     * @param multitype:ArrangerInterface $arrangers
     */
    public function setArrangers($arrangers)
    {
        $this->arrangers = $arrangers;
    }

    public function getAllowedExtensions($type)
    {
        if (!is_null($type)) {
            switch ($type) {
                case 'bild':
                    $extension = [
                        'png',
                        'jpg',
                        'jpeg'
                    ];
                    break;
                case 'file':
                    $extension = [
                        'pdf'
                    ];
                    break;
                case 'audio':
                    $extension = [
                        'mp3'
                    ];
                    break;
            }
            return $extension;
        }
    }

    public function getTypeByExtension($extension)
    {
        $type = '';
        if (!is_null($extension)) {
            switch ($extension) {
                case 'png':
                    $type = 'bild';
                    break;
                case 'jpg':
                    $type = 'bild';
                    break;
                case 'jpeg':
                    $type = 'bild';
                    break;
                case 'pdf':
                    $type = 'file';
                    break;
                case 'mp3':
                    $type = 'audio';
                    break;
            }
            return $type;
        }
    }

    public function checkMediaUsages($id)
    {
        $media = $this->getMediaById($id);
        $type = $this->getTypeByExtension($media->getExtension());
        $isUsed = true;
        $used = $this->crawlAllMediaUsages($id);
        $message = '';

        if (count($used) > 0) {
            $isUsed = true;
            $message .= '<br/><b>Diese Datei wird verwendet in:</b><br/>';
            foreach ($used as $media) {
                $message .= $media['title'] . ' (ISBN: ' . $media['isbn'] . ') auf Seite ' . $media['page'] . ' !<br>';
            }
        } else {
            $isUsed = false;
            $message .= 'Diese Datei wird nicht benutzt.<br>';
        }

        return [
            'isUsed' => $isUsed,
            'message' => $message,
            'used' => $used
        ];
    }

    public function crawlAllMediaUsages($id)
    {
        $used = array();
        foreach ($this->getMediaModulDependencies() as $instanceKey => $instance) {
            $class = $this->getServiceLocator()->get($instanceKey);
            $classInterfaces = is_object($class) ? class_implements($class) : [];
            if (in_array(MediaServiceInterface::class, $classInterfaces)) {
                $temp = $this->getServiceLocator()
                    ->get($instanceKey)
                    ->isMediaUsed($id);
                $used = array_merge($temp, $used);
            }
        }
        return $used;
    }

    public function setMediaModulDependencies($mediaModulDependencies)
    {
        $this->mediaModulDependencies = $mediaModulDependencies;
    }

    public function getMediaModulDependencies()
    {
        return $this->mediaModulDependencies;
    }

    /**
     *
     * @return IndexCache
     */
    public function getIndexCache(): IndexCache
    {
        return $this->store->getStorageCache();
    }

    /**
     * @return array
     */
    public function getIcons()
    {
        $icons = [];
        foreach ($this->store->getMimeTypes() as $mimeType => $values) {
            foreach ($values['allowed_extensions'] as $ext) {
                $icons[$ext] = $values["icon"];
            }
        }

        return $icons;
    }


    /**
     * @var array
     */
    private static $filetypes = null;

    /**
     * @return array
     */
    public function getFiletypes()
    {
        if (self::$filetypes !== null) return self::$filetypes;

        $names = [];
        foreach ($this->store->getMimeTypes() as $mimeType => $values) {
            if (isset($values["filetype"])) {
                if (is_array($values["filetype"])) {
                    foreach ($values["filetype"] as $_name) {
                        if (!isset($names[$_name])) $names[$_name] = [];
                        foreach ($values['allowed_extensions'] as $ext) {
                            $names[$_name][] = $ext;
                        }
                    }
                } else {
                    $_name = $values["filetype"];
                    if (!isset($names[$_name])) $names[$_name] = [];
                    foreach ($values['allowed_extensions'] as $ext) {
                        $names[$_name][] = $ext;
                    }
                }
            }
        }
        self::$filetypes = $names;
        return self::$filetypes;
    }


    /* Root folder infos */
    /**
     * @return array
     */
    public function getRootFolderInfo(): array
    {
        $toReturn = [

            'isFolder' => true,
            'id' => 0,

        ];

        /** @var Media $media */
        $mediaFolders = $this->entityManager->getRepository(Media::class)->findBy(['isDirectory' => 1, 'parent' => null]);

        foreach ($mediaFolders as $folder) {

            if ($folder->getIsDirectory()) {
                $toReturn[$folder->getName()] = [
                    'isFolder'=> true,
                    'name' => $folder->getName(),
                    'id' => $folder->getId(),
                    'children' => $this->getFolder($folder)
                ];
            } else {
                $toReturn[] = [
                    'name' => $folder->getName(),
                    'id' => $folder->getId(),
                    'size' => (int)$folder->getFileSize()
                ];
                if ($folder->getFileSize()) {
                    $this->filesnumber[] = (int)$folder->getFileSize();
                }
            }

        }

        //pre
        $rawSize = array_sum($this->filesnumber);
        $folderSize = $this->formatFileSize($rawSize);

        return ['folderName' => 'Root',
            'id' => 0,
            'isFolder' => true,
            'tree' => $toReturn,
            'size' => $folderSize,
            'numberOfFiles' => count($this->filesnumber)
        ];

    }

    /* Individual folder infos */
    /**
     * Folder infos
     *
     * @param Media $media
     * @return array
     */
    public function getFolderDetails(Media $media): array
    {

        $toReturn = [

            'isFolder' => true,
            'id' => 0,

        ];
        $folderName = $media->getName();
        $folderId = $media->getId();

                foreach ($media->getChildren() as $child) {
                    if ($child->getIsDirectory()) {
                        $toReturn[$child->getName()] = [
                            'isFolder'=> true,
                            'name' => $child->getName(),
                            'id' => $child->getId(),
                            'children' => $this->getFolder($child)
                        ];
                    } else {
                        $toReturn[] = [
                            'name' => $child->getName(),
                            'id' => $child->getId(),
                            'size' => (int)$child->getFileSize()
                        ];
                        if ($child->getFileSize()) {
                            $this->filesnumber[] = (int)$child->getFileSize();
                        }
                    }
                }

        //pre
        $rawSize = array_sum($this->filesnumber);
        $folderSize = $this->formatFileSize($rawSize);

        return ['folderName' => $folderName,
            'id' => $folderId,
            'tree' => $toReturn,
            'size' => $folderSize,
            'numberOfFiles' => count($this->filesnumber),
        ];
    }

    /* Recursion function to build the folder structure */
    /**
     * @param $child
     * @return array
     */
    private function getFolder($child): array
    {
        $toReturn = [];
        foreach ($child->getChildren() as $child) {
            if ($child->getIsDirectory()) {
                $toReturn[$child->getName()] = [
                    'isFolder' => true,
                    'name' => $child->getName(),
                    'id' => $child->getId(),
                    'children' => $this->getFolder($child),
                ];
            } else {
                $this->filesnumber[] = (int)$child->getFileSize();
                $toReturn[] = [
                    'name' => $child->getName(),
                    'id' => $child->getId(),
                    'size' => (int)$child->getFileSize(),
                ];

            }
        }
        return $toReturn;

    }

    /* Retrieve the records that match the string given in the input search */
    /**
     * @param $word
     * @param null $filetype
     * @return array
     */
    public function searchForMedia($word, $filetype = null): array
    {
        $extensionArray = null;
        if ($filetype){
            $extensionArray = $this->getFiletypes()[$filetype];
        }

        $items = $this->entityManager
            ->getRepository(Media::class)->searchByWord($word, $extensionArray);

        return ["totalRows" => count($items), "items" => $items, "tree" => [], "icons" => $this->getIcons()];

    }

    /* Fetrieves the folder size base on the sum of the all file sizes */
    /**
     * @param $bytes
     * @return string
     */
    private function formatFileSize($bytes): string
    {
        $bytes = (int)$bytes;
        if ($bytes <= 0) {
            return '0 KB';
        }
        $names = array(
            'B',
            'Kb',
            'Mb',
            'Gb',
            'Tb'
        );
        $values = array(
            1,
            1024,
            1048576,
            1073741824,
            1099511627776
        );
        $i = log($bytes) / 6.9314718055994530941723212145818;
        return number_format($bytes / $values[$i], 1) . ' ' . $names[$i];
    }

}
