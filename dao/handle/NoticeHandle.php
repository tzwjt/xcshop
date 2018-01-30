<?php
/**
 * NoticeHandle.php
 * @author : wjt
 * @date : 2017.12.24
 * @version : v1.0.0.0
 */

namespace dao\handle;

/**
 * 通知业务层
 */
use dao\handle\BaseHandle;
use dao\model\NoticeRecords as NoticeRecordsModel;

class NoticeHandle extends BaseHandle
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 创建通知记录
     */
    public function createNoticeRecords($shop_id=0, $uid, $send_type, $send_account, $notice_title, $notice_context, $records_type, $send_config){
        $notice_records_model=new NoticeRecordsModel();
        $data = array(
            "shop_id"=>$shop_id,
            "uid"=>$uid,
            "send_type"=>$send_type,
            "send_account"=>$send_account,
            "send_config"=>$send_config,
            "records_type"=>$records_type,
            "notice_title"=>$notice_title,
            "notice_context"=>$notice_context,
            "is_send"=>0,
            "send_message"=>"",
            "create_time"=>time()
        );
        $res =$notice_records_model->save($data);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 发送通知记录
     */
    public function sendNoticeRecords(){
        $notice_records_model=new NoticeRecordsModel();
        $condition=array(
          "is_send"=>0  
        );
        $notice_list=$notice_records_model->getConditionQuery($condition, "*", "");

        foreach ($notice_list as $notice_obj){
            $send_type=$notice_obj["send_type"];
            if($send_type==1){
                #短信发送
                $this->noticeSmsSend($notice_obj["id"], $notice_obj["send_account"], $notice_obj["send_config"], $notice_obj["notice_context"]);
            }else{
                #邮件发送
                $this->noticeEmailSend($notice_obj["id"], $notice_obj["send_account"], $notice_obj["send_config"], $notice_obj["notice_title"], $notice_obj["notice_context"]);
            }
        }
    }

    /**
     * ok-2ok
     * 发送短信
     */
    private function noticeSmsSend($notice_id, $send_account, $send_config, $notice_params){
        $send_config=json_decode($send_config, true);
        $appkey=$send_config["appKey"];
        $secret=$send_config["secretKey"];
        $signName=$send_config["signName"];
        $template_code=$send_config["template_code"];
        $sms_type=$send_config["sms_type"];
        
        $result=aliSmsSend($appkey, $secret, $signName, $notice_params, $send_account, $template_code, $sms_type);
        $code=$this->dealAliSmsResult($result, $sms_type);
        $result=json_encode($result);
        $status=-1;
        if($code==0){
            $status=1;
        }else{
            $status=-1;
        }
        $notice_records_model=new NoticeRecordsModel();
        $ret=$notice_records_model->save(
            ["is_send"=>$status,
                "send_message"=>$result,
                "send_date"=>time(),
                "update_time"=>time()],
            ["id"=>$notice_id]);

        if ($ret > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 处理阿里大于 的返回数据
     * @param unknown $result
     */
    private function dealAliSmsResult($result, $sms_type){
        $code=-1;
        try {
            if($sms_type==0){
                #旧用户发送
                if(!empty($result)){
                    if(!isset($result->result)){
                        #发送失败
                        $code=-1;
                    }else{
                        #发送成功
                        $code=0;
                    }
                }
            }else{
                #新用户发送
                if(!empty($result)){
                    if($result->Code=="OK"){
                        #发送成功
                        $code=0;
                    }else{
                        #发送失败
                        $code=-1;
                    }
                }
            }
        } catch (\Exception $e) {
            $code=-1;
        }
        return $code;
    }

    /**
     * ok-2ok
     * 邮件发送
     */
    private function noticeEmailSend($notice_id, $send_account, $send_config, $notice_title, $notice_context){
        $send_config=json_decode($send_config, true);
        $email_host=$send_config["email_host"];
        $email_id=$send_config["email_id"];
        $email_pass=$send_config["email_pass"];
        $email_port=$send_config["email_port"];
        $email_is_security=$send_config["email_is_security"];
        $email_addr=$send_config["email_addr"];
        $shopName=$send_config["shopName"];
        $result=emailSend($email_host, $email_id, $email_pass, $email_port, $email_is_security, $email_addr, $send_account, $notice_title, $notice_context, $shopName);
        $status=-1;
        if($result){
            $status=1;
        }else{
            $status=-1;
        }
        $notice_records_model=new NoticeRecordsModel();
        $ret=$notice_records_model->save(
            [ "is_send"=>$status,
                "send_message"=>$result,
                "send_date"=>time(),
                "update_time"=>time()],
            ["id"=>$notice_id]);

        if ($ret > 0) {
            return true;
        } else {
            return false;
        }
    }
}