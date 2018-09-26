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
use app\common\logic\DistributLogic1 ;
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

class Orderup extends MobileBase
{

	
    public function getUsemes(){

        $userid = Session::get('userid');
        if(empty($userid)){
           $this->ajaxReturn(['status' => -1, 'msg' => '您还没登录!']); 
        }
        // $userid = I('post.userid') ;
        return $userid ;
    }

    //获取积分抵扣的信息
    public function orderDikoumess(){
        $userid = $this->getUsemes();
        $goods_id = I('post.goods_id');
        $item_id = I('post.itemid');
        $op = I('post.op') ; // 1 为立即购买  2为购物车提交订单

        if($op == 1){
            $contions['goods_id'] = $goods_id ;
            $contions['key'] = $item_id ;
            $specmess = M('spec_goods_price')->where($contions)->find(); //获取商品规格信息
            $price = floatval($specmess['price']) ; //商品价格
            $goods = M('Goods')->where("goods_id = $goods_id")->find(); //获取商品信息
            $exchange_integral = floatval($goods['exchange_integral']); //兑换积分
        }elseif ($op == 2) {
            $cartstr = I('post.cid') ; //获取购物车记录id数组
            $caid = explode('_',$cartstr);
            $carr = array() ;
            foreach ($caid as $vcc) {
                $carmes = db('cart')->where('id',$vcc)->find(); //查询购物车的信息
                $carr[] = $carmes ;
            }
            $totalprice = 0 ;
            $totalintegral = 0 ;
            foreach ($carr as $vgo) {
                $totalprice = $totalprice + floatval($vgo['goods_price']); 
                $cartgoods = M('Goods')->where('goods_id',$vgo['goods_id'])->find(); //获取商品信息
                $totalintegral = $totalintegral + floatval($cartgoods['exchange_integral']);
            }
            $price = $totalprice ; //总价格
            $exchange_integral = $totalintegral ; //总兑换积分
        }
        
        if($price == 0){
            //总价格为0 不用兑换
            $arr['duijifen'] = 0 ;
            $arr['duimoney'] = 0 ;
            $this->ajaxReturn($arr);
        }

        if($exchange_integral == 0){
            //兑换积分为0 不用兑换
            $arr['duijifen'] = 0 ;
            $arr['duimoney'] = 0 ;
            $this->ajaxReturn($arr);
        }
        
        $usermess = M('users')->where('user_id',$userid)->find(); //获取用户信息
        $pay_points = floatval($usermess['pay_points']); //当前用户的消费积分
        if($pay_points == 0){
            //用户无积分
            $arr['duijifen'] = 0 ;
            $arr['duimoney'] = 0 ;
            $this->ajaxReturn($arr);

        }else{
            
            if( $pay_points > $exchange_integral ){
                //当前的积分 大于 兑换积分
                $arr['duijifen'] = $exchange_integral ;
                if( $exchange_integral > $price ){
                    //兑换积分即要兑换的金额 是否当前购买规格的金额
                    $arr['duijifen'] = $price ;
                    $arr['duimoney'] = $price ; 
                }else{
                    $arr['duimoney'] = $exchange_integral ; 
                }
            }else{
                $arr['duijifen'] = $pay_points ;
                if( $pay_points > $price ){
                    //兑换积分即要兑换的金额 是否当前购买规格的金额
                    $arr['duijifen'] = $price ;
                    $arr['duimoney'] = $price ; 
                }else{
                    $arr['duimoney'] = $pay_points ; 
                }
            }
            
            $this->ajaxReturn($arr);

        }

    }

