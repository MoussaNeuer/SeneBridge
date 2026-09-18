<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Moteur de vues PHP minimal (templates + layouts + sections).
 * Tous les templates héritent du mécanisme d'échappement HTML (fonction e()).
 *
 * Utilisation dans une vue (le $this du template est l'instance View) :
 *   $this->layout('layouts/auth');
 *   $this->section('title'); ... $this->endSection();
 *   $this->section('content'); ... $this->endSection();
 *
 * Dans le layout :
 *   <title><?= $this->yield('title') ?></title>
 *   <?= $this->yield('content') ?>
 */
final class View
{
    /** @var array<string, mixed> */
    private array $data;
    private string $layout = '';
    /** @var array<string, string> */
    private array $sections = [];
    /** @var array<int, string> */
    private array $sectionStack = [];
    private int $bufferLevel = 0;

    private function __construct(array $data)
    {
        $this->data = $data;
        $this->bufferLevel = ob_get_level();
    }

    public static function render(string $template, array $data = []): string
    {
        $view = new self($data);

        ob_start();
        try {
            $view->includeTemplate($template);

            if ($view->layout !== '') {
                $view->includeTemplate($view->layout);
            }

            $content = (string) ob_get_contents();
        } finally {
            // Restaure la pile de buffers à son niveau d'entrée (supporte
            // output_buffering=On, courriel (ob off) et embranchements imbriqués).
            while (ob_get_level() > $view->bufferLevel) {
                ob_end_clean();
            }
        }

        return $content;
    }

    public function includeTemplate(string $template): void
    {
        $path = views_path(str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php');

        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Vue introuvable : %s (%s)', $template, $path));
        }

        $data = $this->data;
        extract($data, EXTR_SKIP);

        include $path;
    }

    public function layout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        $content = ob_get_contents() ?: '';
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        if ($name !== null) {
            $this->sections[$name] = $content;
        }
    }

    public function yield(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public function include(string $template, array $data = []): void
    {
        $path = views_path(str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php');

        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Partiel introuvable : %s', $path));
        }

        $data = array_merge($this->data, $data);
        extract($data, EXTR_SKIP);

        include $path;
    }
}