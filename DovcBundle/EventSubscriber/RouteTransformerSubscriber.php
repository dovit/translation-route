<?php

namespace DovcBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\DataCollectorTranslator;

class RouteTransformerSubscriber implements EventSubscriberInterface
{
    private $translator;
    private $requestStack;
    private $router;

    /**
     * RouteTransformerSubscriber constructor.
     * @param DataCollectorTranslator $translator
     * @param RequestStack $requestStack
     * @param Router $router
     */
    public function __construct(DataCollectorTranslator $translator, RequestStack $requestStack, Router $router)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(
                array('transform', 33)
            )
        );
    }

    public function transform(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        $path = '';
        $messages = $this->translator->getCatalogue($request->attributes->get('_locale'))->all('routing');

        $uriArray = explode('/', $request->getPathInfo());
        foreach ($uriArray as $key => $item) {
            $tmp = array_search($item, $messages);
            $path .= (($tmp === false) ? $item : $tmp);

            $path .= (count($uriArray) - 1) == $key ? '' : '/';
        }

        try
        {
            $routeInformation = $this->router->match($path);
            $request->attributes->set('_controller', $routeInformation['_controller']);
            $request->attributes->set('_route', $routeInformation['_route']);
        }
        catch (\Exception $e)
        {
            dump($e->getMessage());
            exit;
        }
    }
}
