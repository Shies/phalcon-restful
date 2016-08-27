<?php

namespace Engine;
use Phalcon\DI;

/* *
 * Phalcon\Filter
 * This's a abstract filter class, author ___Shies
 */
class Filter
{
    // some global function
    private static $struct;

    public function __construct()
    {
        self::$struct = [
            'isword' => false,
            'childs' => []
        ];
    }

    public function &setWord($word)
    {
        $struct =& self::$struct;
        $gather = str_split($word);

        reset($gather);
        while ($opt = current($gather))
        {
            // if word not to end,then record it's status
            if (!isset($struct['childs'][$opt]))
            {
                $struct['childs'][$opt] = ['isword' => false];
            }

            // if substr end, then set it's status
            if (key($gather) == count($gather) - 1)
            {
                $struct['childs'][$opt] = ['isword' => true];
            }

            $struct =& $struct['childs'][$opt];
            next($gather);
        }

        return $struct;
    }

    public function isWord($word)
    {
        $struct =& self::$struct;
        $gather = str_split($word);

        reset($gather);
        while ($opt = current($gather))
        {
            // if struct in not opt, then direct exit
            if (!isset($struct['childs'][$opt]))
            {
                return false;
            }

            // preg word status for true or false
            $bool = end($struct['childs'][$opt]);
            if (key($gather) == count($gather) - 1)
            {
                if ($bool)
                {
                    return true;
                }
                return false;
            }

            $struct =& $struct['childs'][$opt];
            next($gather);
        }

        return $struct;
    }

    public function search($text = null)
    {
        $struct = $trie = self::$struct;
        $find = [];
        $wordrootposition = 0; // word root position
        $prenode = false; // prev a node go find
        $word = '';

        for ($i = 0; $i < strlen($text); $i ++)
        {
            if (!isset($struct['childs'][$text{$i}]))
            {
                $struct = $trie;
                $word = '';

                if ($prenode)
                {
                    $i -= 1;
                    $prenode = false;
                }
            }
            else
            {
                $word .= $text{$i};
                $struct = $struct['childs'][$text{$i}];

                if ($prenode == false)
                {
                    $wordrootposition = $i;
                }

                $prenode = true;
                if ($struct['isword'])
                {
                    $find[] = array(
                        'word' => $word,
                        'position' => $wordrootposition
                    );
                }
            }
        }

        return $find;
    }
}
