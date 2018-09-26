<?php
/**
 * 
 * ============================================================================
 *用户接口
 * ============================================================================
 * 2018-06-02
 */
// namespace app\mobile\controller;
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

class Usersup extends MobileBase
{

	
    /*
     * 用户登录时所调用的接口
     */
    public function login()
    {   
        
        
        $username = I('post.username'); //获取输入的手机号码
        $password = I('post.password'); //获取输入的密码
        if(empty($username)){
            $this->ajaxReturn(['status' => -1, 'msg' => '手机号码不能为空!']);
        }
        if(empty($password)){
            $this->ajaxReturn(['status' => -2, 'msg' => '密码不能为空!']);
        }

        $data['mobile'] = $username ;
        $data['password'] = encrypt($password);
        $re = db('users')->where($data)->find(); //查询是否存在此用户
        if($re){
            Session::set('userid',$re['user_id']);
            // $userid = Session::get('userid');
            // file_put_contents('1.txt',$userid);
            $this->ajaxReturn(['status' => 1, 'msg' => '登录成功!' ,'uid'=>$re['user_id']]);
        }else{
            $this->ajaxReturn(['status' => -3, 'msg' => '手机号码或者密码错误!']);
        }
        
    }


    /*
     * 用户注册时所调用的接口
     */
    public function register(){


        $mobile = I('post.mobile'); //获取输入的手机号码
        $password = I('post.password'); //获取输入密码
        $code = I('post.code'); //获取验证码
    
        if(empty($mobile)){
            $this->ajaxReturn(['status' => -1, 'msg' => '手机号码不能为空!']);    
        }
        if( ! preg_match("/^1[34578]{1}\d{9}$/",$mobile)){
            $this->ajaxReturn(['status' => -1, 'msg' => '请输入正确的手机号码!']);
        }
        if(empty($password)){
            $this->ajaxReturn(['status' => -2, 'msg' => '密码不能为空!']);    
        }
        if(empty($code)){
            $this->ajaxReturn(['status' => -3, 'msg' => '验证码不能为空!']);    
        }
        
        if(! preg_match('/^1([0-9]{9})/',$mobile) ){
            $this->ajaxReturn(['status' => -5, 'msg' => '手机号码格式不正确!']);
        }

        $result = db('users')->where('mobile',$mobile)->find(); //查询手机号码是否已经存在
        if($result){
            $this->ajaxReturn(['status' => -7, 'msg' => '手机号码已被注册!']);
        }

        $data['mobile'] = $mobile ;
        $data['password'] = encrypt($password) ;
        $data['reg_time'] = time() ;
        $data['is_distribut'] = 1 ; //无条件成为分销商
        $data['agenttime'] = time() ;
        $res = db('users')->insert($data);
        if($res){

            $agantid = I('sid'); //获取分享人的用户id
            $rids = db('users')->getLastInsID();
            if(!empty($agantid)){
                $datas['first_leader'] = $agantid ;
                $lastarr = array();
                db('users')->where('user_id',$rids)->update($datas);

                $invite_integral = tpCache('basic.invite_integral'); //邀请人所获积分
                if($invite_integral > 0){
                    accountLog($agantid,0,$invite_integral,"邀请人所获积分");
                    $datayao['userid'] = $agantid ;
                    $datayao['money'] = $invite_integral ;
                    $datayao['addtime'] = time() ;
                    $datayao['type'] = 8 ;
                    db('sz_yi_redpointsdetail')->insert($datayao);  
                }

            }

            $regfen = tpCache('basic.reg_integral'); //注册积分
            if($regfen > 0){
                accountLog($rids,0,$regfen,"用户注册赠送积分");
                $datafen['userid'] = $rids ;
                $datafen['money'] = $regfen ;
                $datafen['addtime'] = time() ;
                $datafen['type'] = 5 ;
                db('sz_yi_redpointsdetail')->insert($datafen);
            }
            
            $this->ajaxReturn(['status' => 1, 'msg' => '注册成功!','sid'=>$agantid]);

        }else{
            $this->ajaxReturn(['status' => -8, 'msg' => '注册失败!']);
        }


    }

