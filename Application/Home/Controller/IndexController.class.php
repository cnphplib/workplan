<?php
namespace Home\Controller;
use Think\Controller;
use Think\Page;
/**
  * 主控制器
  * @auth inmyfree res#mk5i.com
  *	@time 2015-05-27
 **/
class IndexController extends Controller {
    public function index(){
        $UserModel = M("user");
        $field = "tb_user.id,tb_user.`name`,tb_part.`name` as pname,tb_part.id as pid ";
        $join = "LEFT JOIN tb_part on tb_user.pid = tb_part.id ";
        $userGroup = array();
        $userList = $UserModel->join($join)->field($field)->order("tb_part.id,tb_user.id")->select();
        
        $field ="uid , COUNT(*) as num";
        $PlanModel = M("Plan");
        $where["date"] = array("eq",date("Y-m-d"));
        $data = $PlanModel->where($where)->field($field)->group("uid")->select();
        foreach ($data as $item){
            $plancount[$item["uid"]] = $item["num"];
        }
        
        foreach ($userList as $user){
            if(!isset($userGroup[$user["pid"]]["pname"])){
                $userGroup[$user["pid"]]["pname"] = $user["pname"];
            }
            $userGroup[$user["pid"]]["users"][] = array(
                "id"=>$user["id"],
                "name"=>$user["name"],
                "write"=> $plancount[$user["id"]] > 0?  false :  true
            );
        }
        $this->assign("userGroup",$userGroup);
        $this->display(); 
    }
    
    public function showUser(){
        $id = I("uid");
        $where["id"] = array("eq",$id);
        $UserModel = M("user");
        $userInfo = $UserModel->where($where)->find();
        if($userInfo){
            session("uid",$id);
            $today=date("Y-m-d");
            $rows=array("日","一","二","三","四","五","六");
            $dayInfo["date"] = $today;
            $dayInfo["w"] = $rows[date("w")];
            $this->assign("dayInfo",$dayInfo);
            $this->assign("userInfo",$userInfo);
            $this->display();
        }else{
            $this->error("不存在用户",U("Home/Index/index"),3);
        }
    }
    
    public function showUserInfo(){
        $id = session("uid");
        $where["id"] = array("eq",$id);
        $UserModel = M("user");
        $userInfo = $UserModel->where($where)->find();
        if($userInfo){
            dump($userInfo);
        }else{
            echo "不存在用户";
        }
    }
    
    public function modifyWorkPlan(){
        if(IS_POST){
            $PlanModel = M("plan");
            $num = trim(I("num"));
            $planAdd = array();
            for ($i = 0; $i < $num; $i++) {
                $id = trim(I("id_".$i));
                $deleteFlag = I("delete_".$i) == "on" ? true : false;
                if($id != ""){//已经存在
                    $where["id"] = array("eq",$id);
                    if($deleteFlag){//删除
                        $PlanModel->where($where)->delete();
                    }else{//修改
                        $data = array(
                            'uid'        =>  session("uid"),
                            'plan'       =>  trim(I("plan_".$i)),
                            'progress'   =>  trim(I("progress_".$i)),
                            'pprogress'   =>  trim(I("pprogress_".$i)),
                            'cdate'     =>  trim(I("pcdate_".$i)),
                            'date'       =>  trim(I("date_".$i)),
                        );
                        $PlanModel->where($where)->save($data);
                    }
                }else{//添加
                    $data = array(
                        'uid'   =>  session("uid"),
                        'plan'  =>  trim(I("plan_".$i)),
                        'progress'  =>  trim(I("progress_".$i)),
                        'pprogress'  =>  trim(I("pprogress_".$i)),
                        'cdate'  =>  trim(I("pcdate_".$i)),
                        'date'  =>  trim(I("date_".$i)),
                    );
                    if( $data["plan"] != ""){
                        $planAdd[] = $data;
                    }
                }
            }
            if (count($planAdd) > 0){
                $PlanModel->addAll($planAdd);
            }
            $this->success ( "修改成功", U("Home:Index/modifyWorkPlan"), 3 );
        }else{
            $PlanModel = M("plan");
            $item["date"] = date('Y-m-d', time ());
            $where ["uid"] = array("eq",session("uid") );
            $where ["date"] = array("eq",$item["date"] );
            $todayPlanList = $PlanModel->where($where)->order("id")->select();
            $this->assign("planlist",$todayPlanList);
            $havenCount = count($todayPlanList);
            $item["start"] = $havenCount+1;
            $item["end"] = $havenCount+4;
            if($havenCount < 8){
                $item["end"] = 9;
            }
            $this->assign("item",$item);
            $this->display("modifyWorkPlan");
        }
    }
    
