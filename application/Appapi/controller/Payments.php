<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */

namespace app\Appapi\controller;

use think\Db;

use think\Session ;

// 指定允许其他域名访问  
header('Access-Control-Allow-Origin:*');  
// 响应类型  
header('Access-Control-Allow-Methods:*');  
// 响应头设置  
header('Access-Control-Allow-Headers:x-requested-with,content-type'); 

class Payments extends MobileBase
{
    

    public function getUsemes(){
        $userid = Session::get('userid');
        if(empty($userid)){
           $this->ajaxReturn(['status' => -1, 'msg' => '您还没登录!']); 
        }
        // $userid = I('post.userid') ;
        return $userid ;
    }

    //用户进行余额支付操作
    // public function yuepayment(){
        
    //     $userid = $this->getUsemes();
    //     $orderid = I('post.order_id') ; //获取订单id
    //     $money = I('post.account'); //获取所支付的金额
    //     $pay_radio = I('post.pay_radio') ; //获取支付类型
    //     $pay_radio = parse_url_param($pay_radio);
    //     $pay_code = $pay_radio['pay_code'];

    //     // var_dump($money);die;

    //     if($pay_code == 'alipayMobile'){
    //         //余额支付
            
    //         $usermess = $this -> getUsemes() ;
    //         if( floatval($usermess['user_money']) < floatval($money) ){
    //             $data['status'] = -1;
    //             $data['result'] = '余额不足!无法支付';
    //             $this->ajaxReturn($data);
    //         }

    //         accountLog($userid, ($money * -1), 0,"用余额支付商品"); //扣掉余额
    //         $zdata['userid'] = $userid ;
    //         $zdata['money'] = $money ;
    //         $zdata['paytime'] = time() ;
    //         $zdata['type'] = 1 ;
    //         $zdata['typemes'] = '余额支付商品' ;
    //         db('sz_yi_yuezhimes')->insert($zdata);

    //         $updates['pay_name'] = '余额支付' ;
    //         $updates['pay_status'] = 1 ;
    //         db('order')->where('order_id',$orderid)->update($updates);

    //         $this->ajaxReturn(['status' => 1,'result' => '支付成功']);

    //     }
    // }

    //统计当前用户的记录信息
    public function getMoneylog(){
        
        $userid = $this->getUsemes();
        //进账记录包含 充值余额 红积分转余额 全返到余额 start
            $chong['user_id'] = $userid ;
            $chong['pay_status'] = 1 ;
            $totalchong = db('recharge')->where($chong)->sum('account'); //获取充值余额总金额
    
            $wheres['userid'] = $userid ;
            $wheres['type'] = 2 ;
            $totalhong = db('sz_yi_redpointsdetail')->where($wheres)->sum('money'); //获取红积分转余额总金额

            $return['uid'] = $userid ;
            $totalreturn = db('sz_yi_return_detail')->where($return)->sum('money'); //获取全返总金额

            $totaljin = $totalchong + $totalhong + $totalreturn ;
            $logarr['totaljin'] = $totaljin ;
        //进账记录包含 充值余额 红积分转余额 全返到余额 end    
            


        //支出记录包含  余额支付总金额   余额提现总金额 start
            $where['userid'] = $userid ;
            $where['type'] = 1 ;
            $totalyuepay = db('sz_yi_yuezhimes')->where($where)->sum('money'); //获取余额支付总金额
            // var_dump($totalyuepay);die;

            $apply['uid'] = $userid;
            $apply['type'] = 1 ;
            $apply['status'] = 1 ;
            $totalwithdraw = db('sz_yi_withdraw_apply')->where($apply)->sum('apply_money'); //获取余额提现总金额

            $totalzhi = $totalyuepay + $totalwithdraw ;
            $logarr['totalzhi'] = $totalzhi ;
        //支出记录包含  余额支付总金额   余额提现总金额 end    
        

        $this->ajaxReturn($logarr);

    }

    /*
     * 个人中心
     */
    // public function getUsemes(){
    //     $userid = $this->getUsemes();
    //     if(empty($userid)){
    //          $this->ajaxReturn(['status' => -1, 'result' => '用户暂无登录!']);
    //     }
    //     $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息

