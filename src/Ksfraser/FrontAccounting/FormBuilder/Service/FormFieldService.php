<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormFieldDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\FormFieldServiceInterface;

/**
 * FormFieldService — FA adapter implementation of FormFieldServiceInterface.
 *
 * @BABOK Related: FR-FB-001-002
 * @since 1.0.0
 */
class FormFieldService implements FormFieldServiceInterface
{
    public function createField(array $data): \ksfraser\FormBuilder\Dto\FormFieldDto
    {
        if (!function_exists('db_query')) {
            throw FormBuilderException::creationFailed('FA db_query() not available');
        }

        if (empty($data['field_name'])) {
            throw FormBuilderException::invalidData(['field_name is required']);
        }
        if (empty($data['label'])) {
            throw FormBuilderException::invalidData(['label is required']);
        }

        $form_id = (int)($data['form_id'] ?? 0);
        $field_name = $data['field_name'];
        $label = $data['label'];
        $description = $data['description'] ?? null;
        $field_type = $data['field_type'] ?? 'text';
        $source_module = $data['source_module'] ?? null;
        $source_field = $data['source_field'] ?? null;
        $is_required = (int)($data['is_required'] ?? 0);
        $sort_order = (int)($data['sort_order'] ?? 0);
        $options_json = $data['options_json'] ?? null;
        $default_value = $data['default_value'] ?? null;

        $sql = "INSERT INTO `0_ksf_formbuilder_fields` 
            (form_id, field_name, label, description, field_type, source_module, source_field, is_required, sort_order, options_json, default_value, created_at) 
            VALUES (" .
            (int)$form_id . ", " .
            db_escape($field_name) . ", " .
            db_escape($label) . ", " .
            db_escape($description) . ", " .
            db_escape($field_type) . ", " .
            db_escape($source_module) . ", " .
            db_escape($source_field) . ", " .
            (int)$is_required . ", " .
            (int)$sort_order . ", " .
            db_escape($options_json) . ", " .
            db_escape($default_value) . ", " .
            "NOW())";

        if (!db_query($sql, 'Could not create field')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $id = (int)db_insert_id();

        return $this->findFieldById($id);
    }

    public function findFieldById(int $id): ?\ksfraser\FormBuilder\Dto\FormFieldDto
    {
        if (!function_exists('db_query')) {
            return null;
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_fields` WHERE `id` = " . (int)$id;
        $result = db_query($sql, 'Could not query field');
        $row = db_fetch($result);

        if (!$result || !$row) {
            return null;
        }

        return $this->rowToDto($row);
    }

    public function searchFields(array $criteria): array
    {
        if (!function_exists('db_query')) {
            return [];
        }

        $where = ['1 = 1'];

        if (isset($criteria['form_id']) && $criteria['form_id'] !== '') {
            $where[] = "`form_id` = " . (int)$criteria['form_id'];
        }
        if (isset($criteria['field_type']) && $criteria['field_type'] !== '') {
            $where[] = "`field_type` = " . db_escape($criteria['field_type']);
        }
        if (isset($criteria['source_module']) && $criteria['source_module'] !== '') {
            $where[] = "`source_module` = " . db_escape($criteria['source_module']);
        }
        if (isset($criteria['is_required']) && $criteria['is_required'] !== '') {
            $where[] = "`is_required` = " . (int)$criteria['is_required'];
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_fields` WHERE " . implode(' AND ', $where) . " ORDER BY `sort_order` ASC";
        $result = db_query($sql, 'Could not query fields');

        $fields = [];
        while ($row = db_fetch($result)) {
            $fields[] = $this->rowToDto($row);
        }

        return $fields;
    }

    public function getFieldsForForm(int $formId): array
    {
        return $this->searchFields(['form_id' => $formId]);
    }

    public function updateField(int $id, array $data): \ksfraser\FormBuilder\Dto\FormFieldDto
    {
        $existing = $this->findFieldById($id);
        if ($existing === null) {
            throw FormBuilderException::notFound('Field', $id);
        }

        $dto = \ksfraser\FormBuilder\Dto\FormFieldDto::fromArray(array_merge($existing->toArray(), $data));
        $dto->id = $id;

        if ($dto->field_name === '') {
            throw FormBuilderException::invalidData(['field_name is required']);
        }
        if ($dto->label === '') {
            throw FormBuilderException::invalidData(['label is required']);
        }

        $sql = "UPDATE `0_ksf_formbuilder_fields` SET 
            `form_id` = " . (int)$dto->form_id . ", 
            `field_name` = " . db_escape($dto->field_name) . ", 
            `label` = " . db_escape($dto->label) . ", 
            `description` = " . db_escape($dto->description) . ", 
            `field_type` = " . db_escape($dto->field_type) . ", 
            `source_module` = " . db_escape($dto->source_module) . ", 
            `source_field` = " . db_escape($dto->source_field) . ", 
            `is_required` = " . (int)$dto->is_required . ", 
            `sort_order` = " . (int)$dto->sort_order . ", 
            `options_json` = " . db_escape($dto->options_json) . ", 
            `default_value` = " . db_escape($dto->default_value) . " 
            WHERE `id` = " . (int)$id;

        if (!db_query($sql, 'Could not update field')) {
            throw FormBuilderException::updateFailed('db_query() failed');
        }

        return $this->findFieldById($id);
    }

    public function deleteField(int $id): void
    {
        if (!isset($this->fields[$id])) {
            throw FormBuilderException::notFound('Field', $id);
        }

        if (!function_exists('db_query')) {
            throw FormBuilderException::deleteFailed('FA db_query() not available');
        }

        $sql = "DELETE FROM `0_ksf_formbuilder_fields` WHERE `id` = " . (int)$id;
        if (!db_query($sql, 'Could not delete field')) {
            throw FormBuilderException::deleteFailed('db_query() failed');
        }
    }

    private function rowToDto(array $row): \ksfraser\FormBuilder\Dto\FormFieldDto
    {
        return new \ksfraser\FormBuilder\Dto\FormFieldDto([
            'id'             => (int)$row['id'],
            'form_id'        => (int)$row['form_id'],
            'field_name'     => $row['field_name'],
            'label'          => $row['label'],
            'description'    => $row['description'],
            'field_type'     => $row['field_type'],
            'source_module'  => $row['source_module'],
            'source_field'   => $row['source_field'],
            'is_required'    => (int)$row['is_required'],
            'sort_order'     => (int)$row['sort_order'],
            'options_json'   => $row['options_json'],
            'default_value'  => $row['default_value'],
            'created_at'     => $row['created_at'],
        ]);
    }
}
