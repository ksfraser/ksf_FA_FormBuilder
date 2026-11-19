<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * CreateBranchProcessor — FA implementation.
 *
 * Creates a FA branch using add_branch().
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class CreateBranchProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'create_branch';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        if (!function_exists('db_query') || !function_exists('db_escape') || !function_exists('db_insert_id') || !function_exists('add_branch')) {
            throw FormBuilderException::creationFailed('FA db_* functions or add_branch() not available');
        }

        $debtorNo = (int)($params['debtor_id'] ?? $submissionValues['debtor_id'] ?? 0);
        if ($debtorNo <= 0) {
            throw FormBuilderException::invalidData(['debtor_id is required for branch creation']);
        }

        $name = $params['name'] ?? $submissionValues['name'] ?? 'Default Branch';
        $reference = $params['reference'] ?? $submissionValues['reference'] ?? null;
        $address = $params['address'] ?? null;
        $phone = $params['phone'] ?? null;
        $email = $params['email'] ?? null;

        add_branch(
            $debtorNo,
            $name,
            $reference ?: '',
            $address ?: '',
            0,      // salesman
            0,      // area
            0,      // tax_group_id
            '',     // discount_act
            '',     // discount_act2
            '',     // debtors_act
            '',     // prompt_act
            0,      // default_location
            $address ?: '',  // br_post_address
            0,      // group_no
            0,      // default_ship_via
            '',     // notes
            0       // bank_account
        );

        $branchId = (int)db_insert_id();

        if ($branchId <= 0) {
            throw FormBuilderException::creationFailed('add_branch() did not return a branch_id');
        }

        return [
            'success' => true,
            'branch_id' => $branchId,
            'debtor_id' => $debtorNo,
            'name' => $name,
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
