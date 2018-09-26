<?php
/**
 * 
 * ============================================================================
 *操作接口
 * ============================================================================
 * 2018-08-27
 */
namespace app\Appapi\controller;

use app\common\logic\CartLogic;
use app\common\logic\MessageLogic;
use app\common\logic\UsersLogic;
use app\common\logic\OrderLogic;
use app\common\logic\CouponLogic;
use think\Page;
use think\Request;
use think\Verify;
use think\db;
use think\Session ;

// 指定允许其他域名访问  
header('Access-Control-Allow-Origin:*');  
// 响应类型  
header('Access-Control-Allow-Methods:*');  
// 响应头设置  
header('Access-Control-Allow-Headers:x-requested-with,content-type');  

class Operation extends MobileBase
{

    //更新使用劵的状态  0点定时 用户查看
	public function updateUsecode(){
        $where['is_shi'] = 0;
        $usecode = db('sz_yi_usecode') -> where($where) -> select();
        foreach ($usecode as $vs) {
            $endtime = floatval($vs['endtime']) ;
            if( time() > $endtime){
                //当前时间戳 大于 使用劵的结束时间戳 就更新
                $savedata['is_shi'] = 1 ;
                db('sz_yi_usecode') -> where(array('id'=>$vs['id'])) -> update($savedata);
            }
        }
    }

    
    

}
