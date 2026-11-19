<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * CreateContactProcessor — FA implementation.
 *
 * Creates a CRM contact using add_crm_person() and add_crm_contact().
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class CreateContactProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'create_contact';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        if (!function_exists('db_query') || !function_exists('db_escape') || !function_exists('db_insert_id') || !function_exists('add_crm_person') || !function_exists('add_crm_contact')) {
            throw FormBuilderException::creationFailed('FA db_* functions or add_crm_person()/add_crm_contact() not available');
        }

        $firstName = $params['first_name'] ?? $submissionValues['first_name'] ?? null;
        $lastName = $params['last_name'] ?? $submissionValues['last_name'] ?? null;
        $email = $params['email'] ?? $submissionValues['email'] ?? null;
        $phone = $params['phone'] ?? $submissionValues['phone'] ?? null;
        $address = $params['address'] ?? $submissionValues['address'] ?? null;
        $debtorNo = (int)($params['debtor_id'] ?? $submissionValues['debtor_id'] ?? 0);

        if (empty($firstName) && empty($lastName)) {
            throw FormBuilderException::invalidData(['First name or last name is required for contact creation']);
        }

        add_crm_person(
            $reference ?: '',  // person_code
            $firstName ?: '',
            $lastName ?: '',
            $address ?: '',
            $phone ?: '',
            '',         // fax
            '',         // mobile
            $email ?: '',  // email
            '',         // website
            ''          // notes
        );

        $personId = (int)db_insert_id();

        if ($personId <= 0) {
            throw FormBuilderException::creationFailed('add_crm_person() did not return a person_id');
        }

        // Link contact to debtor if available
        if ($debtorNo > 0 && function_exists('add_crm_contact')) {
            add_crm_contact('customer', 'general', $debtorNo, $personId);
        }

        return [
            'success' => true,
            'contact_id' => $personId,
            'person_id' => $personId,
            'name' => trim(($firstName ?: '') . ' ' . ($lastName ?: '')),
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
