<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * SendEmailProcessor — FA implementation.
 *
 * Sends an email using FA's mail functions or PHP mail().
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class SendEmailProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'send_email';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        $to = $params['to'] ?? $submissionValues['email'] ?? null;
        $subject = $params['subject'] ?? $submissionValues['subject'] ?? 'Form Submission';
        $message = $params['message'] ?? $submissionValues['message'] ?? '';
        $from = $params['from'] ?? null;

        if (empty($to)) {
            throw FormBuilderException::invalidData(['Email recipient (to) is required']);
        }

        $headers = '';
        if ($from) {
            $headers .= 'From: ' . $from . "\r\n";
        }
        $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

        $messageBody = '<html><body>';
        $messageBody .= '<h2>Form Submission</h2>';
        $messageBody .= '<table border="1" cellpadding="5">';
        foreach ($submissionValues as $field => $value) {
            $messageBody .= '<tr><td><strong>' . htmlspecialchars($field) . '</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        $messageBody .= '</table>';
        $messageBody .= '</body></html>';

        $sent = @mail($to, $subject, $messageBody, $headers);

        if (!$sent) {
            throw FormBuilderException::creationFailed('mail() function failed to send email');
        }

        return [
            'success' => true,
            'to' => $to,
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
