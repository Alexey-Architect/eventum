<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2009 Sun Microsystem Inc.                       |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: custom_fields.php 3834 2009-02-10 07:37:26Z glen $
//
require_once(dirname(__FILE__) . "/../init.php");
require_once(APP_INC_PATH . "class.template_helper.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.custom_field.php");
require_once(APP_INC_PATH . "class.project.php");
require_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_Helper();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "custom_fields");

$role_id = Auth::getCurrentRole();
if ($role_id == User::getRoleID('administrator')) {
    $tpl->assign("show_setup_links", true);

    if (@$_POST["cat"] == "new") {
        $tpl->assign("result", Custom_Field::insert());
    } elseif (@$_POST["cat"] == "update") {
        $tpl->assign("result", Custom_Field::update());
    } elseif (@$_POST["cat"] == "delete") {
        Custom_Field::remove();
    }elseif (@$_REQUEST["cat"] == "change_rank") {
        Custom_Field::changeRank();
    }

    if (@$_GET["cat"] == "edit") {
        $tpl->assign("info", Custom_Field::getDetails($_GET["id"]));
    }
    
    $excluded_roles = array();
    if (!Customer::hasCustomerIntegration(Auth::getCurrentProject())) {
        $excluded_roles[] = "customer";
    }
    $user_roles = User::getRoles($excluded_roles);
    $user_roles[9] = "Never Display";

    $tpl->assign("list", Custom_Field::getList());
    $tpl->assign("project_list", Project::getAll());
    $tpl->assign("user_roles", $user_roles);
    $tpl->assign("backend_list", Custom_Field::getBackendList());
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
