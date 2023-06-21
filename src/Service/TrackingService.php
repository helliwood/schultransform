<?php

namespace Trollfjord\Service;


use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class TrackingService {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ContainerBagInterface
     */
    private $params;

    /**
     * @var bool
     */
    private $allow = false;

    public function __construct(
        RequestStack $requestStack,
        ContainerBagInterface $params)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->params = $params;

        if(isset($this->request) && $this->request->cookies->has("cookieControlPrefs")) {
            $cookieControlPrefs = (object)json_decode($this->request->cookies->get("cookieControlPrefs"));
            if(isset($cookieControlPrefs->analytics) && $cookieControlPrefs->analytics) {
                $this->allow = true;
            }

        }
    }

    public function getTrackingCode() {
        if(!$this->allow) return '';

        return '<!-- Matomo -->
                <script type="text/javascript">
                  var _paq = window._paq = window._paq || [];
                  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
                  _paq.push(["setDoNotTrack", true]);
                  _paq.push([\'trackPageView\']);
                  _paq.push([\'enableLinkTracking\']);
                  (function() {
                    var u="https://www.hw-stats.de/";
                    _paq.push([\'setTrackerUrl\', u+\'matomo.php\']);
                    _paq.push([\'setSiteId\', \''.$this->params->get('tracking.matomo.site').'\']);
                    var d=document, g=d.createElement(\'script\'), s=d.getElementsByTagName(\'script\')[0];
                    g.type=\'text/javascript\'; g.async=true; g.src=u+\'matomo.js\'; s.parentNode.insertBefore(g,s);
                  })();
                </script>
                <noscript><p><img src="https://www.hw-stats.de/matomo.php?idsite='.$this->params->get('tracking.matomo.site').'&amp;rec=1" style="border:0;" alt="" /></p></noscript>
                <!-- End Matomo Code -->';
    }

}