    public function showAllWorkPlan() {
        $PlanModel = M("plan");
        if(session("uid")){
            $where ["uid"] = array("eq",session("uid") );
            
            $pageNo = trim(I("p"));
            if(!$pageNo){
                $pageNo = 0;
            }
            $pageSize = 5;
            $allDate = $PlanModel->where ($where)->group("date")->order("date desc")->select();
            $pageCount = count($allDate);
            $dates = $PlanModel->where ($where)->group("date")->order("date desc")->page ($pageNo, $pageSize )->field("date")->select();
            foreach ($dates as $item){
                $whereDate[] = $item["date"];
            }
            $page = new Page ( $pageCount, $pageSize ); 
            $page->setConfig ( "prev", "上一页" ); // 上一页
            $page->setConfig ( "next", "下一页" ); // 下一页
            $show = $page->show();
            if($whereDate){
                $where["date"] = array("in",$whereDate);
                $todayPlanList = $PlanModel->where($where)->order("date desc")->select();
                $PlanListDateGroup = array();
                foreach ($todayPlanList as $item) {
                    $PlanListDateGroup[$item["date"]]["date"] = $item["date"];
                    $PlanListDateGroup[$item["date"]]["data"][] = $item;
                }
				foreach ($PlanListDateGroup as $dayWorkPlan){
					$date = $dayWorkPlan["date"];
					$data = $dayWorkPlan["data"];
					for ($i = 0;$i<count($data);$i++){
						if($data[$i]["pprogress"] != "100" && $data[$i]["cdate"] == $date){
							$PlanListDateGroup[$date]["data"][$i]["Warning"] = "完成进度、完成日期不相符,请修改";
						}else{
							$PlanListDateGroup[$date]["data"][$i]["Warning"] = "";
						}
					}
				}
            }
            $this->assign("PlanListDateGroup", $PlanListDateGroup);
            $this->assign ( "show", $show ); // 输出分页
            $this->display("showAllWorkPlan");
        }else{
            $this->error("登陆过期，请重新登陆",("Home/Index/index"), 3 );
        }
    }
    
