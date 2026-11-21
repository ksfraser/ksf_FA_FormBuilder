<?php
/**
 * ksf_FA_FormBuilder — Public form submission endpoint.
 *
 * This endpoint accepts form submissions from external (non-FA) websites.
 * The flow is:
 *   External static site → HTML form → POST to this endpoint →
 *   Cloudflare → FW/Gateway → NPM proxy → this PHP script
 *
 * URL: modules/ksf_FA_FormBuilder/submit.php
 *
 * Expected POST fields:
 *   form_slug   - The form's slug (from 0_ksf_formbuilder_forms.slug)
 *   csrf_token  - CSRF token (validated against session)
 *   form_id     - The form's numeric ID
 *   <field_name> - Any form field values
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */

declare(strict_types=1);

chdir(__DIR__);

$path_to_root = '../..';

// Include FA bootstrap (session, database, etc.)
if (file_exists($path_to_root . '/includes/session.inc')) {
    define('IN_FA', true);
    require_once($path_to_root . '/includes/session.inc');
    add_access_extensions();
}

// Load module autoloader
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use ksfraser\FrontAccounting\FormBuilder\Service\FormService;
use ksfraser\FrontAccounting\FormBuilder\Service\FormFieldService;
use ksfraser\FrontAccounting\FormBuilder\Service\FormSubmissionService;
use ksfraser\FrontAccounting\FormBuilder\Service\FormActionService;
use ksfraser\FrontAccounting\FormBuilder\Service\FormActionParamService;
use ksfraser\FrontAccounting\FormBuilder\Service\FormProcessorService;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;

// Set JSON content type
header('Content-Type: application/json');

// Handle GET: return a CSRF token for the client to use
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['formbuilder_csrf_token'])) {
        $_SESSION['formbuilder_csrf_token'] = bin2hex(random_bytes(16));
    }
    echo json_encode([
        'success' => true,
        'csrf_token' => $_SESSION['formbuilder_csrf_token'],
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Read POST data
$input = $_POST;

// Validate required fields
if (empty($input['form_slug'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'form_slug is required']);
    exit;
}

$formSlug = trim($input['form_slug']);
$csrfToken = $input['csrf_token'] ?? '';
$formId = isset($input['form_id']) ? (int)$input['form_id'] : 0;

// Validate CSRF token (simple check against session)
$sessionToken = $_SESSION['formbuilder_csrf_token'] ?? '';
if ($csrfToken === '' || $csrfToken !== $sessionToken) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
    exit;
}

try {
    // Look up the form by slug
    $formService = new FormService();
    $form = $formService->findFormBySlug($formSlug);

    if ($form === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Form not found: ' . $formSlug]);
        exit;
    }

    // Verify form ID matches if provided
    if ($formId !== 0 && $form->id !== $formId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'form_id does not match form_slug']);
        exit;
    }

    // Check form is active and public
    if ($form->status !== 'active') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Form is not active']);
        exit;
    }
    if ((int)$form->is_public !== 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Form is not public']);
        exit;
    }

    // Collect field values from POST (excluding metadata fields)
    $excludeFields = ['form_slug', 'form_id', 'csrf_token'];
    $fieldValues = [];
    foreach ($input as $key => $value) {
        if (!in_array($key, $excludeFields, true)) {
            $fieldValues[$key] = is_array($value) ? $value : trim((string)$value);
        }
    }

    // Build submitter info
    $submitterInfo = [
        'submitter_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
        'submitter_type' => isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'external',
        'submitter_name' => $fieldValues['submitter_name'] ?? '',
        'submitter_email' => $fieldValues['submitter_email'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];

    // Process the submission
    $fieldService = new FormFieldService();
    $submissionService = new FormSubmissionService();
    $actionService = new FormActionService();
    $actionParamService = new FormActionParamService();

    $processor = new FormProcessorService(
        $formService,
        $fieldService,
        $submissionService,
        $actionService,
        $actionParamService
    );

    $result = $processor->processSubmission($form->id, $fieldValues, $submitterInfo);

    // Handle redirect if configured
    $redirectUrl = $form->redirect_url ?? null;
    $submitMessage = $form->submit_message ?? null;

    $response = [
        'success' => true,
        'submission' => $result['submission'],
        'actions' => $result['actions'],
        'message' => $submitMessage,
        'redirect_url' => $redirectUrl,
    ];

    echo json_encode($response);
    exit;

} catch (FormBuilderException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
    ]);
    exit;
}
