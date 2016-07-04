<?php

class Printer
{
	public $name = '';
	public $number = 1;//签到口编号
	public $company = '';
	public $position = '';
	
	protected const WIDTH = 260;//打印纸宽度
	protected const HEIGHT = 170;//打印纸高度
	protected const FONT_SIZE = 45;//字体大小（字体高度）
	protected const RATIO = 0.5;//字体宽高比
	protected const OFFSET = 0;//左侧偏移量
	protected const PRINTER_NAME = 'Godex EZ-1105';//打印机名称
	protected const OTHER_FONT_SIZE_RATIO = 0.65;//除姓名外的公司与职位的字体高度比例

	public function __contruct($printUserInfo)
	{
		$key = ['no','name','company'];
		foreach ($printUserInfo as $k => $v) {
			$$key[$k] = iconv("UTF-8","GBK",$v);
			$this->$key[$k] = iconv("UTF-8","GBK",$v);
		}
	}
	public function print()
	{
		$handle = printer_open(self::PRINTER_NAME);
		printer_abort($handle);
		printer_start_doc($handle, "mpd");
		printer_start_page($handle);
		//设定打印模式
		printer_set_option($handle, PRINTER_MODE, "RAW");
	
		//公司第一行位置
		$companyCoordinate = $this->getCompanyCoordinate();
		$companyTextArray = $this->addBlankToTextPrev($this->splitAndCalculateString($this->company));

		$this->printNO($handle);//打印签到口编码
		$this->printName($handle);
		$this->printCompany($handle);

		printer_end_page($handle);
		printer_end_doc($handle);
		printer_close($handle);
	}
	/**
	 * 打印姓名
	 * @author wonguohui
	 * @since  2016-07-04T14:58:22+0800
	 * @param  $handle printer hanle
	 */
	public function printName($handle)
	{
		//姓名位置
		$nameCoordinate = $this->getNameCoordinate();
		$nameTextArray = $this->addBlankToTextPrev($this->splitAndCalculateString($this->name),4);
		//姓名字体
		$fontForName = $this->getNameFont();
		printer_select_font($handle,$fontForName);
		printer_draw_text($handle,$nameTextArray['fisrtLine'],OFFSET,$nameCoordinate['y']);
		printer_delete_font($fontForName);
		//draw line
		printer_draw_line($handle,OFFSET,$nameCoordinate['y'],self::WIDTH -5,$nameCoordinate['y']);

		return true;
	}
	/**
	 * 打印公司信息
	 * @author wonguohui
	 * @since  2016-07-04T15:11:41+0800
	 * @param  $handle printer handle
	 */
	public function printCompany($handle)
	{
		//公司位置
		$companyCoordinate = $this->getCompanyCoordinate();
		$companyTextArray = $this->addBlankToTextPrev($this->splitAndCalculateString($this->company));
		//公司字体
		$fontForCompany = $this->getCompanyFont();
		printer_select_font($handle,$fontForCompany);
		printer_draw_text($handle,$companyTextArray['fisrtLine'],OFFSET,$companyCoordinate['y']);

		if(!empty($companyTextArray['secondLine'])){
			$secondLineY = $companyCoordinate['y'] + 3 * self::FONT_SIZE * self::RATIO * OTHER_FONT_SIZE_RATIO;
			printer_draw_text($handle,$companyTextArray['secondLine'],OFFSET,$secondLineY);
		}
		printer_delete_font($fontForCompany);
		return true;
	}
	public function printNO($handle)
	{
		$font = $this->getCompanyFont();
		printer_select_font($handle,$font);
		printer_draw_text($handle, $this->no,350 ,20);
		printer_delete_font($font);
		return true;
	}
	/**
	 * 创建姓名字体
	 * @author wonguohui
	 * @since  2016-07-04T11:22:25+0800
	 */
	public function getNameFont()
	{
		$fontWidth = self::FONT_SIZE * self::RATIO;
		$fontHeight = self::FONT_SIZE;
		$fontForName =  $this->generateFont($fontWidth,$fontHeight,PRINTER_FW_BOLD);
		return $fontForName;
	}
	/** 
	 * 创建公司字体
	 * @author wonguohui
	 * @since  2016-07-04T14:41:56+0800
	 */
	public function getCompanyFont()
	{
		$fontWidth = self::FONT_SIZE * self::RATIO * self::OTHER_FONT_SIZE_RATIO;
		$fontHeight = self::FONT_SIZE * self::OTHER_FONT_SIZE_RATIO;
		$fontForCompany = $this->generateFont($fontWidth,$fontHeight,PRINTER_FW_BOLD);
		return $fontForCompany;
	}
	/**
	 * 创建字体		
	 * @author wonguohui
	 * @since  2016-07-04T11:18:49+0800
	 * @param  $fontWidth 字体宽度
	 * @param  $fontHeight 字体高度
	 * @param  $fontWeight 字体粗细
	 * @return font face
	 */
	public function generateFont($fontWidth,$fontHeight,$fontWeight=PRINTER_FW_BOLD)
	{
		return printer_create_font("simhei",$fontWidth,$fontHeight,$fontWeight,false,false,false,0);
	}
	/**
	 * 获取姓名的y值坐标，x坐标为添加空格计算所得
	 * @author wonguohui
	 * @since  2016-07-04T13:46:00+0800
	 * @return [x,y]
	 */
	public function getNameCoordinate()
	{
		$y = self::HEIGHT * 0.5 - self::FONT_SIZE+40;
		return ['x'=>'','y'=>$y];
	}
	/**
	 * 获取公司的y值坐标
	 * @author wonguohui
	 * @since  2016-07-04T14:43:31+0800
	 * @return [x,y]
	 */
	public function getCompanyCoordinate()
	{
		$y = self::HEIGHT * 0.5 + 10;
		return ['x'=>'','y'=>$y];
	}
	/**
	 * 计算中英文字符数，补空格居中显示
	 * @author wonguohui
	 * @since  2016-07-04T12:07:44+0800
	 * @param  array $textInfo 字符串信息['中文字符数','英文字符串','第一行文本','第二行文本']
	 * @param  integer $total 默认一行打满文字的字数
	 */
	public function addBlankToTextPrev(array $textInfo,$total = 14)
	{
		$key = ['fisrtLine','lastLine'];
		foreach ($textInfo as $k => $v) {
			if(in_array($k, $key) && !empty($v)){
				$lineTextInfo = $this->splitAndCalculateString($v);
				$curTextCount = ($total - $lineTextInfo['chinanum'] - 0.5*$lineTextInfo['notchinanum'])/2;
				$textInfo['fisrtLine'] = $this->addBlank($v,$curTextCount);
			}
		}
		return $textInfo;
	}
	/**
	 * 根据当前的字符数量（中文加英文）和标签纸一行的字符容量添加空格
	 * 完成居中
	 * @author wonguohui
	 * @since  2016-07-04T11:55:49+0800
	 * @param  $text 字符串
	 * @param  $curTextCount 中文字符加英文字符的总数量
	 * @param  $total 标签纸一行总的字符数
	 */
	public function addBlank($text,$curTextCount)
	{
		$blankText = '';
		for($i=0;$i<$curTextCount*2;$i++){
			$blankText .= iconv("UTF-8","GBK"," ");
		}
		return $blankText.$text;
	}
	/**
	 * 计算并拆分一个字符串
	 * @author wonguohui
	 * @since  2016-07-04T12:09:20+0800
	 * @param  $text 字符串文本
	 * @return ['中文字符数','英文字符串','第一行文本','第二行文本']
	 */
	public function splitAndCalculateString($text)
	{
		/**返回数据数组结构 **/
		$returnData = [
			'chinanum' => 0,
			'notchinanum' => 0,
			'fisrtLine' => '',
			'secondLine' => '',
		];
		if(empty($text)) return $returnData;

	    $chinanum = 0;
		$notchinanum = 0;
	    $length = strlen($text);
	    $fisrtLine = "";
	    $lastLine = "";

	    for($i=0;$i<$length;$i++){
	        if(ord(substr($text,$i,1))<=128){
	            $notchinanum++;
	        }else{
	            $i = $i+1;
	            $chinanum++;
	        }
	        if(($notchinanum + $chinanum) == 11 ){
	        	$fisrtLine = substr($text,0,$i-1);
	        	$lastLine = substr($text,$i-1);
	        }
	    }
	    if(empty($fisrtLine)) $fisrtLine = $text;
	    $returnData['fisrtLine'] = $fisrtLine;
	    $returnData['lastLine'] = $lastLine;
	    $returnData['chinanum'] = $chinanum;
	    $returnData['notchinanum'] = $notchinanum;

	    return $returnData;
	}
}
?>