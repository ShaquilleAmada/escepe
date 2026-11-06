<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Handler khusus untuk Web: redirect ke login saat sesi kedaluwarsa
        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if (!$request->is('api/*')) {
                return redirect()->route('login')
                    ->with('info', 'Sesi Anda telah berakhir. Silakan login kembali.');
            }
        });

        // =====================================================================
        // Handler JSON untuk semua request ke API (api/*)
        // Agar mobile app selalu mendapat response JSON, bukan HTML
        // =====================================================================

        // 404 - Data tidak ditemukan (Model tidak ada di database)
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang diminta tidak ditemukan.',
                ], 404);
            }
        });

        // 401 - Belum login atau token tidak valid / kedaluwarsa
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum login atau token tidak valid. Silakan login kembali.',
                ], 401);
            }
        });

        // 422 - Validasi gagal (untuk request tanpa header Accept: application/json)
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dikirim tidak valid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 405 - Method Not Allowed
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTTP Method tidak diizinkan untuk endpoint ini.',
                ], 405);
            }
        });

        // 404 - Route tidak ditemukan
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint API tidak ditemukan.',
                ], 404);
            }
        });
    }
}
