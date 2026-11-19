<?php
declare(strict_types=1);

/**
 * ksf_FA_FormBuilder - FrontAccounting Module
 *
 * FA adapter over ksfraser/ksf-formbuilder standalone business logic.
 *
 * @package   ksfraser\FrontAccounting\FormBuilder
 * @author    Kevin Fraser
 * @version   2.4.19-0
 * @license   GPL-3.0
 *
 * @BABOK Related: FR-FB-001
 *
 * @hook      install           Called when module is installed.
 * @hook      uninstall         Called when module is deleted.
 * @hook      install_options   Register pages under Form Builder app.
 * @hook      install_access    Define security areas.
 * @hook      activate_extension Install SQL schema.
 */

define('SS_ksf_FA_FormBuilder', 149 << 8);

$autoload = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use Ksfraser\FrontAccounting\FormBuilder\App\FormBuilderAppShell;
use Ksfraser\FrontAccounting\FormBuilder\App\FormBuilderFrontController;
use ksfraser\FormBuilder\Dto\FormActionDto;
use ksfraser\FormBuilder\Dto\FormSubmissionDto;
use ksfraser\FormBuilder\Service\ActionProcessorInterface;
use ksfraser\FormBuilder\Service\FormProcessorService;
use ksfraser\FormBuilder\Exceptions\FormBuilderException;

class hooks_ksf_FA_FormBuilder extends hooks
{
    use \Ksfraser\Traits\HookQueryProviderTrait;

    var $module_name = 'ksf_FA_FormBuilder';
    var $version     = '2.4.19-0';

    protected function _getAdvertisedValues(): array
    {
        return [
            'formbuilder.hooks_version' => '1.0',
            'formbuilder.module_version' => '1.0.0',
            'formbuilder.features' => ['forms', 'fields', 'submissions', 'actions', 'process_submission'],
        ];
    }

    public function getModuleConstants(&$data, $opts = null)
    {
        $constants = [
            'KSF_FORMBUILDER_MODULE_NAME' => $this->module_name,
            'KSF_FORMBUILDER_CAPABILITIES' => 'form_create,field_create,submission_create,action_create,form_process',
        ];
        $data['constants'] = $constants;
        $data['_module'] = $this->module_name;
        return $constants;
    }

    public function getModuleCapabilities(&$data, $opts = null)
    {
        $capabilities = [
            'form_create' => [
                'description' => 'Create dynamic forms',
                'methods' => ['CREATE_FORM', 'FORM_CREATED'],
            ],
            'field_create' => [
                'description' => 'Create form fields',
                'methods' => ['CREATE_FIELD', 'FIELD_CREATED'],
            ],
            'submission_create' => [
                'description' => 'Create form submissions',
                'methods' => ['CREATE_SUBMISSION', 'SUBMISSION_CREATED'],
            ],
            'action_create' => [
                'description' => 'Create post-submission actions',
                'methods' => ['CREATE_ACTION', 'ACTION_CREATED'],
            ],
            'form_process' => [
                'description' => 'Process form submissions into other module records',
                'methods' => ['PROCESS_SUBMISSION', 'SUBMISSION_PROCESSED'],
            ],
        ];
        $data['capabilities'] = $capabilities;
        $data['_module'] = $this->module_name;
        return $capabilities;
    }

    public function hasCapability(&$data, $opts = null)
    {
        $cap = $opts['capability'] ?? null;
        if ($cap === null) {
            return false;
        }
        $available = array_keys($this->getModuleCapabilities($data)['capabilities'] ?? []);
        return in_array($cap, $available, true);
    }

    public function respondToCapabilityRequest(&$data, $opts = null)
    {
        $cap = $opts['capability'] ?? null;
        if ($cap === null || !$this->hasCapability($data, $opts)) {
            $data['supported'] = false;
            return;
        }
        $data['supported'] = true;
        $data['module'] = $this->module_name;
    }

