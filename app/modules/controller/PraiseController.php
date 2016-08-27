<?php

namespace Controllers;

use Models\Praise;

use Phalcon\DI;

class PraiseController extends AbstractController
{
    public function addAction()
    {
        $id = $this->request->getPost('id');
        $cid = $this->request->getPost('cid');
        $modelid = $this->request->getPost('mid');

        if (empty($id) || empty($modelid)) {
            callback(false);
        }

        $model_member = new Praise();
        $praiseid = 'c-' . intval($modelid) . '-' . intval($id);
        $r = $model_member->savePraiseInfo(array('praiseid' => $praiseid), $cid);
        ($r) ? callback(true, 'ok', 201, $r) : callback(false);
    }

    public function delAction()
    {
        $id = $this->request->getPost('id');
        $userid = $this->request->getPost('userid');
        $cid = $this->request->getPost('cid');
        $modelid = $this->request->getPost('mid');

        if (empty($id) || empty($modelid)) {
            callback(false);
        }

        $model_member = new Praise();
        $praiseid = 'c-' . intval($modelid) . '-' . intval($id);
        $r = $model_member->delPraiseInfo(array('praiseid' => $praiseid), $cid);
        ($r) ? callback(true, 'ok', 204) : callback(false);
    }

    public function listAction($id, $mid)
    {
        if (empty($id) || empty($mid)) {
            callback(false);
        }
        $model_member = new Praise();
        $praiseid = 'c-' . intval($mid) . '-' . intval($id);
        $json = $model_member->getPraiseInfo(array('praiseid' => $praiseid), 'count(*) as total');
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }
}