    public function downChart(){
        
        Vendor('PHPExcel.PHPExcel');
        Vendor('PHPExcel.PHPExcel.PHPExcel.Autoloader');
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        date_default_timezone_set('PRC');
        
        if (PHP_SAPI == 'cli')
        	die('This example should only be run from a Web Browser');
        
        
        $date = date('Y-m-d', time ());
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("inmyfree")
        							 ->setLastModifiedBy("inmyfree")
        							 ->setTitle("workplan")
        							 ->setSubject("workplan")
        							 ->setDescription("workplan")
        							 ->setKeywords("workplan")
        							 ->setCategory("workplan");
        
        $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:E2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A1:E2')->getFill()->getStartColor()->setARGB('FF808080');
        
        $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', '研发部人员工作计划')
                    ->setCellValue('A2', '姓名') 
                    ->setCellValue('B2', '当日工作计划内容') 
                    ->setCellValue('C2', '目前进度') 
                    ->setCellValue('D2', '进度')
                    ->setCellValue('E2', '预计完成日期');
        $this->setLineCENTER($objPHPExcel);
        
        $A1Style = $objPHPExcel->getActiveSheet()->getStyle('A1');
        $A1Style->getFont()->setName('宋体');
        $A1Style->getFont()->setSize(18);
        $A1Style->getFont()->setBold(true);
        $A1Style->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_BLACK); 
        $A1Style->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        
        $PlanModel = M("plan");
        $field = "tb_user.`name`,tb_plan.plan,tb_plan.progress,tb_plan.pprogress,tb_plan.cdate";
        $join = "LEFT JOIN tb_user on tb_plan.uid = tb_user.id";
        $where["date"] = array("eq",$date);
        $order = "tb_plan.uid ,tb_plan.id";
        $data = $PlanModel->join($join)->where($where)->field($field)->order($order)->select(); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(80);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $name = "";
        $oldLine = 3;
        $linekey = 3;
        $taskID = 1;
        foreach ($data as $key => $value){
            if($name != "" && $name == $value["name"]){
                $taskID ++;
            }else if($name != "" && $name != $value["name"]){
                $taskID = 1;
            }
            $this->setCellTextAndFont($objPHPExcel, 'A'. (3 + $key), $value["name"]);
//             echo 'A'. (3 + $key) . "->" .$value["name"] . "==" .   'B'. (3 + $key) . "->" .$taskID."、".$value["plan"] . "==" .'C'. (3 + $key) . "->" .$value["progress"]."%" . "==" . 'D'. (3 + $key) . "->" .$value["pprogress"]."%" . "==" .'E'. (3 + $key) . "->" .$value["cdate"] ."<br/>";
            $this->setCellTextAndFont($objPHPExcel, 'B'. (3 + $key), $taskID."、".$value["plan"],0,false,true);
            $this->setCellTextAndFont($objPHPExcel, 'C'. (3 + $key), "".$value["progress"]."%");
            $this->setCellTextAndFont($objPHPExcel, 'D'. (3 + $key), "".$value["pprogress"]."%");
            $this->setCellTextAndFont($objPHPExcel, 'E'. (3 + $key), $value["cdate"]);
            
            $this->setCENTER($objPHPExcel, 'C'. (3 + $key),true);
            $this->setCENTER($objPHPExcel, 'D'. (3 + $key),true);
//             echo "name === $name <br/>";
            if($name == ""){
                $name = $value["name"];
            }else if ($name != $value["name"]){
                $endline = 3+$key-1;
                $objPHPExcel->getActiveSheet()->mergeCells("A$oldLine:A$endline")->setCellValue("A$oldLine",$name);
//                 echo "合并A$oldLine:A$endline ====> A$linekey $name <br/>";
                $this->setCENTER($objPHPExcel, "A$oldLine");
                $oldLine = 3 + $key; 
                $name = $value["name"];
                $linekey++;
            }else if ($key == count($data)-1){
                $endline = 3+$key;
                $objPHPExcel->getActiveSheet()->mergeCells("A$oldLine:A$endline")->setCellValue("A$oldLine",$name);
                $this->setCENTER($objPHPExcel, "A$oldLine");
//                 echo "合并A$oldLine:A$endline ====> A$linekey $name <br/>";
            }
//             echo "name === $name  <hr/>";
        }
        
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle($date);
        $objPHPExcel->setActiveSheetIndex(0);
//       /*  
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="研发部人员工作计划' . $date . '.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
//         */
    }
    
	  public function importLastDayPlay(){
        $uid = session("uid");
        if($uid){
            $PlanModel = M("Plan");
            $date = date("Y-m-d");
            $oldWhere["date"] = array("lt",$date);
            $oldDate = $PlanModel->where($oldWhere)->limit(1)->order("date desc")->getField("date");
            
            $where["date"] = array("eq",$oldDate);
            $where["uid"] = array("eq",$uid);
            $where["pprogress"] = array("lt",100);
            $where["cdate"] = array("egt",$date);
            $data = $PlanModel->where($where)->order("id desc")->select();
            $hasAdded = false;
            $hasAdd = false;
            foreach ($data as $task){
                $addWhere = array();
                $addWhere["uid"] = array("eq",$uid);
                $addWhere["plan"] = array("eq",$task["plan"]);
                $addWhere["date"] = array("eq",$date);
                if($PlanModel->where($addWhere)->find()){
                    $hasAdded = true;
                }else{
                    $addItem = array(
                        "uid" => $uid,
                        "plan"=>$task["plan"],
                        "progress"=>$task["pprogress"],
                        "pprogress"=>100,
                        "cdate"=>$task["cdate"],
                        "date"=>$date,
                    );
                    $PlanModel->add($addItem);
                    $hasAdd = true;
                }
            }
            
            if($hasAdded){
                $msg = "部分记录已经导入";
            }
            if($hasAdd){
                if($hasAdded){
                    $msg = $msg .",部分记录导入成功";
                }else{
                    $msg = "部分记录导入成功";
                }
            }
            if($msg == ""){
                $msg = "导入失败，没有未完成计划或者已经导入。";
            }
            $this->success($msg,U("Home/Index/modifyWorkPlan"),3);
        }else{
            "登陆已失效，请重新登陆!";
        }
    } 
    
	
    private function  setCellTextAndFont($objPHPExcel,$cellString,$cellText,$activeSheetIndex =0,$isCenter = true,$isBRow = false){
        $A1Style = $objPHPExcel->getActiveSheet()->getStyle($cellString);
        $A1Style->getFont()->setName('宋体');
        $A1Style->getFont()->setSize(10);
        $A1Style->getFont()->setBold(false);
        $objPHPExcel->setActiveSheetIndex($activeSheetIndex)->setCellValue($cellString, $cellText);
        if($isCenter){
            $this->setCENTER($objPHPExcel, $cellString,true,true);
        }
        if($isBRow){
            $cellStyle = $objPHPExcel->getActiveSheet()->getStyle($cellString);
            $cellStyle->getBorders()->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $cellStyle->getBorders()->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $cellStyle->getBorders()->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $cellStyle->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        }
    } 
    
    /**
     * 设置第二行文字对齐
     * @param unknown $objPHPExcel
     */
    private function setLineCENTER($objPHPExcel){
        $this->setCENTER($objPHPExcel, "A2",true,true);
        $this->setCENTER($objPHPExcel, "B2",true,true);
        $this->setCENTER($objPHPExcel, "C2",true,true);
        $this->setCENTER($objPHPExcel, "D2",true,true);
        $this->setCENTER($objPHPExcel, "E2",true,true);
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);
    }
    
    /**
     * 设置单元格对齐
     * @param unknown $objPHPExcel
     * @param unknown $CellString 
     * @param string $isRight 是否居右对其
     */
    private function setCENTER($objPHPExcel,$CellString,$isRight = flase,$isSetBorder = false){
        $cellStyle = $objPHPExcel->getActiveSheet()->getStyle($CellString);
        $cellAlignment = $cellStyle->getAlignment();
        $cellAlignment->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        if($isRight){
            $cellAlignment->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }else{
            $cellAlignment->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        }
        if($isSetBorder){
            $cellStyle->getBorders()->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $cellStyle->getBorders()->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $cellStyle->getBorders()->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $cellStyle->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        }
    }
	
	
	  public function downWord(){
        $uid = session("uid");
        if(!$uid){
            echo "请重新登陆";
            exit;
        }
        $date = date("Y-m-d");
        $field = "tb_user.`name`,tb_plan.plan,tb_plan.pprogress,tb_part.`name` as pname";
        $join = "LEFT JOIN tb_user ON tb_plan.uid = tb_user.id LEFT JOIN tb_part on tb_user.pid = tb_part.id";
        $where["tb_user.id"] = array("eq",$uid);   
        $where["tb_plan.date"] = array("eq",$date);
        $order = "tb_plan.id";
        $PlanModel = M("Plan");
        $data = $PlanModel->join($join)->where($where)->field($field)->select();
        $result["date"] = $date;
        $result["uid"] = $uid;
        foreach ($data as $task){
            $result["name"]  = $task["name"];
            $result["pname"]  = $task["pname"];
            $result["tasks"][] =  array(
                "plan" => $task["plan"],
                "pprogress" => $task["pprogress"]
            );
        }
        $url =getBaseURL()."downWord/index.php";
        $post = array("json"=>json_encode($result));
      
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($post),
            ),
        );
        
        $result = file_get_contents($url, false, stream_context_create($options));

        if($result != "-1"){
            $file = fopen($result,"r"); // 打开文件
            // 输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: ".filesize($result));
            Header("Content-Disposition: attachment; filename=" . "工作汇报-$date.docx");
            // 输出文件内容
            echo fread($file,filesize($result));
            fclose($file);
            exit();
        }else{
            echo "参数异常";
        }
       
        
    }
    
    public function logout(){
        session(null);
        $this->success("退出成功",U("Home/Index/index"),3);
    }
    
}
