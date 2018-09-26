<?php
namespace app\Appapi\controller;

use think\Db;
use think\Session ;


// 指定允许其他域名访问  
header('Access-Control-Allow-Origin:*');  
// 响应类型  
header('Access-Control-Allow-Methods:*');  
// 响应头设置  
header('Access-Control-Allow-Headers:x-requested-with,content-type');  

class Zhifuway extends MobileBase
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

        // 获取通知的数据
        if (empty($this->pay_code)) {
            exit('pay_code 不能为空');
        }

        // var_dump('<pre>');
        // var_dump($this->pay_code);die;

        // 导入具体的支付类文件
        include_once "bmplugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; 
        $code = '\\' . $this->pay_code; //alipay
        $this->payment = new $code();
    }

    /**
     * 用户支付订单的操作
     */
    public function getCode()
    {
        header("Content-type:text/html;charset=utf-8");
        $userid = Session::get('userid');
        $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
        $user = $usermess ;
        
        // 修改订单的支付方式
        // $order_id = I('order_id/d'); // 订单id
        // $order = Db::name('order')->where("order_id", $order_id)->find();
        
        $oid = I('post.oid') ; //获取记录id
        $order = Db::name('sz_yi_carjoin')->where("id", $oid)->find();
        
        // var_dump($order);die;
        
        if ($order['pay_status'] == 1) {
            $mes = '此订单，已完成支付!' ;
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?mess=".$mes.'&op=1&fail=1' ;
            header("Location:".$links);
        }
        $payment_arr = Db::name('Plugin')->where('type', 'payment')->getField("code,name");
        
        // Db::name('order')->where("order_id", $order_id)->save(['pay_code' => $this->pay_code, 'pay_name' => $payment_arr[$this->pay_code]]);
        Db::name('sz_yi_carjoin')->where("id",$oid)->save(['pay_code' => $this->pay_code, 'pay_name' => $payment_arr[$this->pay_code]]);
        
        // 订单支付提交
        $config = parse_url_param($this->pay_code); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        // $config['body'] = getPayBody($order_id);
        $config['body'] = '驾考报名';
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
            //其他支付(支付宝、银联...)
            $code_str = $this->payment->get_code($order, $config);
        }

        $this->assign('code_str', $code_str);
        // $this->assign('order_id', $order_id);
        $this->assign('order_id', $oid);
        return $this->fetch('payment');  // 分跳转 和不 跳转
    }

    

    // 服务器点对点 用户进行付款操作,付完款并进行更改订单的状态
    public function notifyUrl()
    {   
        $this->payment->response(); 
        exit();
    }

    public function notifyUrls()
    {   
        $this->payment->response();
        $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentsuccess?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=1&orderid='.$order['id'] ;
        header("Location:".$links); 
        exit();
    }

    // 页面跳转 用户进行付款操作后的返还操作结果界面
    public function returnUrl()
    {
        $result = $this->payment->respond2();
        $order = M('sz_yi_carjoin')->where("order_sn", $result['order_sn'])->find();
        if ($result['status'] == 1){
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentsuccess?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=1&orderid='.$order['id'] ;
            header("Location:".$links);
            exit;
        } else{
            $links = "http://".$_SERVER['SERVER_NAME']."/#/paymentfail?order_sn=".$order['order_sn']."&account=".$order['order_amount'].'&op=1&orderid='.$order['id'] ;
            header("Location:".$links);
            exit;
        }
                 
    }



    //用户进行余额支付操作
    public function yuepayment(){
        // $userid = cache('userid') ; //获取用户id
        $userid = Session::get('userid');
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
                getshicodemes($order['order_id']); //附近商城购买后赠送使用劵
                if($order['integral'] > 0){
                    //积分抵扣金额不为0
                    $integral = $order['integral'] ;
                    accountLog($order['user_id'],0,($integral * -1),"用户购买商品抵扣积分"); //追加用户积分
                    $datafen['userid'] = $order['userid'] ;
                    $datafen['money'] = $integral ;
                    $datafen['addtime'] = time() ;
                    $datafen['type'] = 4 ;
                    db('sz_yi_redpointsdetail')->insert($datafen); //插入积分明细
                }

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
