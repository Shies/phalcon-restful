<?php

namespace Models;

use Engine\AbstractModel;
use Phalcon\DI;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

class Badword extends AbstractModel
{
    public function getBadWord(array $param = array(), $condi = '')
    {
        if (isset($condi)) $condi = ['1' => 1];
        $date = rkcache("badword");
        if (empty($date)) {
            $date = $this->getBuilder('Badword')->columns('*')->where(K($condi), V($condi))->orderBy('badid')->getQuery()->execute()->toArray();
            if (empty($date)) {
                return false;
            }
            wkcache("badword", $date);
        }
        return $date;
    }

    /**
     * 敏感词处理接口
     * 对传递的数据进行处理,并返回
     * @param $str
     * @return mixed
     * @throws \Exception
     */
    public function replace_badword($str)
    {
        //读取敏感词缓存
        $badword_cache = rkcache('badword');
        foreach ($badword_cache as $data) {
            if ($data['replaceword'] == '') {
                $replaceword_new = '*';
            } else {
                $replaceword_new = $data['replaceword'];
            }
            $replaceword[] = ($data['level'] == '1') ? $replaceword_new : '';
            $replace[] = $data['badword'];
        }

        return str_replace($replace, $replaceword, $str);
    }
}