    //     return $usermess ;
    // }


    //进账记录的详细信息
    public function getJinzhangdetail(){
        
        $userid = $this->getUsemes();
        $chong['user_id'] = $userid ;
        $chong['pay_status'] = 1 ;
        $totalchong = db('recharge')->where($chong)->select(); //获取充值记录信息
        foreach ($totalchong as $kc => $vac) {
            $totalchong[$kc]['addtime'] = date('Y-m-d H:i:s',$vac['pay_time']);
            $totalchong[$kc]['money'] = $vac['account'];
        }

        $wheres['userid'] = $userid ;
        $wheres['type'] = 2 ;
        $totalhong = db('sz_yi_redpointsdetail')->where($wheres)->select(); //获取红积分转余额总金额记录信息
        foreach ($totalhong as $kh => $vah) {
            $totalhong[$kh]['addtime'] = date('Y-m-d H:i:s',$vah['addtime']);
            // $totalhong[$kh]['money'] = $vah['account'];
        }


        $return['uid'] = $userid ;
        $fenreturn = db('sz_yi_return_detail')->where($return)->order('add_time desc ')->select(); //获取全返总金额记录信息
        // var_dump('<pre>');
        // var_dump($fenreturn);die;
        
        $danewarr = array();
        $this -> createnewarr($fenreturn,$danewarr,$fenreturn[0]['add_time']) ;

        // var_dump('<pre>');
        // var_dump($danewarr);die;

        $totalreturn = $danewarr ;
        foreach ($totalreturn as $khr => $vahr) {
            $totalreturn[$khr]['addtime'] = date('Y-m-d H:i:s',$vahr['add_time']);
            $totalzong = 0 ;
            foreach ($vahr['money'] as $vazz) {
                $totalzong = $totalzong + $vazz ;
            }
            $totalreturn[$khr]['money'] = $totalzong ;
        }


        $tixian['status'] = -1 ;
        $tixian['uid'] = $userid ;
        $totaltixian = db('sz_yi_withdraw_apply')->where($tixian)->select(); //获取余额提现被拒绝的记录信息
        foreach ($totaltixian as $ktt => $vtt) {
            $totaltixian[$ktt]['addtime'] = date('Y-m-d H:i:s',$vtt['apply_time']);
            $totaltixian[$ktt]['money'] = $vtt['apply_money'];
        }

        $zhangarr['chongarr'] = $totalchong ;
        $zhangarr['hongarr'] = $totalhong ;
        $zhangarr['returnarr'] = $totalreturn ;
        $zhangarr['tixianarr'] = $totaltixian ;

        $this->ajaxReturn($zhangarr);

    }


    //递归遍历新的数组信息
    public function createnewarr($fanarr,&$arrs,$add_time,$nu = 0){

        if(empty($fanarr)){
           return ;
        }
        $duibidate = date('Y-m-d',$add_time);
        $arrs[$nu]['add_time'] = $add_time ;
        foreach ($fanarr as $kes => $vkk) {
            // $zongqian = 0 ;
            $dangdate = date('Y-m-d',$vkk['add_time']);
            // if($vkk['add_time'] == $add_time){
            //     $arrs[$nu]['money'][] = $vkk['money'] ;
            //     $zongqian = $zongqian + $vkk['money'] ;
            //     $indexarr[] = $kes ;
            // }
            if($dangdate == $duibidate){
                $arrs[$nu]['money'][] = $vkk['money'] ;
                $zongqian = $zongqian + $vkk['money'] ;
                $indexarr[] = $kes ;
            }
            // var_dump($zongqian);die;
            // $arrs[$nu]['money'] = $zongqian ;
        }

        $newarr = array() ;
        foreach ($fanarr as $jj => $va) {
            $biao = false ;
            foreach ($indexarr as $vin) {
                if($jj == $vin){
                    $biao = true ;
                }
            }
            if(!$biao){
                $newarr[] = $va ;
            }
        }
        $nu++ ;
        $this->createnewarr($newarr,$arrs,$newarr[0]['add_time'],$nu);

    }


