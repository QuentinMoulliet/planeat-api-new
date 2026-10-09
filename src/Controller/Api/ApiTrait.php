<?php

namespace App\Controller\Api;

use App\Entity\Household;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Shared helpers for API controllers: household scoping, JSON body, errors, dates.
 */
trait ApiTrait
{
    /**
     * Household of the authenticated user. Every query is scoped by it.
     */
    private function getHousehold(): Household
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getHousehold()) {
            throw new AccessDeniedException('No household found for current user.');
        }

        return $user->getHousehold();
    }

    /**
     * Decoded JSON body (empty body = empty array).
     */
    private function payload(Request $request): array
    {
        if ($request->getContent() === '') {
            return [];
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadRequestHttpException('JSON invalide.');
        }

        return $data;
    }

    /**
     * Error response with a user-facing (French) message.
     */
    private function fail(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return $this->json(['error' => $message, ...$extra], $status);
    }

    /**
     * 404 unless the entity exists and belongs to the current household.
     *
     * @template T of object
     *
     * @param T|null $entity
     *
     * @return T
     */
    private function owned(?object $entity, callable $householdOf, string $message): object
    {
        if (!$entity || $householdOf($entity) !== $this->getHousehold()) {
            throw new NotFoundHttpException($message);
        }

        return $entity;
    }

    private function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today');
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
