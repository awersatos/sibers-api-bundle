<?php

namespace Sibers\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $loginRoute;

    /**
     * @param string $loginRoute
     */
    public function __construct($loginRoute)
    {
        $this->loginRoute = $loginRoute;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['jsonToForm', 20]
            ]
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function jsonToForm(GetResponseEvent $event)
    {
        $request      = $event->getRequest();
        $currentRoute = $request->attributes->get('_route');

        if ($currentRoute === $this->loginRoute) {
            $contentType = $request->headers->get('content-type', null, false);

            $idx = false;
            foreach ($contentType as $key => $value) {
                if ($value === 'application/json' || 0 === strpos($value, 'application/json;')) {
                    $idx = $key;
                    break ;
                }
            }

            if ($idx !== false) {
                $data = json_decode($request->getContent(), true);
                $event->getRequest()->request->replace($data);
                $contentType[$idx] = 'application/x-www-form-urlencoded';
                $event->getRequest()->headers->set('content-type', $contentType);
            }
        }
    }

}