<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人      
 * 
 * Date: 2016-03-09
 */

namespace app\admin\controller;
use think\Page;
use app\admin\logic\GoodsLogic;
use think\Db;

class Distribut extends Base {
    
    /*
     * 初始化操作
     */
    public function _initialize() {
       parent::_initialize();
    }    
    
    /**
     * 分销树状关系
     */
    public function tree(){                
        $where = 'is_distribut = 1 and first_leader = 0';
        if($this->request->param('user_id'))
            $where = "user_id = '{$this->request->param('user_id')}'";
        
        $list = M('users')->where($where)->select();

        // var_dump('<pre>');
        // var_dump($list);die;

        $this->assign('list',$list);
        return $this->fetch();
    }
 
    /**
     * 分销商列表
     */
    public function distributor_list(){
    	$condition['is_distribut']  = 1;
    	$nickname = trim(I('nickname'));
    	$user_id = trim(I('user_id'));
    	if(!empty($nickname)){
    		$condition['nickname'] = array('like',"%$nickname%");
    	}
        if(!empty($user_id)){
            $condition['user_id'] = array('like',"%$user_id%");
        }
    	$count = M('users')->where($condition)->count();
    	$Page = new Page($count,10);
    	$show = $Page->show();
    	$user_list = M('users')->where($condition)->order('distribut_money DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach ($user_list as $k=>$val){
    		$user_list[$k]['fisrt_leader'] = M('users')->where(array('first_leader'=>$val['user_id']))->count();
    		$user_list[$k]['second_leader'] = M('users')->where(array('second_leader'=>$val['user_id']))->count();
    		$user_list[$k]['third_leader'] = M('users')->where(array('third_leader'=>$val['user_id']))->count();
    		$user_list[$k]['lower_sum'] = $user_list[$k]['fisrt_leader'] +$user_list[$k]['second_leader'] + $user_list[$k]['third_leader'];
    	}
    	$this->assign('page',$show);
    	$this->assign('pager',$Page);
    	$this->assign('user_list',$user_list);
    	return $this->fetch();
    }
    
    /**
     * 分销设置
     */
    public function set(){                       
        header("Location:".U('Admin/System/index',array('inc_type'=>'distribut')));
        exit;
    }
    
