<?php

use GuzzleHttp\Client;

function cugbClockin()
{
    //用户信息
    $username = 'xxx'; //账号
    $password = 'xxx'; //密码
    //打卡的位置信息
    $longitude = 'xxx.xxxxx'; //位置经度
    $latitude = 'xx.xxxxx'; //位置纬度
    $address = 'xxxxx'; //位置名称
    //实例化一个客户端
    $client = new Client([
        'http_errors'     => false, //返回http状态码异常时不抛出异常
        'cookies'         => true, //保持cookie
        'verify'          => false, //不验证ssl
        'allow_redirects' => ['max' => 10], //最大重定向次数
    ]);
    //1.get请求登录页，获取execution和system参数
    $url = 'https://cas.cugb.edu.cn/login?service=https://stu.cugb.edu.cn/ ';
    $html = $client->get($url)->getBody()->getContents();
    $pattern = '/name="execution" value="(.*?)"\/>.*id="userLoginSystem" name="system"\n *value="(.*?)">/';
    preg_match($pattern, $html, $matches); //匹配execution和system参数
    $execution = $matches[1];
    $system = $matches[2];
    //2.post请求登录接口，登录成功后将返回名为TGC和SESSION的Cookie，并在几次自动跳转后返回JSESSIONID
    $data = [
        'username'      => $username, //账号
        'password'      => $password, //密码
        'execution'     => $execution,
        '_eventId'      => 'submit',
        'geolocation'   => '',
        'loginType'     => 'username',
        'system'        => $system,
        'enableCaptcha' => 'N',
    ];
    $html = $client->post('https://cas.cugb.edu.cn/login', ['form_params' => $data])->getBody()->getContents();
    //当登录成功时，页面会有“加载中”
    if (strpos($html, '加载中') === false) {
        echo '登录失败，账号或密码错误';
        die;
    }
    //3.请求首页获取uid
    $html = $client->get('https://stu.cugb.edu.cn/')->getBody()->getContents();
    $pattern = '/uid : \'(.*?)\'/';
    preg_match($pattern, $html, $matches); //匹配uid
    $uid = $matches[1];
    //4.请求一个登录接口，返回用户信息，请求该接口将使JSESSIONID生效
    $url = 'https://stu.cugb.edu.cn/caswisedu/login.htm';
    $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
    $client->post($url, ['form_params' => ['uid' => $uid], 'headers' => $headers]);
    //5.请求打卡接口
    $url = 'https://stu.cugb.edu.cn/syt/zzapply/operation.htm';
    $data = [
        'xmqkb'              => ['id' => '4a4ce9d6725c1d4001725e38fbdb07cd'],
        'c1'                 => '是',
        'c4'                 => '否',
        'c7'                 => '否',
        'c9'                 => '否',
        'c17'                => '否',
        'c19'                => '是',
        'type'               => 'YQSJCJ',
        'location_longitude' => $longitude, //位置经度
        'location_latitude'  => $latitude, //位置纬度
        'location_address'   => $address //位置名称
    ];
    $data = [
        'data'            => json_encode($data),
        'msgUrl'          => 'syt/zzapply/list.htm?type=YQSJCJ&xmid=4a4ce9d6725c1d4001725e38fbdb07cd',
        'uploadFileStr'   => [],
        'multiSelectData' => []
    ];
    $res = $client->post($url, ['form_params' => $data, 'headers' => $headers])->getBody()->getContents();
    //打卡成功返回“success”，已打卡返回“Applied today”
    if ($res == 'success') echo '打卡成功';
    elseif ($res == 'Applied today') echo '今日已打卡';
    else echo '未知错误';
}
