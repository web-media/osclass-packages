<?php
/*
 * Copyright 2019 CodexiLab
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
 
/**
 * Controller Packages system plugin
 */
class CAdminPackages extends AdminSecBaseModel
{

    //Business Layer...
    public function doModel()
    {
        $packageById = Packages::newInstance()->getPackageById(Params::getParam('package'));

        switch (Params::getParam('plugin_action')) {
            case 'set':
                $packageId  = ($packageById) ? $packageById['pk_i_id'] : 0;
                $freeItems  = (Params::getParam('i_free_items') == '') ? 0 : Params::getParam('i_free_items');
                $price      = (Params::getParam('i_price') == '') ? 0 : Params::getParam('i_price');

                // Form validation
                if (!is_numeric($freeItems) || !is_numeric($price)) {
                    osc_add_flash_error_message(__('Free listings, Free days and Price are numeric fields.', 'packages'), 'admin');

                } elseif (!Params::getParam('s_name')) {
                    osc_add_flash_error_message(__('The name can not by empty.', 'packages'), 'admin');

                } else {
                    $data = array(
                        'pk_i_id'           => $packageId,
                        's_name'            => Params::getParam('s_name'),
                        'b_company'         => Params::getParam('b_company'),
                        'i_free_items'      => $freeItems,
                        's_pay_frequency'   => Params::getParam('s_pay_frequency'),
                        'b_active'          => Params::getParam('b_active'),
                        'i_price'           => $price
                    );
                    
                    // Create package
                    if (!$packageId) {
                        $data['dt_date'] = todaydate();
                        Packages::newInstance()->setPackage($data);
                        osc_add_flash_ok_message(__("A new package has been added correctly.", 'packages'), 'admin');

                    // Update package, if exist the package
                    } else {
                        $data['dt_update'] = todaydate();
                        Packages::newInstance()->setPackage($data);
                        osc_add_flash_ok_message(__("The package has been updated correctly.", 'packages'), 'admin');
                    }
                    
                }
                ob_get_clean();
                $this->redirectTo($_SERVER['HTTP_REFERER']);
                break;

            case 'delete':
                $i = 0;
                $packagesId = Params::getParam('id');

                if (!is_array($packagesId)) {
                    osc_add_flash_error_message(__('Select package.', 'packages'), 'admin');
                } else {
                    foreach ($packagesId as $id) {
                        if (Packages::newInstance()->deletePackage($id)) $i++;
                    }
                    if ($i == 0) {
                        osc_add_flash_error_message(__('No package have been deleted.', 'packages'), 'admin');
                    } else {
                        osc_add_flash_ok_message(__($i.' package(s) have been deleted.', 'packages'), 'admin');
                    }
                }
                ob_get_clean();
                $this->redirectTo($_SERVER['HTTP_REFERER']);
                break;

            case 'activate':
                $i = 0;
                $packagesId = Params::getParam('id');

                if (!is_array($packagesId)) {
                    osc_add_flash_error_message(__('Select package.', 'packages'), 'admin');
                } else {
                    foreach ($packagesId as $id) {
                        $data = array(
                            'pk_i_id'   => $id,
                            'dt_update' => todaydate(),
                            'b_active'  => 1
                        );
                        if (Packages::newInstance()->setPackage($data)) $i++;
                    }
                    if ($i == 0) {
                        osc_add_flash_error_message(__('No packages have been activated.', 'packages'), 'admin');
                    } else {
                        osc_add_flash_ok_message(__($i.' package(s) have been activated.', 'packages'), 'admin');
                    }
                }
                ob_get_clean();
                $this->redirectTo($_SERVER['HTTP_REFERER']);
                break;

            case 'deactivate':
                $i = 0;
                $packagesId = Params::getParam('id');

                if (!is_array($packagesId)) {
                    osc_add_flash_error_message(__('Select package.', 'packages'), 'admin');
                } else {
                    foreach ($packagesId as $id) {
                        $data = array(
                            'pk_i_id'   => $id,
                            'dt_update' => todaydate(),
                            'b_active'  => 0
                        );
                        if (Packages::newInstance()->setPackage($data)) $i++;
                    }
                    if ($i == 0) {
                        osc_add_flash_error_message(__('No package have been deactivated.', 'packages'), 'admin');
                    } else {
                        osc_add_flash_ok_message(__($i.' package(s) have been deactivated.', 'packages'), 'admin');
                    }
                }
                ob_get_clean();
                $this->redirectTo($_SERVER['HTTP_REFERER']);
                break;

            case 'set_default':
                $package = get_package_by_id(Params::getParam('id'));
                if ($package && $package['b_company']) {
                    osc_set_preference('default_company_package', Params::getParam('id'), 'packages', 'STRING');
                } else {
                    osc_set_preference('default_package', Params::getParam('id'), 'packages', 'STRING');
                }

                osc_add_flash_ok_message(__('Default package selected.', 'packages'), 'admin');
                ob_get_clean();
                $this->redirectTo(osc_route_admin_url('packages-admin'));
                break;

            case 'unset_default':
                $package = get_package_by_id(Params::getParam('id'));
                if ($package && $package['b_company']) {
                    osc_set_preference('default_company_package', 0, 'packages', 'STRING');
                } else {
                    osc_set_preference('default_package', 0, 'packages', 'STRING');
                }

                osc_add_flash_ok_message(__('Default package disabled.', 'packages'), 'admin');
                ob_get_clean();
                $this->redirectTo(osc_route_admin_url('packages-admin'));
                break;
            
            default:
                $this->_exportVariableToView('packageById', $packageById);

                // DataTable
                require_once PACKAGES_PATH . "classes/datatables/PackagesDataTable.php";

                if( Params::getParam('iDisplayLength') != '' ) {
                    Cookie::newInstance()->push('listing_iDisplayLength', Params::getParam('iDisplayLength'));
                    Cookie::newInstance()->set();
                } else {
                    // Set a default value if it's set in the cookie
                    $listing_iDisplayLength = (int) Cookie::newInstance()->get_value('listing_iDisplayLength');
                    if ($listing_iDisplayLength == 0) $listing_iDisplayLength = 10;
                    Params::setParam('iDisplayLength', $listing_iDisplayLength );
                }
                $this->_exportVariableToView('iDisplayLength', Params::getParam('iDisplayLength'));

                $page  = (int)Params::getParam('iPage');
                if($page==0) { $page = 1; };
                Params::setParam('iPage', $page);

                $params = Params::getParamsAsArray();

                $packagesDataTable = new PackagesDataTable();
                $packagesDataTable->table($params);
                $aData = $packagesDataTable->getData();
                $this->_exportVariableToView('aData', $aData);

                if(count($aData['aRows']) == 0 && $page!=1) {
                    $total = (int)$aData['iTotalDisplayRecords'];
                    $maxPage = ceil( $total / (int)$aData['iDisplayLength'] );

                    $url = osc_admin_base_url(true).'?'.$_SERVER['QUERY_STRING'];

                    if($maxPage==0) {
                        $url = preg_replace('/&iPage=(\d)+/', '&iPage=1', $url);
                        ob_get_clean();
                        $this->redirectTo($url);
                    }

                    if($page > $maxPage) {
                        $url = preg_replace('/&iPage=(\d)+/', '&iPage='.$maxPage, $url);
                        ob_get_clean();
                        $this->redirectTo($url);
                    }
                }

                $bulk_options = array(
                    array('value' => '', 'data-dialog-content' => '', 'label' => __('Bulk actions')),
                    array('value' => 'activate', 'data-dialog-content' => sprintf(__('Are you sure you want to %s the selected packages?'), strtolower(__('Activate'))), 'label' => __('Activate')),
                    array('value' => 'deactivate', 'data-dialog-content' => sprintf(__('Are you sure you want to %s the selected packages?'), strtolower(__('Deactivate'))), 'label' => __('Deactivate')),
                    array('value' => 'delete', 'data-dialog-content' => sprintf(__('Are you sure you want to %s the selected packages?'), strtolower(__('Delete'))), 'label' => __('Delete'))
                );

                $bulk_options = osc_apply_filter("package_bulk_filter", $bulk_options);
                $this->_exportVariableToView('bulk_options', $bulk_options);
                break;
        }
    }
    
}