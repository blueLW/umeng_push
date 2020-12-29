<?php
/**
 * Created By PhpStorm
 * User: LW
 * Date: 2020/12/29
 * Time: 15:16
 * Desc:
 */
namespace umeng\library;
use umeng\library\PushBase;
class Upush extends PushBase{
    private $push_result;								//推送结果
    private $client_data;								//客户端数据
    const CLIENT_TYPE_IOS = 'ios';						//ios
    const CLIENT_TYPE_ANDROID = 'android';				//安卓
    const PUSH_MESSAGE_TYPE = 'message';				//应用内自定义消息
    const PUSH_NOTIFICATION_TYPE = 'notification';		//通知栏消息
    function __construct($appkey,$master_secret){
        parent::__construct();
        $this->_init($appkey,$master_secret);
    }

    /**初始化
     * @param $appkey
     * @param $master_secret
     * @throws Exception
     * @time 2020/11/3 11:16
     * @author LW
     */
    private function _init(string $appkey,string $master_secret){
        if(empty($appkey)) throw new Exception('缺少appkey~',400);
        if(empty($master_secret)) throw new Exception('缺少master_secret~',400);
        $this->setAppKey($appkey);
        $this->setMasterSecret($master_secret);
    }


    /**安卓端
     * @return $this
     * @time 2020/11/3 11:08
     * @author LW
     */
    public function android(){
        $this->setClient(self::CLIENT_TYPE_ANDROID);
        return $this;
    }

    /**ios端
     * @return $this
     * @time 2020/11/3 11:08
     * @author LW
     */
    public function ios(){
        $this->setClient(self::CLIENT_TYPE_IOS);
        return $this;
    }



    /**通知栏消息
     * @param array $data
     * @return mixed
     * @throws Exception
     * @time 2020/11/3 11:44
     * @author LW
     */
    public function notification(array $data){
        $this->setDisplayType(self::PUSH_NOTIFICATION_TYPE);
        return $this->push($data);
    }

    /**自定义消息
     * @param array $data //  ['custom':array自定义数据]
     * @return mixed
     * @throws Exception
     * @time 2020/11/3 11:45
     * @author LW
     */
    public function message(array $data){
        $this->setDisplayType(self::PUSH_MESSAGE_TYPE);
        return $this->push($data);
    }

    /**推送消息
     * @param array $data
     * @return mixed
     * @throws Exception
     * @time 2020/11/3 11:45
     * @author LW
     */
    private function push(array $data){
        $this->client_data = $data;				//客户端数据
        switch ($this->getClient()){
            case 'ios':
                $this->push_result[$this->getDisplayType()] = $this->pushIos();
                break;
            case 'android':
                $this->push_result[$this->getDisplayType()] = $this->pushAndroid();
                break;
            default:
                throw new Exception('系统异常,客户端信息错误~',400);
        }
        return $this;
    }

    /**获取推送结果
     * @return mixed
     * @time 2020/11/3 11:50
     * @author LW
     */
    public function get_result(){
        return $this->push_result[$this->getDisplayType()];
    }


