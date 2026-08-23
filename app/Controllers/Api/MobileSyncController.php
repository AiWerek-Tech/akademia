<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\UniversalSyncService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Exception;

/**
 * Phase 11 — Mobile Sync API Central Router & Controller.
 *
 * Enforces:
 *   - Central Router Pattern (§3) responding to `?action=*` and REST paths.
 *   - Strict JSON-Only responses (§3).
 *   - Session token based authentication (§4).
 *   - RBAC on backend (§5).
 *   - Universal Delta Sync payload formats (§8 & §9).
 */
class MobileSyncController extends BaseController
{
    private UniversalSyncService $syncService;

    public function __construct(?UniversalSyncService $syncService = null)
    {
        $this->syncService = $syncService ?? new UniversalSyncService();
    }

    /**
     * Central API Router entrypoint matching Rule §3.
     */
    public function router(): ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $action = $this->request->getVar('action') ?? ($json['action'] ?? null);

        return match ($action) {
            'login'       => $this->login(),
            'sync_all'    => $this->syncAll(),
            'sync_delta'  => $this->syncDelta(),
            'upload_file' => $this->uploadFile(),
            'health'      => $this->health(),
            default       => $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid or missing API action. Supported: login, sync_all, sync_delta, upload_file, health.',
            ])->setStatusCode(400),
        };
    }

    /**
     * Mobile Login & Session Token Generator (§4).
     */
    public function login(): ResponseInterface
    {
        $identifier = $this->request->getVar('username') ?? $this->request->getVar('identifier');
        $password   = $this->request->getVar('password');
        $deviceId   = $this->request->getVar('device_id');

        if (empty($identifier) || empty($password)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Username dan password wajib diisi.',
            ])->setStatusCode(400);
        }

        $authResult = $this->syncService->authenticate(
            (string) $identifier,
            (string) $password,
            $deviceId ? (string) $deviceId : null,
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString()
        );

        if (! $authResult) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Autentikasi gagal. Periksa username dan password Anda.',
            ])->setStatusCode(401);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $authResult,
        ]);
    }

    /**
     * Universal Full Sync Endpoint (§8).
     */
    public function syncAll(): ResponseInterface
    {
        $session = $this->authenticateRequest();
        if (! $session) {
            return $this->unauthorizedResponse();
        }

        $userId = (int) $session['user_id'];
        $unitId = (int) $session['unit_id'];
        $role   = (string) $session['app_role'];

        $payload = $this->syncService->syncAll($userId, $unitId, $role);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $payload,
        ]);
    }

    /**
     * Universal Delta Sync Endpoint (§8 & §9).
     */
    public function syncDelta(): ResponseInterface
    {
        $session = $this->authenticateRequest();
        if (! $session) {
            return $this->unauthorizedResponse();
        }

        $userId = (int) $session['user_id'];
        $unitId = (int) $session['unit_id'];
        $role   = (string) $session['app_role'];

        // Get client versions from JSON body or POST form
        $rawVersions = $this->request->getVar('versions');
        if (is_string($rawVersions)) {
            $clientVersions = json_decode($rawVersions, true) ?: [];
        } elseif (is_array($rawVersions)) {
            $clientVersions = $rawVersions;
        } else {
            $clientVersions = [];
        }

        $payload = $this->syncService->syncDelta($userId, $unitId, $role, $clientVersions);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $payload,
        ]);
    }

    /**
     * Controlled File Upload Gateway (§10).
     */
    public function uploadFile(): ResponseInterface
    {
        $session = $this->authenticateRequest();
        if (! $session) {
            return $this->unauthorizedResponse();
        }

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Berkas tidak ditemukan atau rusak.',
            ])->setStatusCode(400);
        }

        // Validate max size 15MB
        if ($file->getSizeByUnit('mb') > 15) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Ukuran berkas melebihi batas maksimum (15MB).',
            ])->setStatusCode(400);
        }

        $category = $this->request->getVar('category') ?? 'GENERAL';
        $originalName = $file->getClientName();
        $mimeType = $file->getClientMimeType();
        $newName = $file->getRandomName();

        // Target directory inside writable/uploads
        $uploadDir = WRITEPATH . 'uploads/' . date('Ym') . '/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        try {
            $file->move($uploadDir, $newName);

            $fileRecord = $this->syncService->registerFileMetadata([
                'file_name'     => $newName,
                'original_name' => $originalName,
                'file_type'     => $this->detectFileType($mimeType),
                'category'      => $category,
                'storage_path'  => 'uploads/' . date('Ym') . '/' . $newName,
                'public_url'    => base_url('uploads/' . date('Ym') . '/' . $newName),
                'file_size_kb'  => (int) ceil($file->getSize() / 1024),
                'mime_type'     => $mimeType,
            ], (int) $session['user_id'], (int) $session['unit_id']);

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => [
                    'id'           => (int) $fileRecord['id'],
                    'uuid'         => $fileRecord['uuid'],
                    'file_name'    => $fileRecord['file_name'],
                    'original_name'=> $fileRecord['original_name'],
                    'category'     => $fileRecord['category'],
                    'public_url'   => $fileRecord['public_url'],
                    'created_at'   => $fileRecord['created_at'],
                ],
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Gagal mengunggah berkas: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * System Health Check Endpoint.
     */
    public function health(): ResponseInterface
    {
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'service'   => 'WMVAA HUB Universal Sync Gateway',
                'status'    => 'HEALTHY',
                'timestamp' => date('c'),
                'version'   => '11.0.0',
            ],
        ]);
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================

    private function authenticateRequest(): ?array
    {
        // Check Bearer Token header or query token
        $authHeader = $this->request->getHeaderLine('Authorization');
        $token = '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $this->request->getVar('token') ?? '';
        }

        if (empty($token)) {
            return null;
        }

        $session = $this->syncService->validateToken((string) $token);
        if ($session) {
            $this->syncService->touchSession((string) $token);
        }

        return $session;
    }

    private function unauthorizedResponse(): ResponseInterface
    {
        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Akses ditolak. Sesi tidak valid atau telah kadaluarsa.',
        ])->setStatusCode(401);
    }

    private function detectFileType(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'IMAGE',
            str_starts_with($mime, 'audio/') => 'AUDIO',
            str_starts_with($mime, 'video/') => 'VIDEO',
            $mime === 'application/pdf'       => 'PDF',
            default                           => 'DOCUMENT',
        };
    }
}
