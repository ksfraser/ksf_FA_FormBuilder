<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * CreateDebtorProcessor — FA implementation.
 *
 * Creates a FA debtor/customer using add_customer().
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class CreateDebtorProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'create_debtor';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        if (!function_exists('db_query') || !function_exists('db_escape') || !function_exists('db_insert_id') || !function_exists('add_customer')) {
            throw FormBuilderException::creationFailed('FA db_* functions or add_customer() not available');
        }

        // Map form fields to debtor fields using action parameters
        $params = $this->buildParams($params, $submissionValues);

        $name = $params['name'] ?? $submissionValues['name'] ?? null;
        $reference = $params['reference'] ?? $submissionValues['reference'] ?? null;
        $address = $params['address'] ?? null;
        $tax_id = $params['tax_id'] ?? null;
        $curr_code = $params['currency'] ?? null;
        $discount = (float)($params['discount'] ?? 0);
        $payment_terms = (int)($params['payment_terms'] ?? 1);
        $discount_pct = (float)($params['discount_pct'] ?? 0);
        $payment_discount = (float)($params['payment_discount'] ?? 0);
        $credit_limit = (float)($params['credit_limit'] ?? 0);
        $sales_type = (int)($params['sales_type'] ?? 1);
        $notes = $params['notes'] ?? null;

        if (empty($name)) {
            throw FormBuilderException::invalidData(['Name is required for debtor creation']);
        }

        add_customer(
            $name,
            $reference ?: '',
            $address ?: '',
            $tax_id ?: '',
            $curr_code ?: '',
            0,      // dimension_id
            0,      // dimension2_id
            '0',    // credit_status
            $payment_terms,
            $discount,
            $payment_discount,
            $credit_limit,
            $sales_type,
            $notes ?: ''
        );

        $debtorNo = (int)db_insert_id();

        if ($debtorNo <= 0) {
            throw FormBuilderException::creationFailed('add_customer() did not return a debtor_no');
        }

        return [
            'success' => true,
            'debtor_id' => $debtorNo,
            'debtor_no' => $debtorNo,
            'name' => $name,
        ];
    }

    private function buildParams(array $params, array $submissionValues): array
    {
        $result = [];
        foreach ($params as $param) {
            $sourceField = $param['source_field'];
            $targetField = $param['target_field'];
            if (isset($submissionValues[$sourceField])) {
                $result[$targetField] = $submissionValues[$sourceField];
            } elseif (isset($param['default_value'])) {
                $result[$targetField] = $param['default_value'];
            }
        }
        return $result;
    }
}
