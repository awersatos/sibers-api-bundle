<?php

namespace Sibers\ApiBundle\EventListener;

use Sibers\ApiBundle\Service\ErrorHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Sibers\ApiBundle\Exceptions\SibersApiException;

/**
 * {@inheritdoc}
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * @param ErrorHandler $errorHandler
     */
    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (strpos($event->getRequest()->getPathInfo(), '/api') !== 0) {
            return;
        }

        $e = $event->getException();
        if($e instanceof SibersApiException){
            $event->setResponse($e->getResponce());
            return;
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
        }

        $message = $e->getMessage();
        $code    = $e->getCode();
        $trace   = $e->getTraceAsString();

        $event->setResponse($this->errorHandler->getResponse($statusCode, $code, $message, $trace));
    }
}