<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Services/IntegrationErrorService.php';

class IntegrationErrorController extends Controller
{
    private IntegrationErrorService $errorService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->errorService = new IntegrationErrorService();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();

        $this->view('integration-errors/index', [
            'errors' => $this->errorService->all((int) $store['id']),
        ]);
    }

    public function resolve(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->errorService->markRetried($id, true);
        }
        $this->redirect('/integration-errors');
    }
}