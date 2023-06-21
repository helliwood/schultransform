<?php

namespace Trollfjord\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;

class CacheQuestionnaireMediaService
{

    /**
     * @var HttpClientInterface $client
     */
    protected HttpClientInterface $client;

    /**
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CacheInterface $cache
     * @param HttpClientInterface $client
     */
    public function __construct(EntityManagerInterface $entityManager, CacheInterface $cache, HttpClientInterface $client)
    {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->client = $client;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function cacheQuestionnaires()
    {
//       $this->cache->delete('questionnaire.cache'); //remove for production
        return $this->cache->get('questionnaire.cache', function (ItemInterface $item) {

            try {

                $item->expiresAt(new \DateTime('tomorrow')); //add for production
                $url = 'https://partner.schultransform.org/MediaShare/questionnaires';

                $response = $this->client->request(
                    'GET',
                    $url,
                    [
                        'auth_basic' => ['the-username', 'the-password'],
                    ]
                );
                $responseToReturn = $response->toArray();
                if ($responseToReturn) {

                    if (isset($responseToReturn['success']) && $responseToReturn['success']
                    && isset($responseToReturn['data'])){
                        return $responseToReturn['data'];
                    }
                }



            } catch (Exception $exception) {

            }

        });

    }


}