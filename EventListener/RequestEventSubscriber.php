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
            $headers = $request->headers->all();

            if (!isset($headers['content-type'])) {
                $headers['content-type'] = [];
            }

            $idx = array_search('application/json', $headers['content-type']);
            if ($idx !== false) {
                $data = json_decode($request->getContent(), true);
                $event->getRequest()->request->replace($data);
                $headers['content-type'][$idx] = 'application/x-www-form-urlencoded';
                $event->getRequest()->headers->replace($headers);
            }
        }
    }

}