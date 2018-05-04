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
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    const BASE_API_PATH = '/api';
    const ENV_PROD = 'prod';

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * @var string
     */
    private $env;

    /**
     * @param ErrorHandler $errorHandler
     * @param string       $env
     */
    public function __construct(ErrorHandler $errorHandler, $env)
    {
        $this->errorHandler = $errorHandler;
        $this->env          = $env;
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
        if (strpos($event->getRequest()->getPathInfo(), self::BASE_API_PATH) !== 0) {
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

        $stackTrace = null;
        if (self::ENV_PROD !== $this->env) {
            $stackTrace = $e->getTraceAsString();
        }

        $event->setResponse($this->errorHandler->getResponse($statusCode, $code, $message, $stackTrace));
    }
}