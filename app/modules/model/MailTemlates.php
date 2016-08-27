<?php
namespace Models;

use Engine\AbstractModel;

class MailTemlates extends AbstractModel
{

    /**
     * 取单条信息
     * @param $where
     * @param string $fields
     * @return \Phalcon\Mvc\Model\Ṕhalcon\Mvc\ModelInterface
     * @internal param unknown $condition
     */
    public function getTplInfo($where, $fields = '*')
    {
        return $this->getBuilder('MailTemlates')->where(K($where), V($where))->columns($fields)->getQuery()->getSingleResult();
    }
}