    public function goods_list(){
    	$GoodsLogic = new GoodsLogic();
    	$brandList = $GoodsLogic->getSortBrands();
    	$categoryList = $GoodsLogic->getSortCategory();
    	$this->assign('categoryList',$categoryList);
    	$this->assign('brandList',$brandList);
    	$where = ' commission > 0 ';
    	$cat_id = I('cat_id/d');
        $bind = array();
    	if($cat_id > 0)
    	{
    		$grandson_ids = getCatGrandson($cat_id);
    		$where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
    	}
    	$key_word = I('key_word') ? trim(I('key_word')) : '';
    	if($key_word)
    	{
    		$where = "$where and (goods_name like :key_word1 or goods_sn like :key_word2)" ;
            $bind['key_word1'] = "%$key_word%";
            $bind['key_word2'] = "%$key_word%";
    	}
        $brand_id = I('brand_id');
        if($brand_id){
            $where = "$where and brand_id = :brand_id";
            $bind['brand_id'] = $brand_id;
        }
    	$model = M('Goods');
    	$count = $model->where($where)->bind($bind)->count();
    	$Page  = new Page($count,10);
    	$show = $Page->show();
    	$goodsList = $model->where($where)->bind($bind)->order('sales_sum desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('pager',$Page);
    	$this->assign('goodsList',$goodsList);
    	$this->assign('page',$show);
    	return $this->fetch();
    }
 

    
    /**
     * 分成记录
     */
    public function rebate_log()
    { 
        $model = M("rebate_log"); 
        $status = I('status');
        $user_id = I('user_id/d');
        $order_sn = I('order_sn');        
        $create_time = I('create_time');
        $create_time = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
                       
        $create_time2 = explode(' - ',$create_time);
        $where = " create_time >= '".strtotime($create_time2[0])."' and create_time <= '".strtotime($create_time2[1])."' ";
        
        if($status === '0' || $status > 0)
            $where .= " and status = $status ";        
        $user_id && $where .= " and user_id = $user_id ";
        $order_sn && $where .= " and order_sn like '%{$order_sn}%' ";
                        
        $count = $model->where($where)->count();
        $Page  = new Page($count,16);        
        $list = $model->where($where)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        // var_dump($list);die;
        if(!empty($list)){
        	$get_user_id = get_arr_column($list, 'user_id'); // 获佣用户
        	$buy_user_id = get_arr_column($list, 'buy_user_id'); //购买用户
        	$user_id_arr = array_merge($get_user_id,$buy_user_id);
        	$user_arr = M('users')->where("user_id in (".  implode(',', $user_id_arr).")")->getField('user_id,mobile,nickname,email');
        	$this->assign('user_arr',$user_arr);
        }
        $this->assign('create_time',$create_time);        
        $show  = $Page->show();                 
        $this->assign('show',$show);
        $this->assign('pager',$Page);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        return $this->fetch();
    }
    
    /**
     * 获取某个人下级元素
     */    
    public  function ajax_lower()
    {
        $id = $this->request->param('id');
        $list = M('users')->where("first_leader =".$id)->select();
        $this->assign('list',$list);
        // var_dump('<pre>');
        // var_dump($id);
        // die;
        return $this->fetch();
    }
    
    /**
     * 修改编辑 分成 
     */
    public  function editRebate(){        
        $id = I('id');
        $rebate_log = DB::name('rebate_log')->where('id',$id)->find();
        if (IS_POST) {
            $data = I('post.');
            // 如果是确定分成 将金额打入分佣用户余额
            if ($data['status'] == 3 && $rebate_log['status'] != 3) {
                accountLog($data['user_id'], $rebate_log['money'], 0, "订单:{$rebate_log['order_sn']}分佣", $rebate_log['money']);
            }
            DB::name('rebate_log')->update($data);
            $this->success("操作成功!!!", U('Admin/Distribut/rebate_log'));
            exit;
        }                      
       
       $user = M('users')->where("user_id = {$rebate_log[user_id]}")->find();       
            
       if($user['nickname'])        
           $rebate_log['user_name'] = $user['nickname'];
       elseif($user['email'])        
           $rebate_log['user_name'] = $user['email'];
       elseif($user['mobile'])        
           $rebate_log['user_name'] = $user['mobile'];            
       
       $this->assign('user',$user);
       $this->assign('rebate_log',$rebate_log);
       return $this->fetch();
    }



    //商家提现申请
    public function supplier_apply()
    {
       $this->getsuppliershen(0);
       // $this->assign('jieguo',);   
       return $this->fetch();
    }

    //商家提现明细
    public function supplier_finish()
    {
       $this->getsuppliershen(1); 
       return $this->fetch('supplier_apply');
    }


    //获取提现信息
    public function getsuppliershen($status){
       if($status == 0){
            $where['w.status'] = $status;
       }
       $count = Db::name('sz_yi_distribut_apply')->alias('w')->join('__USERS__ u', 'u.user_id = w.uid', 'INNER')->where($where)->count(); //获取总记录数
       // $count = Db::name('sz_yi_supplier_apply')->alias('w')->where($where)->count(); //获取总记录数
       $Page  = new Page($count,20); //传入总记录数  每页所显示的页数
       $list = Db::name('sz_yi_distribut_apply')->alias('w')->field('w.*,u.user_id')->join('__USERS__ u', 'u.user_id = w.uid', 'INNER')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select(); //多表查询
       // $list = Db::name('sz_yi_supplier_apply')->alias('w')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select(); //多表查询
       // var_dump($list);die;
       $show  = $Page->show();
       $this->assign('show',$show);
       $this->assign('list',$list);
       $this->assign('pager',$Page);
       $this->assign('jieguo',$status);
    }


    //打款到对应得用户中
    public function editapplys(){
        $pid = I('get.id'); 
        $type = I('get.type');
        if($type == 1){
           //手动打款到银行卡
           $mess = '手动打款到银行卡' ;
        }elseif ($type == 2) {
           //打款到微信
           $mess = '打款到微信' ;
        }elseif ($type == 3) {
           //打款到余额
           $mess = '打款到余额' ;
        }
        $re = db('sz_yi_distribut_apply')->where('id',$pid)->update(['status' => 1,'finish_time'=>time()]);
        if($re){
            // if($type == 3){
            //     //打款余额
            //     $applymess = db('sz_yi_supplier_apply')->where('id',$pid)->find();
            //     $storemess = db('sz_yi_store_data')->where('storeid',$applymess['uid'])->find();
            //     accountLog($storemess['useid'], $applymess['apply_money'], 0,"佣金打款到余额");
            // }
            $this->success($mess."成功!",U('supplier_apply'));
        }else{
            $this->success($mess."失败!");
        }
    }         
            

}