    //支出记录的详细信息
    public function getZhichudetail(){

        $userid = $this->getUsemes();
        $where['userid'] = $userid ;
        $where['type'] = 1 ;
        $totalyuepay = db('sz_yi_yuezhimes')->where($where)->select(); //获取余额支付总金额记录信息
        foreach ($totalyuepay as $khy => $vahy) {
            $totalyuepay[$khy]['addtime'] = date('Y-m-d H:i:s',$vahy['paytime']);
            // $totalyuepay[$khw]['money'] = $vahy['apply_money'];
        }   

        $apply['uid'] = $userid;
        $apply['type'] = 1 ;
        $apply['status'] = 1 ;
        $totalwithdraw = db('sz_yi_withdraw_apply')->where($apply)->select(); //获取余额提现总金额记录信息
        foreach ($totalwithdraw as $khw => $vahw) {
            $totalwithdraw[$khw]['addtime'] = date('Y-m-d H:i:s',$vahw['apply_time']);
            $totalwithdraw[$khw]['money'] = $vahw['apply_money'];
        } 

        $zhiarr['payarr'] = $totalyuepay ;
        $zhiarr['withdrawarr'] = $totalwithdraw ;

        $this->ajaxReturn($zhiarr);

    }


    //统计当前用户购物车的数量
    public function totalCartcount(){
        $userid = $this->getUsemes();
        $coutcart = db('cart')->where('user_id',$userid)->count();
        $this->ajaxReturn($coutcart);
    }


    //用户确认收货后返还全返 返还用户佣金
    public function returnYonghuyong(){
        
        $orderid = I('post.orderid'); //获取订单id
        // $orderid = 25 ;
        $ordermes = db('order')->where('order_id',$orderid)->find(); //获取订单信息
        if( $ordermes['supplier_uid'] != 0 ){
            //商家信息
            $storemess = db('sz_yi_store_data')->where('storeid',$ordermes['supplier_uid'])->find(); //获取商家信息
            // if($storemess['cid'] != 0){
            //     //商家选择了优惠比例
            //     $chargemes = db('sz_yi_charge')->where('id',$storemess['cid'])->find(); //获取手续费信息
            //     $platformfee = floatval($chargemes['platformfee'])/ 100; //获取平台手续费
            //     $quanfanfee = floatval($chargemes['quanfanfee']) / 100 ; //获取全返手续费

            //     //购物全返 start
            //         $totalmoney = floatval($ordermes['order_amount']); //应该付款金额
            //         $fanmoney = $totalmoney * $quanfanfee ; //获取用户每天所返还的金额
            //         $data['uid'] = $ordermes['user_id'] ;
            //         $data['totalmoney'] = $totalmoney ; //总返还金额
            //         $data['shengmoney'] = $totalmoney ; //剩余金额
            //         $data['fanmoney'] = $fanmoney ; //每天返还金额
            //         $data['add_time'] = time();
            //         $data['orderid'] = $orderid ;
            //         db('sz_yi_return_total')->insert($data); //插入全返明细
            //     //购物全返 end
                

            //     //商家所获得佣金 start
            //           $getfee = 1 -  $platformfee ; //获取商家所的金额的比例
            //           $getmoneys =  $totalmoney *  $getfee ;
            //           db('order')->where('order_id',$orderid)->update(['yongjin' => $getmoneys]); //更新订单对应的佣金
            //           accountshangyong($ordermes['supplier_uid'], $getmoneys); //修改商家佣金
            //     //商家所获得佣金 end
            
            // }

        }

    }


