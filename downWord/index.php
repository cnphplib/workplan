<?php
require_once __DIR__ . '/PHPWord/Autoloader.php';

date_default_timezone_set('UTC');

/**
 * Header file
*/
use PhpOffice\PhpWord\Autoloader;
use PhpOffice\PhpWord\Settings;

if($_POST){
    error_reporting(E_ALL);
    $json = $_POST["json"];
    $data = json_decode($json);
    define('CLI', (PHP_SAPI == 'cli') ? true : false);
    define('EOL', CLI ? PHP_EOL : '<br />');
    define('SCRIPT_FILENAME', basename($_SERVER['SCRIPT_FILENAME'], '.php'));
    define('IS_INDEX', SCRIPT_FILENAME == 'index');
    
    Autoloader::register();
    Settings::loadConfig();
    // Set writers
    $writers = array(
        'Word2007' => 'docx',
        'ODText' => 'odt',
        'RTF' => 'rtf',
        'HTML' => 'html',
        'PDF' => 'pdf'
    );
    
    if (null === Settings::getPdfRendererPath()) {
        $writers['PDF'] = null;
    }
    
    if (CLI) {
        return;
    }
    
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->addFontStyle('rStyle', array(
        'bold' => true,
        'italic' => true,
        'size' => 16,
        'allCaps' => true,
        'doubleStrikethrough' => true
    ));
    $phpWord->addParagraphStyle('pStyle', array(
        'align' => 'center',
        'spaceAfter' => 100
    ));
    $phpWord->addTitleStyle(1, array(
        'bold' => true
    ), array(
        'spaceAfter' => 240
    ));
    $phpWord->addTitleStyle(2, array(
        'bold' => true
    ), array(
        'spaceAfter' => 240
    ), array(
        'align' => 'center'
    ));
    
    $section = $phpWord->addSection();
    
    $section->addText(htmlspecialchars('exXXX公司工作汇报与总结'), array(
        'size' => 16,
        'name' => '宋体'
    ), array(
        'align' => 'center',
        'bold' => true
    ));
    
    $section->addTextBreak(1);
    
    $styleTable = array(
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    );
    $styleFirstRow = array(
        'borderBottomSize' => 18,
        'borderBottomColor' => '0000FF',
        'bgColor' => 'ffffff'
    );
    $styleCell = array(
        'valign' => 'center'
    );
    $styleCellBTLR = array(
        'valign' => 'center',
        'textDirection' => \PhpOffice\PhpWord\Style\Cell::TEXT_DIR_BTLR
    );
	
	$fontSizeStyle =  array(
        'size' => 14,
        'name' => '宋体'
    );
    $fontStyle = array(
        'bold' => true,
        'align' => 'center'
    );
    $phpWord->addTableStyle('Fancy Table', $styleTable, $styleFirstRow);
    $table = $section->addTable('Fancy Table');
    $table->addRow(900);
    
    $table->addCell(1200, $styleCell)->addText(htmlspecialchars('姓名'),$fontSizeStyle, $fontStyle);
    $table->addCell(2100, $styleCell)->addText(htmlspecialchars($data->name),$fontSizeStyle, $fontStyle);
    $table->addCell(1200, $styleCell)->addText(htmlspecialchars('部门'),$fontSizeStyle, $fontStyle);
    $table->addCell(1200, $styleCell)->addText(htmlspecialchars('研发'),$fontSizeStyle, $fontStyle);
    $table->addCell(1200, $styleCell)->addText(htmlspecialchars('岗位'),$fontSizeStyle, $fontStyle);
    $table->addCell(3000, $styleCell)->addText(htmlspecialchars($data->pname),$fontSizeStyle, $fontStyle);
    
    $table->addCell(1200, $styleCell)->addText(htmlspecialchars('日期'),$fontSizeStyle, $fontStyle);
    $table->addCell(2500, $styleCell)->addText(htmlspecialchars($data->date),$fontSizeStyle, $fontStyle);
    $table->addCell(1800, $styleCell)->addText(htmlspecialchars('备注'),$fontSizeStyle, $fontStyle);
    
    $cellRowContinue = array(
        'vMerge' => 'continue'
    );
    $table->addRow();
    $cell = $table->addCell(9500, $styleCell);
    $cell->addText(htmlspecialchars('工作内容'),$fontSizeStyle,$fontStyle);
    $cell->getStyle()->setGridSpan(6);
    $cell = $table->addCell(2000, $styleCell);
    $cell->addText(htmlspecialchars('完成情况'),$fontSizeStyle,$fontStyle);
    $cell->getStyle()->setGridSpan(3);
    
    $table->addRow();
    $cellplan = $table->addCell(9500);
    $cellplan->getStyle()->setGridSpan(6);
    
    $cellplancompelte = $table->addCell(2000);
    $cellplancompelte->getStyle()->setGridSpan(3);
    
	
	$phpWord->addNumberingStyle(
		'multilevel',
		array(
			'type' => 'multilevel', 
			'levels' => array(
				array(
					'format' => 'decimal', 
					'text' => '%1、', 
					'left' => 360, 
					'hanging' => 360, 
					'tabPos' => 360
					),
				array(
					'format' => 'upperLetter', 
					'text' => '%2、', 
					'left' => 720, 
					'hanging' => 360, 
					'tabPos' => 720
				),
			)
		 )
	);


    $tasks = $data->tasks;
    if (! $tasks) {
        $tasks = array();
    }
    for ($i = 0; $i < count($tasks); $i ++) {
        //$cellplan->addText(htmlspecialchars(($i + 1) . "、" . $tasks[$i]->plan),$fontSizeStyle);
		$cellplan->addListItem(htmlspecialchars($tasks[$i]->plan), 0, $fontSizeStyle, 'multilevel'); 
        $cellplan->addTextBreak(1);
        if ($tasks[$i]->pprogress == 100) {
            $cellplancompelte->addText(htmlspecialchars("完成"),$fontSizeStyle);
        } else {
            $cellplancompelte->addText(htmlspecialchars($tasks[$i]->pprogress . "%"),$fontSizeStyle);
        }
        $cellplancompelte->addTextBreak(1);
    }
    
    $table->addRow();
    $cell = $table->addCell(11500);
    $cell->addText(htmlspecialchars("当日未完成工作或本人不能解决的问题："),$fontSizeStyle);
	$cell->addTextBreak(2);
    $cell->getStyle()->setGridSpan(9);
    $table->addRow();
    $cell = $table->addCell(11500);
    $cell->addText(htmlspecialchars("明天工作计划"),$fontSizeStyle);
    $cell->addTextBreak(2);
    $cell->getStyle()->setGridSpan(9);
    $table->addRow();
    $cell = $table->addCell(11500);
    $cell->addText(htmlspecialchars("前一天未完成的工作处理结果："),$fontSizeStyle);
    $cell->addTextBreak(2);
    $cell->getStyle()->setGridSpan(9);
    $table->addRow();
    $cell = $table->addCell(11500);
    $cell->addText(htmlspecialchars("当天计划完成情况："),$fontSizeStyle);
    $cell->addTextBreak(1);
    $cell->addText(htmlspecialchars("完成   "),$fontSizeStyle);
    $cell->getStyle()->setGridSpan(9);
    $section->addTextBreak(1);
    $section->addText(htmlspecialchars('直接主管签名：'),$fontSizeStyle);
   	
	$dir = "./word/".$data->date;
    if(!is_dir($dir) ){
        mkdir($dir);
    }
    $path = '/word/' . $data->date . '/workplan-' . $data->uid . "-" . $data->date . '.docx';
    $phpWord->save("." . $path, 'Word2007', false);
    echo './downWord' . $path;
}else{
    echo "-1";
}