    /*
     * 重置用户密码
     */
    public function resetpassword(){

        $mobile = I('post.mobile'); //获取输入的手机号码
        $code = I('post.code'); //获取验证码
        $newpassword = I('post.newpassword'); //获取新密码
        if(empty($mobile)){
                $this->ajaxReturn(['status' => -1, 'msg' => '手机号码不能为空!']);    
        }
        if(empty($code)){
            $this->ajaxReturn(['status' => -2, 'msg' => '验证码不能为空!']);    
        }
        if(empty($newpassword)){
                $this->ajaxReturn(['status' => -1, 'msg' => '新密码不能为空!']);    
        }
        $result = db('users')->where('mobile',$mobile)->find(); //查询手机号码是否已经存在
        if(!$result){
            $this->ajaxReturn(['status' => -6, 'msg' => '此会员不存在!']);
        }
        $datas['password'] = encrypt($newpassword) ;
        $resu = db('users')->where('mobile',$mobile)->update($datas); //根据手机号码 更改对应用户的密码
        if($resu){
            $this->ajaxReturn(['status' => 1]);
        }else{
            $this->ajaxReturn(['status' => -2, 'msg' => '重置密码失败!']);
        }
        
    }


    //用户注册的时候发送短信验证码
    public function sendInfomes(){

        $mobile = I('post.mobile') ;
        if(empty($mobile)){
            $this->ajaxReturn(['State' => -17, 'msg' => '手机号码不能为空!']);
        }
        if(! preg_match('/^1([0-9]{9})/',$mobile) ){
            $this->ajaxReturn(['State' => -18, 'msg' => '手机号码格式不正确!']);
        }

        $result = db('users')->where('mobile',$mobile)->find(); //查询手机号码是否已经存在
        if($result){
            $this->ajaxReturn(['State' => -19, 'msg' => '手机号码已被注册!']);
        }

        $codemess = db('sz_yi_code') ->where('mobile',$mobile)->find();
        if($codemess){
            $sendtimes = floatval($codemess['sendtimes']) ;
            if( time() <  $sendtimes ){
                  $this->ajaxReturn(['State' => -20, 'msg' => '五分钟后再重新发验证码!']);
            }
        }

        $code = rand(1000, 9999);
        // $shop_info = tpCache('shop_info');
        // $store_name = $shop_info['store_name'] ; //后台所填写的网站名
        // $message = '【'.$store_name.'】您的验证码是'.$code.',5分钟内有效' ;
        // $post_data = array(
        //    'Id' => 2675 ,
        //    'Name' => $store_name ,
        //    'Psw' => 123456,
        //    'Message' => $message ,
        //    'Phone' => $mobile ,
        //    'Timestamp' => time(),
        //    'Ext' => ''
        // );
        // $url = 'http://124.172.234.157:8180/service.asmx/SendMessageStr?';
        // $list = $this -> curl_request($url,$post_data); //请求结果
        // $resu = explode(',',$list);
        // $newresu = array();
        // foreach ($resu as $vmm) {
        //     $arrs = explode(':',$vmm);
        //     $newresu[$arrs[0]] = $arrs[1] ;
        // }
        // $newresu['code'] = $code ;
        // $this->ajaxReturn($newresu);
        $shop_info = tpCache('shop_info');
        $store_name = $shop_info['store_name'] ; //后台所填写的网站名
        $message = '【'.$store_name.'】您的验证码是'.$code.',5分钟内有效' ;
        $post_data = array(
           'Id' => 2675 ,
           'Name' => '聚慧万顺',
           'Psw' => 123456,
           'Message' => $message ,
           'Phone' => $mobile ,
           'Timestamp' => time(),
           'Ext' => ''
        );
        $url = 'http://124.172.234.157:8180/service.asmx/SendMessageStr?';
        $list = $this -> curl_request($url,$post_data); //请求结果
        $resu = explode(',',$list);
        $newresu = array();
        foreach ($resu as $vmm) {
            $arrs = explode(':',$vmm);
            $newresu[$arrs[0]] = $arrs[1] ;
        }
        if($newresu['State'] = 1){
            $newresu['code'] = $code ;

            $codedata['code'] =  $code ;
            $sendtimes = strtotime("+5 minute") ;
            $codedata['sendtimes'] =  $sendtimes ;
            if($codemess){
                db('sz_yi_code') -> where('id',$codemess['id']) -> update($codedata);
            }else{
                $codedata['mobile'] =  $mobile ;
                db('sz_yi_code')->add($codedata); //插入验证码记录
            }
            
        }
        $this->ajaxReturn($newresu);

    }