    //商家前台进行提现操作
    public function subStorewithdraw(){
        
        $userid = $this->getUsemes();
        $storemess = db('sz_yi_store_data')->where('useid',$userid)->find();
        $sid = $storemess['storeid'] ; //获取商家id
        $type = I('post.type') ; //提现类型 1银行卡 2微信 3余额
        if(!$storemess){
            $this->ajaxReturn(['status' => -4,'result' => '商家不存在']);  
        }
        $yongjin = floatval($storemess['yongjin']);
        if($yongjin == 0){
            $this->ajaxReturn(['status' => -3,'result' => '商家佣金不足,无法提现']);
        }
        $money = $yongjin ; //商家当前佣金总金额
        $data['uid'] = $sid ;
        $data['type'] = $type ;
        $applysn = 'ST'.time().rand(10000000,99999999);
        $data['applysn'] = $applysn ;
        $data['apply_money'] = $money ;
        $data['apply_time'] = time() ;
        if($type == 1){
            //银行卡
            $where['userid'] = $userid ;
            $where['is_default'] = 1 ;
            $bankmess = db('sz_yi_bankmes')->where($where)->find();
            if(!$bankmess){
                $this->ajaxReturn(['status' => -2,'result' => '请先绑定银行卡']);
            }
            $data['realname'] = $bankmess['realname'] ;
            $data['bankcode'] = $bankmess['bankcode'] ;
            $data['bankname'] = $bankmess['bankname'] ;   
        }
        $ress = db('sz_yi_supplier_apply')->insert($data);
        if($ress){
            accountshangyong($sid, ($money * -1) ); //减掉商家佣金
            $this->ajaxReturn(['status' => 1,'result' => '提现成功']);
        }else{
            $this->ajaxReturn(['status' => -1,'result' => '提现失败']);    
        }

    }

    //商家提现明细
    public function withdrawLogmess(){

        $userid = $this->getUsemes();
        $storemess = db('sz_yi_store_data')->where('useid',$userid)->find();
        $sid = $storemess['storeid'] ; //获取商家id
        $applymess = db('sz_yi_supplier_apply')->where('uid',$sid)->order('apply_time desc ')->select();
        foreach ($applymess as $kp => $vap) {
            if($vap['status'] == 0){
                $applymess[$kp]['mess'] = '审核中' ;
            }elseif ($vap['status'] == 1) {
                $applymess[$kp]['mess'] = '审核成功' ;
            }
            if($vap['type'] == 1){
                $applymess[$kp]['leixing'] = '银行卡' ;
            }elseif ($vap['type'] == 2) {
                $applymess[$kp]['leixing'] = '微信' ;
            }elseif ($vap['type'] == 3) {
                $applymess[$kp]['leixing'] = '余额' ;
            }
        }
        $this->ajaxReturn($applymess);
    }


    //商家订单明细
    public function storeOrdermess(){

        $userid = $this->getUsemes();
        $storemess = db('sz_yi_store_data')->where('useid',$userid)->find();
        $sid = $storemess['storeid'] ; //获取商家id
        $where['order_status'] = array('egt',2)  ; //用户确定收货了才有佣金
        $where['supplier_uid'] = $sid  ;
        $where['yongjin'] = array('gt',0)  ;
        $ordermess = db('order')->where($where)->select();
        foreach ($ordermess as $kh => $vah) {
            $ordermess[$kh]['add_time'] = date('Y-m-d H:i:s',$vah['add_time']);
        }

        $this->ajaxReturn($ordermess);
    }

    //获取商家信息
    public function getStoremess(){

        $userid = $this->getUsemes();
        $storemess = db('sz_yi_store_data')->where('useid',$userid)->find();
        $sid = $storemess['storeid'] ; //获取商家id
        $where['order_status'] = array('egt',2)  ;  //用户确定收货了才有佣金
        $where['supplier_uid'] = $sid  ;
        $where['yongjin'] = array('gt',0)  ; 
        $zongorder = db('order')->where($where)->count();
        $zongjine = db('order')->where($where)->sum('yongjin');
        $storemess['zongjine'] = $zongjine ;
        $storemess['zongorder'] = $zongorder ;
        $storemess['storetime'] = date("Y-m-d H:i:s",$storemess['storetime']) ;
        $this->ajaxReturn($storemess);
    }


    


    //获取当前分销商的全部下线
    public function getMynextmess(){

        $userid = $this->getUsemes();
        $nextarr = array();
        $this -> getAllNextLshang($userid,$nextarr); //获取当前用户的下级信息
        $shangarr = array() ;
        $num = 0 ;
        for ( $i = 1; $i <= 3; $i++) {
            foreach ($nextarr as $vaj) {
                if($vaj['i'] == $i){
                    if($num == 0){
                        $tempstr = 'onearr' ;
                    }elseif ($num == 1) {
                        $tempstr = 'twoarr' ;
                    }elseif ($num == 2) {
                        $tempstr = 'threearr' ;
                    }
                    $vaj['agenttimes'] = date('Y-m-d H:i:s',$vaj['agenttime']);
                    $shangarr[$tempstr][] = $vaj ;
                }
            }
            $num++ ;
        }

        // var_dump('<pre>');
        // var_dump($shangarr);die;

        $this->ajaxReturn($shangarr);

    }


