<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Support\App;
use App\Support\Audit;
use App\Support\Hasher;
use App\Support\Response;
use App\Support\Validator;

final class CounselorController extends Controller
{
    private UserRepository $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserRepository();
    }

    public function index(): Response
    {
        return Response::view('admin/counselors/index', [
            'user' => App::user(),
            'pagination' => $this->users->paginateByRole('counselor', 50),
        ]);
    }

    public function store(): Response
    {
        $data = $this->request->only(['first_name', 'last_name', 'email', 'phone', 'password']);
        $validation = Validator::make($data, [
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'email' => 'required|email|max:191',
            'phone' => 'nullable|phone',
            'password' => 'required|min:12',
        ]);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $email = mb_strtolower(trim((string) $data['email']));

        if ($this->users->emailExists($email)) {
            App::flash('error', 'Cette adresse e-mail est déjà utilisée.');
            App::remember($data);

            return Response::redirectBack();
        }

        $role = Role::findByName('counselor');

        if ($role === null) {
            App::flash('error', 'Le rôle conseiller n\'est pas initialisé.');

            return Response::redirectBack();
        }

        $counselor = User::create([
            'role_id' => (int) $role['id'],
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'email' => $email,
            'phone' => trim((string) ($data['phone'] ?? '')) !== '' ? trim((string) $data['phone']) : null,
            'password_hash' => Hasher::make((string) $data['password']),
            'status' => 'active',
            'locality' => null,
            'language' => 'fr',
            'timezone' => 'Africa/Dakar',
        ]);

        if ($counselor === null) {
            App::flash('error', 'Impossible de créer le conseiller.');

            return Response::redirectBack();
        }

        Audit::log('users.counselor_created', 'users', (int) $counselor['id'], [], ['email' => $email]);
        App::flash('success', sprintf('Le conseiller %s %s a été créé.', $counselor['first_name'], $counselor['last_name']));

        return Response::redirect(route('admin.counselors'));
    }

    public function status(string $publicId): Response
    {
        $counselor = $this->users->findByPublicId($publicId);

        if ($counselor === null) {
            return Response::notFound('Conseiller introuvable.');
        }

        $status = (string) $this->request->post('status', 'active');

        if (!in_array($status, ['active', 'suspended'], true)) {
            App::flash('error', 'Statut invalide.');

            return Response::redirectBack();
        }

        $this->users->setStatus((int) $counselor['id'], $status);
        Audit::log('users.counselor_status', 'users', (int) $counselor['id'], [], ['status' => $status]);
        App::flash('success', 'Statut du conseiller mis à jour.');

        return Response::redirect(route('admin.counselors'));
    }
}