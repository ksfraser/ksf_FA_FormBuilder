<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionValueDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\FormSubmissionServiceInterface;

/**
 * FormSubmissionService — FA adapter implementation of FormSubmissionServiceInterface.
 *
 * @BABOK Related: FR-FB-001-003
 * @since 1.0.0
 */
class FormSubmissionService implements FormSubmissionServiceInterface
{
    public function createSubmission(array $data): FormSubmissionDto
    {
        if (!function_exists('db_query')) {
            throw FormBuilderException::creationFailed('FA db_query() not available');
        }

        if (empty($data['form_id'])) {
            throw FormBuilderException::invalidData(['form_id is required']);
        }

        $form_id = (int)$data['form_id'];
        $submitter_id = (int)($data['submitter_id'] ?? 0);
        $submitter_type = $data['submitter_type'] ?? null;
        $submitter_name = $data['submitter_name'] ?? null;
        $submitter_email = $data['submitter_email'] ?? null;
        $ip_address = $data['ip_address'] ?? '';
        $user_agent = $data['user_agent'] ?? null;
        $status = $data['status'] ?? 'submitted';

        $sql = "INSERT INTO `0_ksf_formbuilder_submissions` 
            (form_id, submitter_id, submitter_type, submitter_name, submitter_email, ip_address, user_agent, status, created_at) 
            VALUES (" .
            (int)$form_id . ", " .
            (int)$submitter_id . ", " .
            db_escape($submitter_type) . ", " .
            db_escape($submitter_name) . ", " .
            db_escape($submitter_email) . ", " .
            db_escape($ip_address) . ", " .
            db_escape($user_agent) . ", " .
            db_escape($status) . ", " .
            "NOW())";

        if (!db_query($sql, 'Could not create submission')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $id = (int)db_insert_id();

        return $this->findSubmissionById($id);
    }

    public function findSubmissionById(int $id): ?FormSubmissionDto
    {
        if (!function_exists('db_query')) {
            return null;
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_submissions` WHERE `id` = " . (int)$id;
        $result = db_query($sql, 'Could not query submission');
        $row = db_fetch($result);

        if (!$result || !$row) {
            return null;
        }

        return $this->rowToDto($row);
    }

    public function searchSubmissions(array $criteria): array
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
        if (isset($criteria['submitter_id']) && $criteria['submitter_id'] !== '') {
            $where[] = "`submitter_id` = " . (int)$criteria['submitter_id'];
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_submissions` WHERE " . implode(' AND ', $where) . " ORDER BY `created_at` DESC";
        $result = db_query($sql, 'Could not query submissions');

        $submissions = [];
        while ($row = db_fetch($result)) {
            $submissions[] = $this->rowToDto($row);
        }

        return $submissions;
    }

    public function updateSubmission(int $id, array $data): FormSubmissionDto
    {
        $existing = $this->findSubmissionById($id);
        if ($existing === null) {
            throw FormBuilderException::notFound('Submission', $id);
        }

        $dto = FormSubmissionDto::fromArray(array_merge($existing->toArray(), $data));
        $dto->id = $id;

        $sql = "UPDATE `0_ksf_formbuilder_submissions` SET 
            `form_id` = " . (int)$dto->form_id . ", 
            `submitter_id` = " . (int)$dto->submitter_id . ", 
            `submitter_type` = " . db_escape($dto->submitter_type) . ", 
            `submitter_name` = " . db_escape($dto->submitter_name) . ", 
            `submitter_email` = " . db_escape($dto->submitter_email) . ", 
            `ip_address` = " . db_escape($dto->ip_address) . ", 
            `user_agent` = " . db_escape($dto->user_agent) . ", 
            `status` = " . db_escape($dto->status) . ", 
            `processed_at` = " . (isset($dto->processed_at) ? db_escape($dto->processed_at) : 'NULL') . " 
            WHERE `id` = " . (int)$id;

        if (!db_query($sql, 'Could not update submission')) {
            throw FormBuilderException::updateFailed('db_query() failed');
        }

        return $this->findSubmissionById($id);
    }

    public function deleteSubmission(int $id): void
    {
        if (!isset($this->submissions[$id])) {
            throw FormBuilderException::notFound('Submission', $id);
        }

        if (!function_exists('db_query')) {
            throw FormBuilderException::deleteFailed('FA db_query() not available');
        }

        // Delete associated values first
        $sql = "DELETE FROM `0_ksf_formbuilder_submission_values` WHERE `submission_id` = " . (int)$id;
        db_query($sql, 'Could not delete submission values');

        $sql = "DELETE FROM `0_ksf_formbuilder_submissions` WHERE `id` = " . (int)$id;
        if (!db_query($sql, 'Could not delete submission')) {
            throw FormBuilderException::deleteFailed('db_query() failed');
        }
    }

    public function addSubmissionValue(int $submissionId, int $fieldId, string $value, ?string $valueJson = null): \ksfraser\FormBuilder\Dto\FormSubmissionValueDto
    {
        $submission = $this->findSubmissionById($submissionId);
        if ($submission === null) {
            throw FormBuilderException::notFound('Submission', $submissionId);
        }

        if (!function_exists('db_query')) {
            throw FormBuilderException::creationFailed('FA db_query() not available');
        }

        $sql = "INSERT INTO `0_ksf_formbuilder_submission_values` 
            (submission_id, field_id, value, value_json, created_at) 
            VALUES (" .
            (int)$submissionId . ", " .
            (int)$fieldId . ", " .
            db_escape($value) . ", " .
            db_escape($valueJson) . ", " .
            "NOW())";

        if (!db_query($sql, 'Could not create submission value')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $id = (int)db_insert_id();

        return new \ksfraser\FormBuilder\Dto\FormSubmissionValueDto([
            'id'           => $id,
            'submission_id'=> $submissionId,
            'field_id'     => $fieldId,
            'value'        => $value,
            'value_json'   => $valueJson,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function getSubmissionValues(int $submissionId): array
    {
        if (!function_exists('db_query')) {
            return [];
        }

        $sql = "SELECT * FROM `0_ksf_formbuilder_submission_values` WHERE `submission_id` = " . (int)$submissionId . " ORDER BY `id` ASC";
        $result = db_query($sql, 'Could not query submission values');

        $values = [];
        while ($row = db_fetch($result)) {
            $values[] = new \ksfraser\FormBuilder\Dto\FormSubmissionValueDto([
                'id'           => (int)$row['id'],
                'submission_id'=> (int)$row['submission_id'],
                'field_id'     => (int)$row['field_id'],
                'value'        => $row['value'],
                'value_json'   => $row['value_json'],
                'created_at'   => $row['created_at'],
            ]);
        }

        return $values;
    }

    public function markSubmissionProcessed(int $submissionId): void
    {
        $submission = $this->findSubmissionById($submissionId);
        if ($submission === null) {
            throw FormBuilderException::notFound('Submission', $submissionId);
        }

        if (!function_exists('db_query')) {
            throw FormBuilderException::updateFailed('FA db_query() not available');
        }

        $sql = "UPDATE `0_ksf_formbuilder_submissions` 
            SET `status` = 'processed', `processed_at` = NOW() 
            WHERE `id` = " . (int)$submissionId;

        if (!db_query($sql, 'Could not mark submission processed')) {
            throw FormBuilderException::updateFailed('db_query() failed');
        }
    }

    private function rowToDto(array $row): FormSubmissionDto
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