    //用户忘记密码 发送验证码
    public function sendInfomess(){

        $mobile = I('post.mobile') ;
        if(empty($mobile)){
            $this->ajaxReturn(['State' => -17, 'msg' => '手机号码不能为空!']);
        }
        if(! preg_match('/^1([0-9]{9})/',$mobile) ){
            $this->ajaxReturn(['State' => -18, 'msg' => '手机号码格式不正确!']);
        }

        $codemess = db('sz_yi_code') ->where('mobile',$mobile)->find();
        if($codemess){
            $sendtimes = floatval($codemess['sendtimes']) ;
            if( time() <  $sendtimes ){
                  $this->ajaxReturn(['State' => -20, 'msg' => '五分钟后再重新发验证码!']);
            }
        }

        $result = db('users')->where('mobile',$mobile)->find(); //查询手机号码是否已经存在
        $code = rand(1000, 9999);
        $shop_info = tpCache('shop_info');
        $store_name = $shop_info['store_name'] ; //后台所填写的网站名
        $message = '【'.$store_name.'】您的验证码是'.$code.',5分钟内有效' ;
        $post_data = array(
           'Id' => 2675 ,
           'Name' => '聚慧万顺',
           'Psw' => 123456,
           'Message' => $message ,
           'Phone' => $mobile ,
           'Timestamp' => time(),
           'Ext' => ''
        );
        $url = 'http://124.172.234.157:8180/service.asmx/SendMessageStr?';
        $list = $this -> curl_request($url,$post_data); //请求结果
        $resu = explode(',',$list);
        $newresu = array();
        foreach ($resu as $vmm) {
            $arrs = explode(':',$vmm);
            $newresu[$arrs[0]] = $arrs[1] ;
        }
        if($newresu['State'] = 1){
            $newresu['code'] = $code ;

            $codedata['code'] =  $code ;
            $sendtimes = strtotime("+5 minute") ;
            $codedata['sendtimes'] =  $sendtimes ;
            if($codemess){
                db('sz_yi_code') -> where('id',$codemess['id']) -> update($codedata);
            }else{
                $codedata['mobile'] =  $mobile ;
                db('sz_yi_code')->add($codedata); //插入验证码记录
            }

        }
        // $newresu['code'] = $code ;
        $this->ajaxReturn($newresu);

    }

    //执行某个接口的链接返回对应的结果
    public function curl_request($url,$post='',$cookie='', $returnCookie=0){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{
            return $data;
        }
    }


    //获取首页的内容
    public function indexcon(){


        $pid = config('mobilebanerid') ; //获取配置信息  application/config.php 的参数
        $advment = db('ad')->where(array('pid'=>$pid,'enabled'=>1))->field('ad_id,ad_name,ad_code,ad_link')->select(); //查询轮播图的信息
        $indexarr['bannerimg'] = $advment ;

        $indexsign = db('sz_yi_indexsign')->where('is_show',1)->field('id,signname,signlogo,url,is_login')->order('sorts')->select(); //查询标签图的信息
        $indexarr['indexsignimg'] = $indexsign ;
        
        // $biaoqian1 = db('ad')->where('pid',51318)->field('ad_id,ad_name,ad_code')->find(); //查询标签图1的信息
        // $indexarr['biaoqian1'] = $biaoqian1 ;
        // $biaoqian2 = db('ad')->where('pid',51319)->field('ad_id,ad_name,ad_code')->find(); //查询标签图2的信息
        // $indexarr['biaoqian2'] = $biaoqian2 ;
        // $biaoqian3 = db('ad')->where('pid',51320)->field('ad_id,ad_name,ad_code')->find(); //查询标签图3的信息
        // $indexarr['biaoqian3'] = $biaoqian3 ;
        // $biaoqian4 = db('ad')->where('pid',51321)->field('ad_id,ad_name,ad_code')->find(); //查询标签图4的信息
        // $indexarr['biaoqian4'] = $biaoqian4 ;
        // $biaoqian5 = db('ad')->where('pid',51322)->field('ad_id,ad_name,ad_code')->find(); //查询标签图5的信息
        // $indexarr['biaoqian5'] = $biaoqian5 ;
        // $biaoqian6 = db('ad')->where('pid',51323)->field('ad_id,ad_name,ad_code')->find(); //查询标签图6的信息
        // $indexarr['biaoqian6'] = $biaoqian6 ;
        
        $tuijian = db('goods')->where(array('is_recommend'=>1,'is_on_sale'=>1,'is_index'=>1))->field('goods_id,original_img,goods_name,is_on_sale,is_recommend')->order('on_time desc')->find(); //获取推荐商品
        $indexarr['tuijian'] = $tuijian ;

        $newpin = db('goods')->where(array('is_new'=>1,'is_on_sale'=>1,'is_index'=>1))->field('goods_id,original_img,goods_name,is_new,is_on_sale')->order('on_time desc')->find(); //获取新品商品
        $indexarr['newpin'] = $newpin ;

        $remen = db('goods')->where(array('is_hot'=>1,'is_on_sale'=>1,'is_index'=>1))->field('goods_id,original_img,goods_name,is_on_sale,is_hot')->order('on_time desc')->find(); //获取热门商品
        $indexarr['remen'] = $remen ;

        $articlemes = db('article')->where(array('is_open'=>1,'cat_id'=>19))->field('article_id,title')->order('article_id desc ')->limit(2)->select(); //查询文章的信息
        $indexarr['articlemes'] = $articlemes ;

        // var_dump('<pre>');
        // var_dump($indexarr);die;

        $this->ajaxReturn($indexarr);

    }