    /**
     * Register module menus under Form Builder application
     */
    function install_options($app) {
        if ($app->id == 'orders' && method_exists($this, 'add_module_app')) {
            $this->add_module_app('formbuilder_forms', _("Forms"), 'modules/ksf_FA_FormBuilder/index.php?view=forms', SA_ksf_FA_FormBuilderVIEW);
            $this->add_module_app('formbuilder_fields', _("Fields"), 'modules/ksf_FA_FormBuilder/index.php?view=fields', SA_ksf_FA_FormBuilderVIEW);
            $this->add_module_app('formbuilder_submissions', _("Submissions"), 'modules/ksf_FA_FormBuilder/index.php?view=submissions', SA_ksf_FA_FormBuilderVIEW);
            $this->add_module_app('formbuilder_actions', _("Actions"), 'modules/ksf_FA_FormBuilder/index.php?view=actions', SA_ksf_FA_FormBuilderMANAGE);
            $this->add_module_app('formbuilder_config', _("Config"), 'modules/ksf_FA_FormBuilder/index.php?view=config', SA_ksf_FA_FormBuilderMANAGE);
        }
    }

    /**
     * Define security permission areas
     */
    function install_access() {
        $security_sections[SS_ksf_FA_FormBuilder] = _("Form Builder");
        $security_areas['SA_ksf_FA_FormBuilderVIEW'] = array(
            SS_ksf_FA_FormBuilder | 1,
            _("View Form Builder")
        );
        $security_areas['SA_ksf_FA_FormBuilderMANAGE'] = array(
            SS_ksf_FA_FormBuilder | 2,
            _("Manage Form Builder Config")
        );
        return array($security_areas, $security_sections);
    }

    /**
     * Install database schema on activation
     */
    function activate_extension($company, $check_only=true) {
        $this->ensure_composer_dependencies();

        if (!$this->ksf_fa_common_available()) {
            display_warning(_('Form Builder requires the ksf-fa-common Composer package. Run composer install, then re-activate Form Builder.'));
            return false;
        }

        if (!$check_only) {
            $this->install_common_table('ksf_modules.sql');
        }

        if (file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            $updates = array(
                'ksf_formbuilder_forms.sql'     => array('formbuilder_forms'),
                'ksf_formbuilder_fields.sql'    => array('formbuilder_fields'),
                'ksf_formbuilder_submissions.sql'=> array('formbuilder_submissions'),
                'ksf_formbuilder_values.sql'    => array('formbuilder_submission_values'),
                'ksf_formbuilder_actions.sql'   => array('formbuilder_actions'),
                'ksf_formbuilder_params.sql'    => array('formbuilder_action_params'),
            );
            return $this->update_databases($company, $updates, $check_only);
        }

        return true;
    }

    private function ksf_fa_common_available()
    {
        $autoload = dirname(__FILE__) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return false;
        }
        return class_exists('ksfraser\\FrontAccounting\\Common\\Utils\\SchemaInstaller', false);
    }

    private function install_common_table(string $tableFile): bool
    {
        $vendorDir = dirname(__FILE__) . '/vendor/ksfraser/ksf-fa-common';
        $sqlPath   = $vendorDir . '/sql/' . $tableFile;

        if (!file_exists($sqlPath)) {
            error_log('KSF Module: common table SQL not found: ' . $sqlPath);
            return false;
        }

        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            return false;
        }

        $sql = preg_replace('/--[^\n]*/', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $statements = array_map('trim', explode(';', $sql));

        $ok = true;
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }
            if (!db_query($stmt, 'Common table install failed: ' . substr($stmt, 0, 80))) {
                $ok = false;
            }
        }
        return $ok;
    }

    private function ensure_composer_dependencies() {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';

        if (file_exists($autoload_path)) {
            return;
        }

        $composer_path = $module_dir . '/composer.json';
        if (!file_exists($composer_path)) {
            return;
        }

        chdir($module_dir);
        $output = array();
        $return_code = 0;
        exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            error_log('KSF Module: composer install failed: ' . implode("\n", $output));
        }
    }
}
