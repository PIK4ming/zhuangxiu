<?php
/**
 * 
 * ============================================================================
 *商城接口
 * ============================================================================
 * 2018-09-26
 * 作者:zzs
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

class Shopsup extends MobileBase
{   

    //商城首页接口
	public function shopindex(){

        $advment = db('ad')->where(array('pid'=>1,'enabled'=>1))->field('ad_id,ad_name,ad_code,ad_link')->select(); //查询轮播图的信息
        $indexarr['bannerimg'] = $advment ; //获取轮播图信息

        $categoryone = db('goods_category')->where(array('parent_id'=>0,'is_show'=>1))->field('id,mobile_name')->select(); //查询一级分类的数组信息
        foreach ($categoryone as $kg => $vag) {
             $categorytwo = db('goods_category')->where(array('parent_id'=>$vag['id'],'is_show'=>1))->field('id,mobile_name')->select(); //查询二级分类的数组信息
             foreach ($categorytwo as $khh => $vahh) {
                 $categorythree = db('goods_category')->where(array('parent_id'=>$vahh['id'],'is_show'=>1))->field('id,mobile_name')->select(); //查询三级分类的数组信息   
                 $categorytwo[$khh]['threearr'] = $categorythree ;
             }
             $categoryone[$kg]['twoarr'] = $categorytwo ;
        }

        var_dump('<pre>');
        var_dump($categoryone);die;

        $indexarr['categoryone'] = $categoryone ;
        

        $goodsarr = db('goods')->where(array('is_on_sale'=>1))->field('goods_id,original_img,goods_name,is_on_sale,is_recommend')->order('on_time desc')->select(); //获取所有上架商品信息
        $indexarr['goodsarr'] = $goodsarr ;



    }
    

}
