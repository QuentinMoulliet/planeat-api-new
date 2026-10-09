<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders every exception thrown under /api as JSON: {"error": "..."}.
 * Unexpected errors are logged and hidden behind a generic message (except in debug).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ApiExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage() ?: 'Requête invalide.'],
                $exception->getStatusCode(),
                $exception->getHeaders(),
            ));

            return;
        }

        // Let the security layer handle authentication / authorization exceptions
        if ($exception instanceof \Symfony\Component\Security\Core\Exception\AuthenticationException
            || $exception instanceof \Symfony\Component\Security\Core\Exception\AccessDeniedException) {
            return;
        }

        $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        $event->setResponse(new JsonResponse(
            ['error' => $this->debug ? $exception->getMessage() : 'Une erreur est survenue.'],
            500,
        ));
    }
}
