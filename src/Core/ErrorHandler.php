<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\HttpException;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Transforme toute exception non traitée en réponse HTTP présentable.
 *
 * Les erreurs serveur sont journalisées ; leur détail n'est affiché qu'en
 * mode débogage. Les erreurs 4xx affichent le message de l'exception HTTP.
 */
final class ErrorHandler
{
    /**
     * Titres affichés selon le code de statut.
     */
    private const array TITLES = [
        400 => 'Requête invalide',
        403 => 'Accès refusé',
        404 => 'Page introuvable',
        405 => 'Méthode non autorisée',
        419 => 'Session expirée',
        500 => 'Erreur interne',
    ];

    private const string SERVER_ERROR_MESSAGE = 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.';

    /**
     * @var Closure(string): void
     */
    private readonly Closure $logger;

    /**
     * @param View                       $view   Moteur de rendu des pages d'erreur.
     * @param bool                       $debug  Affiche le détail technique des erreurs serveur.
     * @param (Closure(string): void)|null $logger Journal des erreurs ; `error_log()` par défaut.
     */
    public function __construct(
        private readonly View $view,
        private readonly bool $debug = false,
        ?Closure $logger = null,
    ) {
        $this->logger = $logger ?? static function (string $message): void {
            error_log($message);
        };
    }

    /**
     * Produit la réponse correspondant à une exception.
     *
     * @param Throwable $exception Exception non traitée.
     *
     * @return Response
     */
    public function handle(Throwable $exception): Response
    {
        $status = $exception instanceof HttpException ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $isServerError = $status >= Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($isServerError) {
            ($this->logger)(sprintf('[%s] %s', $exception::class, (string) $exception));
        }

        $message = !$isServerError && $exception->getMessage() !== '' ? $exception->getMessage() : self::SERVER_ERROR_MESSAGE;
        $data = [
            'title' => self::TITLES[$status] ?? 'Erreur',
            'status' => $status,
            'message' => $message,
            'details' => $this->debug && $isServerError ? (string) $exception : null,
        ];

        try {
            return $this->view->render('errors/error', $data, $status);
        } catch (Throwable $renderingError) {
            ($this->logger)(sprintf('[%s] %s', $renderingError::class, (string) $renderingError));

            return new Response($data['title'] . ' — ' . $message, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }
}
