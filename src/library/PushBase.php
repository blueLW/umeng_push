<?php
/**
 * Created By PhpStorm
 * User: LW
 * Date: 2020/12/29
 * Time: 15:21
 * Desc: 推送基础
 */
namespace umeng\library;
class PushBase {
    protected $app_key,$master_secret,$sign;
    static private $client;
    protected $content_available = false;                 //静默推送
    private $display_type;                              //数据推送类型
    const PUSH_URL = "http://msg.umeng.com/api/send";
    const ENVIRONMENT = 'production';

    function __construct(){
    }

    /**设置appkey
     * @param string $app_key
     * @return $this
     * @time 2020/12/29 16:23
     * @author LW
     */
    protected function setAppKey(string $app_key){
        $this->app_key = $app_key;
    }

    protected function setMasterSecret(string $master_secret){
        $this->master_secret = $master_secret;
    }

    /**设置客户端
     * @param string $client
     * @time 2020/12/29 16:59
     * @author LW
     */
    protected function setClient(string $client){
        self::$client = $client;
    }

    /**获取当前客户端
     * @return mixed
     * @time 2020/12/29 17:06
     * @author LW
     */
    protected function getClient(){
        return self::$client;
    }

    /**IOS静默推送
     * @throws Exception
     * @time 2020/11/6 13:58
     * @author LW
     */
    protected function silence(){
        if(self::$client == self::CLIENT_TYPE_ANDROID){
            throw new Exception('静默推送只支持IOS~',500);
        }
        $this->content_available = true;
    }

    /**设置消息类型 (notification/message)
     * @param string $display_type
     * @time 2020/12/29 16:30
     * @author LW
     */
    protected function setDisplayType(string $display_type){
        $this->display_type = $display_type;
    }

    /**获取消息类型
     * @return mixed
     * @time 2020/12/29 17:07
     * @author LW
     */
    protected function getDisplayType(){
        return $this->display_type;
    }


    /**推送延迟时间
     * @param int $second
     * @time 2020/12/29 16:32
     * @author LW
     */
    protected function delay(int $second=2){
        $this->delay = $second;
    }

    /**ios数据结构体
     * @return mixed
     * @time 2020/12/29 16:37
     * @author LW
     */
    protected function iosStructure(){
        $structure = $this->_structure();
        $structure['payload']['aps']=[];
        return $structure;
    }

    /**安卓推送数据结构
     * @return mixed
     * @time 2020/12/29 16:39
     * @author LW
     */
    protected function androidStructure(){
        $structure = $this->_structure();
        $data['payload']['display_type']=$this->display_type;
        $data['payload']['body'] = [];
        return $structure;
    }


    /**基本数据结构
     * @return array
     * @time 2020/12/29 16:35
     * @author LW
     */
    private function _structure(){
        $data = [
            "appkey"=>$this->appkey,
            "timestamp"=>time(),
            "type"=>'listcast',
            "payload"=>[
            ],
            "policy"=>[
                "start_time"=>date('Y-m-d H:i:s',strtotime('+'.$this->delay.'second'))
            ],
            "production_mode"=> (ENVIRONMENT == 'production') ? 'true':'false', //测试，上线为true
            "description"=>'',							//描述
        ];
        return $data;
    }


    /**签名制作
     * @param string $http_method
     * @param string $http_url
     * @param array $post_body
     * @time 2020/11/3 11:28
     * @author LW
     */
    private function make_sign(array $post_body,string $http_method='POST'){
        $post_body = json_encode($post_body);
        $this->sign = $sign = strtolower(md5($http_method . self::PUSH_URL . $post_body . $this->master_secret));
    }

    /**获取推送地址
     * @return string
     * @time 2020/12/29 16:46
     * @author LW
     */
    protected function getPushUrl(array $post_body = []){
        $sign = $this->make_sign($post_body);
        return self::PUSH_URL.'?sign='.$sign;
    }


    /**发起网络请求
     * @param string $url
     * @param array $post
     * @return array
     * @time 2020/12/29 16:51
     * @author LW
     */
    protected function http_request_post(string $url,array $post):array{
        $post = json_encode($post);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $result         = curl_exec($ch);

        $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo      = curl_errno($ch);
        $curlErr        = curl_error($ch);
        curl_close($ch);

        if ($httpCode == "0")
            throw new Exception("Curl error number:" . $curlErrNo . " , Curl error details:" . $curlErr . "\r\n",$httpCode);
        else if ($httpCode != "200")
            throw new Exception($result,$httpCode);
        else
            return json_decode($result,true);
    }
}
