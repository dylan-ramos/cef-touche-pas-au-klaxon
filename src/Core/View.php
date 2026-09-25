<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Moteur de rendu des gabarits PHP.
 *
 * Un gabarit est un fichier `.php` du répertoire des gabarits. Il reçoit ses
 * données sous forme de variables locales ainsi que la variable `$view`
 * (l'instance courante) pour inclure des fragments. Toute donnée dynamique
 * doit être affichée avec la fonction `e()`.
 */
final class View
{
    /**
     * @var array<string, mixed> Données communes à tous les gabarits.
     */
    private array $shared = [];

    /**
     * @param string $templateDir Répertoire contenant les gabarits.
     * @param string $layout      Gabarit de mise en page encapsulant chaque page.
     */
    public function __construct(
        private readonly string $templateDir,
        private readonly string $layout = 'layout',
    ) {
    }

    /**
     * Partage une donnée avec tous les gabarits rendus ensuite.
     *
     * @param string $key   Nom de la variable dans les gabarits.
     * @param mixed  $value Valeur partagée.
     *
     * @return void
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * Rend une page complète (gabarit inséré dans la mise en page).
     *
     * @param string               $template Nom du gabarit, relatif au répertoire des gabarits, sans extension.
     * @param array<string, mixed> $data     Données transmises au gabarit et à la mise en page.
     * @param int                  $status   Code de statut HTTP.
     *
     * @return Response
     */
    public function render(string $template, array $data = [], int $status = Response::HTTP_OK): Response
    {
        $content = $this->partial($template, $data);
        $html = $this->partial($this->layout, array_merge($data, ['content' => $content]));

        return new Response($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Rend un gabarit seul et retourne le HTML produit.
     *
     * @param string               $template Nom du gabarit, sans extension.
     * @param array<string, mixed> $data     Données transmises au gabarit.
     *
     * @return string
     *
     * @throws Throwable Toute exception levée par le gabarit, après nettoyage du tampon.
     */
    public function partial(string $template, array $data = []): string
    {
        $file = $this->resolve($template);
        $variables = array_merge($this->shared, $data);

        ob_start();
        try {
            (static function (string $__file, array $__variables, View $view): void {
                extract($__variables, EXTR_SKIP);
                require $__file;
            })($file, $variables, $this);
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return (string) ob_get_clean();
    }

    /**
     * Résout le chemin d'un gabarit en refusant toute sortie du répertoire.
     *
     * @param string $template Nom du gabarit.
     *
     * @return string Chemin absolu du fichier.
     *
     * @throws InvalidArgumentException Si le nom contient des caractères non autorisés.
     * @throws RuntimeException         Si le gabarit n'existe pas.
     */
    private function resolve(string $template): string
    {
        if (preg_match('~^[a-z0-9_-]+(/[a-z0-9_-]+)*$~', $template) !== 1) {
            throw new InvalidArgumentException(sprintf('Nom de gabarit invalide : %s.', $template));
        }

        $file = $this->templateDir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Gabarit introuvable : %s.', $template));
        }

        return $file;
    }
}
