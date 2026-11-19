<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FormBuilder\Service\FormFieldService;

/**
 * FieldController — controller SRP for Fields tab.
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */
class FieldController extends AbstractTabController
{
    private $service;

    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new FormFieldService();
    }

    protected function getPkField(): string
    {
        return 'id';
    }

    protected function getFieldMetadata(): array
    {
        return [
            'entity'      => 'field',
            'table'       => '0_ksf_formbuilder_fields',
            'label'       => 'Field',
            'labelPlural' => 'Fields',
            'hookPrefix'  => 'field',
            'pk'          => 'id',
            'fields'      => [],
            'fk_ddls'     => [],
            'ddlHooks'    => [],
            'tableSettings' => [],
        ];
    }

    protected function listRows(int $page, int $perPage): array
    {
        $formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
        $offset = ($page - 1) * $perPage;
        return array_slice($this->service->getFieldsForForm($formId), $offset, $perPage);
    }

    protected function countRows(): int
    {
        $formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
        return count($this->service->getFieldsForForm($formId));
    }

    protected function findRecord(string $pk): ?array
    {
        $dto = $this->service->findFieldById((int)$pk);
        return $dto ? $dto->toArray() : null;
    }

    protected function createRecord(array $data): int
    {
        $dto = $this->service->createField($data);
        return $dto->id;
    }

    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->updateField((int)$pk, $data);
    }

    protected function deleteRecord(string $pk): void
    {
        $this->service->deleteField((int)$pk);
    }

    protected function fkOptions(): array
    {
        return [];
    }
}
