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

class Payment extends MobileBase
{
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code

    /**
     * 析构流函数
     */
    public function __construct()
    {
        parent::__construct();

        // 获取支付类型
        $pay_radio = input('pay_radio');
        if (!empty($pay_radio)) {
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        } else {    
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }

        // buyShengjimes(52); //根据会员所购买的商品进行升级操作
        // var_dump(969);die;

        // var_dump($this->pay_code);die;
        // 获取通知的数据
        if (empty($this->pay_code)) {
            exit('pay_code 不能为空');
        }


        // 导入具体的支付类文件
        include_once "orplugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; // D:\wamp\www\svn_tpshop\www\plugins\payment\alipay\alipayPayment.class.php
        $code = '\\' . $this->pay_code; // \alipay
        $this->payment = new $code();
    }


    public function getUsemes(){
        // $userid = Session::get('userid');
        // if(empty($userid)){
        //    $this->ajaxReturn(['status' => -1, 'msg' => '您还没登录!']); 
        // }
        $userid = I('post.userid') ;
        return $userid ;
    }

    /**
     * 用户支付订单的操作
     */
    public function getCode()
    {
        header("Content-type:text/html;charset=utf-8");
        $userid = $this->getUsemes();
        $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
        $user = $usermess ;
        // if (!session('user')) {
        //     $this->error('请先登录', U('User/login'));
        // }
        // if (!$user) {
        //     $this->error('请先登录');
        // }

        // 修改订单的支付方式
        $order_id = I('order_id/d'); // 订单id
        // $order_id = I('order_id'); // 订单id
        // var_dump($order_id);die;

        $order = Db::name('order')->where("order_id", $order_id)->find();
        if ($order['pay_status'] == 1) {
            // $this->error('此订单，已完成支付!');
            // exit('此订单，已完成支付');
            $mes = '此订单，已完成支付!' ;
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=2&fail=1' ;
            header("Location:".$links);
        }

        $payment_arr = Db::name('Plugin')->where('type', 'payment')->getField("code,name");

        if($this->pay_code == 'yueMobile'){
            Db::name('order')->where("order_id", $order_id)->save(['pay_code' => $this->pay_code, 'pay_name' => 'yueMobile']);
        }else{
            Db::name('order')->where("order_id", $order_id)->save(['pay_code' => $this->pay_code, 'pay_name' => $payment_arr[$this->pay_code]]);
        }

        
        // 订单支付提交
        $config = parse_url_param($this->pay_code); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        $config['body'] = getPayBody($order_id);

        if ($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            //微信JS支付
            $code_str = $this->payment->getJSAPI($order);
            exit($code_str);
        } elseif ($this->pay_code == 'weixinH5') {
            //微信H5支付
            $return = $this->payment->get_code($order, $config);
            if ($return['status'] != 1) {
                $this->error($return['msg']);
            }
            $this->assign('deeplink', $return['result']);
        }elseif ($this->pay_code == 'yueMobile') {
            //余额支付
            
            $this->yuepayment();    

        } else {
            //其他支付（支付宝、银联...）
            
            $code_str = $this->payment->get_code($order, $config);
        }

        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('payment');  // 分跳转 和不 跳转
    }