    //获取分销中心的信息
    public function getFenxiaocenter(){
        
        $userid = $this->getUsemes();
        $nextarr = array();
        $this -> getAllNextLshang($userid,$nextarr); //获取当前用户的下级信息
        $fenarr['totalren'] = count($nextarr); //获取下线总人数

        $where['status'] = 1 ;
        $where['user_id'] = $userid ;
        $fenarr['totalfenbi'] = db('rebate_log')->where($where)->count(); //分销佣金的数量
        // $usermess = $this -> getUsemes() ;
        $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
        $fenarr['totaldistribut'] = $usermess['distribut_money'] ; //获取可提现金额

        $wheres['uid'] = $userid ;
        $wheres['status'] = 1 ;
        $totaltiyong = db('sz_yi_distribut_apply')->where($wheres)->sum('apply_money'); //获取成功提现的金额       
        $fenarr['totaltiyong'] = $totaltiyong ;

        $tishis['uid'] = $userid ;
        $totalmingshu = db('sz_yi_distribut_apply')->where($tishis)->count(); //获取提现明细的笔数
        $fenarr['totalmingshu'] = $totalmingshu ;

        $this->ajaxReturn($fenarr);

    }


    //分销佣金提现的操作
    public function subFenxiaoyong(){

        $userid = $this->getUsemes();
        $type = I('post.type') ;
        // $usermess = $this -> getUsemes() ;
        $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
        if($usermess['distribut_money'] == 0){
            $this->ajaxReturn(['status' => -3,'msg' => '分销佣金不足,无法提现']);
        }

        $yonghumoney = floatval($usersmes['distribut_money']);
        $distribut_need = tpCache('basic.need'); // 满多少才能提现
        if($yonghumoney < $distribut_need)
        {
            $this->ajaxReturn(['status'=>-4,'msg'=>'分销佣金最少达到'.$distribut_need.'多少才能提现']);
        }

        $distribut_min = tpCache('basic.min'); // 最少提现额度
        $money = $usermess['distribut_money'] ; //商家当前佣金总金额
        if( floatval($money) < $distribut_min) {
            $this->ajaxReturn(['status'=>-5,'msg'=>'每次最少提现额度' . $distribut_min]);
        }

        
        $data['uid'] = $userid ;
        $data['type'] = $type ;
        $applysn = 'FX'.time().rand(10000000,99999999);
        $data['applysn'] = $applysn ;
        $data['apply_money'] = $money ;
        $data['apply_time'] = time() ;
        if($type == 1){
            //银行卡
            $where['userid'] = $userid ;
            $where['is_default'] = 1 ;
            $bankmess = db('sz_yi_bankmes')->where($where)->find();
            if(!$bankmess){
                $this->ajaxReturn(['status' => -2,'msg' => '请先绑定银行卡']);
            }
            $data['realname'] = $bankmess['realname'] ;
            $data['bankcode'] = $bankmess['bankcode'] ;
            $data['bankname'] = $bankmess['bankname'] ;   
        }
        $ress = db('sz_yi_distribut_apply')->insert($data);
        if($ress){
            accountFenxiao($userid, ($money * -1)); //扣掉佣金
            $this->ajaxReturn(['status' => 1,'msg' => '提现成功']);
        }else{
            $this->ajaxReturn(['status' => -1,'msg' => '提现失败']);    
        }
    }
    

