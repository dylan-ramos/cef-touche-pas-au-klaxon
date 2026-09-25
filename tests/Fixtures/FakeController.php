<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur factice utilisé par les tests du répartiteur.
 */
final class FakeController
{
    public function index(): Response
    {
        return new Response('index');
    }

    public function show(int $id): Response
    {
        return new Response('show:' . $id);
    }

    public function notAResponse(): string
    {
        return 'texte';
    }
}
