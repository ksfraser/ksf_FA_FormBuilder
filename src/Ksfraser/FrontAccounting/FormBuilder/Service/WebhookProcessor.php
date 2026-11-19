<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;

/**
 * WebhookProcessor — FA implementation.
 *
 * Sends a webhook POST to an external URL with the submission data.
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class WebhookProcessor implements ActionProcessorInterface
{
    public function getActionType(): string
    {
        return 'webhook';
    }

    public function process(FormActionDto $action, FormSubmissionDto $submission, array $submissionValues, array $params): array
    {
        $url = $params['url'] ?? null;
        if (empty($url)) {
            throw FormBuilderException::invalidData(['webhook URL is required']);
        }

        $method = $params['method'] ?? 'POST';
        $headers = isset($params['headers']) && is_array($params['headers']) ? $params['headers'] : [];
        $timeout = (int)($params['timeout'] ?? 30);

        $payload = [
            'submission' => [
                'id' => $submission->id,
                'form_id' => $submission->form_id,
                'submitter_id' => $submission->submitter_id,
                'submitter_name' => $submission->submitter_name,
                'submitter_email' => $submission->submitter_email,
                'ip_address' => $submission->ip_address,
                'created_at' => $submission->created_at,
            ],
            'values' => $submissionValues,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            ['Content-Type: application/json'],
            array_map(function($v, $k) { return $k . ': ' . $v; }, $headers, array_keys($headers))
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw FormBuilderException::creationFailed('Webhook request failed: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw FormBuilderException::creationFailed('Webhook returned HTTP ' . $httpCode . ': ' . $response);
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'response' => $response,
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
