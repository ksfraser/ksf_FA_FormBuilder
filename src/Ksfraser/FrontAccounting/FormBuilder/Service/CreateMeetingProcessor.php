<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * CreateMeetingProcessor — FA implementation.
 *
 * Creates a calendar meeting using db_* functions on 0_calendar_events or FA's
 * calendar functions if available.
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class CreateMeetingProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'create_meeting';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        if (!function_exists('db_query') || !function_exists('db_escape') || !function_exists('db_insert_id')) {
            throw FormBuilderException::creationFailed('FA db_* functions not available');
        }

        $title = $params['title'] ?? $submissionValues['title'] ?? 'Meeting';
        $description = $params['description'] ?? $submissionValues['description'] ?? '';
        $startDate = $params['start_date'] ?? $submissionValues['start_date'] ?? date('Y-m-d H:i:s');
        $endDate = $params['end_date'] ?? $submissionValues['end_date'] ?? null;
        $location = $params['location'] ?? $submissionValues['location'] ?? null;
        $instructor = $params['instructor'] ?? $submissionValues['instructor'] ?? null;
        $assignedTo = (int)($params['assigned_to'] ?? $submissionValues['assigned_to'] ?? 0);

        $sql = "INSERT INTO `0_calendar_events` 
            (title, description, start_date, end_date, location, instructor, assigned_to, created_at) 
            VALUES (" .
            db_escape($title) . ", " .
            db_escape($description) . ", " .
            db_escape($startDate) . ", " .
            db_escape($endDate) . ", " .
            db_escape($location) . ", " .
            db_escape($instructor) . ", " .
            (int)$assignedTo . ", " .
            "NOW())";

        if (!db_query($sql, 'Could not create meeting')) {
            throw FormBuilderException::creationFailed('db_query() failed');
        }

        $meetingId = (int)db_insert_id();

        if ($meetingId <= 0) {
            throw FormBuilderException::creationFailed('Meeting insert did not return an ID');
        }

        return [
            'success' => true,
            'meeting_id' => $meetingId,
            'title' => $title,
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
