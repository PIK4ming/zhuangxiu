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

class Centerup extends MobileBase
{

    public function getUsemes(){

        // $userid = Session::get('userid');
        // if(empty($userid)){
        //    $this->ajaxReturn(['status' => -1, 'msg' => '您还没登录!']); 
        // }
        $userid = I('post.userid') ;
        return $userid ;
    }
	
    //商家入驻申请 
    public function getStoreapplie(){
        
        $userid = $this->getUsemes();
        $datashou['uid'] = $userid;
        $datashou['username'] = I('post.companyname');  //店铺名称
        $datashou['companydai'] = I('post.companydai'); //法人代表
        $datashou['mobile'] = I('post.mobile'); //手机号码
        $datashou['companycode'] = I('post.companycode'); //法人代表身份证号码
        $companycode = I('post.companycode');
        if(empty($companycode)){
            $this->ajaxReturn(['status' => -2, 'msg' => '身份证号码不能为空!']);    
        }
        if(! $this->is_idcard($companycode) ){
            $this->ajaxReturn(['status' => -3, 'msg' => '身份证号码不正确!']);
        }

        $datashou['cate_id'] = I('post.cat_id'); //店铺类型
        $datashou['licensenum'] = I('post.licensenum'); //营业执行编号

        $yingstarttime = I('post.yingstarttime') ;
        // $yingstarttimes = strtotime($yingstarttime) ;
        $datashou['yingstarttime'] = $yingstarttime ; //营业开始时间
        $yingendtime = I('post.yingendtime') ;
        // $yingendtimes = strtotime($yingendtime) ;
        $datashou['yingendtime'] = $yingendtime ; //营业结束时间

        $datashou['sex'] = I('post.sex') ; //性别 0为男 1为女
        $datashou['storeaddress'] = I('post.storeaddress') ; //店铺地址

        // $datashou['xiangaddress'] = I('post.xiangaddress') ; //详细地址
        $datashou['provinces'] = I('post.provinces') ; //省份
        $datashou['citys'] = I('post.citys') ; //市区
        $datashou['districts'] = I('post.districts') ; //区域

        $datashou['storelogo'] = I('post.storelogo') ; //店铺图片

        $datashou['certificateimg'] = I('post.fileimg0') ; //身份证正面照
        $datashou['certificateimg1'] = I('post.fileimg1') ; //身份证反面照
        $datashou['accountimg'] = I('post.fileimg2') ; //营业执照正本
        $datashou['licenseimg'] = I('post.fileimg3') ; //营业执照副本
        $datashou['type'] = I('post.type') ; //1是驾校  2是商家
        $datashou['password'] = encrypt(111111) ; //后台默认登录密码
        $datashou['status'] = 0 ;
        $datashou['storemodel'] = I('post.storemodel') ; //0是线上  1是线下
        $datashou['finishtime'] = time() ; //申请时间 
        $result = db('sz_yi_af_supplier')->insert($datashou);
        if($result){
            $this->ajaxReturn(['status' => 1, 'msg' => '提交成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '提交失败!']);
        }
    }




    //店铺类型
    public function getStoretype(){
        $suppliertype = db('sz_yi_supplier_category')->where('status',1)->order('displayorder desc ')->select();
        $this->ajaxReturn($suppliertype);
    }


    //驾校入驻申请
    public function getStoreapplies(){

        $userid = $this->getUsemes();
        $datashou['uid'] = $userid;
        $datashou['username'] = I('post.companyname');  //驾校名称
        $datashou['companydai'] = I('post.companydai'); //驾校法人代表
        $datashou['mobile'] = I('post.mobile'); //手机号码
        $datashou['sex'] = I('post.sex') ; //性别 0为男 1为女
        $datashou['storeaddress'] = I('post.storeaddress') ; //驾校地址
        $datashou['teachercount'] = I('post.teachercount') ; //教练人数
        $datashou['carcount'] = I('post.carcount') ; //车辆人数

        // $datashou['xiangaddress'] = I('post.xiangaddress') ; //详细地址
        $datashou['provinces'] = I('post.provinces') ; //省份
        $datashou['citys'] = I('post.citys') ; //市区
        $datashou['districts'] = I('post.districts') ; //区域

        $datashou['storelogo'] = I('post.storelogo') ; //驾校图片
        $datashou['certificateimg'] = I('post.fileimg0') ; //身份证正面照
        $datashou['certificateimg1'] = I('post.fileimg1') ; //身份证反面照
        $datashou['accountimg'] = I('post.fileimg2') ; //营业执照正本
        $datashou['licenseimg'] = I('post.fileimg3') ; //营业执照副本
        $datashou['type'] = I('post.type') ; //1是驾校  2是商家
        $datashou['password'] = encrypt(111111) ; //后台默认登录密码
        $datashou['status'] = 0 ;
        $datashou['finishtime'] = time() ; //申请时间
        $result = db('sz_yi_af_supplier')->insert($datashou);
        if($result){
            $this->ajaxReturn(['status' => 1, 'msg' => '提交成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '提交失败!']);
        }

    }


    /**
     * 入驻上传相关图片，兼容小程序
     */
    // public function uploadStoreImg(){
    //     $return_imgs = '';
    //     if ($_FILES['fileimg']['tmp_name']) {
    //         $files = request()->file("fileimg");
    //         if (is_object($files)) {
    //             $files = [$files]; //可能是一张图片，小程序情况
    //         }
    //         $image_upload_limit_size = config('image_upload_limit_size');
    //         $validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
    //         $dir = UPLOAD_PATH.'store_applie/';
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
    //                 $datas['status'] = -1 ;
    //                 $datas['msg'] = $file->getError() ;
    //                 $this->ajaxReturn($datas);//上传错误提示错误信息
    //             }
    //         }
    //         $datas['status'] = 1 ;
    //         $datas['msg'] = '操作成功' ;
    //         $datas['result'] = $return_imgs ;
    //         $this->ajaxReturn($datas);
    //     }

    //     $datas['status'] = -1 ;
    //     $datas['msg'] = '文件上传失败' ;
    //     $this->ajaxReturn($datas);

    // }

    /**
     * 入驻上传相关图片，兼容小程序
     */
    public function uploadStoreImg(){
        $img = I('img');//图片base64    用 ”|“符号隔开
        $img = explode('|', $img);
        if($img){
            foreach($img as $v){
                $base64_img = trim($v);
                $up_dir = 'Public/store_applie/'.date('Ymd');
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


    //驾考报名
    public function drivercarjoin(){

        $userid = $this->getUsemes();
        $datajoin['userid'] = $userid ;
        $datajoin['reallname'] = I('post.reallname') ;
        $datajoin['type'] = I('post.type') ; //报考类型
        $datajoin['province'] = I('post.province') ;
        $datajoin['city'] = I('post.city') ;
        $datajoin['district'] = I('post.district') ;
        $datajoin['identify'] = I('post.identify'); //获取身份证号码
        $datajoin['mobile'] = I('post.mobile');
        $datajoin['jid'] = I('post.jid'); //获取驾校id
        $datajoin['chosejiatime'] = time(); //获取驾校时间戳

        $mobile = I('post.mobile'); 
        if(empty($mobile)){
            $this->ajaxReturn(['status' => -1, 'msg' => '手机号码不能为空!']);    
        }
        if( ! preg_match("/^1[34578]{1}\d{9}$/",$mobile)){
            $this->ajaxReturn(['status' => -1, 'msg' => '请输入正确的手机号码!']);
        }
        $identify = I('post.identify'); //获取身份证号码
        if(! $this->is_idcard($identify) ){
            $this->ajaxReturn(['status' => -1, 'msg' => '身份证号码不正确!']);
        }

        $identifymes = db('sz_yi_carjoin')->where('identify',$identify)->find(); //身份证号码是否重复了
        if($identifymes){
            $this->ajaxReturn(['status' => -1, 'msg' => '身份证号码已存在!']);
        }

        $datajoin['baotime'] = time() ; 
        $code = I('post.code'); //获取验证码

        $OrderLogic = new OrderLogic();
        $order_sn = $OrderLogic->get_order_sns() ; //获取订单编号
        $datajoin['order_sn'] = $order_sn;

        $result = db('sz_yi_carjoin')->insert($datajoin);
        if($result){
            $bid = db('sz_yi_carjoin')->getLastInsID(); //获取插入id

            $jid = I('post.jid'); //获取驾校id
            $jiaxiaomess = db('sz_yi_store_data')->where('storeid',$jid)->find(); //获取驾校信息
            $baonum = floatval($jiaxiaomess['baonum']);
            $baonum = $baonum + 1 ;
            db('sz_yi_store_data')->where('storeid',$jid)->update(['baonum' => $baonum]); //更新报名人数
            
            $this->ajaxReturn(['status' => 1, 'msg' => '报名成功!','bid'=>$bid]);
            
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '报名失败!']);
        }

    }

    //判断输入的身份证号码是否正确
    public function is_idcard($id) 
    { 
          $id = strtoupper($id); 
          $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/"; 
          $arr_split = array(); 
          if(!preg_match($regx, $id)) 
          { 
            return FALSE; 
          } 
          if(15==strlen($id)) //检查15位 
          { 
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/"; 
          
            @preg_match($regx, $id, $arr_split); 
            //检查生日日期是否正确 
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4]; 
            if(!strtotime($dtm_birth)) 
            { 
              return FALSE; 
            } else { 
              return TRUE; 
            } 
          } 
          else      //检查18位 
          { 
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/"; 
            @preg_match($regx, $id, $arr_split); 
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4]; 
            if(!strtotime($dtm_birth)) //检查生日日期是否正确 
            { 
              return FALSE; 
            } 
            else
            { 
              //检验18位身份证的校验码是否正确。 
              //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。 
              $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); 
              $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); 
              $sign = 0; 
              for ( $i = 0; $i < 17; $i++ ) 
              { 
                $b = (int) $id{$i}; 
                $w = $arr_int[$i]; 
                $sign += $b * $w; 
              } 
              $n = $sign % 11; 
              $val_num = $arr_ch[$n]; 
              if ($val_num != substr($id,17, 1)) 
              { 
                return FALSE; 
              } //phpfensi.com 
              else
              { 
                return TRUE; 
              } 
            } 
          } 
          
    }


    //报名模式选择
    public function baoModelchose(){
        $op = I('post.op') ;
        if($op == 'all'){
            $baomodelmess = db('sz_yi_baomodel')->where('is_show',1)->select(); //查询所有的报名模式
        }elseif ($op == 'chose') {
            $bid = I('post.bid') ; //获取报名模式id
            $con['id'] = $bid ;
            $baomodelmess = db('sz_yi_baomodel')->where($con)->select(); //查询对应的报名模式
        }

        foreach ($baomodelmess as $kb => $vb) {
            $baomodelmess[$kb]['agreecontent'] = htmlspecialchars_decode($vb['agreecontent']); //解析标签
        }

        $this->ajaxReturn($baomodelmess);

    }

    //点击确定报名模式后 更新报名模式
    public function updateBaomodel(){
        $bid = I('post.bid') ; //获取报名记录id
        $bmid = I('post.bmid'); //模式id
        $datas['bmid'] = $bmid ;
        $carmess = db('sz_yi_baomodel')->where('id',$bmid)->find(); //查询报名驾校信息
        $datas['paymoney'] = $carmess['modelmoney'] ; //报名支付费用
        $datas['order_amount'] = $carmess['modelmoney'] ; //报名支付费用
        $res = db('sz_yi_carjoin')->where('id',$bid)->update($datas);
        if($res){
            $this->ajaxReturn(['status' => 1, 'msg' => '确定成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '确定失败!']);
        }
    }


    /*
     * 个人中心
     */
    public function huoUsemes(){

        $userid =  $this->getUsemes();
        $usermess = db('users')->where('user_id',$userid)->find(); //查询用户的信息
        
        $bankmes = db('sz_yi_bankmes')->where('userid',$userid)->find();
        if($bankmes){
            //已经绑定银行卡
             $usermess['is_bindbank'] = 1 ;
        }else{
            //暂未绑定
            $usermess['is_bindbank'] = 0 ;
        }
        
        $storemess = db('sz_yi_store_data')->where(array('useid'=>$userid))->find(); //审核通过的商家信息
        if($storemess){
            //存在商家信息
            $usermess['is_store'] = 1 ;
        }else{
            $usermess['is_store'] = 0 ;
        }

        if($usermess['first_leader'] == 0){
            $tuiname = '' ;
        }else{
            $tuijianmess = db('users')->where('user_id',$usermess['first_leader'])->find(); //查询用户的信息
            $tuiname = $tuijianmess['nickname'];
        }
        $usermess['tuiname'] = $tuiname ;

        $carjoinmess = db('sz_yi_carjoin')->where('userid',$userid)->find(); //是否已经报名
        if($carjoinmess){
            $usermess['is_state'] = 1 ;
        }else{
            $usermess['is_state'] = 0 ;
        }
        // if($usermess['levelid'] == 0){
        //     $usermess['levelname'] = '普通会员' ;
        // }else{
        //     $levelmes = db('user_level')->where('level_id',$usermess['levelid'])->find();
        //     $usermess['levelname'] = $levelmes['level_name'] ;
        // }

        $data['status'] = 1;
        $data['result'] = $usermess;

        // return $usermess ;

        $this->ajaxReturn($data);

    }


    //获取客服电话
    public function getKefutel(){
        $config = tpCache('shop_info');
        $arr['mobile'] = $config['mobile']; //手机号码
        $arr['mobile1'] = $config['mobile1']; //手机号码2
        $arr['phone'] = $config['phone']; //固定电话
        $arr['phone1'] = $config['phone1']; //固定电话2
        $arr['qq2'] = $config['qq2']; //微信
        $arr['qq21'] = $config['qq21']; //微信2
        $arr['qq'] = $config['qq']; //qq号码
        $arr['qqt'] = $config['qqt']; //qq号码2
        $this->ajaxReturn($arr);
    }

    //查询关于我们信息
    public function getQiyecon(){
        $topiccon = M('topic')->order('ctime')->select();
        $topiccon[0]['content'] = htmlspecialchars_decode($topiccon[0]['content']);
        $this->ajaxReturn($topiccon[0]);
    }

    //查询用户条款信息
    public function getUseritem(){
        $useritem = M('article')->where('cat_id',6)->find();
        $useritem['content'] = htmlspecialchars_decode($useritem['content']);
        $this->ajaxReturn($useritem);
    }

    //提交意见反馈信息
    public function opinionmess(){
        $userid = $this->getUsemes();
        $datayi['userid'] = $userid ;
        $datayi['opinion'] = I('post.opinion') ;
        $datayi['backtime'] = time() ; 
        $res = db('sz_yi_opinion')->insert($datayi);
        if($res){
            $this->ajaxReturn(['status' => 1, 'msg' => '提交成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '提交失败!']);
        }

    }


    //获取管理收货地址
    public function getHuoaddress(){
        $userid = $this->getUsemes();
        $op = I('post.op'); //获取类型
        $data['user_id'] = $userid;
        if($op == ''){
        }elseif ($op == 'default') {
            $data['is_default'] = 1;
        }elseif ($op == 'chose') {
            $data['address_id'] = I('post.address_id') ;
        }
        $address_lists = M('user_address')->where($data)->select() ; //获取对应的收货地址
        foreach ($address_lists as $kp => $vap) {
            $address_lists[$kp]['xiangaddress'] = $vap['provinces'].$vap['citys'].$vap['districts'].$vap['address'] ;
        }
        $this->ajaxReturn($address_lists);
    }

    //添加 编辑管理收货地址
    public function addHuoaddress(){

        $userid = $this->getUsemes();
        $consignee = I('post.consignee'); //获取收货人
        $mobile = I('post.mobile') ; //获取手机号码
        $address = I('post.address') ; //获取详细地址
        $is_default = I('post.is_default') ; //是否为默认地址
        $op = I('post.op') ; //判断类型
        $address_id = I('post.address_id') ; //获取地址id

        if(empty($consignee)){
            $this->ajaxReturn(['status' => -1, 'msg' => '收货人不能为空!']);
        }
        if(empty($mobile)){
            $this->ajaxReturn(['status' => -2, 'msg' => '手机号码不能为空!']);
        }
        if( !check_mobile($mobile) && !check_telephone($mobile) ){
            $this->ajaxReturn(['status' => -3, 'msg' => '请输入正确的手机号码!']);
        }
        if(empty($address)){
            $this->ajaxReturn(['status' => -4, 'msg' => '详细地址不能为空!']);
        }

        $data['user_id'] = $userid; //插入用户id
        $data['consignee'] = $consignee ;
        $data['mobile'] = $mobile ;
        $data['provinces'] = I('post.province') ;
        $data['citys'] = I('post.city') ;
        $data['districts'] = I('post.district') ;
        $data['address'] = $address ;
        $data['is_default'] = $is_default ; //是否默认

        if($op == 'edit'){
            //编辑模式
            $address = M('user_address')->where(array('address_id'=>$address_id,'user_id'=> $userid))->find();
            // if($is_default == 1 && $address['is_default'] != 1){
            //     //编辑的时候设置为默认
            //     M('user_address')->where(array('user_id'=>$userid))->save(array('is_default'=>0));
            // }
            if($address['is_default'] == 1){
                //编辑的时候本来就是默认地址
                $data['is_default'] = 1 ;
            }
            // $row = M('user_address')->where(array('address_id'=>$address_id,'user_id'=>$userid))->save($data);
            $row = db('user_address')->where(array('address_id'=>$address_id,'user_id'=>$userid))->update($data);
            if($row !== false){
                $this->ajaxReturn(['status' => 1, 'msg' => '编辑成功!']);
            }else{
                $this->ajaxReturn(['status' => -6, 'msg' => '编辑失败!']);
            }

        }elseif ($op == 'add') {
            //添加模式

            // 如果目前只有一个收货地址则改为默认收货地址
            $c = M('user_address')->where("user_id", $userid)->count();
            if($c == 0){
                $is_default = 1 ;
            }
            $data['is_default'] = $is_default ; //是否默认
            $res = db('user_address')->insert($data); //插入地址
            if($res){
                $insert_id = db('user_address')->getLastInsID(); //获取插入id
                $map['user_id'] = $userid;
                $map['address_id'] = array('neq',$insert_id);
                if($is_default == 1){
                    M('user_address')->where($map)->save(array('is_default'=>0));
                }
                $this->ajaxReturn(['status' => 1, 'msg' => '添加收货地址成功!']);
            }else{
                $this->ajaxReturn(['status' => -5, 'msg' => '添加收货地址失败!']);
            }

        }

    }


     //删除收货地址
    public function delHuoaddress(){
        $userid = $this->getUsemes();
        $address_id = I('post.address_id') ; //获取地址id
        $res = db('user_address')->where('address_id',$address_id)->delete(); //删除数据
        if($res){
            $msgarr['user_id'] = $userid ;
            $msgarr['is_default'] = 1 ;
            $c = M('user_address')->where($msgarr)->count();
            if($c == 0){
                $addressmes = db('user_address')->where('user_id',$userid)->find();
                db('user_address')->where('address_id',$addressmes['address_id'])->update(['is_default' => 1]);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功!']);
        }else{
            $this->ajaxReturn(['status' => 1, 'msg' => '删除失败!']);
        }
    }

    //点击默认地址时更改默认地址
    public function updefaultnaddress(){
        $userid = $this->getUsemes();
        $address_id = I('post.address_id') ; //获取地址id
        $map['user_id'] = $userid;
        $map['address_id'] = array('neq',$address_id);
        M('user_address')->where($map)->save(array('is_default'=>0)); //先将其他的地址改为不是默认
        $datas['user_id'] = $userid;
        $datas['address_id'] = $address_id;
        $mess = db('user_address')->where($datas)->find();
        if($mess['is_default'] == 1){
            $this->ajaxReturn(['status' => 1, 'msg' => '更改成功!']);
        }
        $datas['is_default'] = 1;
        $res = db('user_address')->where('address_id',$address_id)->update($datas);
        if($res){
            $this->ajaxReturn(['status' => 1, 'msg' => '更改成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '更改失败!']);
        }
    }


    //用户更改信息的操作
    public function updateUsermess(){
        $userid = $this->getUsemes();
        $op = I('op') ;
        if($op == 'is_one'){
            $data['head_pic'] = I('post.head_pic') ;
        }elseif ($op == 'is_two') {
            $data['nickname'] = I('post.nickname') ;
        }elseif ($op == 'is_three') {
            $data['mobile'] = I('post.mobile') ;
        }elseif ($op == 'is_four') {
            $data['sex'] = I('post.sex') ; //0 保密 1 男 2 女
            // $data['sexname'] = I('post.sexname') ; 
        }elseif ($op == 'is_five') {
            $data['password'] = encrypt(I('post.password')) ; 
        }
        $res = db('users')->where('user_id',$userid)->update($data);
        if($res){
            $this->ajaxReturn(['status' => 1, 'msg' => '更改信息成功!','result'=>$data]);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '更改信息失败!']);
        }
    }


    //用户选择对应的教练的操作 接口
    public function chosetrainermess(){
        $storeid = I('post.storeid') ; //获取所选择的驾校id
        $trid = I('post.trid') ; //获取所选择的教练id
        $userid = $this->getUsemes();

        $cons['jid'] = $storeid ; 
        $cons['trid'] = $trid ;
        $cons['chosejiatime'] = time() ; //选择驾校时间戳
        $cons['chosetrainertime'] = time() ; //选择教练时间戳

        $trainermess = db('sz_yi_trainer')->where('id',$trid)->find(); //获取当前驾校教练信息
        $cons['koujifen'] = $trainermess['koujjifen'] ; //扣除积分

        $res = db('sz_yi_carjoin')->where('userid',$userid)->update($cons); //更新教练id
        if($res){
            if($trainermess['koujjifen'] > 0){
                //扣除积分
                $koujjifen = floatval($trainermess['koujjifen']) ;
                accountLog($userid, 0, ($koujjifen * -1) , "选择教练扣除积分");
                $datafen['userid'] = $userid ;
                $datafen['money'] = $koujjifen ;
                $datafen['addtime'] = time() ;
                $datafen['type'] = 2 ;
                db('sz_yi_redpointsdetail')->insert($datafen);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '选择成功!','res'=>$trainermess]);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '选择失败!']);
        }
    }


    //用户报名驾考信息
    public function baoCarjoinmess(){
        $userid = $this->getUsemes();
        $carjoinmess = db('sz_yi_carjoin')->where('userid',$userid)->find();
        if(!$carjoinmess){
            $this->ajaxReturn(['status' => -1, 'msg' => '还没报名!']);
        }
        $carjoinmess['baotime'] = date('Y-m-d H:i:s',$carjoinmess['baotime']);
        $jiaxiaomess = db('sz_yi_store_data')->where('storeid',$carjoinmess['jid'])->field('storename')->find(); //获取当前驾校信息
        $carjoinmess['storename'] = $jiaxiaomess['storename'] ; //驾校名字
        $trainermess = db('sz_yi_trainer')->where('id',$carjoinmess['trid'])->field('trainername')->find(); //获取当前驾校教练信息
        $carjoinmess['trainername'] = $trainermess['trainername'] ; //教练名字
        $this->ajaxReturn($carjoinmess);
    }


    //是否已经报名驾考
    public function isCarjoinmess(){
        $userid = $this->getUsemes();
        $carjoinmess = db('sz_yi_carjoin')->where('userid',$userid)->find(); //获取报名驾考信息
        if(!$carjoinmess){
            $this->ajaxReturn(['status' => 1, 'msg' => '可以报名!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '您已经报名了!']); 
        } 
    }

    //判断是否可以选择教练
    public function isChosetrainer(){
        $userid = $this->getUsemes();
        $carjoinmess = db('sz_yi_carjoin')->where('userid',$userid)->find(); //获取报名驾考信息
        if(!$carjoinmess){
            $this->ajaxReturn(['status' => -1, 'msg' => '您还没有报名!']);
        }
        $where['userid'] = $userid ;
        $where['status'] = 1 ;
        $carjoinmess = db('sz_yi_carjoin')->where($where)->find(); //获取报名驾考信息
        if($carjoinmess){
            $this->ajaxReturn(['status' => 1, 'msg' => '可以选择!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '您还没有支付报名费用!']);
        }

    }

    //是否已经选择驾校
    public function isCarjiaxiaomes(){
        $userid = $this->getUsemes();
        $cons['userid'] = $userid ;
        $cons['jid'] = array('neq',0) ;
        $carjoinmess = db('sz_yi_carjoin')->where($cons)->find();
        if(!$carjoinmess){
            $this->ajaxReturn(['status' => 1, 'msg' => '可以选择驾校!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '您已经选择驾校了!']); 
        } 
    }


    //是否已经申请商家入驻了
    public function isApplicestore(){
        $userid = $this->getUsemes();
        $cons['useid'] = $userid ;
        $cons['type'] = 2 ; //商家
        $storemess = db('sz_yi_store_data')->where($cons)->find();
        if(!$storemess){
            $this->ajaxReturn(['status' => 1, 'msg' => '可以申请!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '您已经申请入驻了!']); 
        }
    }

    //是否已经申请驾校入驻了
    public function isApplicejiaxiao(){
        $userid = $this->getUsemes();
        $cons['useid'] = $userid ;
        $cons['type'] = 1 ; //驾校
        $storemess = db('sz_yi_store_data')->where($cons)->find();
        if(!$storemess){
            $this->ajaxReturn(['status' => 1, 'msg' => '可以申请!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '您已经申请入驻了!']); 
        }
    }


    //查询提现时用户所绑定银行卡信息
    public function getUsebankmes(){
        $userid = $this->getUsemes();
        $bankmes = db('sz_yi_bankmes')->where('userid',$userid)->find();
        if(!$bankmes){
            $this->ajaxReturn(['status' => -1]);
        }
        $bankmes['bankcode'] = $this->strreplace($bankmes['bankcode']);
        $bankmes['status'] = 1 ;
        $this->ajaxReturn($bankmes);
    }

    
    //用户填写绑定银行卡的信息
    public function subBingcard(){
        $userid = $this->getUsemes();
        $data['realname'] = I('post.realname') ; //收款人姓名
        $data['bankcode'] = I('post.bankcode') ; //银行卡卡号
        $data['bankname'] = I('post.bankname') ; //银行卡名称
        $data['userid'] = $userid ;
        $data['is_default'] = 1 ;
        $res = db('sz_yi_bankmes')->insert($data); //插入银行卡信息
        if($res){
            $bankid = db('sz_yi_bankmes')->getLastInsID();
            $count = db('sz_yi_bankmes')->where('is_default',1)->count();
            if($count > 0){
                //证明不是第一次绑定银行卡 更新默认
                $where['userid'] = $userid ;
                $where['id'] = array('neq',$bankid) ;
                db('sz_yi_bankmes')->where($where)->update(array('is_default'=>0));
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '添加成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '添加失败!']);    
        }

    }

    //获取提现银行卡的信息
    public function getMybindcardmes(){
        $op = I('post.op') ;
        $userid = $this->getUsemes();
        $data['userid'] = $userid ;
        if($op == 'default'){
            //获取默认的绑定银行卡信息
            $data['is_default'] = 1 ;
            $yongbankmes = db('sz_yi_bankmes')->where($data)->find();
            if($yongbankmes){
                $yongbankmes['status'] = 1 ;
                $yongbankmes['bankcode'] = $this->strreplace($yongbankmes['bankcode']);
                $this->ajaxReturn($yongbankmes);
            }else{
               $this->ajaxReturn(['status' => -1]);
            }
        }else if($op == 'all'){
            //获取所有绑定银行卡信息
            $yongbankmes = db('sz_yi_bankmes')->where($data)->select();
            foreach ($yongbankmes as $km => $vam) {
                $yongbankmes[$km]['bankcode'] = $this->strreplace($vam['bankcode']);
            }
            $this->ajaxReturn($yongbankmes);
        }
    }

    //用户解绑银行卡的操作
    public function jieBindcard(){
        $userid = $this->getUsemes();
        $pid = I('post.pid') ; //获取记录id
        $data['id'] = $pid ;
        $data['userid'] = $userid ;
        $resu = db('sz_yi_bankmes')->where($data)->delete();
        if($resu){
            $msgarr['userid'] = $userid ;
            $msgarr['is_default'] = 1 ;
            $c = M('sz_yi_bankmes')->where($msgarr)->count();
            if($c == 0){
                $bankmes = db('sz_yi_bankmes')->where('userid',$userid)->find();
                db('sz_yi_bankmes')->where('id',$bankmes['id'])->update(['is_default' => 1]);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '删除失败!']);
        }
    }

    //更改默认银行卡的信息
    public function updateDefault(){
        $userid = $this->getUsemes();
        $pid = I('post.pid') ; //获取记录id
        $where['userid'] = $userid ;
        $where['id'] = array('neq',$pid) ;
        db('sz_yi_bankmes')->where($where)->update(array('is_default'=>0)); //更新掉不为默认地址
        $datas['id'] = $pid ;
        $datas['userid'] = $userid ;
        $res = db('sz_yi_bankmes')->where($datas)->update(array('is_default'=>1));
        if($res){
            $this->ajaxReturn(['status' => 1, 'msg' => '更新成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '更新失败!']);
        }
    } 

    //编辑银行卡的信息
    public function updateBindcard(){
        $userid = $this->getUsemes();
        $pid = I('post.pid') ; //获取记录id
        $data['realname'] = I('post.realname') ; //收款人姓名
        $data['bankcode'] = I('post.bankcode') ; //银行卡卡号
        $data['bankname'] = I('post.bankname') ; //银行卡名称
        $ress = db('sz_yi_bankmes')->where('id',$pid)->update($data);
        if($ress){
            $this->ajaxReturn(['status' => 1, 'msg' => '更新成功!']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '更新失败!']);
        }
    }


    //对银行卡号中间的数字进行用*代替
    public function strreplace($str, $startlen = 4, $endlen = 4) {  
        $repstr = "";  
        if (strlen($str) < ($startlen + $endlen+1)) {  
            return $str;  
        }  
        $count = strlen($str) - $startlen - $endlen;  
        for ($i = 0; $i < $count; $i++) {  
            $repstr.="*";  
        }  
        return preg_replace('/(\d{' . $startlen . '})\d+(\d{' . $endlen . '})/', '${1}' . $repstr . '${2}', $str);  
    }


    //用户报名教练成功后 对教练进行评论操作
    public function pingTrainermess(){
        $userid = $this->getUsemes();
        $content = I('post.content') ; //评论内容
        $jid = I('post.jid') ; //教练id
        $pingfen = I('post.pingfen') ; //评分等级 1 2 3 4 5
        $pingimg = I('post.pingimg') ; //获取所上传的截图 用 , 隔开
        $datac['user_id'] = $userid ;
        $datac['add_time'] = time() ;
        $datac['content'] = $content ;
        $datac['jid'] = $jid ;
        $datac['pingfen'] = $pingfen ;
        $datac['is_show'] = 1 ;
        $datac['pingimg'] = $pingimg ;
        $resu = db('sz_yi_trainercomment')->insert($datac);
        if($resu){
            $this->ajaxReturn(['status'=>1,'msg'=>'评论成功']);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'评论失败']);
        }
    }


    //点赞用户评论教练信息的操作
    public function dianTrainerping(){
        $userid = $this->getUsemes();
        $cid = I('post.cid') ; //评论记录id
        $commentmess = db('sz_yi_trainercomment')->where('id',$cid)->find(); //获取对应的评论信息
        if( !empty($commentmess['zanuse']) ){
            $dianusemess = explode(',',$commentmess['zanuse']) ;
            $biao = false ;
            foreach ($dianusemess as $vzz) {
                if($vzz == $userid){
                    $biao = true ;
                    break;
                }
            }
            if ($biao) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'您已经点赞过了!']);
            }
        }
        $zan_num = floatval($commentmess['zan_num']); //获取当前评论的点赞数
        $zongzan_num = $zan_num + 1 ;
        
        $tiaojiao['zan_num'] = $zongzan_num ;
        if(empty($commentmess['zanuse'])){
            $tiaojiao['zanuse'] = $userid ;
        }else{
            $str = $commentmess['zanuse'] ;
            $str = $str.','.$userid ;
            $tiaojiao['zanuse'] = $str ;
        }
        $res = db('sz_yi_trainercomment')->where('id',$cid)->update($tiaojiao);
        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'点赞成功!']); 
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'点赞失败!']);
        }
    } 


    //积分明细和余额明细
    public function getFenyuedetail(){
        $userid = $this->getUsemes();
        $jifendetail = db('sz_yi_redpointsdetail')->where('userid',$userid)->select(); //获取当前用户的积分明细
        foreach ($jifendetail as $kh => $vah) {
            if($vah['type'] == 1){
                $jifendetail[$kh]['typename'] = '报考支付成功获取' ;
            }elseif($vah['type'] == 2){
                $jifendetail[$kh]['typename'] = '选择教练扣除' ;
            }elseif ($vah['type'] == 3) {
                $jifendetail[$kh]['typename'] = '用户购买商品赠送积分' ;
            }elseif ($vah['type'] == 4) {
                $jifendetail[$kh]['typename'] = '用户购买商品抵扣积分' ;
            }
        }
        $arr['jifendetail'] = $jifendetail ;
        $yuedetail = db('sz_yi_yuezhimes')->where('userid',$userid)->select(); //获取当前用户的余额明细
        foreach ($yuedetail as $ky => $vay) {
            if($vay['type'] == 1){
                $yuedetail[$ky]['typename'] = '报考支付成功获取' ;
            }elseif($vay['type'] == 2){
                $yuedetail[$ky]['typename'] = '支付商品扣除' ;
            }
        }
        $arr['yuedetail'] = $yuedetail ;
        $this->ajaxReturn($arr);
    }


    //查询当前用户的使用劵信息
    public function getShiyongjuan(){
        $userid = $this->getUsemes();
        $usecodemess = db('sz_yi_usecode')->where('userid',$userid)->select(); //获取当前用户的使用劵的信息
        foreach ($usecodemess as $ku => $vu) {
            $usecodemess[$ku]['addtime'] = date('Y-m-d H:i:s',$vu['addtime']) ; //下单时间
            $usecodemess[$ku]['starttime'] = date('Y-m-d H:i:s',$vu['starttime']) ; //有效开始时间戳
            $usecodemess[$ku]['endtime'] = date('Y-m-d H:i:s',$vu['endtime']) ; //有效结束时间戳
            $storemess = db('sz_yi_store_data')->where('storeid',$vu['supplier_uid'])->field('id,storename,tel')->find(); //获取使用劵的商家信息
            $usecodemess[$ku]['storename'] = $storemess['storename'] ; //获取商家名称
            if($vu['status'] == 0){
                $usecodemess[$ku]['mess'] = '未使用' ;
            }elseif ($vu['status'] == 1) {
                $usecodemess[$ku]['mess'] = '已使用' ;
            }
            if($vu['is_shi'] == 0){
                $usecodemess[$ku]['shimess'] = '未失效' ;
            }elseif ($vu['is_shi'] == 1) {
                $usecodemess[$ku]['shimess'] = '已失效' ;
            }
        }
        $this->ajaxReturn($usecodemess);
    }



    //查询用户所申请的记录信息
    public function shangapplicelog(){
        $userid = $this->getUsemes();
        $suppliermess = db('sz_yi_af_supplier')->where('uid',$userid)->select(); //获取当前用户的记录信息
        foreach ($suppliermess as $kf => $vaf) {
            if($vaf['status'] == 0){
                $suppliermess[$kf]['mess'] = '审核中' ;
            }elseif ($vaf['status'] == 1) {
                $suppliermess[$kf]['mess'] = '审核通过' ;
            }elseif ($vaf['status'] == -1) {
                $suppliermess[$kf]['mess'] = '审核失败' ;
            }
            if($vaf['type'] == 1){
                $suppliermess[$kf]['types'] = '驾校申请' ;
            }elseif($vaf['type'] == 2){
                $suppliermess[$kf]['types'] = '商家申请' ;
            }
            $suppliermess[$kf]['finishtime'] = date('Y-m-d H:i:s',$vaf['finishtime']); //申请时间戳
        }
        $this->ajaxReturn($suppliermess);
    }
    

}
