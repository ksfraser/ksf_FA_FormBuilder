<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionParamDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\FormActionParamServiceInterface;

/**
 * FormActionParamService — FA adapter implementation of FormActionParamServiceInterface.
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class FormActionParamService implements FormActionParamServiceInterface
{
    public function createParam(array $data): \ksfraser\FormBuilder\Dto\FormActionParamDto
    {
        if (!function_exists('db_query')) {
            throw FormBuilderException::creationFailed('FA db_query() not available');
        }

        if (empty($data['action_id'])) {
            throw FormBuilderException::invalidData(['action_id is required']);
        }
        if (empty($data['target_field'])) {
            throw FormBuilderException::invalidData(['target_field is required']);
        }
        if (empty($data['source_field'])) {
            throw FormBuilderException::invalidData(['source_field is required']);
        }

        $action_id = (int)($data['action_id'] ?? 0);
        $target_field = $data['target_field'];
        $source_field = $data['source_field'];
        $transformation = $data['transformation'] ?? 'none';
        $default_value = $data['default_value'] ?? null;

        $sql = "INSERT INTO `0_ksf_formbuilder_action_params` 
            (action_id, target_field, source_field, transformation, default_value, created_at) 
            VALUES (" .
            (int)$action_id . ", " .
            db_escape($target_field) . ", " .
            db_escape($source_field) . ", " .
            db_escape($transformation) . ", " .
            db_escape($default_value) . ", " .
            "NOW())";

        if (!db_query($sql, 'Could not create param')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $id = (int)db_insert_id();

        return $this->findParamById($id);
    }

    public function findParamById(int $id): ?\ksfraser\FormBuilder\Dto\FormActionParamDto
    {
        if (!function_exists('db_query')) {
            return null;
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_action_params` WHERE `id` = " . (int)$id;
        $result = db_query($sql, 'Could not query param');
        $row = db_fetch($result);

        if (!$result || !$row) {
            return null;
        }

        return $this->rowToDto($row);
    }

    public function searchParams(array $criteria): array
    {
        if (!function_exists('db_query')) {
            return [];
        }

        $where = ['1 = 1'];

        if (isset($criteria['action_id']) && $criteria['action_id'] !== '') {
            $where[] = "`action_id` = " . (int)$criteria['action_id'];
        }
        if (isset($criteria['target_field']) && $criteria['target_field'] !== '') {
            $where[] = "`target_field` = " . db_escape($criteria['target_field']);
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_action_params` WHERE " . implode(' AND ', $where);
        $result = db_query($sql, 'Could not query params');

        $params = [];
        while ($row = db_fetch($result)) {
            $params[] = $this->rowToDto($row);
        }

        return $params;
    }

    public function getParamsForAction(int $actionId): array
    {
        return $this->searchParams(['action_id' => $actionId]);
    }

    public function updateParam(int $id, array $data): \ksfraser\FormBuilder\Dto\FormActionParamDto
    {
        $existing = $this->findParamById($id);
        if ($existing === null) {
            throw FormBuilderException::notFound('Param', $id);
        }

        $dto = \ksfraser\FormBuilder\Dto\FormActionParamDto::fromArray(array_merge($existing->toArray(), $data));
        $dto->id = $id;

        if ($dto->target_field === '') {
            throw FormBuilderException::invalidData(['target_field is required']);
        }
        if ($dto->source_field === '') {
            throw FormBuilderException::invalidData(['source_field is required']);
        }

        $sql = "UPDATE `0_ksf_formbuilder_action_params` SET 
            `action_id` = " . (int)$dto->action_id . ", 
            `target_field` = " . db_escape($dto->target_field) . ", 
            `source_field` = " . db_escape($dto->source_field) . ", 
            `transformation` = " . db_escape($dto->transformation) . ", 
            `default_value` = " . db_escape($dto->default_value) . " 
            WHERE `id` = " . (int)$id;

        if (!db_query($sql, 'Could not update param')) {
            throw FormBuilderException::updateFailed('db_query() failed');
        }

        return $this->findParamById($id);
    }

    public function deleteParam(int $id): void
    {
        if (!isset($this->params[$id])) {
            throw FormBuilderException::notFound('Param', $id);
        }

        if (!function_exists('db_query')) {
            throw FormBuilderException::deleteFailed('FA db_query() not available');
        }

        $sql = "DELETE FROM `0_ksf_formbuilder_action_params` WHERE `id` = " . (int)$id;
        if (!db_query($sql, 'Could not delete param')) {
            throw FormBuilderException::deleteFailed('db_query() failed');
        }
    }

    private function rowToDto(array $row): \ksfraser\FormBuilder\Dto\FormActionParamDto
    {
        return new \ksfraser\FormBuilder\Dto\FormActionParamDto([
            'id'             => (int)$row['id'],
            'action_id'      => (int)$row['action_id'],
            'target_field'   => $row['target_field'],
            'source_field'   => $row['source_field'],
            'transformation' => $row['transformation'],
            'default_value'  => $row['default_value'],
            'created_at'     => $row['created_at'],
        ]);
    }
}
