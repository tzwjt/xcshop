<?php
namespace dao\extend;

class DikaUtil
{
    
    //递归求笛卡尔积函数
    static public function combineDika($arrayData ,$arrayLen)
    {
        $data = $arrayData;
        $cnt = $arrayLen;
        
        $result = array();
        foreach($data[0] as $item) {
            $result[] = array($item);
        }
        for($i = 1; $i < $cnt; $i++) {
            $result = $this->combineArray($result,$data[$i]);
        }
        return $result;
    }
    
    //求两个数组的笛卡尔积
    static private function combineArray($arr1,$arr2)
    {
        $result = array();
        foreach ($arr1 as $item1) {
            foreach ($arr2 as $item2) {
                $temp = $item1;
                $temp[] = $item2;
                $result[] = $temp;
            }
        }
        return $result;
    }
}

