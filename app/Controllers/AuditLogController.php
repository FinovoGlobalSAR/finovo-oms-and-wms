<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Services/AuditLogService.php';

class AuditLogController extends Controller
{
    private AuditLogService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->auditService = new AuditLogService();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();

        $this->view('audit-log/index', [
            'logs' => $this->auditService->all((int) $store['id']),
        ]);
    }
}