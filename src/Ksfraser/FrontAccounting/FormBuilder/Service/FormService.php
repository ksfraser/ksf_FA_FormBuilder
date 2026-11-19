<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormDto;
use ksfraser\FormBuilder\Dto\FormFieldDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionValueDto;
use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormActionParamDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\FormServiceInterface;
use ksfraser\FormBuilder\Service\FormFieldServiceInterface;
use ksfraser\FormBuilder\Service\FormSubmissionServiceInterface;
use ksfraser\FormBuilder\Service\FormActionServiceInterface;
use ksfraser\FormBuilder\Service\FormActionParamServiceInterface;

/**
 * FormService — FA adapter implementation of FormServiceInterface.
 *
 * Uses FA's native db_* functions for all database operations.
 *
 * @BABOK Related: FR-FB-001-001
 * @since 1.0.0
 */
class FormService implements FormServiceInterface
{
    public function createForm(array $data): FormDto
    {
        if (!function_exists('db_query')) {
            throw FormBuilderException::creationFailed('FA db_query() not available');
        }

        if (empty($data['name'])) {
            throw FormBuilderException::invalidData(['name is required']);
        }

        $slug = empty($data['slug']) ? $this->slugify($data['name']) : $data['slug'];

        // Check slug uniqueness
        $existing = $this->findFormBySlug($slug);
        if ($existing !== null) {
            throw FormBuilderException::invalidData(['slug already exists: ' . $slug]);
        }

        $description = $data['description'] ?? '';
        $status = $data['status'] ?? 'draft';
        $is_public = (int)($data['is_public'] ?? 0);
        $submit_button_label = $data['submit_button_label'] ?? null;
        $submit_message = $data['submit_message'] ?? null;
        $redirect_url = $data['redirect_url'] ?? null;
        $created_by = $data['created_by'] ?? 0;

        $sql = "INSERT INTO `0_ksf_formbuilder_forms` 
            (name, slug, description, status, is_public, submit_button_label, submit_message, redirect_url, created_by, created_at, updated_at) 
            VALUES (" .
            db_escape($data['name']) . ", " .
            db_escape($slug) . ", " .
            db_escape($description) . ", " .
            db_escape($status) . ", " .
            (int)$is_public . ", " .
            db_escape($submit_button_label) . ", " .
            db_escape($submit_message) . ", " .
            db_escape($redirect_url) . ", " .
            (int)$created_by . ", " .
            "NOW(), NOW())";

        if (!db_query($sql, 'Could not create form')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $id = (int)db_insert_id();

        return $this->findFormById($id);
    }

    public function findFormById(int $id): ?FormDto
    {
        if (!function_exists('db_query')) {
            return null;
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_forms` WHERE `id` = " . (int)$id;
        $result = db_query($sql, 'Could not query form');
        $row = db_fetch($result);

        if (!$result || !$row) {
            return null;
        }

        return $this->rowToDto($row);
    }

    public function findFormBySlug(string $slug): ?FormDto
    {
        if (!function_exists('db_query')) {
            return null;
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_forms` WHERE `slug` = " . db_escape($slug);
        $result = db_query($sql, 'Could not query form by slug');
        $row = db_fetch($result);

        if (!$result || !$row) {
            return null;
        }

        return $this->rowToDto($row);
    }

    public function searchForms(array $criteria): array
    {
        if (!function_exists('db_query')) {
            return [];
        }

        $where = ['1 = 1'];

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $where[] = "`status` = " . db_escape($criteria['status']);
        }
        if (isset($criteria['is_public']) && $criteria['is_public'] !== '') {
            $where[] = "`is_public` = " . (int)$criteria['is_public'];
        }
        if (isset($criteria['query']) && $criteria['query'] !== '') {
            $q = '%' . db_escape($criteria['query']) . '%';
            $where[] = "(`name` LIKE " . db_escape($q) . " OR `slug` LIKE " . db_escape($q) . ")";
        }

        $limit = isset($criteria['limit']) ? (int)$criteria['limit'] : 50;

        $sql = "SELECT * FROM `0_ksf_formbuilder_forms` WHERE " . implode(' AND ', $where) . " ORDER BY `created_at` DESC LIMIT " . $limit;
        $result = db_query($sql, 'Could not search forms');

        $forms = [];
        while ($row = db_fetch($result)) {
            $forms[] = $this->rowToDto($row);
        }

        return $forms;
    }

    public function updateForm(int $id, array $data): FormDto
    {
        $existing = $this->findFormById($id);
        if ($existing === null) {
            throw FormBuilderException::notFound('Form', $id);
        }

        $dto = FormDto::fromArray(array_merge($existing->toArray(), $data));
        $dto->id = $id;
        $dto->updated_at = date('Y-m-d H:i:s');

        if ($dto->slug === '') {
            $dto->slug = $this->slugify($dto->name);
        }

        // Check slug uniqueness
        foreach ($this->searchForms([]) as $existing) {
            if ($existing->id !== $id && $existing->slug === $dto->slug) {
                throw FormBuilderException::invalidData(['slug already exists: ' . $dto->slug]);
            }
        }

        $sql = "UPDATE `0_ksf_formbuilder_forms` SET 
            `name` = " . db_escape($dto->name) . ", 
            `slug` = " . db_escape($dto->slug) . ", 
            `description` = " . db_escape($dto->description) . ", 
            `status` = " . db_escape($dto->status) . ", 
            `is_public` = " . (int)$dto->is_public . ", 
            `submit_button_label` = " . db_escape($dto->submit_button_label) . ", 
            `submit_message` = " . db_escape($dto->submit_message) . ", 
            `redirect_url` = " . db_escape($dto->redirect_url) . ", 
            `updated_at` = NOW() 
            WHERE `id` = " . (int)$id;

        if (!db_query($sql, 'Could not update form')) {
            throw FormBuilderException::updateFailed('db_query() failed');
        }

        return $this->findFormById($id);
    }

    public function deleteForm(int $id): void
    {
        if (!isset($this->forms[$id])) {
            throw FormBuilderException::notFound('Form', $id);
        }

        // Check if form has submissions
        $submissions = $this->searchSubmissions(['form_id' => $id]);
        if (count($submissions) > 0) {
            throw FormBuilderException::deleteFailed('Cannot delete form with existing submissions');
        }

        if (!function_exists('db_query')) {
            throw FormBuilderException::deleteFailed('FA db_query() not available');
        }

        $sql = "DELETE FROM `0_ksf_formbuilder_forms` WHERE `id` = " . (int)$id;
        if (!db_query($sql, 'Could not delete form')) {
            throw FormBuilderException::deleteFailed('db_query() failed');
        }
    }

    public function getActiveForms(): array
    {
        return $this->searchForms(['status' => 'active', 'is_public' => 1]);
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug === '' ? 'form' : $slug;
    }

    private function rowToDto(array $row): FormDto
    {
        $dto = new FormDto([
            'id'                 => (int)$row['id'],
            'name'               => $row['name'],
            'slug'               => $row['slug'],
            'description'        => $row['description'],
            'status'             => $row['status'],
            'is_public'          => (int)$row['is_public'],
            'submit_button_label'=> $row['submit_button_label'],
            'submit_message'     => $row['submit_message'],
            'redirect_url'       => $row['redirect_url'],
            'created_by'         => (int)$row['created_by'],
            'created_at'         => $row['created_at'],
            'updated_at'         => $row['updated_at'],
        ]);

        return $dto;
    }

    private function searchSubmissions(array $criteria): array
    {
        if (!function_exists('db_query')) {
            return [];
        }

        $where = ['1 = 1'];

        if (isset($criteria['form_id']) && $criteria['form_id'] !== '') {
            $where[] = "`form_id` = " . (int)$criteria['form_id'];
        }
        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $where[] = "`status` = " . db_escape($criteria['status']);
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_submissions` WHERE " . implode(' AND ', $where) . " ORDER BY `created_at` DESC";
        $result = db_query($sql, 'Could not query submissions');

        $submissions = [];
        while ($row = db_fetch($result)) {
            $submissions[] = $this->submissionRowToDto($row);
        }

        return $submissions;
    }

    private function submissionRowToDto(array $row): FormSubmissionDto
    {
        return new FormSubmissionDto([
            'id'             => (int)$row['id'],
            'form_id'        => (int)$row['form_id'],
            'submitter_id'   => (int)$row['submitter_id'],
            'submitter_type' => $row['submitter_type'],
            'submitter_name' => $row['submitter_name'],
            'submitter_email'=> $row['submitter_email'],
            'ip_address'     => $row['ip_address'],
            'user_agent'     => $row['user_agent'],
            'status'         => $row['status'],
            'processed_at'   => $row['processed_at'],
            'created_at'     => $row['created_at'],
        ]);
    }
}