    //点击立即购买 提交订单的 接口
    public function subOrdermes(){

        $userid = $this->getUsemes();
        $op = I('post.op') ; 
        $goods_num = I('post.goods_num'); 
        $goods_id = I('post.goods_id'); 
        $item_id = I('post.itemid');
        $address_id = I('post.address_id') ; 
        $order_amount = I('post.order_amount') ;
        $integral = I('post.integral') ;
        $integral_money = I('post.integral_money') ;

        $goods = M('Goods')->where("goods_id = $goods_id")->find();

        if(empty($item_id)){
            //无规格
            $store_count = $goods['store_count'] ; 
            // $order_amounts = floatval($goodsmes['shop_price']) * floatval($goods_num) ;
        }else{
            //有规格
           
            $contions['goods_id'] = $goods_id ;
            $contions['key'] = $item_id ;
            $specmess = M('spec_goods_price')->where($contions)->find(); //规格信息
            $store_count = $specmess['store_count'] ;
            // $order_amounts = floatval($specmess['price']) * floatval($goods_num) ;

        }
        if($store_count == 0){
             $this->ajaxReturn(['status' => -1, 'msg' => '此商品库存为0!']);
        }

        $goodsmes = M('goods')->where(array('goods_id'=>$goods_id))->find(); //获取商品信息
        $OrderLogic = new OrderLogic();
        $order_sn = $OrderLogic->get_order_sn() ;
        $data['order_sn'] = $order_sn ;
        $data['user_id'] = $userid ;
        $data['goods_price'] = $order_amount ; //商品总价格
        $data['total_amount'] = $order_amount ;   // 订单总额
        $data['shipping_price'] = 0 ; //物流价格
        $data['integral'] = $integral ; //'使用积分',
        $data['integral_money'] = $integral_money ; //'使用积分抵多少钱',
        $data['order_amount'] = $order_amount ; //'应付款金额',
        $data['add_time'] = time() ;  //下单时间
        $address = M('user_address')->where(array('address_id'=>$address_id,'user_id'=> $userid))->find();
        $data['consignee'] = $address['consignee'] ;
        $data['provinces'] = $address['provinces'] ;
        $data['citys'] = $address['citys'] ;
        $data['districts'] = $address['districts'] ;
        $data['address'] = $address['address'] ;
        $data['mobile'] = $address['mobile'] ;
        $data['supplier_uid'] = $goodsmes['supplier_uid'] ;

        $shangjiames = db('sz_yi_store_data') -> where('storeid',$goodsmes['supplier_uid']) -> find(); //查询商家信息
        if($shangjiames){
            if($shangjiames['storemodel'] == 0){
                //附近商城
                $data['is_kefa'] = 0 ;
            }
        }
        
        db('order')->insert($data);
        $orderid = db('order')->getLastInsID();
        if($op == 'buy_now'){
            //立即购买
            $contions['goods_id'] = $goods_id ;
            $contions['key'] = $item_id ;
            $specmes = M('spec_goods_price')->where($contions)->find(); //规格信息
            $datagood['order_id'] = $orderid ;
            $datagood['goods_id'] = $goods_id ;
            $datagood['goods_name'] = $goodsmes['goods_name'] ;
            $datagood['goods_sn'] = $goodsmes['goods_sn'] ;
            $datagood['goods_num'] = $goods_num ; //购买数量
            
            if($goodsmes['is_discount'] == 1){
                //是折扣商品
                $datagood['final_price'] = $specmes['jiaprice'] ; // 每件商品实际支付价格
                $datagood['goods_price'] = $specmes['jiaprice'] ; // 商品价 
            }else{
                //非折扣商品
                $datagood['final_price'] = $specmes['price'] ; // 每件商品实际支付价格
                $datagood['goods_price'] = $specmes['price'] ; // 商品价
            }

            $datagood['spec_key'] = $specmes['key'] ; //商品规格key 
            $datagood['spec_key_name'] = $specmes['key_name'] ; //规格对应的中文名字
            $datagood['cost_price'] = $goodsmes['cost_price'] ; //成本价
            db('order_goods')->insert($datagood); //插入商品详情信息
            $this->ajaxReturn(['status' => 1, 'msg' => '下单成功!','orderid'=>$orderid,'ordersn'=>$order_sn]);
        }
    }



