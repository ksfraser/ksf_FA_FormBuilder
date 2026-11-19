<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\FormActionServiceInterface;

/**
 * FormActionService — FA adapter implementation of FormActionServiceInterface.
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class FormActionService implements FormActionServiceInterface
{
    public function createAction(array $data): \ksfraser\FormBuilder\Dto\FormActionDto
    {
        if (!function_exists('db_query')) {
            throw FormBuilderException::creationFailed('FA db_query() not available');
        }

        if (empty($data['action_type'])) {
            throw FormBuilderException::invalidData(['action_type is required']);
        }

        if (!in_array($data['action_type'], \ksfraser\FormBuilder\Dto\FormActionDto::validTypes(), true)) {
            throw FormBuilderException::invalidFieldType($data['action_type']);
        }

        $form_id = (int)($data['form_id'] ?? 0);
        $sort_order = (int)($data['sort_order'] ?? 0);
        $action_type = $data['action_type'];
        $is_active = (int)($data['is_active'] ?? 1);
        $conditions_json = $data['conditions_json'] ?? null;

        $sql = "INSERT INTO `0_ksf_formbuilder_actions` 
            (form_id, sort_order, action_type, is_active, conditions_json, created_at) 
            VALUES (" .
            (int)$form_id . ", " .
            (int)$sort_order . ", " .
            db_escape($action_type) . ", " .
            (int)$is_active . ", " .
            db_escape($conditions_json) . ", " .
            "NOW())";

        if (!db_query($sql, 'Could not create action')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $id = (int)db_insert_id();

        return $this->findActionById($id);
    }

    public function findActionById(int $id): ?\ksfraser\FormBuilder\Dto\FormActionDto
    {
        if (!function_exists('db_query')) {
            return null;
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_actions` WHERE `id` = " . (int)$id;
        $result = db_query($sql, 'Could not query action');
        $row = db_fetch($result);

        if (!$result || !$row) {
            return null;
        }

        return $this->rowToDto($row);
    }

    public function searchActions(array $criteria): array
    {
        if (!function_exists('db_query')) {
            return [];
        }

        $where = ['1 = 1'];

        if (isset($criteria['form_id']) && $criteria['form_id'] !== '') {
            $where[] = "`form_id` = " . (int)$criteria['form_id'];
        }
        if (isset($criteria['action_type']) && $criteria['action_type'] !== '') {
            $where[] = "`action_type` = " . db_escape($criteria['action_type']);
        }
        if (isset($criteria['is_active']) && $criteria['is_active'] !== '') {
            $where[] = "`is_active` = " . (int)$criteria['is_active'];
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_actions` WHERE " . implode(' AND ', $where) . " ORDER BY `sort_order` ASC";
        $result = db_query($sql, 'Could not query actions');

        $actions = [];
        while ($row = db_fetch($result)) {
            $actions[] = $this->rowToDto($row);
        }

        return $actions;
    }

    public function getActionsForForm(int $formId): array
    {
        return $this->searchActions(['form_id' => $formId]);
    }

    public function updateAction(int $id, array $data): \ksfraser\FormBuilder\Dto\FormActionDto
    {
        $existing = $this->findActionById($id);
        if ($existing === null) {
            throw FormBuilderException::notFound('Action', $id);
        }

        $dto = \ksfraser\FormBuilder\Dto\FormActionDto::fromArray(array_merge($existing->toArray(), $data));
        $dto->id = $id;

        if (!in_array($dto->action_type, \ksfraser\FormBuilder\Dto\FormActionDto::validTypes(), true)) {
            throw FormBuilderException::invalidFieldType($dto->action_type);
        }

        $sql = "UPDATE `0_ksf_formbuilder_actions` SET 
            `form_id` = " . (int)$dto->form_id . ", 
            `sort_order` = " . (int)$dto->sort_order . ", 
            `action_type` = " . db_escape($dto->action_type) . ", 
            `is_active` = " . (int)$dto->is_active . ", 
            `conditions_json` = " . db_escape($dto->conditions_json) . " 
            WHERE `id` = " . (int)$id;

        if (!db_query($sql, 'Could not update action')) {
            throw FormBuilderException::updateFailed('db_query() failed');
        }

        return $this->findActionById($id);
    }

    public function deleteAction(int $id): void
    {
        if (!isset($this->actions[$id])) {
            throw FormBuilderException::notFound('Action', $id);
        }

        if (!function_exists('db_query')) {
            throw FormBuilderException::deleteFailed('FA db_query() not available');
        }

        $sql = "DELETE FROM `0_ksf_formbuilder_actions` WHERE `id` = " . (int)$id;
        if (!db_query($sql, 'Could not delete action')) {
            throw FormBuilderException::deleteFailed('db_query() failed');
        }
    }

    public function validateActionType(string $actionType): bool
    {
        return in_array($actionType, \ksfraser\FormBuilder\Dto\FormActionDto::validTypes(), true);
    }

    private function rowToDto(array $row): \ksfraser\FormBuilder\Dto\FormActionDto
    {
        return new \ksfraser\FormBuilder\Dto\FormActionDto([
            'id'             => (int)$row['id'],
            'form_id'        => (int)$row['form_id'],
            'sort_order'     => (int)$row['sort_order'],
            'action_type'    => $row['action_type'],
            'is_active'      => (int)$row['is_active'],
            'conditions_json'=> $row['conditions_json'],
            'created_at'     => $row['created_at'],
        ]);
    }
}