    //附近商城  或者 驾校选择 的接口  类似于美团
    public function tradearea(){

        $lng1 = I('post.lng1'); //获取定位的经度
        $lat1 = I('post.lat1'); //获取定位的纬度
        // 113.30764968,23.1200491
        // $lng1 = 113.30764968 ;
        // $lat1 = 23.1200491 ;
        // $tradesign = db('sz_yi_supplier_category')->where('status',1)->field('id,name,fenlei_logo,displayorder')->order('displayorder')->select(); //查询商圈标签图的信息
        // $tradearr['tradesignimg'] = $tradesign ;
        // $lng1 = 113.30764968 ;
        // $lat1 = 23.1200491 ;

        $type = I('post.type'); //入驻类型
        $op = I('post.op');
        $sorts = I('post.sorts');
        $ordermes = 'storetime ' ;
        if($op == 'isone'){
            //商家入驻
            
            if($sorts == 'sell'){
                //销量
                $ordermes = 'baonum desc' ; //销量排下来
                $cons['baonum'] = array('gt',0) ; //销量要大于0
            }elseif ($sorts == 'zuixin') {
                //最新时间排序
                $ordermes = 'storetime desc' ; //入驻时间排下来
            }elseif ($sorts == 'hot') {
                //热门
                $ordermes = 'baonum desc' ; //销量排下来
                $cons['baonum'] = array('gt',0) ; //销量要大于0
            }

        }else if($op == 'istwo'){
            //驾校选择
            
            if($sorts == 'distance'){
                //距离最近
            }elseif ($sorts == 'hot') {
                //热门驾校
                $ordermes = 'baonum desc' ; //报名人数排下来
                $cons['baonum'] = array('gt',0) ; //报名人数要大于0
            }elseif ($sorts == 'zuixin') {
                //最新入驻
                $ordermes = 'storetime desc' ; //入驻时间排下来
            }

        }
        $cons['type'] = $type ;
        $cons['storemodel'] = 0 ; //附近商城是0
        $cons['isopen'] = 1 ; //是否开放
        $storemes = db('sz_yi_store_data')->where($cons)->order($ordermes)->select(); //查询店铺信息
        foreach ($storemes as $kj => $vaj) {
            // $shangmes = db('sz_yi_perm_user')->where('uid',$vaj['storeid'])->find();
            $shangleimes = db('sz_yi_supplier_category')->where('id',$vaj['cat_id'])->find();
            if(!empty($vaj['lng']) && !empty($vaj['lat'])){
                $julimes = $this->getdistance($lng1,$lat1,$vaj['lng'],$vaj['lat']); //获取相距的距离
                $julimes = round($julimes, 2);
            }else{
                $julimes = 0 ;
            }
            $storemes[$kj]['julimes'] = $julimes ;
            $storemes[$kj]['leixing'] = $shangleimes['name'] ;     
        }

        if($sorts == 'distance'){
            //距离最近
            
             $julimes = array_column($storemes, 'julimes');   //先用array_column 多维数组按照纵向（列）取出 
             array_multisort($julimes,SORT_ASC,$storemes); //再用array_multisort  结合array_column得到的结果对$arr进行排序

        }

        $tradearr['tradestore'] = $storemes ;

        // var_dump('<pre>');
        // var_dump($tradearr);die;

        $this->ajaxReturn($tradearr);
        
    }

