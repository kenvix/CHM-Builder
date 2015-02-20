<?php
/**
 * CHM文档编译器
 * @author 无名智者
 * @copyright 无名智者 [http://zhizhe8.net] @ StusGame GROUP [http://www.stus8.com/]
 * @see https://git.oschina.net/kenvix/CHM-Builder
 */

/**
* CHM Builder
*/
class CHMBuilder {
	public $hhp_path    = 'hhp.ini'; //hhp文件保存路径
	public $hhc_path    = 'hhc.html'; //hhc文件保存路径
	public $hhi_path    = 'hhi.html'; //hhi文件保存路径
	public $save_path   = 'chm.chm'; //chm保存路径
	public $work_path   = 'root'; //工作目录（文档所在路径）
	public $copyright   = '无名智者 [http://zhizhe8.net] @ StusGame GROUP [http://www.stus8.com/]'; //版权.
	public $chm_title   = '帮助文档'; //chm标题
	private $dir;
	private $data;
	public $error;
	public $chm_first_open;

	public function __construct() {
		$this->dir = dirname(__FILE__);
	}

	/**
	 * 获取错误信息
	 * @return array 错误信息
	 */
	public function getErrors() {
		return $this->error;
	}

	public function buildHHP(){
        $manual_files = $this->listDir($this->work_path);
        $files = implode(PHP_EOL, $manual_files);
        $this->copyright = $this->iconv($this->copyright);
        $this->chm_first_open = $this->iconv($this->chm_first_open);
        $this->chm_title = $this->iconv($this->chm_title);
        $tpl = <<< DATA
[OPTIONS]
Compatibility=1.1 or later
Compiled file={$this->save_path}
Contents file={$this->hhc_path}
COPYRIGHT={$this->copyright}
Display compile progress=Yes
Default topic={$this->chm_first_open}
Error log file=chm_builder.Log
Full-text search=Yes
Index file={$this->hhi_path}
Language=0x804
Title={$this->chm_title}
[FILES]
{$files}
DATA;
        file_put_contents($this->hhp_path, $tpl);
	}

	public function buildHHC(){
            $list = array();
            $file_tree = $this->listDirTree($this->chm_path,"{$this->chm_hhp} {$this->chm_uninclude_dirs}{$this->chm_uninclude_files}");
            uksort($file_tree, 'self::cmp');
            foreach ($file_tree as $key => $value) {
                if(is_string($value)){
                    $title = explode(DIRECTORY_SEPARATOR, $value);
                    $title = array_pop($title);
                    $title = rtrim($title,'.html');
                    $list[] = <<<eof
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="{$title}">
        <param name="Local" value="{$value}">
        </OBJECT>
eof;
                }else{
                    $child = array();
                    foreach ($value as $k => $val) {
                        $title = explode(DIRECTORY_SEPARATOR, $val);
                        $title = array_pop($title);
                        $title = rtrim($title,'.html');
                        $child[] = <<<eof
        <LI><OBJECT type="text/sitemap">
            <param name="Name" value="{$title}">
            <param name="Local" value="{$val}">
            <param name="ImageNumber" value="9">
            </OBJECT>
eof;
                    }
                    $child = implode(PHP_EOL, $child);
                    $list[] = <<<eof
    <LI> <OBJECT type="text/sitemap">
        <param name="Name" value="{$key}">
        <param name="ImageNumber" value="1">
        </OBJECT>
    <UL>  
{$child}
    </UL>  
eof;
                }
            }
            $list = implode(PHP_EOL, $list);
            $tpl = <<<eof
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<HTML>
<HEAD>
<meta name="GENERATOR" content="yangweijie code-tech.diandian.com">
<!-- Sitemap 1.0 -->
</HEAD><BODY>
<OBJECT type="text/site properties">
    <param name="ExWindow Styles" value="0x200">
    <param name="Window Styles" value="0x800025">
    <param name="Font" value="MS Sans Serif,10,0">
</OBJECT>
<UL>
{$list}
</UL>
</BODY></HTML>
eof;
            file_put_contents("{$this->chm_path}/{$this->chm_hhc}.hhc", $tpl);
        }

        public function buildHhk(){
            $list = array();
            $file_tree = $this->listDir($this->chm_path);
            foreach ($file_tree as $key => $value) {
                if(is_string($value)){
                    if(stripos($value, '.html')){
                        $title = explode(DIRECTORY_SEPARATOR, $value);
                        $title = array_pop($title);
                        $title = rtrim($title,'.html');
                        $list[] = <<<eof
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="{$title}">
        <param name="Local" value="{$value}">
        </OBJECT>
eof;
                    }
                }
            }
            $list = implode(PHP_EOL, $list);
            $tpl = <<<eof
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<HTML>
<HEAD>
<meta name="GENERATOR" content="yangweijie code-tech.diandian.com">
<!-- Sitemap 1.0 -->
</HEAD><BODY>
<UL>
{$list}
</UL>
</BODY></HTML>
eof;
            file_put_contents("{$this->chm_path}/{$this->chm_hhk}.hhk", $tpl);
        }

	public function iconv($str) {
		return iconv('UTF-8','GB2312',$str);
	}

	private function listDir($dirName = null) {
        if (empty($dirName))
            $this->error[] = 'IBFileSystem: directory is empty';
        if (is_dir($dirName)) {
            if ($dh = opendir($dirName)) {
                $tree = array();
                while (( $file = readdir($dh) ) !== false) {
                    if ($file != "." && $file != "..") {
                        $filePath = $dirName . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($filePath)) { //为目录,递归
                            $tree2 = $this->listDir($filePath);
                            $tree = $tree2? array_merge($tree,$tree2):$tree;
                        } else { //为文件,添加到当前数组
                            $tree[] = $filePath;
                        }
                    }
                }
                closedir($dh);
            } else {
                $this->error[] = 'IBFileSystem: can not open directory '.$dirName;
            }
            //返回当前的$tree
            $tree = array_unique($tree);
            natsort($tree);
            return $tree;
        } else {
            $this->error[] = "IBFileSystem: $dirName is not a directory.";
        }
    }

    private function listDirTree($dirName = null,$remove) {
        if (empty($dirName))
            $this->error[] = "IBFileSystem: directory is empty.";
        if (is_dir($dirName)) {
            if ($dh = opendir($dirName)) {
                $tree = array();
                while (( $file = readdir($dh) ) !== false) {
                    if ($file != "." && $file != ".." && stripos($remove, $file) === false) {
                        $filePath = $dirName . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($filePath)) {
                            $arr = $this->listDirTree($filePath,$remove);
                            natsort($arr);
                            $tree[$file] = $arr;
                        } else {
                            $tree[] = $filePath;
                        }
                    }
                }
                closedir($dh);
            } else {
                $this->error[] = "IBFileSystem: can not open directory $dirName.";
            }
                          
            //返回当前的$tree
            return $tree;
        } else {
            exit("IBFileSystem: $dirName is not a directory.");
        }
    }

    private function cmp($a,$b){
        $a = (int)$a;
        $b = (int)$b;
        if($a == $b)    return 0;
        return ($a>$b)? 1:-1;
    }
}