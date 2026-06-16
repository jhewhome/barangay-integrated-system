<?php

class MedicineCatalogController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $this->requireRole('admin');
        $pickerMeta = MedicineCatalog::pickerMeta($this->db);
        $this->view('medicines/index', [
            'medicines' => MedicineCatalog::all($this->db),
            'pickerItems' => $pickerMeta['items'],
            'pickerSource' => $pickerMeta['source'],
            'pickerCount' => count($pickerMeta['items']),
            'gawadPickerError' => $pickerMeta['gawad_error'],
            'gawadMedicinesUrl' => GawadIntegration::medicinesInventoryUrl(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin');
        $this->view('medicines/create', ['errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $old = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'default_unit' => trim((string) ($_POST['default_unit'] ?? 'tablet(s)')),
            'is_active' => isset($_POST['is_active']) ? '1' : '',
        ];
        $errors = $this->validateForm($old);
        if (!empty($errors)) {
            $this->view('medicines/create', ['errors' => $errors, 'old' => $old]);
            return;
        }

        MedicineCatalog::create($this->db, $old);
        $this->redirectWithFlash('/medicines', 'ok', 'Medicine added to clinic list.');
    }

    public function edit(int $id): void
    {
        $this->requireRole('admin');
        $medicine = MedicineCatalog::find($this->db, $id);
        if (!$medicine) {
            http_response_code(404);
            echo 'Medicine not found';
            return;
        }
        $this->view('medicines/edit', ['medicine' => $medicine, 'errors' => []]);
    }

    public function update(int $id): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $medicine = MedicineCatalog::find($this->db, $id);
        if (!$medicine) {
            http_response_code(404);
            echo 'Medicine not found';
            return;
        }

        $old = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'default_unit' => trim((string) ($_POST['default_unit'] ?? 'tablet(s)')),
            'is_active' => isset($_POST['is_active']) ? '1' : '',
        ];
        $errors = $this->validateForm($old, $id);
        if (!empty($errors)) {
            $this->view('medicines/edit', [
                'medicine' => array_merge($medicine, $old),
                'errors' => $errors,
            ]);
            return;
        }

        MedicineCatalog::update($this->db, $id, $old);
        $this->redirectWithFlash('/medicines', 'ok', 'Medicine list entry updated.');
    }

    /** @param array<string,string> $old */
    private function validateForm(array $old, ?int $excludeId = null): array
    {
        $errors = [];
        if (($old['name'] ?? '') === '') {
            $errors[] = 'Medicine name is required.';
        }
        return $errors;
    }
}
