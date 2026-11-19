<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * CreateTicketProcessor — FA implementation.
 *
 * Creates a support ticket using db_* functions on 0_ksf_hd_tickets.
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class CreateTicketProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'create_ticket';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        if (!function_exists('db_query') || !function_exists('db_escape') || !function_exists('db_insert_id')) {
            throw FormBuilderException::creationFailed('FA db_* functions not available');
        }

        $subject = $params['subject'] ?? $submissionValues['subject'] ?? 'Support Ticket';
        $description = $params['description'] ?? $submissionValues['description'] ?? '';
        $customerId = (int)($params['customer_id'] ?? $submissionValues['customer_id'] ?? 0);
        $employeeId = (int)($params['employee_id'] ?? $submissionValues['employee_id'] ?? 0);
        $categoryId = (int)($params['category_id'] ?? 0);
        $priorityId = (int)($params['priority_id'] ?? 0);
        $statusId = (int)($params['status_id'] ?? 1);

        if (empty($subject)) {
            throw FormBuilderException::invalidData(['Subject is required for ticket creation']);
        }

        $sql = "INSERT INTO `0_ksf_hd_tickets` 
            (ticket_number, subject, description, category_id, priority_id, status_id, assigned_to, requester_id, created_at) 
            VALUES (" .
            db_escape('TK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT)) . ", " .
            db_escape($subject) . ", " .
            db_escape($description) . ", " .
            (int)$categoryId . ", " .
            (int)$priorityId . ", " .
            (int)$statusId . ", " .
            (int)$employeeId . ", " .
            (int)$customerId . ", " .
            "NOW())";

        if (!db_query($sql, 'Could not create ticket')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $ticketId = (int)db_insert_id();

        if ($ticketId <= 0) {
            throw FormBuilderException::creationFailed('Ticket insert did not return an ID');
        }

        return [
            'success' => true,
            'ticket_id' => $ticketId,
            'ticket_number' => 'TK-' . date('Ymd') . '-' . str_pad($ticketId, 4, '0', STR_PAD_LEFT),
            'subject' => $subject,
        ];
    }

    private function buildParams(array $params, array $submissionValues): array
    {
        $result = [];
        foreach ($params as $p) {
            $sf = $p['source_field'];
            $tf = $p['target_field'];
            if (isset($submissionValues[$sf])) {
                $result[$tf] = $submissionValues[$sf];
            } elseif (isset($param['default_value'])) {
                $result[$tf] = $param['default_value'];
            }
        }
        return $result;
    }
}
