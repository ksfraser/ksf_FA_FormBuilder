<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;
use ksfraser\FormBuilder\Service\FormServiceInterface;
use ksfraser\FormBuilder\Service\FormFieldServiceInterface;
use ksfraser\FormBuilder\Service\FormSubmissionServiceInterface;
use ksfraser\FormBuilder\Service\FormActionServiceInterface;
use ksfraser\FormBuilder\Service\FormActionParamServiceInterface;

/**
 * CreateLeadProcessor — FA implementation.
 *
 * Creates a CRM lead using hook_invoke('CREATE_CUSTOMER', ...) or direct
 * db_* functions to create a debtor and link it as a CRM lead.
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class CreateLeadProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'create_lead';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        if (!function_exists('db_query') || !function_exists('db_escape') || !function_exists('db_insert_id')) {
            throw FormBuilderException::creationFailed('FA db_* functions not available');
        }

        // Map form fields to lead fields using action parameters
        $email = $submissionValues['email'] ?? null;
        $name = $submissionValues['name'] ?? null;
        $phone = $submissionValues['phone'] ?? null;
        $company = $submissionValues['company'] ?? null;

        if (empty($email)) {
            throw FormBuilderException::invalidData(['Email is required for lead creation']);
        }

        // Check if debtor already exists by email
        $sql = "SELECT `debtor_no`, `name` FROM `" . TB_PREF . "debtors_master` WHERE `email` = " . db_escape($email);
        $result = db_query($sql, 'Could not query debtor by email');
        $existing = db_fetch($result);

        if ($existing) {
            $debtorNo = (int)$existing['debtor_no'];
            $debtorName = $existing['name'];
        } else {
            // Create new debtor using FA's add_customer()
            if (!function_exists('add_customer')) {
                throw FormBuilderException::creationFailed('FA add_customer() not available');
            }

            add_customer(
                $name ?: $email,
                $email, // reference
                '',     // address
                '',     // tax_id
                '',     // currency
                0,      // dimension_id
                0,      // dimension2_id
                '0',    // credit_status
                1,      // payment_terms
                0,      // discount
                0,      // payment_discount
                0,      // credit_limit
                1,      // sales_type
                ''      // notes
            );

            $debtorNo = (int)db_insert_id();
            $debtorName = $name ?: $email;

            if ($debtorNo <= 0) {
                throw FormBuilderException::creationFailed('add_customer() did not return a debtor_no');
            }
        }

        // Create CRM contact linked to the debtor
        if (!function_exists('add_crm_person') || !function_exists('add_crm_contact')) {
            throw FormBuilderException::creationFailed('FA add_crm_person() or add_crm_contact() not available');
        }

        $nameParts = explode(' ', $debtorName, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        add_crm_person(
            $debtorNo,  // person_code
            $firstName,
            $lastName,
            '',         // address
            $phone ?: '',  // phone
            '',         // fax
            '',         // mobile
            $email ?: '',  // email
            '',         // website
            ''          // notes
        );

        $personId = (int)db_insert_id();

        if ($personId > 0 && function_exists('add_crm_contact')) {
            add_crm_contact('customer', 'general', $debtorNo, $personId);
        }

        return [
            'success' => true,
            'lead_id' => $debtorNo,
            'lead_name' => $debtorName,
            'contact_id' => $personId > 0 ? $personId : null,
        ];
    }
}
