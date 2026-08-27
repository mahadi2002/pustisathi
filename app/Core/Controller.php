<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

/** Thin base for every controller — rendering, redirecting, common lookups. */
abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($template, $data), $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    protected function back(Request $request, string $fallback = '/'): Response
    {
        $ref  = (string) $request->header('referer', '');
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === $host) {
            return Response::redirect($ref);
        }
        return Response::redirect($fallback);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * The validate-fail-then-flash-notify-redirect dance every form controller
     * needs, in one place instead of five near-identical copies.
     */
    protected function failValidation(Validator $v, string $redirectTo, string $default, bool $flash = false): Response
    {
        if ($flash) {
            $v->flash();
        }
        Session::notify('error', $v->firstError() ?? $default);
        return $this->redirect($redirectTo);
    }

    /** IDOR and missing rows both end here — never confirm existence either way. */
    protected function notFound(): never
    {
        throw new HttpException(404);
    }

    protected function currentUserId(): ?int
    {
        return Session::userId();
    }

    /** The logged-in user's row, or null. Always re-read from the DB. */
    protected function currentUser(): ?array
    {
        $id = Session::userId();
        if ($id === null) {
            return null;
        }
        return (new \App\Repositories\UserRepo())->find($id);
    }

    /** True when the viewer is signed in — used to unlock the free surfaces' logged-in-only perks (e.g. unlimited food search). */
    protected function isAuthenticated(): bool
    {
        return Session::userId() !== null;
    }
}
