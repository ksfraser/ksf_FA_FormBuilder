<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\Menu\FAModuleMenu;
use Ksfraser\FrontAccounting\FormBuilder\Controller\FormController;
use Ksfraser\FrontAccounting\FormBuilder\Controller\FieldController;
use Ksfraser\FrontAccounting\FormBuilder\Controller\SubmissionController;
use Ksfraser\FrontAccounting\FormBuilder\Controller\ActionController;

/**
 * FormBuilderAppShell — application shell SRP.
 *
 * Registers the Form Builder core tabs, then fires `formbuilder_register_tabs`
 * on boot() so other modules can register their own tabs.
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */
class FormBuilderAppShell extends AbstractAppShell
{
    public function __construct()
    {
        parent::__construct('formbuilder', 'index.php', 'view', 'forms');

        $this->add_module(_("Form Builder"));

        $menu = new FAModuleMenu(
            'modules/ksf_FA_FormBuilder/index.php',
            'view',
            ''
        );

        $menu->addItem('forms', _("Forms"), MENU_INQUIRY)
             ->registerWithApp($this, 'SA_ksf_FA_FormBuilderVIEW');
        $menu->addItem('fields', _("Fields"), MENU_INQUIRY)
             ->registerWithApp($this, 'SA_ksf_FA_FormBuilderVIEW');
        $menu->addItem('submissions', _("Submissions"), MENU_INQUIRY)
             ->registerWithApp($this, 'SA_ksf_FA_FormBuilderVIEW');
        $menu->addItem('actions', _("Actions"), MENU_INQUIRY)
             ->registerWithApp($this, 'SA_ksf_FA_FormBuilderMANAGE');
        $menu->addItem('config', _("Config"), MENU_INQUIRY)
             ->registerWithApp($this, 'SA_ksf_FA_FormBuilderMANAGE');

        $this->add_extensions();
    }
}
