<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\Controller\Manage;

use Misc;
use Resolution;

class ResolutionController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/resolution.tpl.html';

    /** @var string */
    private $cat;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'new') {
            $this->newAction();
        } elseif ($this->cat == 'update') {
            $this->updateAction();
        } elseif ($this->cat == 'delete') {
            $this->deleteAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        }
    }

    private function newAction()
    {
        $res = Resolution::insert();
        $map = array(
            1 => array(ev_gettext('Thank you, the issue resolution was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new issue resolution.'), Misc::MSG_INFO),
            -2 => array(ev_gettext('Please enter the title for this new issue resolution.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = Resolution::update();
        $map = array(
            1 => array(ev_gettext('Thank you, the issue resolution was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the new issue resolution.'), Misc::MSG_INFO),
            -2 => array(ev_gettext('Please enter the title for this new issue resolution.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        Resolution::remove();
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Resolution::getDetails($get->getInt('id')));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'list' => Resolution::getList(),
            )
        );
    }
}
