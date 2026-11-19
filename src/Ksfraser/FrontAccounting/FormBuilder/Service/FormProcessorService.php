<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\Service;

use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;
use ksfraser\FormBuilder\Service\FormProcessorInterface as StandaloneProcessorInterface;
use ksfraser\FormBuilder\Service\FormProcessorService as StandaloneProcessor;

/**
 * FormProcessorService — FA adapter implementation of FormProcessorServiceInterface.
 *
 * Processes form submissions into other module records using FA's native db_* functions
 * and hooks. Action processors create FA records:
 *   - create_lead: Create CRM debtor/contact via CREATE_CUSTOMER hook or direct db_*
 *   - create_debtor: Create FA debtor using add_customer()
   - create_branch: Create FA branch using add_branch()
   - create_contact: Create CRM contact using add_crm_person() + add_crm_contact()
   - create_invoice: Create sales invoice using FA cart (write_sales_invoice())
   - create_meeting: Create calendar meeting using db_* functions
   - create_ticket: Create support ticket using db_* functions
   - send_email: Send email using FA mail functions
   - webhook: POST to external webhook
 *
 * @BABOK Related: FR-FB-001-004
 * @since 1.0.0
 */
class FormProcessorService extends \ksfraser\FormBuilder\Service\FormProcessorService
{
    public function __construct(
        \ksfraser\FormBuilder\Service\FormServiceInterface $formService,
        \ksfraser\FormBuilder\Service\FormFieldServiceInterface $fieldService,
        \ksfraser\FormBuilder\Service\FormSubmissionServiceInterface $submissionService,
        \ksfraser\FormBuilder\Service\FormActionServiceInterface $actionService,
        \ksfraser\FormBuilder\Service\FormActionParamServiceInterface $actionParamService
    ) {
        parent::__construct($formService, $fieldService, $submissionService, $actionService, $actionParamService);

        // Register FA-specific action processors
        $this->registerProcessor(new CreateLeadProcessor($this->formService, $this->fieldService, $this->submissionService, $this->actionService, $this->actionParamService));
        $this->registerProcessor(new CreateDebtorProcessor());
        $this->registerProcessor(new CreateBranchProcessor());
        $this->registerProcessor(new CreateContactProcessor());
        $this->registerProcessor(new CreateInvoiceProcessor());
        $this->registerProcessor(new CreateMeetingProcessor());
        $this->registerProcessor(new CreateTicketProcessor());
        $this->registerProcessor(new SendEmailProcessor());
        $this->registerProcessor(new WebhookProcessor());
    }
}
