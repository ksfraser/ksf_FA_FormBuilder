<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\FormBuilder\App;

use ksfraser\FrontAccounting\Common\App\AbstractFrontController;

/**
 * FormBuilderFrontController — single front controller.
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */
class FormBuilderFrontController extends AbstractFrontController
{
    public function __construct(
        FormBuilderAppShell $appShell,
        string $fallbackSecurityArea,
        string $path_to_root
    ) {
        parent::__construct($appShell, $fallbackSecurityArea, $path_to_root);
    }
}