    //分销商提现明细
    public function fenxiaoDetail(){

        $userid = $this->getUsemes();
        $applymess = db('sz_yi_distribut_apply')->where('uid',$userid)->select();
        foreach ($applymess as $kp => $vap) {
            if($vap['status'] == 0){
                $applymess[$kp]['mess'] = '审核中' ;
            }elseif ($vap['status'] == 1) {
                $applymess[$kp]['mess'] = '审核成功' ;
            }
            if($vap['type'] == 1){
                $applymess[$kp]['leixing'] = '银行卡' ;
            }elseif ($vap['type'] == 2) {
                $applymess[$kp]['leixing'] = '微信' ;
            }elseif ($vap['type'] == 3) {
                $applymess[$kp]['leixing'] = '余额' ;
            }
            $applymess[$kp]['apply_times'] = date('Y-m-d H:i:s',$vap['apply_time']) ;
        }
        $this->ajaxReturn($applymess);
    }




    //用递归的方法获取当前用户的全部下线信息
    public function getAllNextLshang($first_leader = '', &$arr,$i=0){
        $where['first_leader'] = $first_leader ;
        $where['is_distribut'] = 1 ;
        $getmyl = db('users')->where($where)->select();
        $i++;
        foreach ($getmyl as $kg => $vag) {
            $vag['i'] = $i ;
            if($vag['first_leader']){
                $arr[] = $vag;
                $this->getAllNextLshang($vag['user_id'], $arr,$i);
            }
        }
    }


    //微信支付成功后 用户点击报名支付的操作处理
    public function carjoindetail(){
        $oid = I('post.oid') ; //获取记录id
        $order = Db::name('sz_yi_carjoin')->where("id", $oid)->find();
        if ($order['pay_status'] == 1) {
            $links = "http://".$_SERVER['SERVER_NAME']."/#/applysuccess?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=2&orderid='.$order['id'].'&isbao=1' ;
            header("Location:".$links);
            exit;
        }else{
            $mes = '此报名未完成支付!' ;
            $links = "http://".$_SERVER['SERVER_NAME']."/#/applysuccess?mess=".$mes.'&op=2&fail=1' ;
            header("Location:".$links);
        }
    }


    //微信支付成功后 用户点击支付订单的操作处理
    public function carjoindetails(){
        $oid = I('post.oid') ; //获取记录id
        $order = Db::name('order')->where("order_id", $oid)->find();
        if ($order['pay_status'] == 1) {
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentsuccess?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=2&orderid='.$order['order_id'] ;
            header("Location:".$links);
            exit;
        }else{
            $mes = '此订单未完成支付!' ;
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=2&fail=1&isbao=1' ;
            header("Location:".$links);
        }
    }



    //获取驾校中心信息
    public function getJiaxiaomess(){
        $userid = $this->getUsemes();
        $storemess = db('sz_yi_store_data')->where('useid',$userid)->find();
        $sid = $storemess['storeid'] ; //获取商家id
        $storemess['storetime'] = date("Y-m-d H:i:s",$storemess['storetime']) ;
        $trainercount = db('sz_yi_trainer')->where('userid',$userid)->count(); //获取教练信息总数
        $storemess['trainercount'] = $trainercount ; //教练总数
        $carjoincount = db('sz_yi_carjoin')->where('jid',$sid)->count(); //获取驾校所报的人数
        $storemess['carjoincount'] = $carjoincount ; //驾校报名总数
        $this->ajaxReturn($storemess);
    }



    //获取驾校中心 教练信息列表 驾考报名会员信息
    public function getJiacentermess(){
        
    }

    //订单支付回调地址
    public function notifyUrls(){
        $order_id = I('orderid'); // 订单id
        $this->assign('order_id', $order_id);
        return $this->fetch('payment');
    }

    //报名支付回调地址
    public function notifyUrlss(){
        $order_id = I('orderid'); // 订单id
        $this->assign('order_id', $order_id);
        return $this->fetch('payments');
    }


    //微信支付成功后 用户点击支付订单的操作处理
    public function bmcarjoindetails(){
        $oid = I('post.oid') ; //获取记录id
        $order = Db::name('sz_yi_carjoin')->where("id", $oid)->find();
        if ($order['pay_status'] == 1) {
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentsuccess?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=1&orderid='.$order['id']."&isbao=1" ;
            header("Location:".$links);
            exit;
        }else{
            $mes = '此报名未完成支付!' ;
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=1&fail=1&isbao=1' ;
            header("Location:".$links);
        }
    }


}