    //加入购物车的操作
    public function addShopcart(){
        
        $userid = $this->getUsemes();
        $goods_id = I("post.goods_id"); // 商品id
        $goods_num = I("post.goods_num");// 商品数量
        $item_id = I("post.item_id"); // 商品规格id
        if(empty($goods_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'请选择要购买的商品','result'=>'']);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'购买商品数量不能为0','result'=>'']);
        }
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($userid);
        $cartLogic->setGoodsModel($goods_id);
        if($item_id){
            $cartLogic->setSpecGoodsPriceModel($item_id);
        }
        $cartLogic->setGoodsBuyNum($goods_num);
        $result = $cartLogic->addGoodsToCart(); //添加到购物车
        $this->ajaxReturn($result);

    }

    //获取用户购物车的商品信息
    public function getShopcart(){
        
        $userid = $this->getUsemes();

        $cartLogic = new CartLogic();
        $cartLogic->setUserId($userid);
        $cartList = $cartLogic->getCartList();//用户购物车
        $userCartGoodsTypeNum = $cartLogic->getUserCartGoodsTypeNum();//获取用户购物车商品总数
        
        $newarr = array() ;
        foreach ($cartList as $h => $vah) {
           $newarr[$h]['id'] = $cartList[$h]['id'] ;
           $newarr[$h]['goods_id'] = $cartList[$h]['goods_id'] ;
           $newarr[$h]['goods_name'] = $cartList[$h]['goods_name'] ;
           $newarr[$h]['spec_key_name'] = $cartList[$h]['spec_key_name'] ;
           $newarr[$h]['checked'] = $cartList[$h]['checked'] ;
           $goodmes = db('goods')->where('goods_id',$cartList[$h]['goods_id'])->find();
           // $newarr[$h]['shop_price'] = $goodmes['shop_price'] ;
           $newarr[$h]['shop_price'] = $cartList[$h]['goods_price'] ;

           $newarr[$h]['market_price'] = $goodmes['market_price'] ; 
           $newarr[$h]['original_img'] = $goodmes['original_img'] ;
           $newarr[$h]['goods_num'] = $cartList[$h]['goods_num'] ;
           if($goodmes['supplier_uid'] == ''){
           		$goodmes['supplier_uid'] = 0 ;
           }
           $newarr[$h]['supplier_uid'] = $goodmes['supplier_uid'] ;
        }

        $zuarr = array() ;
        $this -> getStoremes($newarr,$zuarr);
        $cartarr = array() ;
        foreach ($zuarr as $ks => $vst) {
        	$suid = $vst[0]['supplier_uid'] ;
        	if($suid == 0){
        		$storename = '自营' ;
        	}else{
        		$storemes = db('sz_yi_store_data')->where('storeid',$suid)->find(); //查询商家信息
        		$storename = $storemes['storename'] ;
        	}
        	$cartarr[$ks]['storename'] = $storename ;
        	$cartarr[$ks]['checked'] = false ;
        	$cartarr[$ks]['goodmes'] = $vst ; 
        }
        
        // var_dump('<pre>');
        // var_dump($cartarr);die;

        $this->ajaxReturn($cartarr);

    }

    //循环递归 获取商家下面对应的商品信息
    public function getStoremes($newarr,&$zuarr,$num = 0){
        $supplier_uid = 0 ;
        $suos = count($newarr) - 1 ;
        $suoyin = array();
        foreach ($newarr as $kp => $vap) {
            if($kp == 0){
                $supplier_uid = $newarr[0]['supplier_uid'] ;
                $zuarr[$num][] = $vap ;
                $suoyin[] = 0 ;
            }
            if($kp <= $suos && $kp != 0){
                if($supplier_uid == $newarr[$kp]['supplier_uid']){
                    $zuarr[$num][] = $vap ;
                    $suoyin[] = $kp ;
                }
            }
            if($kp == $suos){
                $num ++;
                $newarrs = array();
                foreach ($newarr as $kyin => $vayin) {
                    $biao = true ;
                    foreach($suoyin as $zhi){
                        if($kyin == $zhi){
                            $biao = false ;
                        }
                    }
                    if($biao){
                        $newarrs[] = $vayin ;
                    }
                }
                $this->getStoremes($newarrs,$zuarr,$num);
            }   
        }
    }

    /**
     * 点击购物车加减操作
     */
    public function changeCartnum(){

        $cart['id'] = I('post.cid') ; //购物车记录id
        $cart['goods_num'] = I('post.goods_num') ; //购物车的数量
        if (empty($cart)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择要更改的商品', 'result' => '']);
        }
        $cartLogic = new CartLogic();
        $result = $cartLogic->changeNum($cart['id'],$cart['goods_num']);
        
        $this->ajaxReturn($result);
    }


    /**
     * 删除购物车商品
     */
    public function deleteCart(){
        $userid = $this->getUsemes();
        $cart_ids = I('post.cid') ; //购物车记录id
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($userid);
        $result = $cartLogic->delete($cart_ids);
        if($result !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'删除成功','result'=>$result]);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'删除失败','result'=>$result]);
        }
    }



     //在购物车  点击结算的操作 成功返回数组信息
    public function payCartgoods(){
    	$caid = I('get.cid') ; //获取购物车记录id数组
        $caid=explode("_",$caid);//对字符串进行分割
    	$carr = array() ;
    	foreach ($caid as $vcc) {
    		$carmes = db('cart')->where('id',$vcc)->find(); //查询购物车的信息
            $goodmess = db('goods')->where('goods_id',$carmes['goods_id'])->find();
            $carmes['goodimages'] = $goodmess['original_img'] ;
    		$carr[] = $carmes ;
    	}
        
    	$goodmes = db('goods')->where('goods_id',$carr[0]['goods_id'])->find(); //查询商品信息
    	$supplier_uid = $goodmes['supplier_uid'] ;
    	$biao = true ;
    	foreach ($carr as $ka => $vac) {
    		$goodmess = db('goods')->where('goods_id',$vac['goods_id'])->find(); //查询商品信息
    		if($goodmess['supplier_uid'] != $supplier_uid){
    			$biao = false ;
    			break;
    		}
            $carr[$ka]['shop_price'] = $vac['goods_price'] ;
            $carr[$ka]['market_price'] = $vac['goods_price'] ;	
    	}

        if($supplier_uid == 0 ){
            $storename = '自营店铺' ;
        }else{
            $storemess = db('sz_yi_store_data')->where('storeid',$supplier_uid)->find();
            $storename = $storemess['storename'] ;
        }

    	if(!$biao){
			$this->ajaxReturn(['status'=>0,'msg'=>'不同商家店铺需分开下单!']);
    	}

    	$this->ajaxReturn(['status'=>1,'result'=>$carr,'storename'=>$storename]);

    }


    //在购物车选择完之后 提交订单的操作
    public function subCartordermes(){
        
        $userid = $this->getUsemes();
        $op = I('post.op') ; 
        $caid = array();
        $cartstr = I('post.cid') ; //获取购物车记录id数组

        $integral = I('post.integral') ;
        $integral_money = I('post.integral_money') ;

        $caid = explode('_',$cartstr);
        $carr = array() ;
    	foreach ($caid as $vcc) {
    		$carmes = db('cart')->where('id',$vcc)->find(); //查询购物车的信息
    		$carr[] = $carmes ;
    	}
        $address_id = I('post.address_id') ; 
        $order_amount = I('post.order_amount') ;  //应付款金额,
        $OrderLogic = new OrderLogic();
        $order_sn = $OrderLogic->get_order_sn() ;
        $data['order_sn'] = $order_sn ;
        $data['user_id'] = $userid ;
        $totalprice = 0 ;
        foreach ($carr as $vag) {
        	$shopzongjia = floatval($vag['goods_price']) * floatval($vag['goods_num']) ;
        	$totalprice = $totalprice + $shopzongjia ;
            $shangpinmes = db('goods')->where('goods_id',$vag['goods_id'])->find();
            $supplier_uid = $shangpinmes['supplier_uid'] ; 
        }
        $data['goods_price'] = $totalprice ; //商品总价格
        $data['total_amount'] = $totalprice ;   // 订单总额
        $data['shipping_price'] = 0 ; //物流价格
        $data['integral'] = $integral ; //'使用积分',
        $data['integral_money'] = $integral_money ; //'使用积分抵多少钱',
        $data['order_amount'] = $order_amount ; //'应付款金额',
        $data['add_time'] = time() ;  //下单时间
        $address = M('user_address')->where(array('address_id'=>$address_id,'user_id'=> $userid))->find();
        $data['consignee'] = $address['consignee'] ;
        $data['provinces'] = $address['provinces'] ;
        $data['citys'] = $address['citys'] ;
        $data['districts'] = $address['districts'] ;
        $data['address'] = $address['address'] ;
        $data['mobile'] = $address['mobile'] ;
        $data['supplier_uid'] = $supplier_uid ;
        $order = db('order')->insert($data);
        $orderid = Db::name('order')->getLastInsID();

        if($op == 'buy_now'){
            //立即购买
            foreach ($carr as $vag) {
	            $datagood['order_id'] = $orderid ;
	            $datagood['goods_id'] = $vag['goods_id'] ;
	            $datagood['goods_name'] = $vag['goods_name'] ;
	            $datagood['goods_sn'] = $vag['goods_sn'] ;
	            $datagood['goods_num'] = $vag['goods_num'] ; //购买数量
	            $datagood['final_price'] = $vag['goods_price'] ; // 每件商品实际支付价格
	            $datagood['goods_price'] = $vag['goods_price'] ; // 商品价
	            $datagood['spec_key'] = $vag['spec_key'] ; //商品规格key 
	            $datagood['spec_key_name'] = $vag['spec_key_name'] ; //规格对应的中文名字
	            db('order_goods')->insert($datagood); //插入商品详情信息
	            db('cart')->where('id',$vag['id'])->delete(); //删除购物车的信息
	        }
            $this->ajaxReturn(['status' => 1, 'msg' => '下单成功!','orderid'=>$orderid,'ordersn'=>$order_sn]);
        }

    }



    //我的订单信息
    public function getMyordermes(){

        $op = I('post.op') ;
        $userid = $this->getUsemes();
        
        $data['user_id'] = $userid ;
        if($op == 'all'){
            //全部订单
        }elseif ($op == 'weipay') {
            //待支付
            // AND pay_status = 0 AND order_status = 0 AND pay_code !="cod"
            $data['pay_status'] = 0 ;
            $data['order_status'] = 0 ;
            $data['pay_code'] = array('neq','cod');
        }elseif ($op == 'weifa') {
            //待发货
            $where = ' user_id = :user_id ';
            $bind['user_id'] = $userid ;
            $where .= ' AND (pay_status=1 OR pay_code="cod") AND shipping_status !=1 AND order_status in(0,1) ' ;
            $ops = 'fahuo' ;

        }elseif ($op == 'weishou') {
            //待收货
            // AND shipping_status=1 AND order_status = 1
            $data['order_status'] = 1 ;
            $data['shipping_status'] = 1 ;
        }elseif ($op == 'weiping') {
            //待评价 确认收货
            $data['order_status'] = 2 ;
        }elseif ($op == 'finish') {
            //已完成
            $data['order_status'] = 4 ;
        }elseif ($op == 'cancel') {
            //取消
            $data['order_status'] = 3 ;
        }elseif ($op == 'cancel') {
            //已作废
            $data['order_status'] = 5 ;
        }
        if($ops == 'fahuo'){
            $order_str = "order_id DESC";
            $order_list = M('order')->order($order_str)->where($where)->bind($bind)->select();
        }else{
            $order_list = db('order')->where($data)->whereOr($datas)->order('order_id desc')->select(); //获取全部订单信息
        }

        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            $data = $model->get_order_goods($v['order_id']);
            $goodsarr = $data['result'];
            foreach ($goodsarr as $kg => $vg) {
                if ($vg['is_comment'] == 1) {
                    $goodsarr[$kg]['commentmess'] = '已评论' ;
                }elseif ($vg['is_comment'] == 0) {
                    $goodsarr[$kg]['commentmess'] = '待评论' ;
                }
            }
            $order_list[$k]['goods_list'] = $goodsarr ;
            $storemes = db('sz_yi_store_data')->where('storeid',$v['supplier_uid'])->field('id,storename')->find(); //查询店铺信息的
            $order_list[$k]['storename'] = $storemes['storename'] ;
        }
        //统计订单商品数量
        foreach ($order_list as $key => $value) {
            $count_goods_num = 0;
            foreach ($value['goods_list'] as $kk => $vv) {
                $count_goods_num += $vv['goods_num'];
            }
            $order_list[$key]['count_goods_num'] = $count_goods_num;
        }
        foreach ($order_list as $ks => $vst) {
            $suid = $vst['supplier_uid'] ;
            if($suid == 0){
                $storename = '自营' ;
                $telphone = '' ;
            }else{
                $storemes = db('sz_yi_store_data')->where('storeid',$suid)->find(); //查询商家信息
                $storename = $storemes['storename'] ;
                $telphone = $storemes['tel'] ;
            }
            $order_list[$ks]['storename'] = $storename ;
            $order_list[$ks]['tel'] = $telphone ;
        }

        

        $this->ajaxReturn($order_list);

    }



    //查询 售后的商品信息
    public function getReturngoods(){
        $userid = $this->getUsemes();
        $op = I('post.op') ;
        $rid = I('post.rid') ;
        if($op == 'all'){
            //查询全部
            $list = Db::name('return_goods')->alias('rg')
            ->field('rg.*,og.order_sn,og.total_amount,og.shipping_price')
            ->join('order og','rg.order_id=og.order_id','LEFT')
            ->where(['rg.user_id'=>$userid])
            ->order("rg.id desc")
            ->select();
        }elseif ($op == 'dan') {
            //查询对应的售后信息
            $data['rg.user_id'] = $userid ;
            $data['rg.id'] = $rid ;
            $list = Db::name('return_goods')->alias('rg')
            ->field('rg.*,og.order_sn,og.total_amount,og.shipping_price')
            ->join('order og','rg.order_id=og.order_id','LEFT')
            ->where($data)
            ->order("rg.id desc")
            ->select();
        }

        foreach ($list as $ko => $vao) {
            $ordergoods = db('order_goods')->where('order_id',$vao['order_id'])->select();
            $totalnum = 0 ;
            foreach ($ordergoods as $kg => $vag) {
                $goodmess = db('goods')->where('goods_id',$vag['goods_id'])->find();
                $ordergoods[$kg]['original_img'] = $goodmess['original_img'] ;
                $totalnum = floatval($vag['goods_num']) + $totalnum  ;
            }
            $list[$ko]['goods_list'] = $ordergoods ;

            $list[$ko]['count_goods_num'] = $totalnum ;

            if($vao['supplier_uid'] == 0){
                $list[$ko]['storename'] =  '自营店铺' ;
            }else{
                $storemess = db('sz_yi_store_data')->where('useid',$vao['supplier_uid'])->find();
                $list[$ko]['storename'] =  $storemess['storename'] ;
            }

            if($vao['status'] == 1){
                $list[$ko]['tishiyu'] = '审核通过' ;
            }else if($vao['status'] == 0){
                $list[$ko]['tishiyu'] = '待审核' ;
            }
            
        }

        // var_dump('<pre>');
        // var_dump($list);die;

        $this->ajaxReturn($list);

    }

    //用户取消售后的 操作接口
    public function cancelReturngoods(){
        $rid = I('post.rid') ; //获取记录id
        $res = db('return_goods')->where('id',$rid)->update(['status' => -2]); //用户取消
        if($res){
            $this->ajaxReturn(['status' => 1]);
        }else{
            $this->ajaxReturn(['status' => -1]);
        }
    }

    //用户进行评论商品的操作
    public function pingGoodsmess(){
        $userid = $this->getUsemes();
        $order_id = I('post.oid') ; //订单id
        $goods_id = I('post.goods_id') ; //商品id
        $content = I('post.content') ; //评论内容
        $pingimg = I('post.pingimg') ; //获取所上传的截图 用 , 隔开
        $datac['order_id'] = $order_id ;
        $datac['goods_id'] = $goods_id ;
        $datac['user_id'] = $userid ;
        $datac['add_time'] = time() ;
        $datac['content'] = $content ;
        $datac['pingimg'] = $pingimg ;
        $resu = db('comment')->insert($datac);
        if($resu){
            db('order_goods')->where(array('order_id'=>$order_id,'goods_id'=>$goods_id))->update(['is_comment' => 1]); //更新评论
            $ordergoodscount = db('order_goods')->where(array('order_id'=>$order_id))->count(); //查询订单商品信息总数量
            $pingcount = db('order_goods')->where(array('order_id'=>$order_id,'is_comment'=>1))->count(); //查询已经评论的商品总数量
            if($ordergoodscount == $pingcount){
                db('order')->where(array('order_id'=>$order_id))->update(['order_status' => 4]); //更新订单评论状态
            }
            $this->ajaxReturn(['status'=>1,'msg'=>'评论成功']);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'评论失败']);
        }

    }


    //获取对应的订单信息
    public function getDuiordermes(){
        $orderid = I('post.oid');
        $userid = $this->getUsemes();
        $data['user_id'] = $userid ;
        $data['order_id'] = $orderid ;
        $order_list = db('order')->where($data)->order('order_id desc')->select(); //获取全部订单信息
        //获取订单商品
        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
            $order_list[$k]['addtime'] = date("Y-m-d H:i:s",$v['add_time']);
            $order_list[$k]['shipping_time'] = date("Y-m-d H:i:s",$v['shipping_time']);
            $order_list[$k]['confirm_time'] = date("Y-m-d H:i:s",$v['confirm_time']);
        }
        //统计订单商品数量
        foreach ($order_list as $key => $value) {
            $count_goods_num = 0;
            foreach ($value['goods_list'] as $kk => $vv) {
                $count_goods_num += $vv['goods_num'];
            }
            $order_list[$key]['count_goods_num'] = $count_goods_num;
        }

        $storemes = db('sz_yi_store_data')->where('storeid',$order_list[0]['supplier_uid'])->field('id,storename')->find(); //查询店铺信息的
        $ordermes = $order_list[0] ;
        $ordermes['xiangaddress']= $order_list[0]['provinces'].$order_list[0]['citys'].$order_list[0]['districts'].$order_list[0]['address'] ;
        if($storemes){
            $ordermes['storename'] = $storemes['storename'];
        }else{
            $ordermes['storename'] = '自营店铺';
        }
        $ordermes['newlogistice'] = '(我的订单 - 查看物流)' ;
        $this->ajaxReturn($ordermes);

    }
   

     //用户取消订单的操作
    public function cancelOrdermess(){
        $userid = $this->getUsemes();
        $data['user_id'] = $userid ; //获取用户id
        $data['order_id'] = I('post.oid'); //获取订单id
        $ress = db('order')->where($data)->update(['order_status' => 3]);
        if($ress){
            $this->ajaxReturn(['status' => 1, 'msg' => '取消订单成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '取消订单失败!']);
        }
    }

    //用户删除订单的操作
    public function deletOrdermess(){
        $userid = $this->getUsemes();
        $data['user_id'] = $userid ; //获取用户id 
        $data['order_id'] = I('post.oid'); //获取订单id
        $ress = db('order')->where($data)->delete();
        if($ress){
            $this->ajaxReturn(['status' => 1, 'msg' => '删除订单成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '删除订单失败!']);
        }
    }


    //用户确认收货的操作处理
    public function Confirmshou(){
        $orderid = I('post.oid');
        $userid = $this->getUsemes();
        $data['order_status'] = 2 ;
        $res = db('order')->where('order_id',$orderid)->update($data);
        if($res){
            $ordergoods = db('order_goods')->where('order_id',$orderid)->select(); //获取订单详细商品信息
            $zengjifen = 0 ;
            foreach ($ordergoods as $vg) {
                $goodmes = db('goods')->where('goods_id',$vg['goods_id'])->find(); //获取商品信息
                if( floatval($goodmes['give_integral']) > 0){
                    //赠送积分
                    $zengjifen = $zengjifen + floatval($goodmes['give_integral']) ;
                }
            }
            // if($zengjifen > 0){
            //     accountLog($userid, 0, $zengjifen,"用户购买商品送积分"); //追加积分
            // }
            $this->ajaxReturn(['status' => 1, 'msg' => '确认收货成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '确认收货失败!']);
        }

    }


    //查询订单的快递信息
    public function orderExpress()
    {
        $order_id = I('post.oid');
        $order_goods = M('order_goods')->where("order_id", $order_id)->select();
        if(empty($order_goods) || empty($order_id)){
            $this->ajaxReturn(['status' => -1, 'msg' => '没有获取到订单信息!']);
        }
        $delivery = M('delivery_doc')->where("order_id", $order_id)->find();
        $delivery['status'] = 1 ;
        $orders = db('order')->where("order_id", $order_id)->find();
        $delivery['shouaddress'] = $orders['provinces'].$orders['citys'].$orders['districts'].$orders['address'] ;
        $this->ajaxReturn($delivery);
    }


    /**
     * 查询物流
     */
    public function queryExpress()
    {
            
        $express_switch = tpCache('express.express_switch');
        if($express_switch == 1){
            require_once(PLUGIN_PATH . 'kdniao/kdniao.php');
            $kdniao = new \kdniao();
            $data['OrderCode'] = empty(I('order_sn')) ? date('YmdHis') : I('order_sn');
            $data['ShipperCode'] = I('shipping_code');
            $data['LogisticCode'] = I('invoice_no');
            $res = $kdniao->getOrderTracesByJson(json_encode($data));
            $res =  json_decode($res, true);
            if($res['State'] == 3){
                foreach ($res['Traces'] as $val){
                    $tmp['context'] = $val['AcceptStation'];
                    $tmp['time'] = $val['AcceptTime'];
                    $res['data'][] = $tmp;
                }
                $res['status'] = "200";
            }else{
                $res['message'] = $res['Reason'];
            }
            return json($res);
        }else{
            $shipping_code = input('shipping_code');
            $invoice_no = input('invoice_no');
            if(empty($shipping_code) || empty($invoice_no)){
                return json(['status'=>0,'message'=>'参数有误','result'=>'']);
            }
            return json(queryExpress($shipping_code,$invoice_no));
        }
    }


    //查询 商品信息 售后申请 收货状态 退款原因 的信息
    public function getShoumes(){
        $rec_id = I('post.rec_id'); //获取订单详细商品记录id
        $order_goods = M('order_goods')->where(array('rec_id'=>$rec_id))->find(); //查询商品信息
        $shouarr['goodarr'] = $order_goods ;
        $shouarr['shou'] = C('RETURN_TYPES') ; //收货状态
        $shouarr['tuikuan'] = C('RETURN_REASON') ; //退货原因
        $this->ajaxReturn($shouarr);
    }

     /**
     * 上传退换货图片，兼容小程序
     */
    // public function uploadGoodsImg(){
    //     $return_imgs = '';
    //     if ($_FILES['return_imgs']['tmp_name']) {
    //         $files = request()->file("return_imgs");
    //         if (is_object($files)) {
    //             $files = [$files]; //可能是一张图片，小程序情况
    //         }
    //         $image_upload_limit_size = config('image_upload_limit_size');
    //         $validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
    //         $dir = UPLOAD_PATH.'return_goods/';
    //         if (!($_exists = file_exists($dir))){
    //             $isMk = mkdir($dir);
    //         }
    //         $parentDir = date('Ymd');
    //         foreach($files as $key => $file){
    //             $info = $file->rule($parentDir)->validate($validate)->move($dir, true);
    //             if($info){
    //                 $filename = $info->getFilename();
    //                 $new_name = '/'.$dir.$parentDir.'/'.$filename;
    //                 $return_imgs[]= $new_name;
    //             }else{
    //                 $this->ajaxReturn(['status' => -1, 'msg' => $file->getError()]);//上传错误提示错误信息
    //             }
    //         }
    //         $this->ajaxReturn(['status' => 1, 'mess' => '操作成功', 'result' => $return_imgs]);
    //     }

    //     $this->ajaxReturn(['status' => -1, 'msg' => '文件上传失败']);
    // }

    /**
     * 上传退换货图片，兼容小程序
     */
    public function uploadGoodsImg(){
        $img = I('img');//图片base64    用 ”|“符号隔开
        $img = explode('|', $img);
        if($img){
            foreach($img as $v){
                $base64_img = trim($v);
                $up_dir = 'Public/return_goods/'.date('Ymd');
                if(!is_dir($up_dir)){
                    mkdir($up_dir,0777,true);
                }
                if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)){
                    $type = $result[2];
                    if(in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png'))){
                        $new_name = time().uniqid();
                        $new_file = $up_dir.'/'.$new_name.'.'.$type;
                        $base64_1 = str_replace($result[1],'', $base64_img);
                        if(file_put_contents($new_file, base64_decode($base64_1))){
                            $pic = '/'.$new_file;
                            $nnpic = $pic.','.$nnpic;
                        }else{
                            $this->ajaxReturn(['status' => -1, 'mess' => '上传失败']);
                        }
                    }else{
                        //文件类型错误
                        $this->ajaxReturn(['status' => -1, 'mess' => '文件类型错误']);
                    }
                }else{
                    //文件错误
                    $this->ajaxReturn(['status' => -1, 'mess' => 'base64错误']);
                }
            }
            $this->ajaxReturn(['status' => 1, 'mess' => '操作成功', 'result' => $nnpic]);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '文件上传失败']);
        }
    }



    //提交 申请售后的商品信息
    public function subOrdergoods(){
        $oid = I('post.oid'); //获取订单id
        $rec_id = I('post.rec_id'); //获取商品id
        $userid = $this->getUsemes();
        
        $return_goods = M('return_goods')->where(array('order_id'=>$oid))->find();
        if(!empty($return_goods))
        {
            $this->ajaxReturn(['status' => -1, 'msg' => '已经提交过退货申请!']);
        }

        if($return_goods['status'] != 0){
            if($return_goods['status'] == 1){
                $this->ajaxReturn(['status' => -5, 'msg' => '审核通过!']);
            }elseif ($return_goods['status'] == -1) {
                $this->ajaxReturn(['status' => -6, 'msg' => '审核失败!']);
            }elseif ($return_goods['status'] == -2) {
                $this->ajaxReturn(['status' => -7, 'msg' => '取消售后!']);
            }
        }

        $order = M('order')->where(array('order_id'=>$oid,'user_id'=>$userid))->find(); //查询订单信息
        $confirm_time_config = tpCache('shopping.auto_service_date');//后台设置多少天内可申请售后
        $confirm_time = $confirm_time_config * 24 * 60 * 60;
        if ((time() - $order['confirm_time']) > $confirm_time && !empty($order['confirm_time'])) {
            $msg = '已经超过' . $confirm_time_config . "天内退货时间" ;
            $this->ajaxReturn(['status' => -2, 'msg' => $msg]);
        }
        if(empty($order)){
            $this->ajaxReturn(['status' => -3, 'msg' => '非法操作']);
        }
        $op = I('post.op'); //获取类型
        if($op == 'sub')
        {
            //提交售后信息
            $datashou['order_sn'] = $order['order_sn'] ;
            $datashou['order_id'] = $oid ;
            $datashou['types'] = I('post.types'); ; //收货状态
            $datashou['reasons'] = I('post.reasons'); ; //退款原因
            $datashou['refund_money'] = I('post.money'); ; //退款金额
            $datashou['describe'] = I('post.describe'); ; //问题描述
            $imgstr = I('post.images') ; //获取上传图片数组
            $datashou['supplier_uid'] = $order['supplier_uid'] ;
            $datashou['imgs'] = $imgstr ; //兼容小程序，多传imgs
            $datashou['addtime'] = time() ;
            $datashou['user_id'] = $userid ;

            $ops = I('post.ops');
            if($ops == 'tuikuan'){
                //仅退款
                $datashou['rtypes'] = 0 ;
            }else{
                //退货退款
                $datashou['rtypes'] = 1 ;
            }

            $result = db('return_goods')->insert($datashou);
            if($result){
                $this->ajaxReturn(['status' => 1]);
            }else{
                $this->ajaxReturn(['status' => -4, 'msg' => '提交失败!']);
            }

        }
    }


    //商家入驻审核进度
    public function checkStorejin(){
        $userid = $this->getUsemes();
        $type = I('post.type') ;
        $where['uid'] = $userid ;
        $where['type'] = $type ; //1是驾校  2是商家
        $suppliermes = db('sz_yi_af_supplier')->where($where)->find(); //查询申请商家入驻信息
        if(!$suppliermes){
            //可以申请
            $this->ajaxReturn(['status' => 2]);
        }

        $data['uid'] = $userid ;
        $data['status'] = 0 ;
        $res = db('sz_yi_af_supplier')->where($data)->find(); //查询申请商家入驻信息
        if($res){
            //待审核
            $this->ajaxReturn(['status' => 0]);
        }

        $datas['uid'] = $userid ;
        $datas['status'] = 1 ;
        $ress = db('sz_yi_af_supplier')->where($datas)->find(); //查询申请商家入驻信息
        if($ress){
            //审核成功
            $this->ajaxReturn(['status' => 1]);
        }

        $datass['uid'] = $userid ;
        $datass['status'] = -1 ;
        $datass['is_con'] = 0 ;
        $resss = db('sz_yi_af_supplier')->where($datass)->find();
        if($resss){
            //审核失败
            $this->ajaxReturn(['status' => -1,'account'=>$resss['account']]);
        }

        $this->ajaxReturn(['status' => 2]); //可以申请

    }



     //用户  直接点击加入购物车的操作处理
    public function addCartdefault(){
        $userid = $this->getUsemes();
        $goods_id = I("post.goods_id"); // 商品id
        $goods_num = 1;// 商品数量
        $itemmess = db('spec_goods_price')->where('goods_id',$goods_id)->order('item_id ')->find();
        $item_id = $itemmess['item_id']; // 商品规格id
        if(empty($goods_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'请选择要购买的商品','result'=>'']);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'购买商品数量不能为0','result'=>'']);
        }
        if(empty($item_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'暂无上架商品','result'=>'']);
        }
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($userid);
        $cartLogic->setGoodsModel($goods_id);
        if($item_id){
            $cartLogic->setSpecGoodsPriceModel($item_id);
        }
        $cartLogic->setGoodsBuyNum($goods_num);
        $result = $cartLogic->addGoodsToCart(); //添加到购物车

        $this->ajaxReturn($result);

    }


    //用户支付订单的时候判断是否可以用余额支付
    public function isOrderpayyue(){
        $oid = I('oid') ; //传入订单id
        $ordergoods = db('order_goods') -> where('order_id',$oid) -> field('rec_id,order_id,goods_id') -> select() ;
        $is_pay = 0 ;
        foreach ($ordergoods as $vg) {
            $goodmes = db('goods') -> where('goods_id',$vg['goods_id']) -> find() ;
            if($goodmes['is_yuepay'] == 1){
                //支持余额支付
                $is_pay = 1 ;
                break;
            }
        }

        $resu['is_pay'] = $is_pay ;
        $this->ajaxReturn($resu);

    }
    

}
