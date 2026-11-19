<?php
/**
 * ksf_FA_FormBuilder — single front controller.
 *
 * URL shape:
 *   index.php                                default tab (forms list)
 *   index.php?view=forms                     read a tab
 *   index.php?tab=forms                      alias
 *   index.php?view=forms&page=3              tab + paging
 *
 * @package ksf_FA_FormBuilder
 * @since   1.0.0
 */

chdir(__DIR__);

if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
}

$path_to_root = "../..";

$frontController = new \Ksfraser\FrontAccounting\FormBuilder\App\FormBuilderFrontController(
    new \Ksfraser\FrontAccounting\FormBuilder\App\FormBuilderAppShell(),
    'SA_ksf_FA_FormBuilderVIEW',
    $path_to_root
);

$page_security = $frontController->getSecurityArea();
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");

$frontController->run();
