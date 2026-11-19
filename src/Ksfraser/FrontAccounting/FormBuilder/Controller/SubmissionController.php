<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FormBuilder\Service\FormSubmissionService;

/**
 * SubmissionController — controller SRP for Submissions tab.
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */
class SubmissionController extends AbstractTabController
{
    private $service;

    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new FormSubmissionService();
    }

    protected function getPkField(): string
    {
        return 'id';
    }

    protected function getFieldMetadata(): array
    {
        return [
            'entity'      => 'submission',
            'table'       => '0_ksf_formbuilder_submissions',
            'label'       => 'Submission',
            'labelPlural' => 'Submissions',
            'hookPrefix'  => 'submission',
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
        return array_slice($this->service->searchSubmissions(['form_id' => $formId]), $offset, $perPage);
    }

    protected function countRows(): int
    {
        $formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
        return count($this->service->searchSubmissions(['form_id' => $formId]));
    }

    protected function findRecord(string $pk): ?array
    {
        $dto = $this->service->findSubmissionById((int)$pk);
        return $dto ? $dto->toArray() : null;
    }

    protected function createRecord(array $data): int
    {
        $dto = $this->service->createSubmission($data);
        return $dto->id;
    }

    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->updateSubmission((int)$pk, $data);
    }

    protected function deleteRecord(string $pk): void
    {
        $this->service->deleteSubmission((int)$pk);
    }

    protected function fkOptions(): array
    {
        return [];
    }
}