    //线上商城  跟淘宝一样的
    public function tradeareas(){

        $lng1 = I('post.lng1'); //获取定位的经度
        $lat1 = I('post.lat1'); //获取定位的纬度
        $type = I('post.type'); //入驻类型
        $op = I('post.op');
        $sorts = I('post.sorts');
        $ordermes = 'storetime desc' ;
        if($op == 'isone'){
            //商家入驻
            if($sorts == 'sell'){
                //销量
                $ordermes = 'baonum desc' ; //销量排下来
                $cons['baonum'] = array('gt',0) ; //销量要大于0
            }elseif ($sorts == 'zuixin') {
                //最新时间排序
                $ordermes = 'storetime desc' ; //入驻时间排下来
            }
        }else if($op == 'istwo'){
            //驾校选择
            
            if($sorts == 'distance'){
                //距离最近
            }elseif ($sorts == 'hot') {
                //热门驾校
                $ordermes = 'baonum desc' ; //报名人数排下来
                $cons['baonum'] = array('gt',0) ; //报名人数要大于0
            }elseif ($sorts == 'zuixin') {
                //最新入驻
                $ordermes = 'storetime desc' ; //入驻时间排下来
            }

        }
        $cons['type'] = $type ;
        $cons['storemodel'] = 1 ; //线上商城是1
        $cons['isopen'] = 1 ; //是否开放
        $storemes = db('sz_yi_store_data')->where($cons)->order($ordermes)->select(); //查询店铺信息
        foreach ($storemes as $kj => $vaj) {
            // $shangmes = db('sz_yi_perm_user')->where('uid',$vaj['storeid'])->find();
            $shangleimes = db('sz_yi_supplier_category')->where('id',$vaj['cat_id'])->find();
            if(!empty($vaj['lng']) && !empty($vaj['lat'])){
                $julimes = $this->getdistance($lng1,$lat1,$vaj['lng'],$vaj['lat']); //获取相距的距离
                $julimes = round($julimes, 2);
            }else{
                $julimes = 0 ;
            }
            $storemes[$kj]['julimes'] = $julimes ;
            $storemes[$kj]['leixing'] = $shangleimes['name'] ;     
        }

        if($sorts == 'distance'){
            //距离最近
            
             $julimes = array_column($storemes, 'julimes');   //先用array_column 多维数组按照纵向（列）取出 
             array_multisort($julimes,SORT_ASC,$storemes); //再用array_multisort  结合array_column得到的结果对$arr进行排序

        }

        $tradearr['tradestore'] = $storemes ;
        $this->ajaxReturn($tradearr);

    }


    /**
     * 求两个已知经纬度之间的距离,单位为米
     * 
     * @param lng1 $ ,lng2 经度
     * @param lat1 $ ,lat2 纬度
     * @return float 距离，单位千米
     * @author www.Alixixi.com 
     */
    public function getdistance($lng1, $lat1, $lng2, $lat2) {
        // 将角度转为狐度
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 ;
        return $s;
    }

