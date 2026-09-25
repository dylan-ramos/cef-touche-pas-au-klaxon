<?php

declare(strict_types=1);

namespace App\Core\Session;

/**
 * Messages à afficher une seule fois, après une redirection.
 *
 * Typiquement : « Le trajet a été créé » affiché sur la liste des trajets
 * après l'enregistrement du formulaire.
 */
final class Flash
{
    public const string SUCCESS = 'success';
    public const string ERROR = 'danger';
    public const string INFO = 'info';

    private const string SESSION_KEY = '_flash';

    /**
     * @param Session $session Stockage des messages entre deux requêtes.
     */
    public function __construct(private readonly Session $session)
    {
    }

    /**
     * Ajoute un message de réussite.
     *
     * @param string $message Texte du message.
     *
     * @return void
     */
    public function success(string $message): void
    {
        $this->add(self::SUCCESS, $message);
    }

    /**
     * Ajoute un message d'erreur.
     *
     * @param string $message Texte du message.
     *
     * @return void
     */
    public function error(string $message): void
    {
        $this->add(self::ERROR, $message);
    }

    /**
     * Ajoute un message d'information.
     *
     * @param string $message Texte du message.
     *
     * @return void
     */
    public function info(string $message): void
    {
        $this->add(self::INFO, $message);
    }

    /**
     * Retourne les messages en attente et les retire de la session.
     *
     * @return list<array{type: string, message: string}>
     */
    public function consume(): array
    {
        $messages = $this->pending();
        $this->session->remove(self::SESSION_KEY);

        return $messages;
    }

    /**
     * Ajoute un message typé.
     *
     * @param string $type    Type de message (constante de la classe).
     * @param string $message Texte du message.
     *
     * @return void
     */
    private function add(string $type, string $message): void
    {
        $messages = $this->pending();
        $messages[] = ['type' => $type, 'message' => $message];
        $this->session->set(self::SESSION_KEY, $messages);
    }

    /**
     * Messages en attente, en ignorant toute donnée de session mal formée.
     *
     * @return list<array{type: string, message: string}>
     */
    private function pending(): array
    {
        $stored = $this->session->get(self::SESSION_KEY, []);
        $messages = [];

        foreach (is_array($stored) ? $stored : [] as $item) {
            if (is_array($item) && is_string($item['type'] ?? null) && is_string($item['message'] ?? null)) {
                $messages[] = ['type' => $item['type'], 'message' => $item['message']];
            }
        }

        return $messages;
    }
}
