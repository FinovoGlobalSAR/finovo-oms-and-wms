<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/FieldMapping.php';

class FieldMappingController extends Controller
{
    private FieldMapping $mappingModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->requireStoreContext();
        $this->mappingModel = new FieldMapping();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $platform = $store['platform'] ?? 'manual';

        $productMappings = $this->mappingModel->allForStore((int) $store['id'], $platform, 'product');

        $this->view('field-mappings/index', [
            'platform' => $platform,
            'productMappings' => $productMappings,
            'availableFields' => FieldMapping::availableFields('product'),
            'saved' => $_GET['saved'] ?? null,
        ]);
    }

    public function save(): void
    {
        $store = $this->getCurrentStore();
        $platform = $store['platform'] ?? 'manual';

        $fields = FieldMapping::availableFields('product');
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = trim($_POST['field_' . $field] ?? '');
        }

        $this->mappingModel->save((int) $store['id'], $platform, 'product', $values);

        $this->redirect('/field-mappings?saved=1');
    }
}