    //用户进行充值操作
    public function getPay()
    {
        //手机端在线充值
        //C('TOKEN_ON',false); // 关闭 TOKEN_ON 
        header("Content-type:text/html;charset=utf-8");
        $order_id = I('order_id/d'); //订单id
        // $user = session('user');
        // $userid =  cache('userid') ;
        $userid = $this->getUsemes();
        $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
        $user = $usermess ;
        // if (!$user) {
        //     // $this->error('请先登录');
        //     exit('请先登录');
        // }
        $data['account'] = I('account');

        $zongaccount = floatval(I('account'));
        if($zongaccount == 0){
            $mes = '输入金额不能为0!' ;
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=1&fail=2' ;
            header("Location:".$links);
            exit;
            // exit;
        }
 
        if ($order_id > 0) {
            M('recharge')->where(array('order_id' => $order_id, 'user_id' => $user['user_id']))->save($data);
        } else {
        	$data['buy_vip'] = I('buy_vip',0);
        	// if($data['buy_vip'] == 1){
        	// 	$map['user_id'] = $user['user_id'];
        	// 	$map['buy_vip'] = 1;
        	// 	$map['pay_status'] = 1;
        	// 	$info = M('recharge')->where($map)->order('order_id desc')->find();
        	// 	if (($info['pay_time'] + 86400 * 365) > time() && $user['is_vip'] == 1) {
        	// 		$this->error('您已是VIP且未过期，无需重复充值办理该业务！');
        	// 	}
        	// }

        	$data['user_id'] = $user['user_id'];
        	$data['nickname'] = $user['nickname'];
        	$data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
        	$data['ctime'] = time();
        	$order_id = M('recharge')->add($data);
        }
        if ($order_id) {
            $order = M('recharge')->where("order_id", $order_id)->find();
            if (is_array($order) && $order['pay_status'] == 0) {
                $order['order_amount'] = $order['account'];
                $pay_radio = $_REQUEST['pay_radio'];
                $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
                $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
                M('recharge')->where("order_id", $order_id)->save(array('pay_code' => $this->pay_code, 'pay_name' => $payment_arr[$this->pay_code]));
                //微信JS支付
                if ($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    $code_str = $this->payment->getJSAPI($order);
                    exit($code_str);
                } else {
                    $code_str = $this->payment->get_code($order, $config_value);
                    // var_dump($code_str);die;
                }
            } else {
                // $this->error('此充值订单，已完成支付!');
                // exit('此充值订单，已完成支付!');
                $mes = '此订单，已完成支付!' ;
                $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=2&fail=1' ;
                header("Location:".$links);
                exit;
            }
        } else {
            // $this->error('提交失败,参数有误!');
            // exit('提交失败,参数有误!');
            $mes = '提交失败,参数有误!' ;
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=1&fail=4' ;
            header("Location:".$links);
            exit;
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('recharge'); //分跳转 和不 跳转
    }

    //用户进行充值操作
    public function getPaynew()
    {
        //手机端在线充值
        //C('TOKEN_ON',false); // 关闭 TOKEN_ON 
        header("Content-type:text/html;charset=utf-8");
        $order_id = I('order_id/d'); //订单id
        // $user = session('user');
        $userid = $this->getUsemes();
        $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
        $user = $usermess ;
        $data['account'] = I('account');
        if ($order_id > 0) {
            M('recharge')->where(array('order_id' => $order_id, 'user_id' => $user['user_id']))->save($data);
        } else {
            $data['buy_vip'] = I('buy_vip',0);
            if($data['buy_vip'] == 1){
                $map['user_id'] = $user['user_id'];
                $map['buy_vip'] = 1;
                $map['pay_status'] = 1;
                $info = M('recharge')->where($map)->order('order_id desc')->find();
                if (($info['pay_time'] + 86400 * 365) > time() && $user['is_vip'] == 1) {
                    $this->error('您已是VIP且未过期，无需重复充值办理该业务！');
                }
            }

            $data['user_id'] = $user['user_id'];
            $data['nickname'] = $user['nickname'];
            $data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
            $data['ctime'] = time();
            $order_id = M('recharge')->add($data);
        }
        if ($order_id) {
            $order = M('recharge')->where("order_id", $order_id)->find();
            if (is_array($order) && $order['pay_status'] == 0) {
                $order['order_amount'] = $order['account'];
                $pay_radio = $_REQUEST['pay_radio'];
                $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
                $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
                M('recharge')->where("order_id", $order_id)->save(array('pay_code' => $this->pay_code, 'pay_name' => $payment_arr[$this->pay_code]));
                //微信JS支付
                if ($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    $code_str = $this->payment->getJSAPI($order);
                    exit($code_str);
                } else {
                    $code_str = $this->payment->get_code($order, $config_value);
                    // var_dump($code_str);die;
                }
            } else {
                $this->error('此充值订单，已完成支付!');
            }
        } else {
            $this->error('提交失败,参数有误!');
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('recharge'); //分跳转 和不 跳转
    }

    // 服务器点对点 用户进行付款操作,付完款并进行更改订单的状态
    public function notifyUrl()
    {
        $this->payment->response(); 
        exit();
    }

    // 页面跳转 用户进行付款操作后的返还操作结果界面
    public function returnUrl()
    {
        $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';
        if (stripos($result['order_sn'], 'recharge') !== false) {
            $order = M('recharge')->where("order_sn", $result['order_sn'])->find();
            // $this->assign('order', $order); 
            if ($result['status'] == 1){
                //用户支付成功的跳转成功界面
                // $links = "http://".$_SERVER['SERVER_NAME']."/dist/recharge_success.html?order_sn=".$order['order_sn']."&account=".$order['account'] ;
                $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentsuccess?order_sn=".$order['order_sn']."&account=".$order['account'].'&op=1' ;
                header("Location:".$links);
                exit;
            }else{
                //支付失败的失败界面
                // $links = "http://".$_SERVER['SERVER_NAME']."/dist/recharge_error.html?order_sn=".$order['order_sn']."&account=".$order['account'] ;
                $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?order_sn=".$order['order_sn']."&account=".$order['account'].'&op=1' ;
                header("Location:".$links);
                exit;
            }
            // if ($result['status'] == 1)
            //     return $this->fetch('recharge_success');
            // else
            //     return $this->fetch('recharge_error');
        }
        $order = M('order')->where("order_sn", $result['order_sn'])->find();
        // $this->assign('order', $order);
        if ($result['status'] == 1){
             $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentsuccess?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=2&orderid='.$order['order_id'] ;
            header("Location:".$links);
            exit;
            // return $this->fetch('success');
        } else{
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=2&orderid='.$order['order_id'] ;
            header("Location:".$links);
            exit;
            // return $this->fetch('error');
        }
                 
    }



    //用户进行余额支付操作
    public function yuepayment(){
        
        $userid = $this->getUsemes();
        $orderid = I('post.order_id') ; //获取订单id
        $money = I('post.account'); //获取所支付的金额
        $pay_radio = I('post.pay_radio') ; //获取支付类型
        $pay_radio = parse_url_param($pay_radio);
        $pay_code = $pay_radio['pay_code'];
        
        if($pay_code == 'yueMobile'){
            //余额支付
            
            $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
            if( floatval($usermess['user_money']) < floatval($money) ){
                $mes = '余额不足!无法支付!' ;
                $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=3&fail=5' ;
                header("Location:".$links); 
                exit;
            }

            accountLog($userid, ($money * -1), 0,"用余额支付商品"); //扣掉余额
            $zdata['userid'] = $userid ;
            $zdata['money'] = $money ;
            $zdata['paytime'] = time() ;
            $zdata['type'] = 1 ;
            $zdata['typemes'] = '余额支付商品' ;
            db('sz_yi_yuezhimes')->insert($zdata);

            $updates['pay_name'] = '余额支付' ;
            $updates['pay_status'] = 1 ;
            $res = db('order')->where('order_id',$orderid)->update($updates);
            $order = db('order')->where("order_id", $orderid)->find();    
            if($res){

                buyShengjimes($orderid); //根据会员所购买的商品进行升级操作
                getFenxiaomes($orderid); //分销操作
                getshicodemes($orderid); //附近商城购买后赠送使用劵

                $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentsuccess?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=3&orderid='.$order['order_id'] ;
                header("Location:".$links);
                exit;
            }else{
                $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=3&orderid='.$order['order_id'] ;
                header("Location:".$links);
                exit; 
            }
            
        }
    }


    


}
