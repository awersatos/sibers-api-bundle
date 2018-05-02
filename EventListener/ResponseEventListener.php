<?php

namespace Sibers\ApiBundle\EventListener;

use Sibers\ApiBundle\Service\ErrorHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

final class ResponseEventListener
{
    /**
     * @var array
     */
    private static $successStatusCodes = [
        Response::HTTP_OK,
        Response::HTTP_CREATED,
    ];

    /**
     * @var array
     */
    private static $validationFailedStatusCodes = [
        Response::HTTP_BAD_REQUEST,
        Response::HTTP_UNAUTHORIZED,
    ];

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
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $decoded = json_decode($event->getResponse()->getContent(), true);

        if (!$decoded || !count($decoded)) {
            return;
        }

        $statusCode = $event->getResponse()->getStatusCode();

        if (in_array($statusCode, self::$successStatusCodes)) {
            $result = [
                'status'   => 'success',
                'response' => $decoded,
            ];
            $event->getResponse()->setContent(json_encode($result));
        } elseif (isset($decoded['status']) && $decoded['status'] == 'error') {
            return;
        } elseif (
            in_array($statusCode, self::$validationFailedStatusCodes)
            && !empty($decoded['violations'])
        ) {
            $errors = array_map(function (array $violation) {
                return [
                    'code'        => isset($violation['code']) ? $violation['code'] : null,
                    'name'        => $violation['propertyPath'],
                    'description' => $violation['message'],
                ];
            }, $decoded['violations']);

            $result = [
                'status' => 'error',
                'errors' => $errors
            ];
            $event->getResponse()->setContent(json_encode($result));
        } else {
           $statusCode = $event->getResponse()->getStatusCode();
           $message = isset($decoded['message']) ? $decoded['message'] : '';

           $event->setResponse($this->errorHandler->getResponse($statusCode, -1, $message, 'Internal error'));
        }
    }
}