<?php

namespace Trollfjord\Service;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Trollfjord\Service\ChartDataService;
use Trollfjord\Service\QuestionaireDataService;

/**
 * Class ChartService
 * @package Trollfjord\Bundle\MediaBaseBundle\Service
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
class ChartService
{
    /**
     * @param Request $request
     * @return false|JsonResponse
     */
    public function execute(Request $request) {
        if ($request->isXmlHttpRequest()) {
            //TODO: CHANGE FROM INTERNAL REQUEST TO DIRECT DATAREQUEST FROM SERVICE
            $url = 'http://'.$_SERVER['HTTP_HOST'];
            switch($request->query->get('chart')) {
                case 'rose':
                    return new JsonResponse(json_decode($this->chartDataService->getFullResults()));
                case 'results':
                    return new JsonResponse(json_decode($this->chartDataService->getFullResults()));
            }
        }

        return false;
    }


    public function __construct(QuestionaireDataService $chartDataService){
        $this->chartDataService=$chartDataService;
    }

}