    /**安卓设备推送
     * @return array
     * @throws Exception
     * @time 2020/11/3 13:59
     * @author LW
     */
    private function pushAndroid(){
        $post_body = $this->androidStructure();
        if(empty($this->client_data)){
            throw new Exception('客户端数据不能为空~',400);
        }
        //拼接数据
        $clien_data = $this->client_data;
        switch ($this->getDisplayType()){
            case "notification":
                if(!isset($clien_data['ticker']) || empty($clien_data['ticker'])){
                    throw new Exception('通知栏提示参数ticker:string不能为空~',400);
                }
                if(!isset($clien_data['title']) || empty($clien_data['title'])){
                    throw new Exception('通知栏标题参数title:string不能为空~',400);
                }
                if(!isset($clien_data['text']) || empty($clien_data['text'])){
                    throw new Exception('通知文字描述参数text:string不能为空~',400);
                }
                $post_body['extra']	= $clien_data['extra'] ?? null;				//可选,用户自定义key->value
                $body['ticker'] = $clien_data['ticker'];    						// 必填，通知栏提示文字
                $body['title'] = $clien_data['title'];    							// 必填，通知栏标题
                $body['text'] = $clien_data['text'];    							// 必填，通知文字描述
                $body['icon'] = $clien_data['icon'] ?? null;    					// 可选，状态栏图标
                $body['largeIcon'] = $clien_data['largeIcon'] ?? null;				// 可选,通知栏拉开后左侧图标
                $body['after_open'] = $clien_data['after_open'] ?? 'go_app';		// 可选,点击通知后续行为,默认跳转APP
                if(isset($clien_data['go_custom'])){
                    $body['custom'] = $clien_data['custom'];			//用户自定义内容
                }
                if($body['after_open'] == "go_url"){
                    $body['url'] = $clien_data['url'];					//跳转链接,必须是http/https开头
                }
                if($body['after_open'] == "go_activity"){
                    $body['activity'] = $clien_data['activity'];		//通知栏点击后打开的Activity
                }
                $post_body['payload']['body'] = $body;
                break;
            case "message":
                if(!isset($clien_data['custom']) || empty($clien_data['custom'])){
                    throw new Exception('自定义参数custom:array不能为空~',400);
                }
                $post_body['payload']['body']['custom'] = $clien_data['custom'];				//应用内自定义消息数据
                break;
        }
        $send_type = $clien_data['send_type'] ?? 'listcast';
        if(in_array($send_type,['listcast','unicast'])){
            if(!isset($clien_data['device_tokens']) || empty($clien_data['device_tokens'])){
                throw new Exception('device_tokens:array 推送设备不能为空,推送上线500~',400);
            }
            $device_tokens =  $clien_data['device_tokens'];
            $post_body['device_tokens'] = trim(is_array($device_tokens) ? implode(',',$device_tokens) : $device_tokens);
        }
        if($send_type == "customizedcast"){
            if(!isset($clien_data['alias_type']) || empty($clien_data['alias_type'])){
                throw new Exception('alias_type不能为空~',400);
            }
            $post_body['alias_type'] = $clien_data['alias_type'];				//必填,alias_type由开发者自己定义
        }
        //生成签名
        $push_url = $this->getPushUrl($post_body);
        return $this->http_request_post($push_url,$post_body);
    }


    /**IOS设备推送
     * @return array
     * @throws Exception
     * @time 2020/11/3 14:39
     * @author LW
     */
    private function pushIos(){
        $post_body = $this->iosStructure();
        if(empty($this->client_data)){
            throw new Exception('客户端数据不能为空~',400);
        }
        $clien_data = $this->client_data;

        if($this->content_available){
            $post_body['payload']['aps']['content-available'] = 1;		//静默推送
            $alert = [];
        }else{
            $alert = [
                "title"=>$clien_data['title'] ?? null,
                "subtitle"=>$clien_data['subtitle'] ?? null,
                "body"=>$clien_data['text']?? null
            ];
            $post_body['payload']['aps']['alert'] = $alert;
            $post_body['payload']['aps']['sound'] = "default";	    //通知默认声音
        }

        $post_body['payload']['aps']['badge'] = +1;					//通知角标
        if(isset($clien_data['custom']) && !empty($clien_data['custom'])){
            $post_body['payload']['aps']['alert'] = array_merge($alert??[],$clien_data['custom']);	//自定义字段
        }

        $send_type = $clien_data['send_type'] ?? 'listcast';
        if(in_array($send_type,['listcast','unicast'])){
            if(!isset($clien_data['device_tokens']) || empty($clien_data['device_tokens'])){
                throw new Exception('推送设备不能为空,推送上限500~',400);
            }
            $device_tokens =  $clien_data['device_tokens'];
            $post_body['device_tokens'] = trim(is_array($device_tokens) ? implode(',',$device_tokens) : $device_tokens);
        }
        if($send_type == "customizedcast"){
            if(!isset($clien_data['alias_type']) || empty($clien_data['alias_type'])){
                throw new Exception('alias_type不能为空~',400);
            }
            $post_body['alias_type'] = $clien_data['alias_type'];				//必填,alias_type由开发者自己定义
        }
        //生成签名
        $push_url = $this->getPushUrl($post_body);
        return $this->http_request_post($push_url,$post_body);
    }

}