    //用户上传头像的 操作
    public function updateUseimg(){

        if ($_FILES['head_pic']['tmp_name']) {
                $file = $this->request->file('head_pic');
                $image_upload_limit_size = config('image_upload_limit_size');
                $validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
                $dir = UPLOAD_PATH.'head_pic/';
                if (!($_exists = file_exists($dir))){
                    $isMk = mkdir($dir);
                }
                $parentDir = date('Ymd');
                $info = $file->validate($validate)->move($dir, true);
                if($info){
                    $post['head_pic'] = '/'.$dir.$parentDir.'/'.$info->getFilename();
                    $return_imgs = $post['head_pic'] ;
                }else{
                    // $this->error($file->getError());//上传错误提示错误信息
                    $this->ajaxReturn(['status' => -1, 'msg' => $file->getError()]);//上传错误提示错误信息
                }

                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'result' => $return_imgs]);
        }

        $this->ajaxReturn(['status' => -1, 'msg' => '文件上传失败']);

    }


     /*
     * 店铺详请
     */
    public function getStoremes(){
        $sid = I('post.sid') ;
        $storemes = db('sz_yi_store_data')->where('id',$sid)->find(); //查询店铺信息
        $shangleimes = db('sz_yi_supplier_category')->where('id',$storemes['cate_id'])->find();
        $storemes['leixing'] = $shangleimes['name'] ;
        $goodpinmes = db('goods')->where('supplier_uid',$storemes['storeid'])->select(); //获取商家对应的店铺商品信息
        $storemes['goodarr'] = $goodpinmes ;
        $this->ajaxReturn($storemes);
    }


    //退出登录
    public function loginout(){
        Session::delete('userid'); //删除缓存用户id
        if( ! Session::has('userid') ){
            $this->ajaxReturn(['status' => 1, 'msg' => '退出登录成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '退出登录失败!']); 
        }   
    }


    /*
     * 获取对应的协议内容
     */
    public function allAgreement(){
        $type = I('post.type') ; //1为会员注册协议 2为驾考报名协议  3位商家入驻协议 4为驾校入驻协议
        $data['type'] = $type ;
        $agreement = db('sz_yi_memberagree')->where($data)->find(); //查询商家注册信息
        $agreement['agreecontent'] = htmlspecialchars_decode($agreement['agreecontent']);
        $this->ajaxReturn($agreement);        
    }


     public function seachgoods(){
         $gkeyword = I('post.keyword') ; //获取搜索关键字
         $chames = '%'.$gkeyword.'%' ;
         $op = I('post.op') ;
         if($op == 'isone'){
            //首页搜索
            $res = db('goods')->where('is_on_sale',1)->where('goods_name','like',$chames)->field('goods_id,goods_name,original_img,market_price,shop_price')->select();
            // var_dump($res);die;
         }elseif ($op == 'istwo') {
            //商圈搜索
            $res = db('sz_yi_store_data')->where('storename','like',$chames)->order('id desc')->select(); //查询店铺信息
            foreach ($res as $kj => $vaj) {
                $shangleimes = db('sz_yi_supplier_category')->where('id',$vaj['cat_id'])->find();
                $res[$kj]['leixing'] = $shangleimes['name'] ;
            }
         }elseif ($op == 'isthree') {
            //资讯搜索
            $res = db('article')->where('is_open',1)->where('keywords','like',$chames)->order('article_id desc ')->select(); //查询文章的信息
            foreach ($res as $ka => $vaa) {
                $res[$ka]['publish_time'] = date('Y-m-d H:i:s',$vaa['publish_time']); 
            }
         }
         if($res){
            $data['status'] = 1 ;
            $data['goodarr'] = $res ;
         }else{
            $data['status'] = 0 ;
         }
         $this->ajaxReturn($data);
    }


    //查看所有资讯的接口
    public function lookInfomations(){

        $articlecat = db('article_cats')->where('show_in_nav',1)->order('sort_order')->field('cat_id,cat_name')->select(); //查询所有资讯分类信息
        foreach ($articlecat as $kc => $vc) {
            $articlemess = db('articles')->where(array('cat_id'=>$vc['cat_id'],'is_open'=>1))->field('article_id,title,content,click,add_time,thumb')->select(); //查询当前分类下的所有文章信息
            $articlecat[$kc]['articlearr'] = $articlemess ;
        }

        $this->ajaxReturn($articlecat);

    }

    //查看资讯信息
    public function lookDuiyinginfo(){
        $aid = I('post.aid') ;
        $duiarticle = db('articles')->where('article_id',$aid)->field('article_id,title,content,click,add_time,thumb')->find(); //查询对应的文章信息
        $this->ajaxReturn($duiarticle);
    }


    //点击资讯详情 追加浏览次数
    public function clickInfotime(){
        $aid = I('post.aid') ;
        $duiarticle = db('articles')->where('article_id',$aid)->find(); //查询对应的文章信息
        $videoclick = floatval($duiarticle['clicks']); //获取点击次数
        $clicks = $videoclick + 1 ;
        $datas['clicks'] = $clicks ;
        $res = db('articles')->where('article_id',$aid)->update($datas);
        if($res){
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败!']);
        }
    }

    //查看所有商品分类信息
    public function getAllcategory(){

        $categorymess = db('goods_category')->where(array('is_show'=>1,'parent_id'=>0))->order('sort_order')->field('id,mobile_name,image')->select(); //查询所有商品分类信息
        foreach ($categorymess as $kgc => $vagc) {
            $did = $vagc['id'] ;
            $goosmess = db('goods')->where('is_on_sale',1)->field('goods_id,cat_id,goods_name,goods_name,original_img,fenstore_ico')->order('on_time desc ')->select(); //查询分类下的所有商品信息
            $newarr = array();
            foreach ($goosmess as $kh => $vah) {
                $cat_id = $vah['cat_id'] ;
                $dangcategory = db('goods_category')->where(array('id'=>$cat_id)) -> find() ;
                $parentpath = explode('_',$dangcategory['parent_id_path']) ;
                foreach ($parentpath as $vpp) {
                    if($vpp == $did){
                        $newarr[] = $vah ;
                        break; 
                    }
                }
            }
            foreach ($newarr as $knn => $vann) {
                $newarr[$knn]['original_img'] = $vann['fenstore_ico'] ;
            }
            $categorymess[$kgc]['goodarr'] = $newarr ;
        }

        $tuijianarr[0]['id'] = 0 ;
        $tuijianarr[0]['mobile_name'] = '热门推荐' ;
        $tuijianimg = db('ad')->where('pid',51327)->field('ad_id,ad_name,ad_code,ad_link')->find(); //查询热门推荐商品
        $tuijianarr[0]['image'] = $tuijianimg['ad_code'] ;
        $tuijiangoosmess = db('goods')->where(array('is_on_sale'=>1,'is_recommend'=>1))->field('goods_id,cat_id,goods_name,goods_name,original_img,fenstore_ico')->order('on_time desc ')->select();
        foreach ($tuijiangoosmess as $tk => $vatk) {
            $tuijiangoosmess[$tk]['original_img'] = $vatk['fenstore_ico'] ;
        }
        $tuijianarr[0]['goodarr'] = $tuijiangoosmess ;

        $allgoodarr = array_merge($tuijianarr,$categorymess) ;

        $this->ajaxReturn($allgoodarr);


    }


    //获取资讯内容
    public function newsinfomation(){
        $articlemes = db('article')->where(array('is_open'=>1,'cat_id'=>19))->field('article_id,title,clicks,add_time,thumb')->order('article_id desc ')->select(); //查询文章的信息
        foreach ($articlemes as $ka => $vaa) {
            $articlemes[$ka]['add_time'] = date('Y-m-d H:i:s',$vaa['add_time']); 
        }

        $zixunarr['articlemes'] = $articlemes ;

        $advment = db('ad')->where(array('pid'=>51326,'enabled'=>1))->field('ad_id,ad_name,ad_code')->select(); //查询资讯轮播图的信息
        $zixunarr['advment'] = $advment ;

        $this->ajaxReturn($zixunarr);
    }

    //获取所要查看的资讯信息
    public function danginfomation(){
        $aid = I('get.aid') ;
        $duiarticle = db('article')->where('article_id',$aid)->find(); //查询对应的文章信息
        $duiarticle['add_time'] = date('Y-m-d H:i:s',$duiarticle['add_time']);
        $duiarticle['content'] = htmlspecialchars_decode($duiarticle['content']);
        $this->ajaxReturn($duiarticle);
    }

     //点击资讯详情 追加浏览次数
    public function clickInfotimes(){
        $aid = I('post.aid') ;
        $duiarticle = db('article')->where('article_id',$aid)->find(); //查询对应的文章信息
        $videoclick = floatval($duiarticle['clicks']); //获取点击次数
        $clicks = $videoclick + 1 ;
        $datas['clicks'] = $clicks ;
        $res = db('article')->where('article_id',$aid)->update($datas);
        if($res){
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败!']);
        }

    }

    //获取对应的版本号
    public function getVersion(){
        $config = tpCache('pushs');
        $this->ajaxReturn($config);
    }

     /**
     * 获取计算两个经纬度地点之间的距离 单位为千米
     * 
     * @param lng1 $ ,lng2 经度
     * @param lat1 $ ,lat2 纬度
     * @return float 距离，单位千米
     */
    public function getStoredistance(){
        $lng1 = I('post.lng1') ; //经度1
        $lat1 = I('post.lat1') ; //纬度1 
        $lng2 = I('post.lng2') ; //经度2
        $lat2 = I('post.lat2') ; //纬度2 

        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137  ;
        $arr['distance'] = $s ;
        $this->ajaxReturn($arr);

    }


}
