<?php
/**
 * 获取服务器资源文件base url
 * @return string
 */
function getBaseURL(){
    $baseURL = "http://" . I("server.HTTP_HOST") . __ROOT__ . "/";
    return $baseURL;
}
