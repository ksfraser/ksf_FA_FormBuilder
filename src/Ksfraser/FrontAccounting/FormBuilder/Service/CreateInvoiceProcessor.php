<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * CreateInvoiceProcessor — FA implementation.
 *
 * Creates a FA sales invoice using FA's cart system (write_sales_invoice()).
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class CreateInvoiceProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'create_invoice';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        if (!function_exists('db_query') || !function_exists('db_escape') || !function_exists('db_insert_id') || !function_exists('add_customer') || !function_exists('add_branch') || !class_exists('Cart')) {
            throw FormBuilderException::creationFailed('FA db_* functions, add_customer(), add_branch(), or Cart class not available');
        }

        $debtorNo = (int)($params['debtor_id'] ?? $submissionValues['debtor_id'] ?? 0);
        if ($debtorNo <= 0) {
            throw FormBuilderException::invalidData(['debtor_id is required for invoice creation']);
        }

        $branchId = (int)($params['branch_id'] ?? 0);
        if ($branchId <= 0) {
            // Get default branch
            if (function_exists('get_default_branch')) {
                $branch = get_default_branch($debtorNo);
                $branchId = (int)($branch['branch_id'] ?? 0);
            }
            if ($branchId <= 0) {
                throw FormBuilderException::invalidData(['branch_id is required for invoice creation']);
            }
        }

        $documentDate = $params['document_date'] ?? $submissionValues['document_date'] ?? date('Y-m-d');
        $dueDate = $params['due_date'] ?? $submissionValues['due_date'] ?? null;
        $reference = $params['reference'] ?? $submissionValues['reference'] ?? null;
        $memo = $params['memo'] ?? $submissionValues['memo'] ?? null;

        // Get customer info
        if (!function_exists('get_customer')) {
            throw FormBuilderException::creationFailed('FA get_customer() not available');
        }
        $customer = get_customer($debtorNo);
        if (!$customer) {
            throw FormBuilderException::invalidData(['Debtor #' . $debtorNo . ' not found']);
        }

        $currency = $params['currency'] ?? $customer['curr_code'] ?? '';

        // Build cart
        $cart = new \Cart(\ST_SALESINVOICE);
        $cart->trans_type = \ST_SALESINVOICE;
        $cart->trans_no = 0;
        $cart->customer_id = $debtorNo;
        $cart->branch_id = $branchId;
        $cart->document_date = $documentDate;
        $cart->due_date = $dueDate;
        $cart->reference = $reference ?: '';
        $cart->Comments = $memo ?: '';

        $cart->set_customer(
            $debtorNo,
            $customer['name'],
            $currency,
            $customer['discount'],
            $customer['payment_terms']
        );
        $cart->set_branch($branchId, 0, '', $customer['phone'], $customer['email']);

        // Add line items from form values
        $lineItems = $params['line_items'] ?? [];
        if (empty($lineItems)) {
            // Try to build from submission values with line_item_ prefix
            foreach ($submissionValues as $key => $value) {
                if (strpos($key, 'line_item_') === 0) {
                    $parts = explode('_', $key, 3);
                    if (count($parts) === 3) {
                        $lineItems[$parts[1]][$parts[2]] = $value;
                    }
                }
            }
        }

        if (empty($lineItems)) {
            throw FormBuilderException::invalidData(['Invoice requires at least one line item']);
        }

        foreach ($lineItems as $lineNo => $line) {
            $stockId = $line['stock_id'] ?? '';
            $quantity = (float)($line['quantity'] ?? 1);
            $price = (float)($line['price'] ?? 0);
            $discount = (float)($line['discount'] ?? 0);
            $description = $line['description'] ?? '';

            if (empty($stockId)) {
                continue;
            }

            $added = $cart->add_to_cart(
                $lineNo,
                $stockId,
                $quantity,
                $price,
                $discount,
                0,      // qty_done
                0,      // standard_cost
                $description
            );

            if (!$added) {
                throw FormBuilderException::creationFailed('FA rejected invoice line for stock_id: ' . $stockId);
            }
        }

        // Write the invoice
        $transNo = $cart->write();

        if (!$transNo) {
            throw FormBuilderException::creationFailed('write_sales_invoice() did not return an invoice number');
        }

        return [
            'success' => true,
            'invoice_id' => (int)$transNo,
            'invoice_no' => (int)$transNo,
            'trans_type' => \ST_SALESINVOICE,
            'debtor_id' => $debtorNo,
            'branch_id' => $branchId,
            'line_count' => count($lineItems),
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
