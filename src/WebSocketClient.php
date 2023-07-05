<?php

/*
 * @author: 布尔
 * @name: websocket客户端操作类
 * @desc: 介绍
 * @LastEditTime: 2023-07-05 22:40:17
 */
declare (strict_types=1);

namespace Eykj\WebSocketClient;

use Hyperf\Di\Annotation\Inject;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;
use function Hyperf\Support\env;

class WebSocketClient
{
    
    #[Inject]
    protected ClientFactory $clientFactory;
    
    /**
     * @author: 布尔
     * @name: 推送socket信息
     * @param {array} $param 推送数据 
     * @param {bool} $autoClose 是否自动关闭连接
     * @return {*}
     */
    public function send(array $param, bool $autoClose = true)
    {
        /* 加密钥 */
        $param['ticket'] = substr(md5(env('SOCKET_KEY', 'eykjcn2099') . json_encode($param)), 0, 6);
        /* 记录日志 */
        alog($param, 5);
        // 对端服务的地址，如没有提供 ws:// 或 wss:// 前缀，则默认补充 ws://
        $host = env('WEB_SOCKET_HOST');
        // 通过 ClientFactory 创建 Client 对象，创建出来的对象为短生命周期对象
        $client = $this->clientFactory->create($host, $autoClose);
        // 向 WebSocket 服务端发送消息
        $json = json_encode($param, 320);
        $client->push($json);
        // 获取服务端响应的消息，服务端需要通过 push 向本客户端的 fd 投递消息，才能获取；以下设置超时时间 2s，接收到的数据类型为 Frame 对象。
        /** @var Frame $msg */
        $msg = $client->recv(2);
        /* 记录返回数据日志 */
        alog($msg,5);
        // 获取文本数据：$res_msg->data
        return $msg->data;
    }
    /**
     * @author: 布尔
     * @name: 设备回调socket推送
     * @param {array} $param
     * @return {*}
     */
    public function post_device_send(array $param)
    {
        $to = 'YY2099_' . $param['deviceSn'];
        $data = ['func' => $param['func'], 'data' => $param['data'], 'errmsg' => $param['errmsg'], 'errcode' => $param['errcode']];
        $param = array("act" => $param['act'] ?? 'send', "data" => $data, "to" => $to);
        $r = $this->send($param);
        return $r;
    }
}