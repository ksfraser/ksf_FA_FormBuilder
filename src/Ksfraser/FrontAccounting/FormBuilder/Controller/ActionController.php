<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FormBuilder\Service\FormActionService;

/**
 * ActionController — controller SRP for Actions tab.
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */
class ActionController extends AbstractTabController
{
    private $service;

    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new FormActionService();
    }

    protected function getPkField(): string
    {
        return 'id';
    }

    protected function getFieldMetadata(): array
    {
        return [
            'entity'      => 'action',
            'table'       => '0_ksf_formbuilder_actions',
            'label'       => 'Action',
            'labelPlural' => 'Actions',
            'hookPrefix'  => 'action',
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
        return array_slice($this->service->getActionsForForm($formId), $offset, $perPage);
    }

    protected function countRows(): int
    {
        $formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
        return count($this->service->getActionsForForm($formId));
    }

    protected function findRecord(string $pk): ?array
    {
        $dto = $this->service->findActionById((int)$pk);
        return $dto ? $dto->toArray() : null;
    }

    protected function createRecord(array $data): int
    {
        $dto = $this->service->createAction($data);
        return $dto->id;
    }

    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->updateAction((int)$pk, $data);
    }

    protected function deleteRecord(string $pk): void
    {
        $this->service->deleteAction((int)$pk);
    }

    protected function fkOptions(): array
    {
        return [];
    }
}
