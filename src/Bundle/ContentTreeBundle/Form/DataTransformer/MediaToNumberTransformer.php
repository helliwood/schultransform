<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use function sprintf;

/**
 * MediaToNumberTransformer
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Form\DataTransformer
 */
class MediaToNumberTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $em;

    /**
     * MediaToNumberTransformer constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param object|string|null $media
     * @return int|string|null
     */
    public function transform($media)
    {
        if (null === $media) {
            return null;
        }
        return $media->getId();
    }

    /**
     * @param int|string|null $id
     * @return object|string|null
     */
    public function reverseTransform($id)
    {
        if (! $id) {
            return null;
        }

        $media = $this->em
            ->getRepository(Media::class)
            ->find($id);

        if (null === $media) {
            throw new TransformationFailedException(sprintf(
                'An media with id "%s" does not exist!',
                $id
            ));
        }

        return $media;
    }
}
