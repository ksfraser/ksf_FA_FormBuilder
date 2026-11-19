<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FormBuilder\Service\FormService;

/**
 * FormController — controller SRP for Forms tab.
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */
class FormController extends AbstractTabController
{
    private $service;

    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new FormService();
    }

    protected function getPkField(): string
    {
        return 'id';
    }

    protected function getFieldMetadata(): array
    {
        return [
            'entity'      => 'form',
            'table'       => '0_ksf_formbuilder_forms',
            'label'       => 'Form',
            'labelPlural' => 'Forms',
            'hookPrefix'  => 'form',
            'pk'          => 'id',
            'fields'      => [],
            'fk_ddls'     => [],
            'ddlHooks'    => [],
            'tableSettings' => [],
        ];
    }

    protected function listRows(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        return array_slice($this->service->searchForms([]), $offset, $perPage);
    }

    protected function countRows(): int
    {
        return count($this->service->searchForms([]));
    }

    protected function findRecord(string $pk): ?array
    {
        $dto = $this->service->findFormById((int)$pk);
        return $dto ? $dto->toArray() : null;
    }

    protected function createRecord(array $data): int
    {
        $dto = $this->service->createForm($data);
        return $dto->id;
    }

    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->updateForm((int)$pk, $data);
    }

    protected function deleteRecord(string $pk): void
    {
        $this->service->deleteForm((int)$pk);
    }

    protected function fkOptions(): array
    {
        return [];
    }
}
