<?php
/**
 * 
 * ============================================================================
 *用户接口
 * ============================================================================
 * 2018-06-02
 */
namespace app\Appapi\controller;

use app\common\logic\CartLogic;
use app\common\logic\MessageLogic;
use app\common\logic\UsersLogic;
use app\common\logic\OrderLogic;
use app\common\logic\CouponLogic;
use app\common\logic\GoodsLogic;
use think\Page;
use think\Request;
use think\Verify;
use think\db;
use app\common\logic\Pay;
use app\common\logic\DistributLogic1 ;
use think\Session ;

// 指定允许其他域名访问  
header('Access-Control-Allow-Origin:*');  
// 响应类型  
header('Access-Control-Allow-Methods:*');  
// 响应头设置  
header('Access-Control-Allow-Headers:x-requested-with,content-type');  

class Goodsup extends MobileBase
{

    public function getUsemes(){

        // $userid = Session::get('userid');
        // if(empty($userid)){
        //    $this->ajaxReturn(['status' => -1, 'msg' => '您还没登录!']); 
        // }
        $userid = I('post.userid') ;
        return $userid ;
    }

	/*
     * 获取对应的店铺信息
     */
    public function getStoremes(){
        $sid = I('post.sid') ; //商家记录id
        $storemes = db('sz_yi_store_data')->where('id',$sid)->find(); //查询店铺信息
        $shangleimes = db('sz_yi_supplier_category')->where('id',$storemes['cate_id'])->find();
        $storemes['leixing'] = $shangleimes['name'] ;
        $goodpinmes = db('goods')->where('supplier_uid',$storemes['storeid'])->select(); //获取商家对应的店铺商品信息
        $storemes['xiangaddress'] = $storemes['provinces'].$storemes['citys'].$storemes['districts'].$storemes['street'] ;
        $storemes['goodarr'] = $goodpinmes ;

        if($storemes['type'] == 1){
            //驾校信息
            $trainermess = db('sz_yi_trainer')->where('userid',$storemes['useid'])->order('orders desc ')->select(); //获取当前驾校教练信息
            foreach ($trainermess as $kt => $vat) {
                $sid = $vat['id'] ;
                $pingfen = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>0))->sum('pingfen'); //获取教练总评分
                $pingfen = floatval($pingfen);
                $zongpingshu = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>0))->count(); //获取评论人数
                $zongpingshu = floatval($zongpingshu);
                if($zongpingshu == 0){
                    $junfen = 0 ;
                }else{
                    $junfen = $pingfen / $zongpingshu ;
                }
                $junfen = round($junfen, 1); //保留小数点后一位
                $trainermess[$kt]['junfen'] = $junfen ; 
            }
            $storemes['trainerarr'] = $trainermess ;
        }    
        
        $this->ajaxReturn($storemes);
    }

    //获取教练信息的接口
    public function gettrainermess(){
        $sid = I('post.sid') ; //教练记录id
        $userid = $this->getUsemes();
        $trainermess = db('sz_yi_trainer')->where('id',$sid)->find(); //获取当前驾校教练信息
        $trainercomment = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>0,'is_show'=>1))->select(); //获取教练评论信息
        foreach ($trainercomment as $kc => $vc) {
             $usermes = db('users')->where('user_id',$vc['user_id'])->field('nickname,head_pic')->find(); //查询用户信息
             $trainercomment[$kc]['nickname'] = $usermes['nickname'] ;
             $trainercomment[$kc]['head_pic'] = $usermes['head_pic'] ;
             $trainercomment[$kc]['add_times'] = date('Y-m-d H:i:s',$vc['add_time']) ;
             $parentmess = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>$vc['id'],'is_show'=>1))->field('id,content')->find(); //获取后台用户所回复的评论信息
             $trainercomment[$kc]['huiarr']['content'] = $parentmess['content'] ;
             $trainercomment[$kc]['huiarr']['huinickname'] = '追加回复' ;
        }   
        $trainermess['pingarr'] = $trainercomment ;

        $pingcount = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>0,'is_show'=>1))->count(); //获取教练评论总数
        $trainermess['pingcount'] = $pingcount ; //获取总数

        $pingfen = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>0))->sum('pingfen'); //获取教练总评分
        $pingfen = floatval($pingfen);

        $zongpingshu = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>0))->count(); //获取评论人数
        $zongpingshu = floatval($zongpingshu);
        if($zongpingshu == 0){
            $junfen = 0 ;
        }else{
            $junfen = $pingfen / $zongpingshu ;
        }
        $junfen = round($junfen, 1); //保留小数点后一位
        $trainermess['junfen'] = $junfen ; //获取学员平均评分

        $haoping = $junfen / 5.0 ;
        $haoping = $haoping * 100 ;
        $haoping = round($haoping);
        $trainermess['haoping'] = $haoping ; //获取好评率

        $zongzan = db('sz_yi_trainercomment')->where(array('jid'=>$sid,'pid'=>0))->sum('zan_num'); //获取总点赞人数
        $trainermess['zongzan'] = $zongzan ;

        $cons['userid'] = $userid ;
        // $cons['jid'] = array('neq',0) ;
        $carjoinmess = db('sz_yi_carjoin')->where($cons)->find();
        if ($carjoinmess) {
            $trainermess['is_bao'] = 1;
        }else{
            $trainermess['is_bao'] = -1;
        }
        
        $wheres['jid'] = $sid ; //教练id
        $wheres['user_id'] = $userid ; 
        $commentmess = db('sz_yi_trainercomment')->where($wheres)->find();
        if($commentmess){
            $trainermess['is_comments'] = 1;    
        }else{
            $trainermess['is_comments'] = -1;
        }

        $this->ajaxReturn($trainermess);

    }


    

    

    /*
     * 商品详情 的接口 
     */
    public function getGoodxiangmes()
    {   

        $goodsLogic = new GoodsLogic();
        $goods_id = I("post.gid"); //获取商品id
        // $goods_id = 148 ;
        $goodsModel = new \app\common\model\Goods();
        $goods = $goodsModel::get($goods_id);
        if(empty($goods) || ($goods['is_on_sale'] == 0) || ($goods['is_virtual']==1 && $goods['virtual_indate'] <= time())){
            $this->ajaxReturn(['status' => -1, 'msg' => '此商品不存在或者已下架!']);
        }
        
        $goods = $goods->toArray(); //对象转换为数组
        $goods_images_list = M('GoodsImages')->where("goods_id", $goods_id)->select(); // 商品详情轮播图册
        $goods['imgarrs'] = $goods_images_list ;

        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id", $goods_id)->select(); // 查询商品属性表 
        foreach ($goods_attr_list as $kat => $vat) {
        	$goods_attr_list[$kat]['attrname'] = $goods_attribute[ $vat['attr_id'] ] ;
        }
		$goods['attrarr'] = $goods_attr_list ; //查询商品的参数信息数组

        $filter_spec = $goodsLogic->get_spec($goods_id); //获取商品对应的规格属性
        $num = 0 ;
        $specarrs = array() ;
        foreach ($filter_spec as $kn => $van) {
            $specarrs[$num]['name'] = $kn ;
            $specarrs[$num]['namearr'] = $van ;
            $num ++;
        }
        $goods['filterarr'] = $specarrs ;

        $spec_goods_price  = M('spec_goods_price')->where("goods_id", $goods_id)->getField("key,price,store_count,item_id"); // 规格 对应 价格 库存表
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        
        $goods['goods_content'] = htmlspecialchars_decode($goods['goods_content']); //解析标签
        if($goods['supplier_uid'] == 0){
            $storename = '自营店铺' ;
        }else{
            $storemes = db('sz_yi_store_data')->where('storeid',$goods['supplier_uid'])->find(); //查询商家信息
            if($storemes){
            	$storename = $storemes['storename'] ;
            }else{
            	$storename = '自营店铺' ;
            }
            
        }
        
        $goods['storename'] = $storename ;

       	$goodscomment = db('comment')->where(array('goods_id'=>$goods_id,'parent_id'=>0,'is_show'=>1))->field('comment_id,goods_id,content,user_id,add_time')->select(); //查询所要显示商品的评论信息
       	foreach ($goodscomment as $kc => $vc) {
       		 $usermes = db('users')->where('user_id',$vc['user_id'])->field('nickname,head_pic')->find(); //查询用户信息
       		 $goodscomment[$kc]['nickname'] = $usermes['nickname'] ;
       		 $goodscomment[$kc]['head_pic'] = $usermes['head_pic'] ;
       		 $goodscomment[$kc]['add_times'] = date('Y-m-d H:i:s',$vc['add_time']) ;

             $parentmess = db('comment')->where(array('goods_id'=>$goods_id,'parent_id'=>$vc['comment_id'],'is_show'=>1))->field('comment_id,content')->find(); //获取后台用户所回复的评论信息
             $goodscomment[$kc]['huiarr']['content'] = $parentmess['content'] ;
             $goodscomment[$kc]['huiarr']['huinickname'] = '追加回复' ;

       	}	

       	$goods['goodscomment'] = $goodscomment ;
       	$goods['commentnum'] = count($goodscomment) ;

        // var_dump('<pre>');
        // var_dump($goods);die;

        $this->ajaxReturn($goods);

    }


    //加入购物车或者立即购买选择规格的总规格id
    public function getGuigemes(){
        $data['goods_id'] = I("post.gid"); //获取商品id
        $data['key'] = I('post.itemstr'); //获取所选中的规格id组成的字符串
        $specarr = db('spec_goods_price')->where($data)->find();
        $this->ajaxReturn($specarr);
    }

    //提交订单时  根据总规格id获取属性信息
    public function getChoseguige(){
        $data['item_id'] = I("post.itemid"); //获取总规格id
        $specarr = db('spec_goods_price')->where($data)->find();
        $this->ajaxReturn($specarr);
    }


}
