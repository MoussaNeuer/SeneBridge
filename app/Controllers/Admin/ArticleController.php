<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Article;
use App\Repositories\ArticleRepository;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;
use App\Support\Str;
use App\Validators\ArticleValidator;

final class ArticleController extends Controller
{
    private ArticleRepository $articles;

    public function __construct()
    {
        parent::__construct();
        $this->articles = new ArticleRepository();
    }

    public function index(): Response
    {
        $page = max(1, (int) $this->request->query('page', 1));

        return Response::view('admin/articles/index', [
            'user' => App::user(),
            'pagination' => $this->articles->adminPaginated(20, $page),
        ]);
    }

    public function create(): Response
    {
        return Response::view('admin/articles/create', [
            'user' => App::user(),
        ]);
    }

    public function store(): Response
    {
        $data = $this->request->only(['title', 'excerpt', 'body', 'cover_url', 'status']);
        $validation = ArticleValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $slug = Str::slug((string) $data['title']);
        $base = $slug;
        $i = 2;

        while (Article::findBySlug($slug) !== null) {
            $slug = $base . '-' . $i++;
        }

        $status = in_array($data['status'] ?? '', Article::STATUSES, true) ? $data['status'] : 'brouillon';

        $article = Article::create([
            'title' => trim((string) $data['title']),
            'slug' => $slug,
            'excerpt' => trim((string) ($data['excerpt'] ?? '')) !== '' ? trim((string) $data['excerpt']) : null,
            'body' => trim((string) $data['body']),
            'cover_url' => trim((string) ($data['cover_url'] ?? '')) !== '' ? trim((string) $data['cover_url']) : null,
            'status' => $status,
            'author_id' => App::id(),
            'published_at' => $status === 'publie' ? date('Y-m-d H:i:s') : null,
        ]);

        if ($article === null) {
            App::flash('error', 'Impossible de créer l\'article.');

            return Response::redirectBack();
        }

        Audit::log('articles.created', 'articles', (int) $article['id'], [], ['title' => $article['title'], 'status' => $status]);
        App::flash('success', sprintf('Article « %s » créé (slug : %s).', $article['title'], $article['slug']));

        return Response::redirect(route('admin.articles'));
    }

    public function edit(string $publicId): Response
    {
        $article = Article::findByPublicId($publicId);

        if ($article === null) {
            return Response::notFound('Article introuvable.');
        }

        return Response::view('admin/articles/create', [
            'user' => App::user(),
            'article' => $article,
        ]);
    }

    public function update(string $publicId): Response
    {
        $article = Article::findByPublicId($publicId);

        if ($article === null) {
            return Response::notFound('Article introuvable.');
        }

        $data = $this->request->only(['title', 'excerpt', 'body', 'cover_url', 'status']);
        $validation = ArticleValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $status = in_array($data['status'] ?? '', Article::STATUSES, true) ? $data['status'] : $article['status'];
        $wasPublished = $article['status'] === 'publie';
        $nowPublished = $status === 'publie';

        Article::update((int) $article['id'], [
            'title' => trim((string) $data['title']),
            'excerpt' => trim((string) ($data['excerpt'] ?? '')) !== '' ? trim((string) $data['excerpt']) : null,
            'body' => trim((string) $data['body']),
            'cover_url' => trim((string) ($data['cover_url'] ?? '')) !== '' ? trim((string) $data['cover_url']) : null,
            'status' => $status,
            'published_at' => $nowPublished && !$wasPublished ? date('Y-m-d H:i:s') : $article['published_at'],
        ]);

        Audit::log('articles.updated', 'articles', (int) $article['id'], [], ['title' => $data['title'], 'status' => $status]);
        App::flash('success', 'L\'article a été mis à jour.');

        return Response::redirect(route('admin.articles'));
    }

    public function toggle(string $publicId): Response
    {
        $article = Article::findByPublicId($publicId);

        if ($article === null) {
            return Response::notFound('Article introuvable.');
        }

        $status = $article['status'] === 'publie' ? 'brouillon' : 'publie';

        Article::update((int) $article['id'], [
            'status' => $status,
            'published_at' => $status === 'publie' ? date('Y-m-d H:i:s') : null,
        ]);

        Audit::log('articles.status_toggled', 'articles', (int) $article['id'], [
            'from' => $article['status'],
            'to' => $status,
        ]);

        App::flash('success', $status === 'publie' ? 'Article publié.' : 'Article repassé en brouillon.');

        return Response::redirectBack();
    }
}